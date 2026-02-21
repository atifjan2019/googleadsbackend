@extends('layouts.app')

@section('title', 'Overview')
@section('page-title', 'Overview')
@section('page-subtitle', 'All clients at a glance')

@section('content')
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-label">Total Spend</span>
                <div class="kpi-icon spend">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
            </div>
            <div class="kpi-value">£{{ number_format($kpis['total_spend']) }}</div>
            <div class="kpi-change positive">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+8.2% vs last period</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-label">Total Conversions</span>
                <div class="kpi-icon conversions">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
            </div>
            <div class="kpi-value">{{ $kpis['total_conversions'] }}</div>
            <div class="kpi-change positive">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+14.5% vs last period</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-label">Avg. CPA</span>
                <div class="kpi-icon cpa">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
            </div>
            <div class="kpi-value">£{{ $kpis['avg_cpa'] }}</div>
            <div class="kpi-change negative">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    style="transform:rotate(180deg)">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>-3.1% vs last period</span>
            </div>
        </div>
        <div class="kpi-card highlight">
            <div class="kpi-header">
                <span class="kpi-label">Overall ROAS</span>
                <div class="kpi-icon roas">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                        <polyline points="16 7 22 7 22 13" />
                    </svg>
                </div>
            </div>
            <div class="kpi-value">{{ $kpis['overall_roas'] }}x</div>
            <div class="kpi-change positive">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                <span>+0.6x vs last period</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card large">
            <div class="chart-header">
                <h3>Revenue vs Spend</h3>
                <div class="chart-legend">
                    <span class="legend-item"><span class="legend-dot revenue"></span> Revenue</span>
                    <span class="legend-item"><span class="legend-dot spend"></span> Spend</span>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        <div class="chart-card small">
            <div class="chart-header">
                <h3>Spend by Client</h3>
            </div>
            <div class="chart-body">
                <canvas id="channelChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Client Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>Client Performance</h3>
            <div class="table-actions">
                <input type="text" class="search-input" placeholder="Search clients..." id="clientSearch">
            </div>
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="clientTable">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Spend</th>
                        <th>Clicks</th>
                        <th>Conv.</th>
                        <th>CPA</th>
                        <th>ROAS</th>
                        <th>Budget Used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"
                                        style="background: {{ $client['status'] === 'active' ? '#EE314F' : ($client['status'] === 'warning' ? '#f59e0b' : '#ef4444') }}">
                                        {{ strtoupper(substr($client['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="client-name">{{ $client['name'] }}</div>
                                        <div class="client-industry">{{ $client['industry'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge {{ $client['status'] }}">
                                    @if($client['status'] === 'active') 🟢 Performing
                                    @elseif($client['status'] === 'warning') 🟡 Attention
                                    @else 🔴 Underperforming
                                    @endif
                                </span>
                            </td>
                            <td class="number">£{{ number_format($client['spend']) }}</td>
                            <td class="number">{{ number_format($client['clicks']) }}</td>
                            <td class="number">{{ $client['conversions'] }}</td>
                            <td class="number">£{{ number_format($client['cpa'], 2) }}</td>
                            <td>
                                <span
                                    class="roas-badge {{ $client['roas'] >= 4 ? 'high' : ($client['roas'] >= 2 ? 'medium' : 'low') }}">
                                    {{ $client['roas'] }}x
                                </span>
                            </td>
                            <td>
                                @php $budgetPct = round(($client['spend'] / $client['budget']) * 100); @endphp
                                <div class="budget-bar-cell">
                                    <div class="budget-bar">
                                        <div class="budget-fill {{ $budgetPct > 90 ? 'danger' : ($budgetPct > 70 ? 'warning' : 'ok') }}"
                                            style="width: {{ min($budgetPct, 100) }}%"></div>
                                    </div>
                                    <span class="budget-pct">{{ $budgetPct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            let chartData = @json($chartData);
            let clientsData = @json($clients);
            let revenueChartInstance = null;
            let channelChartInstance = null;

            function formatNumber(n) {
                return n.toLocaleString('en-GB');
            }

            function updateKpis(kpis) {
                const kpiValues = document.querySelectorAll('.kpi-value');
                if (kpiValues[0]) kpiValues[0].textContent = '£' + formatNumber(kpis.total_spend);
                if (kpiValues[1]) kpiValues[1].textContent = kpis.total_conversions;
                if (kpiValues[2]) kpiValues[2].textContent = '£' + kpis.avg_cpa;
                if (kpiValues[3]) kpiValues[3].textContent = kpis.overall_roas + 'x';
            }

            function updateClientTable(clients) {
                const tbody = document.querySelector('#clientTable tbody');
                if (!tbody) return;
                tbody.innerHTML = '';

                const activeClients = clients.filter(c => c.status !== 'paused');
                activeClients.forEach(client => {
                    const statusLabel = client.status === 'active' ? '🟢 Performing' : (client.status === 'warning' ? '🟡 Attention' : '🔴 Underperforming');
                    const avatarColor = client.status === 'active' ? '#EE314F' : (client.status === 'warning' ? '#f59e0b' : '#ef4444');
                    const roasClass = client.roas >= 4 ? 'high' : (client.roas >= 2 ? 'medium' : 'low');
                    const budgetPct = client.budget > 0 ? Math.round((client.spend / client.budget) * 100) : 0;
                    const budgetClass = budgetPct > 90 ? 'danger' : (budgetPct > 70 ? 'warning' : 'ok');

                    tbody.innerHTML += `<tr>
                        <td><div class="client-cell">
                            <div class="client-avatar" style="background:${avatarColor}">${client.name.charAt(0).toUpperCase()}</div>
                            <div><div class="client-name">${client.name}</div><div class="client-industry">${client.industry || ''}</div></div>
                        </div></td>
                        <td><span class="status-badge ${client.status}">${statusLabel}</span></td>
                        <td class="number">£${formatNumber(client.spend)}</td>
                        <td class="number">${formatNumber(client.clicks)}</td>
                        <td class="number">${client.conversions}</td>
                        <td class="number">£${client.cpa.toFixed(2)}</td>
                        <td><span class="roas-badge ${roasClass}">${client.roas}x</span></td>
                        <td><div class="budget-bar-cell">
                            <div class="budget-bar"><div class="budget-fill ${budgetClass}" style="width:${Math.min(budgetPct,100)}%"></div></div>
                            <span class="budget-pct">${budgetPct}%</span>
                        </div></td>
                    </tr>`;
                });
            }

            function renderCharts(data, clients) {
                const rvCtx = document.getElementById('revenueChart');
                if (rvCtx && data.labels.length) {
                    if (revenueChartInstance) revenueChartInstance.destroy();
                    revenueChartInstance = new Chart(rvCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                { label: 'Revenue', data: data.revenue, borderColor: '#EE314F', backgroundColor: 'rgba(238,49,79,0.08)', fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#EE314F' },
                                { label: 'Spend', data: data.spend, borderColor: '#1e1e2e', backgroundColor: 'rgba(30,30,46,0.05)', fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#1e1e2e' },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e1e2e', padding: 12, callbacks: { label: (ctx) => `${ctx.dataset.label}: £${ctx.parsed.y.toLocaleString()}` } } },
                            scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: (v) => '£' + (v/1000).toFixed(0) + 'k' } }, x: { grid: { display: false } } }
                        }
                    });
                }

                const chCtx = document.getElementById('channelChart');
                if (chCtx && clients.length) {
                    if (channelChartInstance) channelChartInstance.destroy();
                    channelChartInstance = new Chart(chCtx, {
                        type: 'doughnut',
                        data: {
                            labels: clients.map(c => c.name),
                            datasets: [{ data: clients.map(c => c.spend), backgroundColor: ['#EE314F','#1e1e2e','#ff6b81','#555','#ff9eb0','#999'], borderWidth: 0, hoverOffset: 6 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '68%',
                            plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 11 }, padding: 12, usePointStyle: true } }, tooltip: { backgroundColor: '#1e1e2e', padding: 12, callbacks: { label: (ctx) => ` £${ctx.parsed.toLocaleString()}` } } }
                        }
                    });
                }
            }

            function getRange() {
                const sel = document.getElementById('dateRange');
                return sel ? sel.value : 'LAST_7_DAYS';
            }

            function loadOverviewData(fresh) {
                document.querySelectorAll('.kpi-value').forEach(el => el.style.opacity = '0.4');
                const tbody = document.querySelector('#clientTable tbody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">Loading data...</td></tr>';

                let url = '/api/overview?range=' + getRange();
                if (fresh) url += '&fresh=1';

                fetch(url)
                    .then(r => r.json())
                    .then(data => {
                        updateKpis(data.kpis);
                        updateClientTable(data.clients);
                        renderCharts(data.chartData, data.clients);
                        document.querySelectorAll('.kpi-value').forEach(el => el.style.opacity = '1');
                    })
                    .catch(err => {
                        console.error('Failed to load data:', err);
                        if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#ef4444;">Failed to load data. Please refresh.</td></tr>';
                    });
            }

            document.addEventListener('DOMContentLoaded', function() {
                renderCharts(chartData, clientsData);
                loadOverviewData(false);

                const dateRange = document.getElementById('dateRange');
                if (dateRange) dateRange.addEventListener('change', () => loadOverviewData(true));

                const searchInput = document.getElementById('clientSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const q = this.value.toLowerCase();
                        document.querySelectorAll('#clientTable tbody tr').forEach(row => {
                            const name = row.querySelector('.client-name')?.textContent.toLowerCase() || '';
                            row.style.display = name.includes(q) ? '' : 'none';
                        });
                    });
                }
            });

            // Global refresh function for the Refresh button
            function refreshData() {
                const btn = document.getElementById('refreshDataBtn');
                const icon = document.getElementById('refreshIcon');
                const syncStatus = document.getElementById('syncStatus');

                btn.disabled = true;
                btn.style.opacity = '0.6';
                icon.style.animation = 'spin 1s linear infinite';

                // Add spin animation
                if (!document.getElementById('spinStyle')) {
                    const style = document.createElement('style');
                    style.id = 'spinStyle';
                    style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
                    document.head.appendChild(style);
                }

                syncStatus.textContent = 'Refreshing...';

                document.querySelectorAll('.kpi-value').forEach(el => el.style.opacity = '0.4');
                const tbody = document.querySelector('#clientTable tbody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">Refreshing data...</td></tr>';

                fetch('/api/overview?range=' + getRange() + '&fresh=1')
                    .then(r => r.json())
                    .then(data => {
                        updateKpis(data.kpis);
                        updateClientTable(data.clients);
                        renderCharts(data.chartData, data.clients);
                        document.querySelectorAll('.kpi-value').forEach(el => el.style.opacity = '1');

                        const now = new Date();
                        syncStatus.textContent = 'Synced ' + now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                    })
                    .catch(err => {
                        console.error('Refresh failed:', err);
                        syncStatus.textContent = 'Refresh failed';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        icon.style.animation = '';
                    });
            }
        </script>
    @endpush
@endsection