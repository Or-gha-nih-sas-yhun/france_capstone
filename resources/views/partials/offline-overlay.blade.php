{{-- Blocks the UI whenever the device drops its internet connection. --}}
<div class="offline-overlay" id="offlineOverlay" aria-hidden="true">
    <div class="offline-dialog" role="alertdialog" aria-modal="true" aria-labelledby="offlineTitle"
        aria-describedby="offlineText">
        <div class="offline-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"></line>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                <line x1="12" y1="20" x2="12.01" y2="20"></line>
            </svg>
        </div>

        <div class="offline-copy">
            <h3 id="offlineTitle">No Internet Connection</h3>
            <p id="offlineText">
                Your device is offline. Check your Wi-Fi or mobile data &mdash; {{ config('app.name') }}
                will pick up right where you left off once you are back.
            </p>
        </div>

        <p class="offline-status" id="offlineStatus" role="status" aria-live="polite"></p>

        <button type="button" class="offline-retry-btn" id="offlineRetryBtn">
            <svg class="offline-retry-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            <span>Try Again</span>
        </button>

        <div class="offline-autonote">
            <span class="offline-pulse-dot"></span> Reconnecting automatically&hellip;
        </div>
    </div>
</div>

<style>
    /* ── Offline Blocker ─────────────────────────────── */
    .offline-overlay {
        position: fixed;
        inset: 0;
        /* Ties the chatbot widget's max z-index; this partial is included after it
           so the equal-stacking tiebreak on DOM order lands in our favour. */
        z-index: 2147483647;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }

    .offline-overlay.open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .offline-dialog {
        width: min(430px, 100%);
        background: var(--color-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.92);
        border-radius: var(--radius-lg, 16px);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.35);
        padding: 30px 28px;
        text-align: center;
        transform: translateY(12px) scale(0.97);
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .offline-overlay.open .offline-dialog {
        transform: translateY(0) scale(1);
    }

    .offline-icon {
        width: 62px;
        height: 62px;
        margin: 0 auto 18px;
        border-radius: 18px;
        color: var(--color-danger, #dc2626);
        background: linear-gradient(135deg, #fff7ed, var(--color-danger-light, #fef2f2));
        border: 1px solid #fecaca;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 24px rgba(220, 38, 38, 0.16);
    }

    .offline-copy h3 {
        margin: 0 0 8px;
        color: var(--color-secondary, #0f172a);
        font-size: 1.18rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .offline-copy p {
        margin: 0;
        color: var(--color-text-muted, #64748b);
        font-size: 0.9rem;
        line-height: 1.55;
    }

    .offline-status {
        margin: 14px 0 0;
        min-height: 18px;
        color: var(--color-danger, #dc2626);
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .offline-retry-btn {
        margin-top: 14px;
        width: 100%;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        border: none;
        border-radius: var(--radius-sm, 8px);
        background: var(--color-primary, #4f46e5);
        color: #ffffff;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: var(--shadow-primary, 0 4px 14px rgba(79, 70, 229, 0.3));
        transition: background-color 0.15s ease, transform 0.15s ease;
    }

    .offline-retry-btn:hover:not(:disabled) {
        background: var(--color-primary-hover, #4338ca);
    }

    .offline-retry-btn:active:not(:disabled) {
        transform: translateY(1px);
    }

    .offline-retry-btn:disabled {
        opacity: 0.72;
        cursor: progress;
    }

    .offline-retry-btn.is-checking .offline-retry-icon {
        animation: offlineSpin 0.7s linear infinite;
    }

    .offline-autonote {
        margin-top: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        color: var(--color-text-subtle, #94a3b8);
        font-size: 0.76rem;
        font-weight: 600;
    }

    .offline-pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--color-warning, #d97706);
        animation: offlinePulse 1.5s ease-in-out infinite;
    }

    /* Nothing behind the blocker should scroll, and the floating chat widget is
       useless without a network, so it goes away while we are offline. */
    body.offline-blocked {
        overflow: hidden;
    }

    body.offline-blocked .meras-chatbot-wrap,
    body.offline-blocked .chatbot-widget-container {
        display: none !important;
    }

    @keyframes offlineSpin {
        to { transform: rotate(360deg); }
    }

    @keyframes offlinePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.35; transform: scale(0.8); }
    }

    @media (prefers-reduced-motion: reduce) {
        .offline-overlay,
        .offline-dialog,
        .offline-retry-btn,
        .offline-retry-btn.is-checking .offline-retry-icon,
        .offline-pulse-dot {
            transition: none;
            animation: none;
        }
    }

    @media (max-width: 480px) {
        .offline-dialog {
            padding: 26px 20px;
        }
    }

    @media print {
        .offline-overlay {
            display: none !important;
        }
    }
</style>

<script>
    (function () {
        var PROBE_URL = @json(route('connection.check'));
        var POLL_ONLINE_MS = 15000;  // relaxed cadence while everything is healthy
        var POLL_OFFLINE_MS = 3000;  // check back quickly so recovery feels instant
        var PROBE_TIMEOUT_MS = 6000;

        var overlay = document.getElementById('offlineOverlay');
        var dialog = overlay.querySelector('.offline-dialog');
        var retryBtn = document.getElementById('offlineRetryBtn');
        var statusEl = document.getElementById('offlineStatus');

        var isOffline = false;
        var inFlight = false;
        var unloading = false;
        var timer = null;

        // A navigation aborts any pending fetch. Without this flag that abort would
        // be read as a dropped connection and flash the blocker on the way out.
        window.addEventListener('beforeunload', function () {
            unloading = true;
        });

        // Resolves true only if the server actually answered. A request that hangs
        // past the timeout counts as offline, which is what a dead Wi-Fi link looks
        // like from here — the socket opens and then nothing ever comes back.
        function probe() {
            var controller = new AbortController();
            var abortTimer = setTimeout(function () { controller.abort(); }, PROBE_TIMEOUT_MS);

            return fetch(PROBE_URL + '?_=' + Date.now(), {
                method: 'GET',
                cache: 'no-store',
                credentials: 'omit',
                signal: controller.signal
            }).then(function () {
                // Any HTTP answer proves the network is up. A CDN bot challenge (403)
                // or a server hiccup (5xx) is not the device being offline.
                clearTimeout(abortTimer);
                return true;
            }).catch(function () {
                clearTimeout(abortTimer);
                return false;
            });
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(check, isOffline ? POLL_OFFLINE_MS : POLL_ONLINE_MS);
        }

        function setState(online) {
            var wasOffline = isOffline;
            isOffline = !online;

            if (isOffline && !wasOffline) {
                overlay.classList.add('open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('offline-blocked');
                setTimeout(function () { retryBtn.focus(); }, 80);
            } else if (!isOffline && wasOffline) {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('offline-blocked');
                statusEl.textContent = '';
            }

            schedule();
        }

        function check() {
            if (inFlight || unloading) return;

            // navigator.onLine going false is conclusive — there is no network
            // interface at all, so skip the round trip. It going true is not, which
            // is why the probe still runs in that case.
            if (!navigator.onLine) {
                setState(false);
                return;
            }

            // A hidden tab cannot show the blocker anyway; visibilitychange re-checks.
            if (document.hidden) {
                schedule();
                return;
            }

            inFlight = true;
            probe().then(function (reachable) {
                inFlight = false;
                if (!unloading) setState(reachable);
            });
        }

        window.addEventListener('offline', function () { setState(false); });
        window.addEventListener('online', check);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) check();
        });

        // Keep focus inside the dialog so tabbing cannot reach the blocked page.
        document.addEventListener('focusin', function (event) {
            if (isOffline && !dialog.contains(event.target)) {
                retryBtn.focus();
            }
        });

        retryBtn.addEventListener('click', function () {
            if (inFlight) return;

            inFlight = true;
            retryBtn.disabled = true;
            retryBtn.classList.add('is-checking');
            statusEl.textContent = 'Rechecking your connection…';

            probe().then(function (reachable) {
                inFlight = false;
                retryBtn.disabled = false;
                retryBtn.classList.remove('is-checking');
                statusEl.textContent = reachable ? '' : 'Still offline. Check your Wi-Fi or mobile data.';
                setState(reachable);
            });
        });

        // This page was just served, so the server is reachable; don't spend a probe
        // on it while the page's own assets are still loading.
        setState(navigator.onLine);
    })();
</script>
