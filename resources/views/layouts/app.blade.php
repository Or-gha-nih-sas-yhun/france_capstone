<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="@yield('body_class')">
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand">
                <div class="brand-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-brand" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <span>{{ config('app.name') }}</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <p class="sidebar-section-label">Main Menu</p>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="9"></rect>
                    <rect x="14" y="3" width="7" height="5"></rect>
                    <rect x="14" y="12" width="7" height="9"></rect>
                    <rect x="3" y="16" width="7" height="5"></rect>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                    <line x1="12" y1="4" x2="12" y2="20"></line>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                </svg>
                <span>POS Checkout</span>
            </a>
            <a href="{{ route('products.index') }}"
                class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="7.5 4.21 12 6.81 16.5 4.21"></polyline>
                    <polyline points="7.5 19.79 7.5 14.6 3 12"></polyline>
                    <polyline points="16.5 19.79 16.5 14.6 21 12"></polyline>
                    <polyline points="12 22.08 12 16.88 12 12"></polyline>
                    <polyline points="12 12 7.5 9.4 3 12"></polyline>
                    <polyline points="12 12 16.5 9.4 21 12"></polyline>
                </svg>
                <span>Inventory</span>
            </a>
            <a href="{{ route('chat.index') }}" class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Team Chat</span>
            </a>
            <a href="{{ route('inquiries.index') }}"
                class="nav-link {{ request()->routeIs('inquiries.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <span>Inquiries</span>
            </a>
            <a href="{{ route('reports.index') }}"
                class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Reports</span>
            </a>
            @if(auth()->user()->role === 'admin')
                <p class="sidebar-section-label">Administration</p>
                <a href="{{ route('settings.index') }}"
                    class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
                        </path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>Settings</span>
                </a>
                <a href="{{ route('users.index') }}"
                    class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>User Management</span>
                </a>
                <a href="{{ route('logs.index') }}"
                    class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="9"></line>
                        <line x1="9" y1="13" x2="15" y2="13"></line>
                        <line x1="9" y1="17" x2="13" y2="17"></line>
                    </svg>
                    <span>Activity Logs</span>
                </a>
            @endif
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="user-details">
                <div class="username">{{ auth()->user()->name }}</div>
                <div class="user-role-badge">{{ auth()->user()->role }}</div>
            </div>
            <a href="{{ route('logout') }}" data-logout-confirm class="sidebar-logout-btn" title="Sign out">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <header class="mobile-topbar" id="mobileTopbar">
        <button class="burger-btn" id="burgerBtn" aria-label="Open menu">
            <span class="burger-line"></span>
            <span class="burger-line"></span>
            <span class="burger-line"></span>
        </button>
        <a href="{{ route('dashboard') }}" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon-brand" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                </path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>{{ config('app.name') }}</span>
        </a>
        <div class="mobile-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
    </header>
    <div class="logout-confirm-overlay" id="logoutConfirmModal" aria-hidden="true">
        <div class="logout-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle"
            aria-describedby="logoutConfirmText">
            <div class="logout-confirm-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v10"></path>
                    <path d="M18.4 6.6a9 9 0 1 1-12.8 0"></path>
                </svg>
            </div>
            <div class="logout-confirm-copy">
                <h3 id="logoutConfirmTitle">Sign out of {{ config('app.name') }}?</h3>
                <p id="logoutConfirmText">Your current session will end and you will return to the login screen.</p>
            </div>
            <div class="logout-confirm-actions">
                <button type="button" class="btn btn-secondary" id="cancelLogoutBtn">Stay Signed In</button>
                <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="confirmLogoutBtn">Log Out</button>
                </form>
            </div>
        </div>
    </div>
    <div class="app-wrapper has-sidebar">
        <main class="app-main">
            @yield('content')
        </main>
    </div>
    <script>
        window.isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};

        const burgerBtn = document.getElementById('burgerBtn');
        const appSidebar = document.getElementById('appSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const logoutConfirmModal = document.getElementById('logoutConfirmModal');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
        const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');

        function openSidebar() {
            appSidebar.classList.add('open');
            sidebarOverlay.classList.add('visible');
            burgerBtn.classList.add('active');
        }

        function closeSidebar() {
            appSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('visible');
            burgerBtn.classList.remove('active');
        }

        burgerBtn.addEventListener('click', function () {
            appSidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        sidebarOverlay.addEventListener('click', closeSidebar);

        document.addEventListener('click', function (event) {
            const logoutLink = event.target.closest('[data-logout-confirm]');
            if (!logoutLink) return;
            event.preventDefault();
            logoutConfirmModal.classList.add('open');
            logoutConfirmModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('logout-modal-open');
            setTimeout(() => cancelLogoutBtn.focus(), 80);
        });

        cancelLogoutBtn.addEventListener('click', function () {
            logoutConfirmModal.classList.remove('open');
            logoutConfirmModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('logout-modal-open');
        });

        logoutConfirmModal.addEventListener('click', function (event) {
            if (event.target === logoutConfirmModal) {
                logoutConfirmModal.classList.remove('open');
                logoutConfirmModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('logout-modal-open');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && logoutConfirmModal.classList.contains('open')) {
                logoutConfirmModal.classList.remove('open');
                logoutConfirmModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('logout-modal-open');
            }
        });
    </script>

    <!-- Mobile Bottom Navigation Styles -->
    <style>
        @media (max-width: 767px) {
            .mobile-topbar, .app-sidebar, .sidebar-overlay {
                display: none !important;
            }
            .app-wrapper {
                padding-top: 0 !important;
                padding-bottom: 65px !important;
            }
        }
    </style>

    @php
        $isMobileApp = session('is_mobile_app', false) || (isset($_SERVER['HTTP_USER_AGENT']) && str_contains($_SERVER['HTTP_USER_AGENT'], 'MerasUserApp'));
        if ($isMobileApp && !session('is_mobile_app')) {
            session(['is_mobile_app' => true]);
        }
        $showWebBottomNav = !$isMobileApp;
    @endphp

    @if($showWebBottomNav)
        <!-- Premium Mobile Bottom Navigation Bar (Bootstrap Pattern) -->
        <div class="mobile-bottom-nav">
            @if(auth()->user()->role === 'user')
                <!-- Customer Bottom Navigation -->
                <a href="{{ route('home') }}" class="mobile-nav-item {{ request()->routeIs('home') && !request()->has('products') && !request()->has('inquire') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-house-door"></i></span>
                    <span class="nav-label">Home</span>
                </a>
                <a href="{{ route('home') }}#products" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-grid"></i></span>
                    <span class="nav-label">Products</span>
                </a>
                <a href="{{ route('home') }}#inquire" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-envelope"></i></span>
                    <span class="nav-label">Inquiry</span>
                </a>
                <a href="{{ route('profile.index') }}" class="mobile-nav-item {{ request()->routeIs('profile.index') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-person-fill"></i></span>
                    <span class="nav-label">Profile</span>
                </a>
            @else
                <!-- Staff / Admin Bottom Navigation -->
                <a href="{{ route('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('pos.index') }}" class="mobile-nav-item {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-calculator"></i></span>
                    <span class="nav-label">POS</span>
                </a>
                <a href="{{ route('inquiries.index') }}" class="mobile-nav-item {{ request()->routeIs('inquiries.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-envelope-paper"></i></span>
                    <span class="nav-label">Inquiries</span>
                </a>
                <a href="{{ route('reports.index') }}" class="mobile-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span>
                    <span class="nav-label">Reports</span>
                </a>
            @endif
        </div>
    @endif
    @include('partials.offline-overlay')

    @stack('scripts')
</body>

</html>