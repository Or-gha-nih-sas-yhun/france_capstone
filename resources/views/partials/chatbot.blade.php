{{-- Mera's Floating AI Chatbot Widget --}}
@php
    $isMobileWebview = str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'MerasUserApp')
        || (session('is_mobile_app', false) && (request()->is('user-app*') || request()->routeIs('mobile.*')));
    $messengerUrl = \App\Http\Controllers\ChatController::MESSENGER_URL;
    $messengerName = \App\Http\Controllers\ChatController::MESSENGER_NAME;
@endphp
@if(!$isMobileWebview)
<div class="meras-chatbot-wrap" id="merasChatbotWrap">

    {{-- Floating Trigger Button --}}
    <button class="meras-fab" id="merasFab" aria-label="Open Mera's AI Chat Assistant" title="Chat with our Assistant">
        <div class="meras-fab-icon-ring">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <span class="meras-fab-txt">Chat with Us</span>
        <span class="meras-fab-dot"></span>
    </button>

    {{-- Chat Window --}}
    <div class="meras-chat-win" id="merasChatWin" role="dialog" aria-label="Mera's Support Assistant" aria-hidden="true">

        {{-- Header --}}
        <div class="meras-chat-head">
            <div class="meras-chat-head-left">
                <div class="meras-bot-avatar">🤖</div>
                <div class="meras-chat-head-info">
                    <div class="meras-chat-head-title">Mera's Assistant</div>
                    <div class="meras-chat-head-status">
                        <span class="meras-pulse-dot"></span> Online &bull; Instant Replies
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <a href="{{ $messengerUrl }}" target="_blank" rel="noopener noreferrer" class="meras-head-messenger-btn" title="Chat live on Messenger" aria-label="Chat live on Messenger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.19.16.15.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.53-.04 1.05.29 2.17.44 3.32.44 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm5.6 7.28-2.94 4.66c-.47.74-1.47.93-2.18.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.18-.4l2.34 1.75c.21.16.51.16.72 0l3.16-2.4c.42-.32.97.18.69.63z"/>
                    </svg>
                    <span>Messenger</span>
                </a>
                <button class="meras-close-btn" id="merasCloseBtn" aria-label="Close chat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="meras-chat-body" id="merasChatBody">
            <div class="meras-msg meras-msg-bot">
                <div class="meras-msg-ava">🤖</div>
                <div class="meras-msg-bbl">
                    👋 Hello! Welcome to <strong>Mera's General Merchandise</strong>!<br>
                    How can I help you today?

                    {{-- Live Messenger Card – always visible in the welcome bubble on desktop --}}
                    <div class="meras-handoff" style="margin-top: 10px; display: block !important;">
                        <div class="meras-handoff-text">💬 Want to talk to a real person? We can assist you live:</div>
                        <a class="meras-handoff-btn" href="{{ $messengerUrl }}" target="_blank" rel="noopener noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.19.16.15.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.53-.04 1.05.29 2.17.44 3.32.44 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm5.6 7.28-2.94 4.66c-.47.74-1.47.93-2.18.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.18-.4l2.34 1.75c.21.16.51.16.72 0l3.16-2.4c.42-.32.97.18.69.63z"/>
                            </svg>
                            Chat live on Messenger
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Suggestion Chips --}}
        <div class="meras-chips-bar" id="merasChipsBar">
            <div class="meras-chips-row" id="merasChipsRow">
                <button type="button" class="meras-chip" onclick="merasSendChip('Can I chat live on Messenger?')">Messenger 💬</button>
                <button type="button" class="meras-chip" onclick="merasSendChip('What are your store hours?')">Store Hours 🕒</button>
                <button type="button" class="meras-chip" onclick="merasSendChip('Where is your store located?')">Location 📍</button>
                <button type="button" class="meras-chip" onclick="merasSendChip('What products are in stock?')">Products 🛍️</button>
                <button type="button" class="meras-chip" onclick="merasSendChip('What payment methods do you accept?')">Payment 💳</button>
            </div>
        </div>

        {{-- Input --}}
        <form class="meras-chat-form" id="merasChatForm">
            <input type="text" id="merasInput" class="meras-chat-input"
                placeholder="Ask me anything..." autocomplete="off">
            <button type="submit" class="meras-chat-send" aria-label="Send">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>
</div>

<style>
/* ── Mera's Chatbot Widget Styles ─────────────────── */
.meras-chatbot-wrap {
    position: fixed !important;
    bottom: 28px !important;
    right: 24px !important;
    z-index: 2147483647 !important;
    display: block !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', sans-serif !important;
}

.meras-fab {
    display: flex !important;
    align-items: center !important;
    gap: 9px !important;
    padding: 11px 18px 11px 13px !important;
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%) !important;
    color: #fff !important;
    border: none !important;
    border-radius: 50px !important;
    box-shadow: 0 8px 28px rgba(79,70,229,0.45), 0 2px 8px rgba(0,0,0,0.12) !important;
    cursor: pointer !important;
    font-size: 0.9rem !important;
    font-weight: 700 !important;
    transition: transform 0.2s cubic-bezier(.34,1.56,.64,1), box-shadow 0.2s ease !important;
    outline: none !important;
    position: relative !important;
    user-select: none !important;
}

.meras-fab:hover {
    transform: translateY(-3px) scale(1.04) !important;
    box-shadow: 0 12px 32px rgba(79,70,229,0.55) !important;
}

.meras-fab:active {
    transform: scale(0.97) !important;
}

.meras-fab-icon-ring {
    width: 32px !important;
    height: 32px !important;
    background: rgba(255,255,255,0.18) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.meras-fab-txt {
    white-space: nowrap !important;
    letter-spacing: -0.01em !important;
}

.meras-fab-dot {
    position: absolute !important;
    top: -3px !important;
    right: -3px !important;
    width: 11px !important;
    height: 11px !important;
    background: #10b981 !important;
    border-radius: 50% !important;
    border: 2px solid #fff !important;
    box-shadow: 0 0 0 2px rgba(16,185,129,0.3) !important;
}

/* Chat Window */
.meras-chat-win {
    position: absolute !important;
    bottom: 68px !important;
    right: 0 !important;
    width: 370px !important;
    max-width: calc(100vw - 32px) !important;
    height: 530px !important;
    max-height: calc(100vh - 120px) !important;
    background: #fff !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 60px rgba(15,23,42,0.2), 0 0 0 1px rgba(15,23,42,0.06) !important;
    display: none !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

.meras-chat-win.meras-open {
    display: flex !important;
    animation: merasSlideIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

@keyframes merasSlideIn {
    from { opacity: 0; transform: translateY(16px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header */
.meras-chat-head {
    background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;
    padding: 14px 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-shrink: 0 !important;
}

.meras-chat-head-left {
    display: flex !important;
    align-items: center !important;
    gap: 11px !important;
}

.meras-bot-avatar {
    width: 38px !important;
    height: 38px !important;
    background: rgba(255,255,255,0.18) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.25rem !important;
    border: 1.5px solid rgba(255,255,255,0.3) !important;
    flex-shrink: 0 !important;
}

.meras-chat-head-title {
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    color: #fff !important;
    line-height: 1.2 !important;
}

.meras-chat-head-status {
    font-size: 0.75rem !important;
    color: rgba(255,255,255,0.8) !important;
    display: flex !important;
    align-items: center !important;
    gap: 5px !important;
    margin-top: 2px !important;
}

.meras-pulse-dot {
    width: 7px !important;
    height: 7px !important;
    background: #34d399 !important;
    border-radius: 50% !important;
    display: inline-block !important;
    box-shadow: 0 0 6px #34d399 !important;
    animation: merasPulse 2s infinite !important;
}

@keyframes merasPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.meras-head-messenger-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    font-size: 0.74rem !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    border: 1px solid rgba(255, 255, 255, 0.35) !important;
    transition: background 0.15s ease, transform 0.15s ease !important;
}

.meras-head-messenger-btn:hover {
    background: rgba(255, 255, 255, 0.35) !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
    text-decoration: none !important;
}

.meras-close-btn {
    background: rgba(255,255,255,0.15) !important;
    border: none !important;
    color: #fff !important;
    width: 30px !important;
    height: 30px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: background 0.15s !important;
    flex-shrink: 0 !important;
}

.meras-close-btn:hover {
    background: rgba(255,255,255,0.3) !important;
}

/* Messages Area */
.meras-chat-body {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 14px 14px 6px !important;
    background: #f8fafc !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    scroll-behavior: smooth !important;
}

.meras-msg {
    display: flex !important;
    gap: 8px !important;
    max-width: 88% !important;
    animation: merasMsgIn 0.2s ease !important;
}

@keyframes merasMsgIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.meras-msg-bot { align-self: flex-start !important; }
.meras-msg-user {
    align-self: flex-end !important;
    flex-direction: row-reverse !important;
}

.meras-msg-ava {
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    background: #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0.85rem !important;
    flex-shrink: 0 !important;
    margin-top: 2px !important;
}

.meras-msg-user .meras-msg-ava {
    background: #c7d2fe !important;
}

.meras-msg-bbl {
    padding: 10px 14px !important;
    border-radius: 16px 16px 16px 4px !important;
    background: #fff !important;
    color: #1e293b !important;
    font-size: 0.875rem !important;
    line-height: 1.55 !important;
    box-shadow: 0 1px 4px rgba(15,23,42,0.06) !important;
    border: 1px solid #e2e8f0 !important;
    word-break: break-word !important;
}

.meras-msg-user .meras-msg-bbl {
    background: #4f46e5 !important;
    color: #fff !important;
    border-radius: 16px 16px 4px 16px !important;
    border: none !important;
    box-shadow: 0 3px 10px rgba(79,70,229,0.25) !important;
}

/* Product Cards in Chat */
.meras-prod-grid {
    display: flex !important;
    flex-direction: column !important;
    gap: 7px !important;
    margin-top: 10px !important;
}

.meras-prod-card {
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    padding: 8px 12px !important;
    font-size: 0.82rem !important;
    display: block !important;
    text-decoration: none !important;
    transition: border-color 0.15s, background 0.15s, transform 0.12s !important;
    cursor: pointer !important;
}

a.meras-prod-card:hover {
    background: #e8f0fe !important;
    border-color: #4f46e5 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(79,70,229,0.12) !important;
    text-decoration: none !important;
}

.meras-prod-name {
    font-weight: 700 !important;
    color: #0f172a !important;
    font-size: 0.84rem !important;
}

a.meras-prod-card:hover .meras-prod-name {
    color: #4f46e5 !important;
}

.meras-prod-row {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    margin-top: 4px !important;
}

.meras-prod-price {
    font-weight: 800 !important;
    color: #4f46e5 !important;
    font-size: 0.86rem !important;
}

.meras-prod-stock {
    font-size: 0.74rem !important;
    font-weight: 600 !important;
}

.meras-prod-qty {
    font-size: 0.73rem !important;
    color: #64748b !important;
    margin-top: 3px !important;
}

.meras-prod-view {
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
    margin-top: 6px !important;
    font-size: 0.74rem !important;
    font-weight: 700 !important;
    color: #4f46e5 !important;
}

/* Typing Indicator */
.meras-typing-bbl {
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 12px 14px !important;
}

.meras-typing-bbl span {
    width: 7px !important;
    height: 7px !important;
    border-radius: 50% !important;
    background: #94a3b8 !important;
    display: inline-block !important;
    animation: merasBounce 1.3s infinite ease-in-out !important;
}

.meras-typing-bbl span:nth-child(2) { animation-delay: 0.18s !important; }
.meras-typing-bbl span:nth-child(3) { animation-delay: 0.36s !important; }

@keyframes merasBounce {
    0%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-6px); }
}

/* Chips */
.meras-chips-bar {
    padding: 8px 14px !important;
    background: #fff !important;
    border-top: 1px solid #f1f5f9 !important;
    flex-shrink: 0 !important;
}

.meras-chips-row {
    display: flex !important;
    gap: 6px !important;
    overflow-x: auto !important;
    padding-bottom: 2px !important;
    scrollbar-width: none !important;
}

.meras-chips-row::-webkit-scrollbar { display: none !important; }

.meras-chip {
    background: #eef2ff !important;
    color: #4f46e5 !important;
    border: 1px solid #c7d2fe !important;
    border-radius: 20px !important;
    padding: 5px 12px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    transition: background 0.15s, color 0.15s !important;
    flex-shrink: 0 !important;
}

.meras-chip:hover {
    background: #4f46e5 !important;
    color: #fff !important;
    border-color: #4f46e5 !important;
}

/* Live Messenger handoff card */
.meras-handoff {
    margin-top: 10px !important;
    padding: 10px !important;
    border: 1px solid #d8e3fe !important;
    border-radius: 10px !important;
    background: linear-gradient(135deg, #f5f8ff 0%, #eef4ff 100%) !important;
}

.meras-handoff-text {
    font-size: 0.8rem !important;
    line-height: 1.45 !important;
    color: #1e293b !important;
    margin-bottom: 9px !important;
}

.meras-handoff-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 7px !important;
    width: 100% !important;
    padding: 9px 12px !important;
    border-radius: 8px !important;
    background: linear-gradient(90deg, #0084ff 0%, #a033ff 100%) !important;
    color: #fff !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    box-shadow: 0 2px 6px rgba(0, 132, 255, 0.28) !important;
    transition: filter 0.15s, transform 0.15s !important;
}

.meras-handoff-btn:hover {
    filter: brightness(1.07) !important;
    transform: translateY(-1px) !important;
    color: #fff !important;
    text-decoration: none !important;
}

/* Store location card */
.meras-map {
    margin-top: 10px !important;
    padding: 10px !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    background: #f8fafc !important;
}

.meras-map-address {
    display: flex !important;
    align-items: flex-start !important;
    gap: 6px !important;
    font-size: 0.8rem !important;
    line-height: 1.45 !important;
    color: #1e293b !important;
    margin-bottom: 9px !important;
}

.meras-map-address svg {
    flex-shrink: 0 !important;
    margin-top: 1px !important;
}

.meras-map-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    padding: 9px 12px !important;
    border-radius: 8px !important;
    background: #1a73e8 !important;
    color: #fff !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    box-shadow: 0 2px 6px rgba(26, 115, 232, 0.28) !important;
    transition: filter 0.15s, transform 0.15s !important;
}

.meras-map-btn:hover {
    filter: brightness(1.07) !important;
    transform: translateY(-1px) !important;
    color: #fff !important;
    text-decoration: none !important;
}

/* Input */
.meras-chat-form {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 10px 14px !important;
    background: #fff !important;
    border-top: 1px solid #e2e8f0 !important;
    flex-shrink: 0 !important;
}

.meras-chat-input {
    flex: 1 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 22px !important;
    padding: 9px 15px !important;
    font-size: 0.875rem !important;
    outline: none !important;
    background: #f8fafc !important;
    transition: border-color 0.15s, box-shadow 0.15s !important;
    min-width: 0 !important;
    color: #1e293b !important;
}

.meras-chat-input:focus {
    border-color: #4f46e5 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1) !important;
}

.meras-chat-input::placeholder {
    color: #94a3b8 !important;
}

.meras-chat-send {
    width: 38px !important;
    height: 38px !important;
    border-radius: 50% !important;
    background: #4f46e5 !important;
    color: #fff !important;
    border: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: background 0.15s, transform 0.15s !important;
    flex-shrink: 0 !important;
}

.meras-chat-send:hover {
    background: #4338ca !important;
    transform: scale(1.07) !important;
}

@media (max-width: 767px) {
    .meras-chatbot-wrap {
        bottom: 82px !important;
        right: 14px !important;
    }
    .meras-chat-win {
        width: calc(100vw - 28px) !important;
        height: 480px !important;
        bottom: 64px !important;
        right: 0 !important;
    }
}
</style>

<script>
(function() {
    function merasInit() {
        var fab  = document.getElementById('merasFab');
        var win  = document.getElementById('merasChatWin');
        var cls  = document.getElementById('merasCloseBtn');
        var form = document.getElementById('merasChatForm');
        var inp  = document.getElementById('merasInput');

        if (!fab || !win) return;

        function open()  { win.classList.add('meras-open'); win.setAttribute('aria-hidden','false'); setTimeout(function(){ if(inp) inp.focus(); }, 120); }
        function close() { win.classList.remove('meras-open'); win.setAttribute('aria-hidden','true'); }
        function toggle(){ win.classList.contains('meras-open') ? close() : open(); }

        fab.addEventListener('click', toggle);
        if (cls) cls.addEventListener('click', close);
        if (form) form.addEventListener('submit', merasSubmit);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && win.classList.contains('meras-open')) close();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', merasInit);
    } else {
        merasInit();
    }
})();

function merasEsc(t) {
    return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function merasScroll() {
    var b = document.getElementById('merasChatBody');
    if (b) b.scrollTop = b.scrollHeight;
}

function merasSendChip(q) {
    var inp = document.getElementById('merasInput');
    if (inp) {
        inp.value = q;
        var form = document.getElementById('merasChatForm');
        if (form) form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
}

function merasAppendUser(txt) {
    var b = document.getElementById('merasChatBody');
    if (!b) return;
    var d = document.createElement('div');
    d.className = 'meras-msg meras-msg-user';
    d.innerHTML = '<div class="meras-msg-ava">👤</div><div class="meras-msg-bbl">' + merasEsc(txt) + '</div>';
    b.appendChild(d);
    merasScroll();
}

function merasShowTyping() {
    var b = document.getElementById('merasChatBody');
    if (!b) return;
    var d = document.createElement('div');
    d.className = 'meras-msg meras-msg-bot';
    d.id = 'merasTyping';
    d.innerHTML = '<div class="meras-msg-ava">🤖</div><div class="meras-msg-bbl meras-typing-bbl"><span></span><span></span><span></span></div>';
    b.appendChild(d);
    merasScroll();
}

function merasRemoveTyping() {
    var t = document.getElementById('merasTyping');
    if (t) t.remove();
}

function merasAppendBot(reply, products, suggestions, handoff, map) {
    merasRemoveTyping();
    var b = document.getElementById('merasChatBody');
    if (!b) return;

    var html = merasEsc(reply).replace(/\n/g, '<br>');

    if (products && products.length) {
        html += '<div class="meras-prod-grid">';
        products.forEach(function(p) {
            var unit = p.unit || 'pcs';
            var qtyText = (p.quantity > 0) ? (p.quantity + ' ' + unit + ' available') : 'Out of stock';
            if (p.url) {
                // Navigate in same tab so #product-{id} hash routing works correctly
                html += '<a class="meras-prod-card" href="' + merasEsc(p.url) + '">' +
                    '<div class="meras-prod-name">' + merasEsc(p.name) + '</div>' +
                    '<div class="meras-prod-row">' +
                    '<span class="meras-prod-price">' + merasEsc(p.price) + '</span>' +
                    '<span class="meras-prod-stock">' + merasEsc(p.status) + '</span>' +
                    '</div>' +
                    '<div class="meras-prod-qty">' + merasEsc(qtyText) + '</div>' +
                    '<div class="meras-prod-view">View Item &rarr;</div>' +
                    '</a>';
            } else {
                html += '<div class="meras-prod-card">' +
                    '<div class="meras-prod-name">' + merasEsc(p.name) + '</div>' +
                    '<div class="meras-prod-row">' +
                    '<span class="meras-prod-price">' + merasEsc(p.price) + '</span>' +
                    '<span class="meras-prod-stock">' + merasEsc(p.status) + '</span>' +
                    '</div>' +
                    '<div class="meras-prod-qty">' + merasEsc(qtyText) + '</div>' +
                    '</div>';
            }
        });
        html += '</div>';
    }

    // Store location: opens Google Maps (the app on phones) for directions
    if (map && map.url) {
        html += '<div class="meras-map">' +
            '<div class="meras-map-address">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#ea4335" aria-hidden="true">' +
            '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>' +
            '</svg><span>' + merasEsc(map.address) + '</span></div>' +
            '<a class="meras-map-btn" href="' + merasEsc(map.url) + '" target="_blank" rel="noopener noreferrer">' +
            merasEsc(map.label) + ' &rarr;</a></div>';
    }

    // Offered once, after the customer has sent a few messages, so they can reach
    // a real person on Messenger instead of looping through canned answers.
    if (handoff && handoff.url) {
        html += '<div class="meras-handoff">' +
            '<div class="meras-handoff-text">' + merasEsc(handoff.text).replace(/\n/g, '<br>') + '</div>' +
            '<a class="meras-handoff-btn" href="' + merasEsc(handoff.url) + '" target="_blank" rel="noopener noreferrer">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
            '<path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.19.16.15.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.53-.04 1.05.29 2.17.44 3.32.44 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm5.6 7.28-2.94 4.66c-.47.74-1.47.93-2.18.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.18-.4l2.34 1.75c.21.16.51.16.72 0l3.16-2.4c.42-.32.97.18.69.63z"/>' +
            '</svg>' + merasEsc(handoff.label) + '</a></div>';
    }

    var d = document.createElement('div');
    d.className = 'meras-msg meras-msg-bot';
    d.innerHTML = '<div class="meras-msg-ava">🤖</div><div class="meras-msg-bbl">' + html + '</div>';
    b.appendChild(d);

    if (suggestions && suggestions.length) {
        var cr = document.getElementById('merasChipsRow');
        if (cr) {
            cr.innerHTML = suggestions.map(function(s) {
                return '<button type="button" class="meras-chip" onclick="merasSendChip(\'' + merasEsc(s) + '\')">' + merasEsc(s) + '</button>';
            }).join('');
        }
    }

    merasScroll();
}

function merasSubmit(e) {
    e.preventDefault();
    var inp = document.getElementById('merasInput');
    if (!inp) return;
    var txt = inp.value.trim();
    if (!txt) return;

    merasAppendUser(txt);
    inp.value = '';
    merasShowTyping();

    fetch("{{ route('chat.bot-response') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ message: txt })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data && data.reply) {
            merasAppendBot(data.reply, data.products || [], data.suggestions || [], data.handoff || null, data.map || null);
        } else {
            merasAppendBot("Sorry, I couldn't get a response right now. Please try again!", [], [], null);
        }
    })
    .catch(function() {
        merasAppendBot("⚠️ Could not connect. Check your internet connection and try again.", [], [], null);
    });
}
</script>
@endif
