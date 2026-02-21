@extends('layouts.app')

@section('title', 'Budget')
@section('page-title', 'Budget Tracker')
@section('page-subtitle', 'Monthly budget utilisation per client')

@section('content')
    <div class="client-selector" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
        <div>
            <label for="budgetDateRange" style="margin-right:6px;">Date Range:</label>
            <select id="budgetDateRange" class="date-select">
                <option value="TODAY">Today</option>
                <option value="YESTERDAY">Yesterday</option>
                <option value="LAST_7_DAYS">Last 7 Days</option>
                <option value="LAST_14_DAYS">Last 14 Days</option>
                <option value="LAST_30_DAYS">Last 30 Days</option>
                <option value="THIS_MONTH" selected>This Month</option>
                <option value="LAST_MONTH">Last Month</option>
                <option value="LAST_90_DAYS">Last 90 Days</option>
            </select>
        </div>
    </div>

    <div class="budget-grid" id="budgetGrid">
        <div style="text-align:center;padding:40px;color:#9ca3af;grid-column:1/-1;">Loading budget data...</div>
    </div>

    @push('scripts')
        <script>
            function loadBudget(fresh) {
                const grid = document.getElementById('budgetGrid');
                grid.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;grid-column:1/-1;">Loading budget data...</div>';

                let url = '/api/overview?range=' + document.getElementById('budgetDateRange').value;
                if (fresh) url += '&fresh=1';

                fetch(url)
                    .then(r => r.json())
                    .then(data => {
                        grid.innerHTML = '';
                        if (!data.clients || !data.clients.length) {
                            grid.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;grid-column:1/-1;">No budget data available</div>';
                            return;
                        }

                        data.clients.forEach(client => {
                            const budget = client.budget || 1;
                            const spend = client.spend || 0;
                            const pct = Math.round((spend / budget) * 100);
                            const remaining = budget - spend;
                            const statusClass = pct > 95 ? 'danger' : (pct > 75 ? 'warning' : 'ok');
                            const statusLabel = pct > 95 ? '⚠️ Critical' : (pct > 75 ? '⚡ Watch' : '✅ On Track');

                            const now = new Date();
                            const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
                            const dayOfMonth = now.getDate();
                            const daysLeft = daysInMonth - dayOfMonth;
                            const dailySpend = dayOfMonth > 0 ? spend / dayOfMonth : 0;
                            const projectedSpend = Math.round(dailySpend * daysInMonth);
                            const projectedPct = Math.round((projectedSpend / budget) * 100);
                            const projectionText = projectedPct > 100
                                ? `£${projectedSpend.toLocaleString()} (${projectedPct - 100}% over budget)`
                                : `£${projectedSpend.toLocaleString()} (${projectedPct}% of budget)`;

                            const initial = client.name.charAt(0).toUpperCase();

                            grid.innerHTML += `
                            <div class="budget-card ${statusClass}">
                                <div class="budget-card-header">
                                    <div class="budget-client-info">
                                        <div class="client-avatar-sm" style="background: #EE314F">${initial}</div>
                                        <h4>${client.name}</h4>
                                    </div>
                                    <span class="budget-status-tag ${statusClass}">${statusLabel}</span>
                                </div>
                                <div class="budget-numbers">
                                    <div class="budget-stat">
                                        <span class="budget-stat-label">Spent</span>
                                        <span class="budget-stat-value">£${spend.toLocaleString()}</span>
                                    </div>
                                    <div class="budget-stat">
                                        <span class="budget-stat-label">Budget</span>
                                        <span class="budget-stat-value">£${budget.toLocaleString()}</span>
                                    </div>
                                    <div class="budget-stat">
                                        <span class="budget-stat-label">Remaining</span>
                                        <span class="budget-stat-value ${remaining < 200 ? 'negative-text' : ''}">£${remaining.toLocaleString()}</span>
                                    </div>
                                </div>
                                <div class="budget-progress">
                                    <div class="budget-progress-bar">
                                        <div class="budget-progress-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                                    </div>
                                    <div class="budget-progress-labels">
                                        <span>${pct}% used</span>
                                        <span>${daysLeft} days left</span>
                                    </div>
                                </div>
                                <div class="budget-projection">
                                    <span class="projection-label">Projected end-of-month:</span>
                                    <span class="projection-value ${projectedPct > 100 ? 'negative-text' : ''}">${projectionText}</span>
                                </div>
                            </div>`;
                        });

                        const syncStatus = document.getElementById('syncStatus');
                        if (syncStatus) {
                            const now = new Date();
                            syncStatus.textContent = 'Synced ' + now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load budget:', err);
                        grid.innerHTML = '<div style="text-align:center;padding:40px;color:#ef4444;grid-column:1/-1;">Failed to load data. Please refresh.</div>';
                    });
            }

            document.addEventListener('DOMContentLoaded', () => {
                loadBudget(false);
                document.getElementById('budgetDateRange').addEventListener('change', () => loadBudget(true));
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
                loadBudget(true);
                setTimeout(() => {
                    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                    if (icon) icon.style.animation = '';
                }, 3000);
            }
        </script>
    @endpush
@endsection