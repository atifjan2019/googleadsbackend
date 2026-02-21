@extends('layouts.app')

@section('title', 'Alerts')
@section('page-title', 'Alerts')
@section('page-subtitle', 'Actions that need your attention')

@section('content')
    <div class="alerts-list" id="alertsList">
        <div style="text-align:center;padding:40px;color:var(--text-muted);">Loading alerts...</div>
    </div>

    @push('scripts')
        <script>
            function loadAlerts() {
                const list = document.getElementById('alertsList');
                list.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">Loading alerts...</div>';

                fetch('/api/campaigns?range=LAST_7_DAYS')
                    .then(r => r.json())
                    .then(data => {
                        const alerts = [];

                        (data.campaigns || []).forEach(c => {
                            // High CPA alert
                            if (c.cpa > 80 && c.status !== 'paused') {
                                alerts.push({
                                    type: 'danger',
                                    icon: '🔴',
                                    title: 'High CPA: ' + c.name,
                                    message: `CPA is £${c.cpa.toFixed(2)} — significantly above target. Consider pausing or optimising this campaign.`,
                                    client: c.client_name || 'Unknown',
                                    time: 'Last 7 days'
                                });
                            }

                            // Low conversions
                            if (c.conversions === 0 && c.spend > 50 && c.status !== 'paused') {
                                alerts.push({
                                    type: 'danger',
                                    icon: '⚠️',
                                    title: 'No Conversions: ' + c.name,
                                    message: `Spent £${c.spend.toLocaleString()} with zero conversions. Review targeting and ad copy.`,
                                    client: c.client_name || 'Unknown',
                                    time: 'Last 7 days'
                                });
                            }

                            // Low CTR
                            if (c.ctr < 2 && c.impressions > 1000 && c.status !== 'paused') {
                                alerts.push({
                                    type: 'warning',
                                    icon: '🟡',
                                    title: 'Low CTR: ' + c.name,
                                    message: `CTR is only ${c.ctr}% with ${c.impressions.toLocaleString()} impressions. Ad relevance may need improvement.`,
                                    client: c.client_name || 'Unknown',
                                    time: 'Last 7 days'
                                });
                            }

                            // Budget nearly spent
                            if (c.budget && c.spend > c.budget * 0.9 && c.status !== 'paused') {
                                alerts.push({
                                    type: 'warning',
                                    icon: '⚡',
                                    title: 'Budget Alert: ' + c.name,
                                    message: `Campaign has used ${Math.round((c.spend / c.budget) * 100)}% of its budget.`,
                                    client: c.client_name || 'Unknown',
                                    time: 'Last 7 days'
                                });
                            }

                            // Strong performance
                            if (c.conversions > 20 && c.cpa < 30 && c.status !== 'paused') {
                                alerts.push({
                                    type: 'success',
                                    icon: '🟢',
                                    title: 'Strong Performance: ' + c.name,
                                    message: `${c.conversions} conversions at £${c.cpa.toFixed(2)} CPA. Consider increasing budget.`,
                                    client: c.client_name || 'Unknown',
                                    time: 'Last 7 days'
                                });
                            }
                        });

                        // Sort: danger first, then warning, then success
                        const order = { danger: 0, warning: 1, success: 2 };
                        alerts.sort((a, b) => (order[a.type] ?? 9) - (order[b.type] ?? 9));

                        if (!alerts.length) {
                            list.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted);background:var(--bg-card);border-radius:12px;border:1px solid var(--border);"><div style="font-size:48px;margin-bottom:12px;">✅</div><p style="margin:0;font-size:14px;">All clear! No alerts at this time.</p></div>';
                            return;
                        }

                        list.innerHTML = '';
                        alerts.forEach(alert => {
                            list.innerHTML += `
                            <div class="alert-card ${alert.type}">
                                <div class="alert-icon">${alert.icon}</div>
                                <div class="alert-body">
                                    <div class="alert-title">${alert.title}</div>
                                    <div class="alert-message">${alert.message}</div>
                                    <div class="alert-meta">
                                        <span class="alert-client">${alert.client}</span>
                                        <span class="alert-time">${alert.time}</span>
                                    </div>
                                </div>
                                <button class="alert-dismiss" onclick="this.closest('.alert-card').style.display='none'" aria-label="Dismiss">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                </button>
                            </div>`;
                        });

                        const syncStatus = document.getElementById('syncStatus');
                        if (syncStatus) {
                            const now = new Date();
                            syncStatus.textContent = 'Synced ' + now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load alerts:', err);
                        list.innerHTML = '<div style="text-align:center;padding:40px;color:#ef4444;">Failed to load alerts. Please refresh.</div>';
                    });
            }

            document.addEventListener('DOMContentLoaded', () => loadAlerts());

            function refreshData() { loadAlerts(); }
        </script>
    @endpush
@endsection