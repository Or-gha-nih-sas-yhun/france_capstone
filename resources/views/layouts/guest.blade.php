<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/style.css">
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/landing.css">
    {{-- Icon font is loaded without blocking first paint; a third-party CDN round trip must not hold up the page. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
        media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"></noscript>

    @php
        $isMobileApp = session('is_mobile_app', false) || (isset($_SERVER['HTTP_USER_AGENT']) && str_contains($_SERVER['HTTP_USER_AGENT'], 'MerasUserApp'));
        if ($isMobileApp && !session('is_mobile_app')) {
            session(['is_mobile_app' => true]);
        }
        $showWebBottomNav = !$isMobileApp;
    @endphp

    @if($isMobileApp)
        <!-- Premium Mobile App UI Polish -->
        <style>
            @media (max-width: 767px) {
                /* Full height content and zero offsets since there is no header/footer */
                .guest-main-content {
                    min-height: 100vh !important;
                    display: flex !important;
                    flex-direction: column !important;
                    background: #ffffff !important; /* Premium clean layout background */
                }

                /* Hide standard web header and footer inside mobile app */
                .guest-header, .app-footer {
                    display: none !important;
                }

                /* For login, register, forgot-password, reset-password card containers */
                .guest-main-content:has(.auth-container) {
                    padding: 8px 16px !important;
                    background: #ffffff !important;
                    justify-content: center !important;
                    align-items: center !important;
                }

                .auth-container {
                    box-shadow: none !important;
                    border: none !important;
                    padding: 16px 4px !important;
                    background-color: transparent !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                }

                /* Ensure all inputs and buttons feel highly native, rounded and polished */
                .form-control {
                    height: 48px !important;
                    font-size: 0.95rem !important;
                    border-radius: 10px !important;
                    border: 1px solid #cbd5e1 !important;
                    background-color: #f8fafc !important;
                    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
                }
                .form-control:focus {
                    border-color: #4f46e5 !important;
                    background-color: #ffffff !important;
                    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
                }
                .btn-primary {
                    height: 48px !important;
                    border-radius: 10px !important;
                    font-size: 0.95rem !important;
                    font-weight: 700 !important;
                    background: #4f46e5 !important;
                    border: none !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1), 0 2px 4px -1px rgba(79, 70, 229, 0.06) !important;
                }
                /* Social login simulated buttons inside the app */
                .btn-social {
                    height: 48px !important;
                    border-radius: 10px !important;
                    border: 1px solid #cbd5e1 !important;
                    font-size: 0.9rem !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 10px !important;
                }
                /* Spacing adjustments on mobile app */
                .auth-header h2 {
                    font-size: 1.6rem !important;
                    font-weight: 800 !important;
                    color: #0f172a !important;
                    margin-bottom: 6px !important;
                }
                .auth-header p {
                    font-size: 0.85rem !important;
                    color: #64748b !important;
                    line-height: 1.4 !important;
                }
                /* Custom alert styling */
                .alert {
                    border-radius: 10px !important;
                    font-size: 0.85rem !important;
                    padding: 12px 16px !important;
                }
            }
        </style>
    @endif

    @stack('styles')
</head>
<body>
    @if(!$isMobileApp)
        <header class="guest-header">
            <div class="guest-nav-container">
                <a href="{{ route('home') }}" class="guest-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>{{ config('app.name') }}</span>
                </a>
                <div class="guest-links">
                    <a href="{{ route('home') }}#products" class="guest-link">Browse</a>
                    <a href="{{ route('home') }}#inquire" class="guest-link">Contact</a>
                    @if(Auth::check())
                        <a href="{{ route('profile.index') }}" class="guest-link" style="font-weight: bold; color: #4f46e5;">👤 My Account</a>
                        <a href="{{ route('profile.notifications') }}" class="guest-link">🔔 Alerts</a>
                        <a href="{{ route('logout') }}" data-logout-confirm class="guest-link" style="color: #ef4444;">Logout</a>
                    @else
                        <a href="{{ route('login') }}" class="guest-link btn-guest-login-outline" style="font-weight: bold; color: #4f46e5 !important; border: 1px solid #4f46e5; padding: 6px 14px; border-radius: 6px; margin-right: 4px;">🔑 Log In</a>
                        <a href="{{ route('register') }}" class="guest-link btn-guest-login" style="background-color: #4f46e5; color: #ffffff !important; padding: 6px 14px; border-radius: 6px;">Register</a>
                    @endif
                </div>
            </div>
        </header>
    @endif

    <main class="guest-main-content">
        @yield('content')
    </main>

    @if(!$isMobileApp)
        <footer class="app-footer">
            <div class="footer-grid">
                <div class="footer-brand-col">
                    <a href="{{ route('home') }}" class="footer-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        <span>{{ config('app.name') }}</span>
                    </a>
                    <p class="footer-desc">Premium school supplies, high-quality fabrics, and general merchandise for families and businesses throughout Bantayan. Proudly serving the community with value and care.</p>
                </div>
                <div class="footer-links-col">
                    <h4 class="footer-title">Quick Links</h4>
                    <nav class="footer-nav">
                        <a href="{{ route('home') }}#products" class="footer-nav-link">Browse Products</a>
                        <a href="{{ route('home') }}#inquire" class="footer-nav-link">Send Inquiry</a>
                        @empty($hideAppDownload)
                            <a href="{{ route('mobile.download') }}" class="footer-nav-link">Download Android App</a>
                        @endempty
                        @empty($hideStaffLinks)
                            <a href="{{ route('login') }}" class="footer-nav-link">Staff Dashboard Portal</a>
                        @endempty
                    </nav>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p>Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</p>
            </div>
        </footer>
    @endif

    <script>
        window.isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
        
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.href;
            document.querySelectorAll('.mobile-nav-item').forEach(item => {
                const href = item.getAttribute('href');
                if (href) {
                    if (currentUrl.includes('/chat') && href.includes('/chat')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/profile') && href.includes('/profile')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/notifications') && href.includes('/notifications')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/login') && href.includes('/login')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/register') && href.includes('/register')) {
                        item.classList.add('active');
                    }
                }
            });
        });
    </script>

    @if($showWebBottomNav)
        <!-- Bottom Navigation Bar for Mobile -->
        <div class="mobile-bottom-nav">
            <a href="{{ route('home') }}#home" class="mobile-nav-item">
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
            @if(Auth::check())
                <a href="{{ route('chat.index') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-chat-dots"></i></span>
                    <span class="nav-label">Chat</span>
                </a>
                <a href="{{ route('profile.index') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-person"></i></span>
                    <span class="nav-label">Profile</span>
                </a>
                <a href="{{ route('profile.notifications') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-bell"></i></span>
                    <span class="nav-label">Alerts</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                    <span class="nav-label">Sign In</span>
                </a>
                <a href="{{ route('register') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-person-plus"></i></span>
                    <span class="nav-label">Register</span>
                </a>
            @endif
        </div>
    @endif

    @if(Auth::check())
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
                    <a href="{{ route('logout') }}" class="btn btn-danger" id="confirmLogoutBtn">Log Out</a>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const logoutConfirmModal = document.getElementById('logoutConfirmModal');
                const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
                const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');

                document.addEventListener('click', function (event) {
                    const logoutLink = event.target.closest('[data-logout-confirm]');
                    if (!logoutLink) return;
                    event.preventDefault();
                    confirmLogoutBtn.href = logoutLink.href;
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
            });
        </script>
    @endif

    @include('partials.chatbot')

    {{-- Must stay after the chatbot: both sit at the max z-index, so DOM order decides. --}}
    @include('partials.offline-overlay')

    @stack('scripts')
</body>
</html>