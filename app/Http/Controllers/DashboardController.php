<?php

namespace App\Http\Controllers;

use App\Services\GoogleAdsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private GoogleAdsService $googleAds;
    private bool $useLiveData;

    public function __construct(GoogleAdsService $googleAds)
    {
        $this->googleAds = $googleAds;
        $this->useLiveData = $googleAds->isConfigured();
    }

    /**
     * Get the date range for API queries based on filter selection.
     */
    private function getDateRange(Request $request): string
    {
        return match ($request->get('range', '7days')) {
            'today' => 'TODAY',
            '7days' => 'LAST_7_DAYS',
            '30days' => 'LAST_30_DAYS',
            '90days' => 'LAST_90_DAYS',
            default => 'LAST_7_DAYS',
        };
    }

    /**
     * Main dashboard overview page.
     */
    public function overview(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        if ($this->useLiveData) {
            try {
                return $this->liveOverview($dateRange);
            } catch (\Exception $e) {
                \Log::error('Google Ads API error: ' . $e->getMessage());
                // Fall back to mock data
            }
        }

        return $this->mockOverview();
    }

    /**
     * Live overview from Google Ads API.
     */
    private function liveOverview(string $dateRange)
    {
        $accounts = $this->googleAds->getCustomerAccounts();
        $clients = [];
        $allChartData = ['labels' => [], 'revenue' => [], 'spend' => []];

        foreach ($accounts as $account) {
            $summary = $this->googleAds->getAccountSummary($account['id'], $dateRange);
            $campaigns = $this->googleAds->getCampaignPerformance($account['id'], $dateRange);

            $totalBudget = array_sum(array_column($campaigns, 'budget')) * 30; // Monthly budget estimate

            $status = 'active';
            if ($summary['roas'] < 1.5)
                $status = 'danger';
            elseif ($summary['roas'] < 3)
                $status = 'warning';

            $clients[] = [
                'id' => $account['id'],
                'name' => $account['name'],
                'industry' => '',
                'status' => $status,
                'spend' => $summary['spend'],
                'budget' => max($totalBudget, $summary['spend']),
                'clicks' => $summary['clicks'],
                'impressions' => $summary['impressions'],
                'conversions' => $summary['conversions'],
                'revenue' => $summary['revenue'],
                'cpa' => $summary['cpa'],
                'ctr' => $summary['ctr'],
                'roas' => $summary['roas'],
            ];

            // Get chart data from first account (or aggregate)
            if (empty($allChartData['labels'])) {
                $allChartData = $this->googleAds->getDailyMetrics($account['id'], $dateRange);
            } else {
                $daily = $this->googleAds->getDailyMetrics($account['id'], $dateRange);
                foreach ($daily['revenue'] as $i => $v) {
                    $allChartData['revenue'][$i] = ($allChartData['revenue'][$i] ?? 0) + $v;
                    $allChartData['spend'][$i] = ($allChartData['spend'][$i] ?? 0) + $daily['spend'][$i];
                }
            }
        }

        $totalSpend = array_sum(array_column($clients, 'spend'));
        $totalConversions = array_sum(array_column($clients, 'conversions'));
        $totalRevenue = array_sum(array_column($clients, 'revenue'));

        $kpis = [
            'total_spend' => $totalSpend,
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenue,
            'avg_cpa' => $totalConversions > 0 ? round($totalSpend / $totalConversions, 2) : 0,
            'overall_roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0,
            'total_clicks' => array_sum(array_column($clients, 'clicks')),
        ];

        $alerts = $this->generateAlerts($clients);
        $activeAlerts = count(array_filter($alerts, fn($a) => $a['type'] !== 'success'));

        return view('dashboard.overview', [
            'clients' => $clients,
            'kpis' => $kpis,
            'chartData' => $allChartData,
            'activeAlerts' => $activeAlerts,
            'alerts' => $alerts,
            'isLive' => true,
        ]);
    }

    /**
     * Auto-generate alerts based on live data.
     */
    private function generateAlerts(array $clients): array
    {
        $alerts = [];

        foreach ($clients as $client) {
            if ($client['roas'] < 1) {
                $alerts[] = [
                    'id' => count($alerts) + 1,
                    'type' => 'danger',
                    'icon' => '🔴',
                    'title' => "{$client['name']} — ROAS below 1x",
                    'message' => "ROAS is {$client['roas']}x. You're losing money on this account. Review immediately.",
                    'time' => 'Now',
                    'client' => $client['name'],
                ];
            }

            if ($client['budget'] > 0) {
                $budgetPct = ($client['spend'] / $client['budget']) * 100;
                if ($budgetPct > 90) {
                    $alerts[] = [
                        'id' => count($alerts) + 1,
                        'type' => 'warning',
                        'icon' => '🟡',
                        'title' => "{$client['name']} — Budget " . round($budgetPct) . "% used",
                        'message' => "Monthly budget is nearly exhausted. Consider pausing low-performing campaigns.",
                        'time' => 'Now',
                        'client' => $client['name'],
                    ];
                }
            }

            if ($client['roas'] >= 5) {
                $alerts[] = [
                    'id' => count($alerts) + 1,
                    'type' => 'success',
                    'icon' => '🟢',
                    'title' => "{$client['name']} — Excellent ROAS",
                    'message' => "ROAS is {$client['roas']}x. Consider increasing budget to scale.",
                    'time' => 'Now',
                    'client' => $client['name'],
                ];
            }

            if ($client['cpa'] > 80) {
                $alerts[] = [
                    'id' => count($alerts) + 1,
                    'type' => 'warning',
                    'icon' => '🟡',
                    'title' => "{$client['name']} — High CPA",
                    'message' => "CPA is £{$client['cpa']}. Review targeting and landing pages.",
                    'time' => 'Now',
                    'client' => $client['name'],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Campaigns page.
     */
    public function campaigns(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $clientId = $request->get('client', 'all');

        if ($this->useLiveData) {
            try {
                $accounts = $this->googleAds->getCustomerAccounts();
                $clients = $accounts;
                $allCampaigns = [];

                $targetAccounts = $clientId !== 'all'
                    ? array_filter($accounts, fn($a) => $a['id'] == $clientId)
                    : $accounts;

                foreach ($targetAccounts as $account) {
                    $campaigns = $this->googleAds->getCampaignPerformance($account['id'], $dateRange);
                    foreach ($campaigns as &$c) {
                        $c['client_id'] = $account['id'];
                        $c['client_name'] = $account['name'];
                    }
                    $allCampaigns = array_merge($allCampaigns, $campaigns);
                }

                return view('dashboard.campaigns', [
                    'clients' => $clients,
                    'campaigns' => $allCampaigns,
                    'clientId' => $clientId,
                    'isLive' => true,
                ]);
            } catch (\Exception $e) {
                \Log::error('Google Ads API error: ' . $e->getMessage());
            }
        }

        return $this->mockCampaigns($request);
    }

    /**
     * Keywords page.
     */
    public function keywords(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        if ($this->useLiveData) {
            try {
                $accounts = $this->googleAds->getCustomerAccounts();
                $allKeywords = ['top' => [], 'wasted' => []];

                foreach ($accounts as $account) {
                    $kws = $this->googleAds->getKeywordPerformance($account['id'], $dateRange);
                    $allKeywords['top'] = array_merge($allKeywords['top'], $kws['top']);
                    $allKeywords['wasted'] = array_merge($allKeywords['wasted'], $kws['wasted']);
                }

                usort($allKeywords['top'], fn($a, $b) => $b['conversions'] <=> $a['conversions']);
                usort($allKeywords['wasted'], fn($a, $b) => $b['spend'] <=> $a['spend']);

                $allKeywords['top'] = array_slice($allKeywords['top'], 0, 15);
                $allKeywords['wasted'] = array_slice($allKeywords['wasted'], 0, 10);

                $totalWasted = array_sum(array_column($allKeywords['wasted'], 'spend'));
                $allCpcs = array_column($allKeywords['top'], 'cpa');
                $allQs = array_filter(array_column($allKeywords['top'], 'qs'));

                $kpis = [
                    'top_count' => count($allKeywords['top']),
                    'avg_cpc' => '£' . (count($allCpcs) > 0 ? number_format(array_sum($allCpcs) / count($allCpcs), 2) : '0.00'),
                    'wasted_spend' => '£' . number_format($totalWasted),
                    'avg_qs' => count($allQs) > 0 ? number_format(array_sum($allQs) / count($allQs), 1) : 'N/A',
                ];

                return view('dashboard.keywords', [
                    'clients' => $accounts,
                    'keywords' => $allKeywords,
                    'kpis' => $kpis,
                    'isLive' => true,
                ]);
            } catch (\Exception $e) {
                \Log::error('Google Ads API error: ' . $e->getMessage());
            }
        }

        return $this->mockKeywords();
    }

    /**
     * Budget tracking page.
     */
    public function budget(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        if ($this->useLiveData) {
            try {
                $accounts = $this->googleAds->getCustomerAccounts();
                $clients = [];

                foreach ($accounts as $account) {
                    $summary = $this->googleAds->getAccountSummary($account['id'], $dateRange);
                    $campaigns = $this->googleAds->getCampaignPerformance($account['id'], $dateRange);
                    $totalBudget = array_sum(array_column($campaigns, 'budget')) * 30;

                    $clients[] = [
                        'id' => $account['id'],
                        'name' => $account['name'],
                        'spend' => $summary['spend'],
                        'budget' => max($totalBudget, $summary['spend'] + 100),
                        'clicks' => $summary['clicks'],
                        'conversions' => $summary['conversions'],
                        'revenue' => $summary['revenue'],
                        'roas' => $summary['roas'],
                    ];
                }

                return view('dashboard.budget', ['clients' => $clients, 'isLive' => true]);
            } catch (\Exception $e) {
                \Log::error('Google Ads API error: ' . $e->getMessage());
            }
        }

        return $this->mockBudget();
    }

    /**
     * Alerts page.
     */
    public function alerts(Request $request)
    {
        if ($this->useLiveData) {
            try {
                $accounts = $this->googleAds->getCustomerAccounts();
                $clients = [];

                foreach ($accounts as $account) {
                    $summary = $this->googleAds->getAccountSummary($account['id']);
                    $summary['name'] = $account['name'];
                    $summary['budget'] = 0;
                    $clients[] = $summary;
                }

                $alerts = $this->generateAlerts($clients);
                if (empty($alerts)) {
                    $alerts = [['id' => 1, 'type' => 'success', 'icon' => '🟢', 'title' => 'All accounts healthy', 'message' => 'No issues detected across your client accounts.', 'time' => 'Now', 'client' => 'All']];
                }

                return view('dashboard.alerts', ['alerts' => $alerts, 'isLive' => true]);
            } catch (\Exception $e) {
                \Log::error('Google Ads API error: ' . $e->getMessage());
            }
        }

        return view('dashboard.alerts', ['alerts' => $this->getMockAlerts()]);
    }

    /**
     * Notes page.
     */
    public function notes()
    {
        $clients = [];
        if ($this->useLiveData) {
            try {
                $clients = $this->googleAds->getCustomerAccounts();
            } catch (\Exception $e) {
                // Use mock
            }
        }

        if (empty($clients)) {
            $clients = array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name']], $this->getMockClients());
        }

        $notes = $this->getMockNotes();
        return view('dashboard.notes', compact('clients', 'notes'));
    }

    // ============================
    // MOCK DATA FALLBACKS
    // ============================

    private function mockOverview()
    {
        $clients = $this->getMockClients();
        $totalSpend = array_sum(array_column($clients, 'spend'));
        $totalConversions = array_sum(array_column($clients, 'conversions'));
        $totalRevenue = array_sum(array_column($clients, 'revenue'));

        $kpis = [
            'total_spend' => $totalSpend,
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenue,
            'avg_cpa' => $totalConversions > 0 ? round($totalSpend / $totalConversions, 2) : 0,
            'overall_roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0,
            'total_clicks' => array_sum(array_column($clients, 'clicks')),
        ];

        $chartData = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'revenue' => [7200, 8100, 6800, 9400, 8900, 5200, 12060],
            'spend' => [1640, 1890, 1720, 2100, 1980, 1340, 2177],
        ];

        $alerts = $this->getMockAlerts();
        $activeAlerts = count(array_filter($alerts, fn($a) => $a['type'] !== 'success'));

        return view('dashboard.overview', [
            'clients' => $clients,
            'kpis' => $kpis,
            'chartData' => $chartData,
            'activeAlerts' => $activeAlerts,
            'alerts' => $alerts,
            'isLive' => false,
        ]);
    }

    private function mockCampaigns(Request $request)
    {
        $clients = $this->getMockClients();
        $campaigns = $this->getMockCampaigns();
        $clientId = $request->get('client', 'all');

        if ($clientId !== 'all') {
            $campaigns = array_filter($campaigns, fn($c) => $c['client_id'] == $clientId);
        }

        return view('dashboard.campaigns', ['clients' => $clients, 'campaigns' => $campaigns, 'clientId' => $clientId, 'isLive' => false]);
    }

    private function mockKeywords()
    {
        $keywords = $this->getMockKeywords();
        $kpis = [
            'top_count' => count($keywords['top']),
            'avg_cpc' => '£2.14',
            'wasted_spend' => '£487',
            'avg_qs' => '7.2',
        ];

        return view('dashboard.keywords', ['clients' => $this->getMockClients(), 'keywords' => $keywords, 'kpis' => $kpis, 'isLive' => false]);
    }

    private function mockBudget()
    {
        return view('dashboard.budget', ['clients' => $this->getMockClients(), 'isLive' => false]);
    }

    // ============================
    // MOCK DATA SETS
    // ============================

    private function getMockClients()
    {
        return [
            ['id' => 1, 'name' => 'Birmingham Kitchens', 'industry' => 'Home Improvement', 'status' => 'active', 'spend' => 3240, 'budget' => 4000, 'clicks' => 1842, 'impressions' => 28400, 'conversions' => 87, 'revenue' => 18900, 'cpa' => 37.24, 'ctr' => 6.49, 'roas' => 5.83],
            ['id' => 2, 'name' => 'London Dental Care', 'industry' => 'Healthcare', 'status' => 'active', 'spend' => 2780, 'budget' => 3000, 'clicks' => 1245, 'impressions' => 19800, 'conversions' => 64, 'revenue' => 12800, 'cpa' => 43.44, 'ctr' => 6.29, 'roas' => 4.60],
            ['id' => 3, 'name' => 'Manchester Plumbing', 'industry' => 'Trade Services', 'status' => 'active', 'spend' => 1890, 'budget' => 2500, 'clicks' => 967, 'impressions' => 15200, 'conversions' => 52, 'revenue' => 9100, 'cpa' => 36.35, 'ctr' => 6.36, 'roas' => 4.81],
            ['id' => 4, 'name' => 'Bristol Auto Repairs', 'industry' => 'Automotive', 'status' => 'warning', 'spend' => 2450, 'budget' => 2500, 'clicks' => 1120, 'impressions' => 22100, 'conversions' => 41, 'revenue' => 7200, 'cpa' => 59.76, 'ctr' => 5.07, 'roas' => 2.94],
            ['id' => 5, 'name' => 'Leeds Legal Services', 'industry' => 'Legal', 'status' => 'active', 'spend' => 1540, 'budget' => 2000, 'clicks' => 643, 'impressions' => 11400, 'conversions' => 58, 'revenue' => 8700, 'cpa' => 26.55, 'ctr' => 5.64, 'roas' => 5.65],
            ['id' => 6, 'name' => 'Edinburgh Fitness', 'industry' => 'Health & Fitness', 'status' => 'danger', 'spend' => 947, 'budget' => 1500, 'clicks' => 412, 'impressions' => 8900, 'conversions' => 12, 'revenue' => 960, 'cpa' => 78.92, 'ctr' => 4.63, 'roas' => 1.01],
        ];
    }

    private function getMockCampaigns()
    {
        return [
            ['id' => 1, 'client_id' => 1, 'client_name' => 'Birmingham Kitchens', 'name' => 'Brand - Birmingham Kitchens', 'type' => 'Search', 'status' => 'active', 'spend' => 820, 'impressions' => 9200, 'clicks' => 612, 'conversions' => 34, 'revenue' => 7400, 'ctr' => 6.65, 'cpa' => 24.12, 'roas' => 9.02],
            ['id' => 2, 'client_id' => 1, 'client_name' => 'Birmingham Kitchens', 'name' => 'Kitchen Renovation Services', 'type' => 'Search', 'status' => 'active', 'spend' => 1440, 'impressions' => 12800, 'clicks' => 845, 'conversions' => 38, 'revenue' => 8200, 'ctr' => 6.60, 'cpa' => 37.89, 'roas' => 5.69],
            ['id' => 3, 'client_id' => 1, 'client_name' => 'Birmingham Kitchens', 'name' => 'Display - Remarketing', 'type' => 'Display', 'status' => 'warning', 'spend' => 580, 'impressions' => 4200, 'clicks' => 245, 'conversions' => 9, 'revenue' => 1950, 'ctr' => 5.83, 'cpa' => 64.44, 'roas' => 3.36],
            ['id' => 4, 'client_id' => 2, 'client_name' => 'London Dental Care', 'name' => 'Brand - London Dental', 'type' => 'Search', 'status' => 'active', 'spend' => 640, 'impressions' => 7200, 'clicks' => 487, 'conversions' => 28, 'revenue' => 5600, 'ctr' => 6.76, 'cpa' => 22.86, 'roas' => 8.75],
            ['id' => 5, 'client_id' => 2, 'client_name' => 'London Dental Care', 'name' => 'Dental Implants London', 'type' => 'Search', 'status' => 'active', 'spend' => 1340, 'impressions' => 8400, 'clicks' => 512, 'conversions' => 24, 'revenue' => 4800, 'ctr' => 6.10, 'cpa' => 55.83, 'roas' => 3.58],
            ['id' => 6, 'client_id' => 3, 'client_name' => 'Manchester Plumbing', 'name' => 'Plumbing Services Manchester', 'type' => 'Search', 'status' => 'active', 'spend' => 980, 'impressions' => 8400, 'clicks' => 523, 'conversions' => 31, 'revenue' => 5400, 'ctr' => 6.23, 'cpa' => 31.61, 'roas' => 5.51],
            ['id' => 7, 'client_id' => 4, 'client_name' => 'Bristol Auto Repairs', 'name' => 'Auto Repair Services', 'type' => 'Search', 'status' => 'active', 'spend' => 1200, 'impressions' => 11200, 'clicks' => 567, 'conversions' => 22, 'revenue' => 3900, 'ctr' => 5.06, 'cpa' => 54.55, 'roas' => 3.25],
            ['id' => 8, 'client_id' => 5, 'client_name' => 'Leeds Legal Services', 'name' => 'Personal Injury Claims', 'type' => 'Search', 'status' => 'active', 'spend' => 840, 'impressions' => 6200, 'clicks' => 342, 'conversions' => 32, 'revenue' => 4800, 'ctr' => 5.52, 'cpa' => 26.25, 'roas' => 5.71],
            ['id' => 9, 'client_id' => 6, 'client_name' => 'Edinburgh Fitness', 'name' => 'Personal Training', 'type' => 'Search', 'status' => 'danger', 'spend' => 427, 'impressions' => 4100, 'clicks' => 178, 'conversions' => 4, 'revenue' => 320, 'ctr' => 4.34, 'cpa' => 106.75, 'roas' => 0.75],
        ];
    }

    private function getMockKeywords()
    {
        return [
            'top' => [
                ['keyword' => 'birmingham kitchen fitters', 'match' => 'Exact', 'clicks' => 312, 'conversions' => 24, 'cpa' => 18.50, 'qs' => 9],
                ['keyword' => 'personal injury solicitor leeds', 'match' => 'Phrase', 'clicks' => 189, 'conversions' => 19, 'cpa' => 22.10, 'qs' => 8],
                ['keyword' => 'emergency dentist london', 'match' => 'Exact', 'clicks' => 246, 'conversions' => 18, 'cpa' => 28.40, 'qs' => 9],
                ['keyword' => 'plumber near me manchester', 'match' => 'Broad', 'clicks' => 198, 'conversions' => 16, 'cpa' => 24.80, 'qs' => 8],
                ['keyword' => 'kitchen renovation cost uk', 'match' => 'Phrase', 'clicks' => 167, 'conversions' => 14, 'cpa' => 31.20, 'qs' => 7],
                ['keyword' => 'family law solicitor', 'match' => 'Exact', 'clicks' => 142, 'conversions' => 13, 'cpa' => 26.90, 'qs' => 8],
            ],
            'wasted' => [
                ['keyword' => 'free kitchen design software', 'clicks' => 87, 'spend' => 142, 'conversions' => 0],
                ['keyword' => 'diy plumbing tips', 'clicks' => 64, 'spend' => 98, 'conversions' => 0],
                ['keyword' => 'gym equipment for sale', 'clicks' => 52, 'spend' => 87, 'conversions' => 0],
                ['keyword' => 'car repair manual pdf', 'clicks' => 43, 'spend' => 72, 'conversions' => 0],
                ['keyword' => 'dental school free treatment', 'clicks' => 38, 'spend' => 54, 'conversions' => 0],
            ],
        ];
    }

    private function getMockAlerts()
    {
        return [
            ['id' => 1, 'type' => 'danger', 'icon' => '🔴', 'title' => 'Edinburgh Fitness — ROAS below 1x', 'message' => 'Personal Training campaign has a ROAS of 0.75x. Consider pausing or restructuring.', 'time' => '2 hours ago', 'client' => 'Edinburgh Fitness'],
            ['id' => 2, 'type' => 'warning', 'icon' => '🟡', 'title' => 'Bristol Auto Repairs — Budget 98% used', 'message' => 'Monthly budget is nearly exhausted with 10 days remaining.', 'time' => '3 hours ago', 'client' => 'Bristol Auto Repairs'],
            ['id' => 3, 'type' => 'danger', 'icon' => '🔴', 'title' => 'Wasted spend detected — 5 keywords', 'message' => '£453 spent on keywords with zero conversions. Review and add as negatives.', 'time' => '5 hours ago', 'client' => 'Multiple'],
            ['id' => 4, 'type' => 'success', 'icon' => '🟢', 'title' => 'Leeds Legal Services — CPA improved', 'message' => 'CPA dropped to £26.55. Great performance on brand campaigns.', 'time' => '6 hours ago', 'client' => 'Leeds Legal Services'],
            ['id' => 5, 'type' => 'warning', 'icon' => '🟡', 'title' => 'Birmingham Kitchens — Competitor campaign CPA high', 'message' => 'CPA is £66.67. Monitor for another week before deciding.', 'time' => '1 day ago', 'client' => 'Birmingham Kitchens'],
        ];
    }

    private function getMockNotes()
    {
        return [
            ['id' => 1, 'client' => 'Birmingham Kitchens', 'text' => 'Client approved budget increase to £4,000/month starting March.', 'date' => '20 Feb 2026', 'time' => '14:30'],
            ['id' => 2, 'client' => 'Edinburgh Fitness', 'text' => 'Scheduled call to discuss poor performance. May need to pause Personal Training campaign.', 'date' => '19 Feb 2026', 'time' => '10:15'],
            ['id' => 3, 'client' => 'London Dental Care', 'text' => 'New landing page for dental implants goes live next week.', 'date' => '18 Feb 2026', 'time' => '16:45'],
        ];
    }
}
