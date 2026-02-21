<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Google Ads Command Center') — Webspires</title>
    <meta name="description" content="Personal Google Ads ROI Dashboard for Webspires">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="{{ asset('images/logo-white.png') }}" alt="Webspires" class="logo-text-img">
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard.overview') }}"
                class="nav-item {{ request()->routeIs('dashboard.overview') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('dashboard.campaigns') }}"
                class="nav-item {{ request()->routeIs('dashboard.campaigns') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                </svg>
                <span>Campaigns</span>
            </a>
            <a href="{{ route('dashboard.keywords') }}"
                class="nav-item {{ request()->routeIs('dashboard.keywords') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <span>Keywords</span>
            </a>
            <a href="{{ route('dashboard.budget') }}"
                class="nav-item {{ request()->routeIs('dashboard.budget') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23" />
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>
                <span>Budget</span>
            </a>
            <a href="{{ route('dashboard.alerts') }}"
                class="nav-item {{ request()->routeIs('dashboard.alerts') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <span>Alerts</span>
                @if(isset($activeAlerts) && $activeAlerts > 0)
                    <span class="nav-badge">{{ $activeAlerts }}</span>
                @endif
            </a>
            <a href="{{ route('dashboard.notes') }}"
                class="nav-item {{ request()->routeIs('dashboard.notes') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                <span>Notes</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">AJ</div>
                <div class="user-details">
                    <span class="user-name">Atif Jan</span>
                    <span class="user-role">Admin</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Top Bar -->
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <div class="page-title-area">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                    <p class="page-subtitle">@yield('page-subtitle', '')</p>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="date-filter">
                    <select id="dateRange" class="date-select">
                        <option value="today">Today</option>
                        <option value="7days" selected>Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="90days">Last 90 Days</option>
                    </select>
                </div>
                <div class="last-synced">
                    <span class="sync-dot"></span>
                    <span id="syncStatus">Synced just now</span>
                </div>
                <button id="refreshDataBtn" onclick="refreshData()" style="background:var(--primary);color:#fff;border:none;padding:8px 16px;border-radius:var(--radius-xs);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="refreshIcon">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-area">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')

    <script>
        // Auto-refresh data every 1 hour
        setInterval(function() {
            if (typeof refreshData === 'function') {
                refreshData();
            }
        }, 3600000);
    </script>
</body>

</html>