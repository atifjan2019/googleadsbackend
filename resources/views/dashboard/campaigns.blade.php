@extends('layouts.app')

@section('title', 'Campaigns')
@section('page-title', 'Campaigns')
@section('page-subtitle', 'Track all campaign performance')

@section('content')
    <style>
        th.sortable { cursor: pointer; user-select: none; }
        th.sortable:hover { color: #818cf8; }
        .sort-arrow { font-size: 0.75em; color: #818cf8; }
    </style>
    <div class="client-selector" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div>
            <label for="campaignClient" style="margin-right:6px;">Client:</label>
            <select id="campaignClient" name="client" class="date-select">
                <option value="all" selected>All Clients</option>
            </select>
        </div>
        <div>
            <label for="statusFilter" style="margin-right:6px;">Status:</label>
            <select id="statusFilter" class="date-select">
                <option value="all">All Statuses</option>
                <option value="active" selected>🟢 Active</option>
                <option value="paused">⏸ Paused</option>
            </select>
        </div>
        <div>
            <label for="dateRangeFilter" style="margin-right:6px;">Date Range:</label>
            <select id="dateRangeFilter" class="date-select">
                <option value="TODAY" selected>Today</option>
                <option value="YESTERDAY">Yesterday</option>
                <option value="LAST_7_DAYS">Last 7 Days</option>
                <option value="LAST_14_DAYS">Last 14 Days</option>
                <option value="LAST_30_DAYS">Last 30 Days</option>
                <option value="THIS_MONTH">This Month</option>
                <option value="LAST_MONTH">Last Month</option>
                <option value="LAST_90_DAYS">Last 90 Days</option>
            </select>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3>Campaign Performance</h3>
            <input type="text" class="search-input" placeholder="Search campaigns..." id="campaignSearch">
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="campaignTable">
                <thead>
                    <tr>
                        <th class="sortable" data-key="name" onclick="sortCampaigns('name')">Campaign <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="client_name" onclick="sortCampaigns('client_name')">Client <span class="sort-arrow"></span></th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="sortable" data-key="spend" onclick="sortCampaigns('spend')">Spend <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="impressions" onclick="sortCampaigns('impressions')">Impr. <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="clicks" onclick="sortCampaigns('clicks')">Clicks <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="ctr" onclick="sortCampaigns('ctr')">CTR <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="conversions" onclick="sortCampaigns('conversions')">Conv. <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="cpa" onclick="sortCampaigns('cpa')">CPA <span class="sort-arrow"></span></th>
                        <th class="sortable" data-key="phone_calls" onclick="sortCampaigns('phone_calls')">Calls <span class="sort-arrow"></span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="11" style="text-align:center;padding:40px;color:#9ca3af;">Loading campaigns...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            let allCampaigns = [];
            let currentSortKey = 'spend';
            let sortAsc = false;

            function loadCampaigns(fresh) {
                const tbody = document.querySelector('#campaignTable tbody');
                const clientId = document.getElementById('campaignClient').value;
                const dateRange = document.getElementById('dateRangeFilter').value;
                tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px;color:#9ca3af;">Loading campaigns...</td></tr>';

                let url = `/api/campaigns?client=${clientId}&range=${dateRange}`;
                if (fresh) url += '&fresh=1';

                fetch(url)
                    .then(r => r.json())
                    .then(data => {
                        // Populate client filter — only clients with active campaigns
                        const activeClientIds = new Set(
                            data.campaigns
                                .filter(c => ['active', 'warning', 'danger'].includes(c.status))
                                .map(c => String(c.client_id))
                        );
                        const select = document.getElementById('campaignClient');
                        const currentVal = select.value;
                        select.innerHTML = '<option value="all">All Clients</option>';
                        data.clients
                            .filter(c => activeClientIds.has(String(c.id)))
                            .forEach(c => {
                                select.innerHTML += `<option value="${c.id}" ${currentVal == c.id ? 'selected' : ''}>${c.name}</option>`;
                            });

                        allCampaigns = data.campaigns;
                        applyStatusFilter();

                        const syncStatus = document.getElementById('syncStatus');
                        if (syncStatus) {
                            const now = new Date();
                            syncStatus.textContent = 'Synced ' + now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load campaigns:', err);
                        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px;color:#ef4444;">Failed to load data. Please refresh.</td></tr>';
                    });
            }

            function sortCampaigns(key) {
                if (currentSortKey === key) {
                    sortAsc = !sortAsc;
                } else {
                    currentSortKey = key;
                    sortAsc = (typeof allCampaigns[0]?.[key] === 'string');
                }

                allCampaigns.sort((a, b) => {
                    let va = a[key] ?? 0, vb = b[key] ?? 0;
                    if (typeof va === 'string') {
                        return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
                    }
                    return sortAsc ? va - vb : vb - va;
                });

                // Update arrow indicators
                document.querySelectorAll('#campaignTable th.sortable .sort-arrow').forEach(el => el.textContent = '');
                const activeHeader = document.querySelector(`#campaignTable th[data-key="${key}"] .sort-arrow`);
                if (activeHeader) activeHeader.textContent = sortAsc ? ' ▲' : ' ▼';

                applyStatusFilter();
            }

            function applyStatusFilter() {
                const tbody = document.querySelector('#campaignTable tbody');
                const statusFilter = document.getElementById('statusFilter').value;

                let filtered = allCampaigns;
                if (statusFilter === 'active') {
                    filtered = allCampaigns.filter(c => ['active', 'warning', 'danger'].includes(c.status));
                } else if (statusFilter !== 'all') {
                    filtered = allCampaigns.filter(c => c.status === statusFilter);
                }

                tbody.innerHTML = '';
                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px;color:#9ca3af;">No campaigns found</td></tr>';
                    return;
                }

                filtered.forEach(c => {
                    const isActive = c.status !== 'paused';
                    const statusLabel = isActive ? '🟢 Active' : '⏸ Paused';
                    const statusClass = isActive ? 'active' : 'paused';
                    const roasClass = c.roas >= 5 ? 'high' : (c.roas >= 3 ? 'medium' : 'low');
                    tbody.innerHTML += `<tr data-status="${c.status}">
                        <td><span class="campaign-name">${c.name}</span></td>
                        <td><span class="client-tag">${c.client_name || 'Unknown'}</span></td>
                        <td><span class="type-badge ${c.type.toLowerCase()}">${c.type}</span></td>
                        <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                        <td class="number">£${c.spend.toLocaleString()}</td>
                        <td class="number">${c.impressions.toLocaleString()}</td>
                        <td class="number">${c.clicks.toLocaleString()}</td>
                        <td class="number">${c.ctr}%</td>
                        <td class="number">${c.conversions}</td>
                        <td class="number">£${c.cpa.toFixed(2)}</td>
                        <td class="number">${c.phone_calls || 0}</td>
                    </tr>`;
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                loadCampaigns(false);

                document.getElementById('campaignClient').addEventListener('change', () => loadCampaigns(false));
                document.getElementById('dateRangeFilter').addEventListener('change', () => loadCampaigns(true));
                document.getElementById('statusFilter').addEventListener('change', () => applyStatusFilter());

                const s = document.getElementById('campaignSearch');
                if (s) {
                    s.addEventListener('input', function() {
                        const q = this.value.toLowerCase();
                        document.querySelectorAll('#campaignTable tbody tr').forEach(row => {
                            const name = row.querySelector('.campaign-name')?.textContent.toLowerCase() || '';
                            row.style.display = name.includes(q) ? '' : 'none';
                        });
                    });
                }
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
                loadCampaigns(true);
                setTimeout(() => {
                    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                    if (icon) icon.style.animation = '';
                }, 3000);
            }
        </script>
    @endpush
@endsection