<?php

namespace App\Services;

use Google_Client;

class GoogleAdsService
{
    private Google_Client $client;
    private string $developerToken;
    private string $managerAccountId;

    public function __construct()
    {
        $this->developerToken = config('services.google.developer_token');
        $this->managerAccountId = config('services.google.manager_account_id');

        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->refreshToken(config('services.google.refresh_token'));
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->developerToken)
            && !empty(config('services.google.refresh_token'))
            && !empty(config('services.google.client_id'));
    }

    /**
     * Get an access token for API requests.
     */
    private function getAccessToken(): string
    {
        $token = $this->client->getAccessToken();
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken();
            $token = $this->client->getAccessToken();
        }
        return $token['access_token'];
    }

    /**
     * Make a Google Ads API REST request using the Search endpoint.
     */
    private function query(string $customerId, string $gaqlQuery): array
    {
        $accessToken = $this->getAccessToken();
        $cleanCustomerId = str_replace('-', '', $customerId);

        $url = "https://googleads.googleapis.com/v23/customers/{$cleanCustomerId}/googleAds:searchStream";

        set_time_limit(120);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query' => $gaqlQuery]),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'developer-token: ' . $this->developerToken,
                'login-customer-id: ' . str_replace('-', '', $this->managerAccountId),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            \Log::error('Google Ads API Error', ['code' => $httpCode, 'url' => $url, 'response' => $response]);
            return [];
        }

        $decoded = json_decode($response, true);

        // searchStream returns an array of result batches
        $results = [];
        if (is_array($decoded)) {
            foreach ($decoded as $batch) {
                if (isset($batch['results'])) {
                    $results = array_merge($results, $batch['results']);
                }
            }
        }

        return $results;
    }

    /**
     * Get all accessible customer accounts under the MCC.
     */
    public function getCustomerAccounts(): array
    {
        $accessToken = $this->getAccessToken();
        $cleanManagerId = str_replace('-', '', $this->managerAccountId);

        $url = "https://googleads.googleapis.com/v23/customers/{$cleanManagerId}/googleAds:searchStream";

        $gaql = "SELECT customer_client.id, customer_client.descriptive_name, customer_client.status, customer_client.manager FROM customer_client WHERE customer_client.status = 'ENABLED' AND customer_client.manager = false";

        $results = $this->query($cleanManagerId, $gaql);

        $accounts = [];
        foreach ($results as $row) {
            $cc = $row['customerClient'] ?? [];
            $accounts[] = [
                'id' => $cc['id'] ?? '',
                'name' => $cc['descriptiveName'] ?? 'Unnamed',
            ];
        }

        return $accounts;
    }

    /**
     * Get campaign performance for a specific customer.
     */
    public function getCampaignPerformance(string $customerId, string $dateRange = 'LAST_7_DAYS'): array
    {
        $gaql = "SELECT
            campaign.id,
            campaign.name,
            campaign.status,
            campaign.advertising_channel_type,
            campaign_budget.amount_micros,
            metrics.cost_micros,
            metrics.impressions,
            metrics.clicks,
            metrics.conversions,
            metrics.conversions_value,
            metrics.ctr,
            metrics.average_cpc,
            metrics.cost_per_conversion,
            metrics.phone_calls
        FROM campaign
        WHERE campaign.status != 'REMOVED'
            AND segments.date DURING {$dateRange}
        ORDER BY metrics.cost_micros DESC";

        $results = $this->query($customerId, $gaql);

        $campaigns = [];
        foreach ($results as $row) {
            $campaign = $row['campaign'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $budget = $row['campaignBudget'] ?? [];

            $spend = ($metrics['costMicros'] ?? 0) / 1000000;
            $conversions = floatval($metrics['conversions'] ?? 0);
            $convValue = floatval($metrics['conversionsValue'] ?? 0);
            $roas = $spend > 0 ? round($convValue / $spend, 2) : 0;
            $cpa = $conversions > 0 ? round($spend / $conversions, 2) : 0;
            $phoneCalls = intval($metrics['phoneCalls'] ?? 0);

            $channelType = $campaign['advertisingChannelType'] ?? 'SEARCH';
            $type = match ($channelType) {
                'DISPLAY' => 'Display',
                'SHOPPING' => 'Shopping',
                'VIDEO' => 'Video',
                'PERFORMANCE_MAX' => 'PMax',
                default => 'Search',
            };

            $status = match ($campaign['status'] ?? '') {
                'ENABLED' => $roas >= 3 ? 'active' : ($roas >= 1.5 ? 'warning' : 'danger'),
                'PAUSED' => 'paused',
                default => 'unknown',
            };

            $campaigns[] = [
                'id' => $campaign['id'] ?? '',
                'name' => $campaign['name'] ?? 'Unnamed',
                'type' => $type,
                'status' => $status,
                'spend' => round($spend, 2),
                'budget' => ($budget['amountMicros'] ?? 0) / 1000000,
                'impressions' => intval($metrics['impressions'] ?? 0),
                'clicks' => intval($metrics['clicks'] ?? 0),
                'ctr' => round(floatval($metrics['ctr'] ?? 0) * 100, 2),
                'conversions' => intval($conversions),
                'revenue' => round($convValue, 2),
                'cpa' => $cpa,
                'roas' => $roas,
                'phone_calls' => $phoneCalls,
                'avg_cpc' => ($metrics['averageCpc'] ?? 0) / 1000000,
            ];
        }

        return $campaigns;
    }

    /**
     * Get account-level summary metrics.
     */
    public function getAccountSummary(string $customerId, string $dateRange = 'LAST_7_DAYS'): array
    {
        $gaql = "SELECT
            metrics.cost_micros,
            metrics.impressions,
            metrics.clicks,
            metrics.conversions,
            metrics.conversions_value,
            metrics.ctr,
            metrics.cost_per_conversion
        FROM customer
        WHERE segments.date DURING {$dateRange}";

        $results = $this->query($customerId, $gaql);

        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;
        $totalRevenue = 0;

        foreach ($results as $row) {
            $m = $row['metrics'] ?? [];
            $totalSpend += ($m['costMicros'] ?? 0) / 1000000;
            $totalImpressions += intval($m['impressions'] ?? 0);
            $totalClicks += intval($m['clicks'] ?? 0);
            $totalConversions += floatval($m['conversions'] ?? 0);
            $totalRevenue += floatval($m['conversionsValue'] ?? 0);
        }

        return [
            'spend' => round($totalSpend, 2),
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'conversions' => intval($totalConversions),
            'revenue' => round($totalRevenue, 2),
            'cpa' => $totalConversions > 0 ? round($totalSpend / $totalConversions, 2) : 0,
            'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0,
            'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
        ];
    }

    /**
     * Get keyword performance for a customer.
     */
    public function getKeywordPerformance(string $customerId, string $dateRange = 'LAST_7_DAYS'): array
    {
        $gaql = "SELECT
            ad_group_criterion.keyword.text,
            ad_group_criterion.keyword.match_type,
            ad_group_criterion.quality_info.quality_score,
            metrics.cost_micros,
            metrics.clicks,
            metrics.impressions,
            metrics.conversions,
            metrics.conversions_value,
            metrics.cost_per_conversion,
            metrics.average_cpc
        FROM keyword_view
        WHERE segments.date DURING {$dateRange}
            AND campaign.status = 'ENABLED'
            AND ad_group.status = 'ENABLED'
            AND ad_group_criterion.status = 'ENABLED'
        ORDER BY metrics.clicks DESC
        LIMIT 50";

        $results = $this->query($customerId, $gaql);

        $keywords = ['top' => [], 'wasted' => []];

        foreach ($results as $row) {
            $criterion = $row['adGroupCriterion'] ?? [];
            $kw = $criterion['keyword'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $spend = ($metrics['costMicros'] ?? 0) / 1000000;
            $convs = floatval($metrics['conversions'] ?? 0);
            $qs = $criterion['qualityInfo']['qualityScore'] ?? null;

            $entry = [
                'keyword' => $kw['text'] ?? 'Unknown',
                'match' => ucfirst(strtolower($kw['matchType'] ?? 'BROAD')),
                'clicks' => intval($metrics['clicks'] ?? 0),
                'conversions' => intval($convs),
                'spend' => round($spend, 2),
                'cpa' => $convs > 0 ? round($spend / $convs, 2) : 0,
                'qs' => $qs,
            ];

            if ($convs > 0) {
                $keywords['top'][] = $entry;
            } else if ($spend > 5) {
                $keywords['wasted'][] = $entry;
            }
        }

        // Sort top by conversions desc, wasted by spend desc
        usort($keywords['top'], fn($a, $b) => $b['conversions'] <=> $a['conversions']);
        usort($keywords['wasted'], fn($a, $b) => $b['spend'] <=> $a['spend']);

        return $keywords;
    }

    /**
     * Get daily metrics for chart data.
     */
    public function getDailyMetrics(string $customerId, string $dateRange = 'LAST_7_DAYS'): array
    {
        $gaql = "SELECT
            segments.date,
            metrics.cost_micros,
            metrics.conversions_value
        FROM customer
        WHERE segments.date DURING {$dateRange}
        ORDER BY segments.date ASC";

        $results = $this->query($customerId, $gaql);

        $labels = [];
        $revenue = [];
        $spend = [];

        foreach ($results as $row) {
            $date = $row['segments']['date'] ?? '';
            $labels[] = date('D', strtotime($date));
            $revenue[] = round(floatval($row['metrics']['conversionsValue'] ?? 0), 2);
            $spend[] = round(($row['metrics']['costMicros'] ?? 0) / 1000000, 2);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'spend' => $spend,
        ];
    }
}
