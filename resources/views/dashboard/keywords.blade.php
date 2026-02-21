@extends('layouts.app')

@section('title', 'Keywords')
@section('page-title', 'Keywords')
@section('page-subtitle', 'Keyword performance by campaign')

@section('content')
    <div class="client-selector" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div>
            <label for="kwClient" style="margin-right:6px;">Client:</label>
            <select id="kwClient" class="date-select">
                <option value="all" selected>All Clients</option>
            </select>
        </div>
        <div>
            <label for="kwCampaign" style="margin-right:6px;">Campaign:</label>
            <select id="kwCampaign" class="date-select">
                <option value="" selected>Select a campaign...</option>
            </select>
        </div>
        <div>
            <label for="kwDateRange" style="margin-right:6px;">Date Range:</label>
            <select id="kwDateRange" class="date-select">
                <option value="TODAY">Today</option>
                <option value="YESTERDAY">Yesterday</option>
                <option value="LAST_7_DAYS" selected>Last 7 Days</option>
                <option value="LAST_14_DAYS">Last 14 Days</option>
                <option value="LAST_30_DAYS">Last 30 Days</option>
                <option value="THIS_MONTH">This Month</option>
                <option value="LAST_MONTH">Last Month</option>
                <option value="LAST_90_DAYS">Last 90 Days</option>
            </select>
        </div>
    </div>

    <div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Top Keywords</span></div>
            <div class="kpi-value" id="kpiTopCount">-</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Avg. CPC</span></div>
            <div class="kpi-value" id="kpiAvgCpc">-</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Avg. Quality Score</span></div>
            <div class="kpi-value" id="kpiAvgQs">-</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3>🏆 Top Performing Keywords</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Match</th>
                        <th>Clicks</th>
                        <th>Conv.</th>
                        <th>CPA</th>
                        <th>QS</th>
                    </tr>
                </thead>
                <tbody id="topKeywordsBody">
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">Select a campaign to view keywords</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            let campaignsCache = [];

            // Load campaigns list for the selector
            function loadCampaignsList() {
                const clientId = document.getElementById('kwClient').value;
                const dateRange = document.getElementById('kwDateRange').value;

                fetch(`/api/campaigns?client=${clientId}&range=${dateRange}`)
                    .then(r => r.json())
                    .then(data => {
                        // Populate client filter
                        const clientSelect = document.getElementById('kwClient');
                        const currentClient = clientSelect.value;
                        clientSelect.innerHTML = '<option value="all">All Clients</option>';
                        data.clients.forEach(c => {
                            clientSelect.innerHTML += `<option value="${c.id}" ${currentClient == c.id ? 'selected' : ''}>${c.name}</option>`;
                        });

                        // Populate campaign selector
                        campaignsCache = data.campaigns;
                        const campSelect = document.getElementById('kwCampaign');
                        const currentCamp = campSelect.value;
                        campSelect.innerHTML = '<option value="">Select a campaign...</option>';
                        data.campaigns.forEach(c => {
                            campSelect.innerHTML += `<option value="${c.id}" ${currentCamp == c.id ? 'selected' : ''}>${c.name} (${c.client_name || ''})</option>`;
                        });
                    });
            }

            function loadKeywords() {
                const campaignId = document.getElementById('kwCampaign').value;
                const topBody = document.getElementById('topKeywordsBody');

                if (!campaignId) {
                    topBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">Select a campaign to view keywords</td></tr>';
                    document.getElementById('kpiTopCount').textContent = '-';
                    document.getElementById('kpiAvgCpc').textContent = '-';
                    document.getElementById('kpiAvgQs').textContent = '-';
                    return;
                }

                topBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">Loading keywords...</td></tr>';

                const dateRange = document.getElementById('kwDateRange').value;
                const campaign = campaignsCache.find(c => c.id == campaignId);
                const customerId = campaign ? campaign.client_id : '';

                fetch(`/api/keywords?customer_id=${customerId}&campaign_id=${campaignId}&range=${dateRange}`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('kpiTopCount').textContent = data.kpis.top_count;
                        document.getElementById('kpiAvgCpc').textContent = data.kpis.avg_cpc;
                        document.getElementById('kpiAvgQs').textContent = data.kpis.avg_qs;

                        topBody.innerHTML = '';
                        if (!data.keywords.top.length) {
                            topBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">No keywords found</td></tr>';
                        } else {
                            data.keywords.top.forEach(kw => {
                                const qsClass = kw.qs >= 8 ? 'high' : (kw.qs >= 6 ? 'medium' : 'low');
                                topBody.innerHTML += `<tr>
                                    <td><code class="keyword-text">${kw.keyword}</code></td>
                                    <td><span class="match-badge">${kw.match}</span></td>
                                    <td class="number">${kw.clicks}</td>
                                    <td class="number">${kw.conversions}</td>
                                    <td class="number">£${kw.cpa.toFixed(2)}</td>
                                    <td><span class="qs-badge ${qsClass}">${kw.qs !== null ? kw.qs + '/10' : 'N/A'}</span></td>
                                </tr>`;
                            });
                        }

                        const syncStatus = document.getElementById('syncStatus');
                        if (syncStatus) {
                            const now = new Date();
                            syncStatus.textContent = 'Synced ' + now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load keywords:', err);
                        topBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#ef4444;">Failed to load data</td></tr>';
                    });
            }

            document.addEventListener('DOMContentLoaded', function() {
                loadCampaignsList();

                document.getElementById('kwClient').addEventListener('change', () => {
                    loadCampaignsList();
                    document.getElementById('kwCampaign').value = '';
                    loadKeywords();
                });
                document.getElementById('kwCampaign').addEventListener('change', () => loadKeywords());
                document.getElementById('kwDateRange').addEventListener('change', () => {
                    loadCampaignsList();
                    loadKeywords();
                });
            });

            function refreshData() {
                const btn = document.getElementById('refreshDataBtn');
                const icon = document.getElementById('refreshIcon');
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
                if (icon) {
                    if (!document.getElementById('spinStyle')) {
                        const style = document.createElement('style');
                        style.id = 'spinStyle';
                        style.textContent = '@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }';
                        document.head.appendChild(style);
                    }
                    icon.style.animation = 'spin 1s linear infinite';
                }
                loadCampaignsList();
                loadKeywords();
                setTimeout(() => {
                    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                    if (icon) icon.style.animation = '';
                }, 3000);
            }
        </script>
    @endpush
@endsection