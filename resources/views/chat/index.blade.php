@php
    $isMobileApp = session('is_mobile_app', false) || (isset($_SERVER['HTTP_USER_AGENT']) && str_contains($_SERVER['HTTP_USER_AGENT'], 'MerasUserApp'));
@endphp

@extends('layouts.app')

@section('title', 'Customer Support Chat')

@section('content')
<style>
    @media (max-width: 767px) {
        /* Hide page header on mobile to maximize chat window height */
        .page-header {
            display: none !important;
        }
        
        .chat-workspace-card {
            /* Bulletproof fixed full-bleed layout for mobile WebViews */
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            height: 100% !important;
            width: 100% !important;
            border-radius: 0 !important; /* Full bleed layout on mobile */
            border: none !important;
            margin: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: #f8fafc !important;
            z-index: 9999 !important;
        }
        
        .chat-layout {
            gap: 0 !important;
        }

        .chat-box {
            padding: 12px 14px !important;
            gap: 12px !important;
            background-color: #f8fafc !important; /* Clean premium slate grey background */
            -webkit-overflow-scrolling: touch !important; /* Kinetic inertia scroll */
            flex: 1 !important;
            overflow-y: auto !important;
        }

        .chat-message {
            max-width: 90% !important;
            gap: 8px !important;
        }

        .chat-bubble {
            padding: 10px 14px !important;
            font-size: 0.88rem !important;
            line-height: 1.4 !important;
            border-radius: 16px !important;
        }

        .chat-bubble-own {
            border-bottom-right-radius: 4px !important;
        }

        .chat-bubble-other {
            border-bottom-left-radius: 4px !important;
        }

        .chat-quick-replies {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important; /* Single horizontal line strip */
            overflow-x: auto !important; /* Enable swiping horizontally */
            -webkit-overflow-scrolling: touch !important; /* Fluid swipe scrolling */
            padding: 10px 12px !important;
            background-color: #ffffff !important;
            border-top: 1px solid #f1f5f9 !important;
            gap: 8px !important;
            scrollbar-width: none !important; /* Hide scrollbar on Firefox */
        }

        .chat-quick-replies::-webkit-scrollbar {
            display: none !important; /* Hide scrollbar on Safari/Chrome */
        }

        .chat-quick-btn {
            flex-shrink: 0 !important; /* Prevent squishing of text */
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
            border-radius: 999px !important;
            padding: 6px 14px !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            box-shadow: none !important;
            transition: all 0.15s ease-in-out !important;
        }

        .chat-quick-btn:active {
            background-color: #cbd5e1 !important;
            transform: scale(0.95) !important;
        }

        .chat-input-wrapper {
            border-radius: 24px !important; /* Elegant rounded capsule input */
            padding: 6px 14px !important;
            border-color: #cbd5e1 !important;
            background-color: #ffffff !important;
            transition: all 0.2s ease !important;
        }

        .chat-input-wrapper:focus-within {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }

        .chat-send-btn {
            border-radius: 50% !important;
            width: 32px !important;
            height: 32px !important;
            background-color: #4f46e5 !important;
            transition: all 0.15s ease !important;
        }

        .chat-send-btn:active {
            transform: scale(0.88) !important;
            background-color: #3730a3 !important;
        }
    }

    /* Hide the redundant web header inside the native mobile APK WebView container */
    @if($isMobileApp)
        .chat-panel-header {
            display: none !important;
        }
        html, body {
            overflow: hidden !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    @endif
</style>

@if(!auth()->check() || auth()->user()->role === 'user')
    <style>
        .support-queue {
            display: none !important;
        }
        .support-chat-layout {
            grid-template-columns: 1fr !important;
        }
    </style>
@endif

<div class="page-header">
    <div class="page-title">
        <h2>Customer Support Chat</h2>
        <p>Coordinate with the team and save replies for customer questions from one workspace.</p>
    </div>
    <div class="page-actions chat-page-actions">
        <div class="chat-live-indicator">
            <span class="chat-live-dot"></span> Live
        </div>
        <span class="support-count-pill">{{ $pendingInquiries }} pending</span>
    </div>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
@endif

<div class="chat-layout support-chat-layout">
    <aside class="support-queue">
        <div class="panel-card support-panel-card">
            <div class="panel-header support-panel-header">
                <div>
                    <h3>Customer Questions</h3>
                    <p>Latest website inquiries</p>
                </div>
                <a href="{{ route('inquiries.index') }}" class="btn btn-secondary btn-sm">View All</a>
            </div>

            <div class="support-stats-row">
                <div>
                    <span>Pending</span>
                    <strong>{{ $pendingInquiries }}</strong>
                </div>
                <div>
                    <span>Responded today</span>
                    <strong>{{ $respondedToday }}</strong>
                </div>
            </div>

            <div class="support-ticket-list">
                @forelse($supportInquiries as $inquiry)
                    <article class="support-ticket {{ $inquiry->status === 'pending' ? 'is-pending' : 'is-responded' }}">
                        <div class="support-ticket-topline">
                            <span class="badge {{ $inquiry->status === 'pending' ? 'badge-warning' : 'badge-success' }}">{{ $inquiry->status === 'pending' ? 'Pending' : 'Responded' }}</span>
                            <time>{{ $inquiry->created_at->format('M d, h:i A') }}</time>
                        </div>
                        <h4>{{ $inquiry->subject }}</h4>
                        <p class="support-ticket-message">{{ $inquiry->message }}</p>
                        <div class="support-customer-meta">
                            <span>{{ $inquiry->customer_name }}</span>
                            <a href="mailto:{{ $inquiry->customer_email }}">{{ $inquiry->customer_email }}</a>
                        </div>

                        @if($inquiry->response)
                            <div class="support-saved-response">
                                <strong>Saved response</strong>
                                <p>{{ $inquiry->response }}</p>
                                <small>
                                    {{ optional($inquiry->respondent)->name ?? 'Staff' }}
                                    @if($inquiry->responded_at)
                                        | {{ $inquiry->responded_at->format('M d, h:i A') }}
                                    @endif
                                </small>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('inquiries.respond', $inquiry) }}" class="support-response-form">
                            @csrf
                            <textarea id="response-{{ $inquiry->id }}" name="response" rows="3" placeholder="Write the customer response..." required>{{ old('response', $inquiry->response) }}</textarea>
                            <div class="support-response-actions">
                                <button type="button" class="btn btn-outline btn-sm" data-target="response-{{ $inquiry->id }}" data-customer="{{ $inquiry->customer_name }}" onclick="insertResponseTemplate(this)">Use Template</button>
                                <button type="submit" class="btn btn-primary btn-sm">Save Response</button>
                            </div>
                        </form>

                        <div class="support-ticket-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-customer="{{ $inquiry->customer_name }}" data-subject="{{ $inquiry->subject }}" onclick="draftCustomerReply(this)">Discuss in Chat</button>
                            <form method="POST" action="{{ route('inquiries.toggle', $inquiry) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-sm">Mark {{ $inquiry->status === 'pending' ? 'Responded' : 'Pending' }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="chat-empty support-empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <p>No customer inquiries yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </aside>

    <section class="chat-main-panel">
        <div class="panel-card chat-workspace-card">
            <div class="panel-header chat-panel-header">
                <div class="chat-heading-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <div>
                        @if(!auth()->check() || auth()->user()->role === 'user')
                            <h3>💬 Mera's Support Assistant</h3>
                            <p>Ask us anything! • Online</p>
                        @else
                            <h3>Team Conversation</h3>
                            <p>{{ $messagesToday }} today | {{ $totalMessages }} total</p>
                        @endif
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="{{ \App\Http\Controllers\ChatController::MESSENGER_URL }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background: linear-gradient(90deg, #0084ff 0%, #a033ff 100%); color: #fff; border: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; text-decoration: none;" title="Chat live on Messenger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.19.16.15.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.53-.04 1.05.29 2.17.44 3.32.44 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm5.6 7.28-2.94 4.66c-.47.74-1.47.93-2.18.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.18-.4l2.34 1.75c.21.16.51.16.72 0l3.16-2.4c.42-.32.97.18.69.63z"/>
                        </svg>
                        <span>Chat live on Messenger</span>
                    </a>
                    <button type="button" class="chat-clear-btn" onclick="clearInput()" title="Clear input">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                        Clear
                    </button>
                </div>
            </div>

            <div class="chat-box" id="chatbox">
                @forelse($messages as $message)
                    @php $isOwn = auth()->check() && $message->user_name === auth()->user()->name; @endphp
                    <div class="chat-message {{ $isOwn ? 'chat-own' : 'chat-other' }}">
                        <div class="chat-msg-avatar {{ $isOwn ? 'chat-avatar-own' : 'chat-avatar-other' }}">
                            {{ strtoupper(substr($message->user_name, 0, 1)) }}
                        </div>
                        <div class="chat-msg-content">
                            <div class="chat-msg-header">
                                <strong class="chat-msg-author {{ $isOwn ? 'chat-author-own' : '' }}">
                                    {{ $isOwn ? 'You' : $message->user_name }}
                                </strong>
                                <span class="chat-msg-time">{{ optional($message->created_at)->diffForHumans() ?? '' }}</span>
                            </div>
                            <div class="chat-bubble {{ $isOwn ? 'chat-bubble-own' : 'chat-bubble-other' }}">
                                {{-- Escaped first, then **text** is bolded and bare URLs are linked so the bot's Messenger handoff is tappable. --}}
                                {!! preg_replace('~https?://[^\s<]+~', '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', preg_replace('~\*\*(.+?)\*\*~', '<strong>$1</strong>', e($message->message))) !!}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="chat-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>No messages yet. Start the conversation.</p>
                    </div>
                @endforelse
            </div>

            <div class="chat-quick-replies" id="quickReplies">
                @if(!auth()->check() || auth()->user()->role === 'user')
                    <span class="chat-quick-label">Frequently Asked:</span>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Can I chat live on Messenger?')">Messenger 💬</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('What are your store hours?')">Store Hours 🕒</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Where is your store located?')">Location 📍</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('What products do you have in stock?')">Products 🛍️</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('What payment methods do you accept?')">Payment 💳</button>
                @else
                    <span class="chat-quick-label">Quick replies:</span>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Hello! How can I help you today?')">Hello</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('The product you are looking for is available in store.')">Available</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Sorry, that item is currently out of stock.')">Out of Stock</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Our store hours are Monday to Saturday, 8 AM to 6 PM.')">Hours</button>
                    <button type="button" class="chat-quick-btn" onclick="setMsg('Please visit us at Stall No. 18, Bantayan Public Market.')">Address</button>
                @endif
            </div>

            <form id="msgForm" method="POST" action="{{ route('chat.store') }}" class="chat-input-form">
                @csrf
                <input id="user_name" type="hidden" name="user_name" value="{{ auth()->check() ? auth()->user()->name : 'Guest' }}">
                <div class="chat-input-wrapper">
                    @if(!auth()->check() || auth()->user()->role === 'user')
                        <textarea id="msgInput" name="message" placeholder="Ask about products, store hours, location..." rows="1" autocomplete="off" required></textarea>
                    @else
                        <textarea id="msgInput" name="message" placeholder="Type a team note or customer reply draft..." rows="1" autocomplete="off" required></textarea>
                    @endif
                    <button type="submit" class="chat-send-btn" id="sendBtn" title="Send message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Escapes first, then shows **text** in bold and turns bare URLs into links so
// handoff links (e.g. the Messenger one the bot offers) are tappable instead of plain text.
function linkifyMessage(value) {
    return escapeHtml(value)
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/https?:\/\/[^\s<]+/g, function (url) {
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });
}

function scrollToBottom() {
    const box = document.getElementById('chatbox');
    if (box) box.scrollTop = box.scrollHeight;
}
scrollToBottom();

function setMsg(text) {
    const input = document.getElementById('msgInput');
    input.value = text;
    input.focus();
    autoResize(input);
}

function clearInput() {
    const input = document.getElementById('msgInput');
    input.value = '';
    input.style.height = '';
    input.focus();
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
}

function draftCustomerReply(button) {
    const customer = button.dataset.customer || 'customer';
    const subject = button.dataset.subject || 'their question';
    setMsg('Customer follow-up for ' + customer + ' about "' + subject + '": ');
}

function insertResponseTemplate(button) {
    const target = document.getElementById(button.dataset.target);
    const customer = button.dataset.customer || 'there';
    if (!target) return;
    target.value = 'Hi ' + customer + ', thank you for reaching out. We checked your inquiry and will be happy to assist you. Please visit us at Stall No. 18, Bantayan Public Market or reply with any additional details.';
    target.focus();
}

const form = document.getElementById('msgForm');
const textarea = document.getElementById('msgInput');

// Asynchronous AJAX Message Submission (Responsive and Instant, No Page Reload)
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const input = document.getElementById('msgInput');
        const message = input.value.trim();
        if (!message) return;
        
        const formData = new FormData(form);
        
        // Optimistically clear and resize input instantly for snappy experience
        input.value = '';
        input.style.height = '';
        input.focus();
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(() => {
            // Instantly refresh message list with zero lag
            refreshMessages(true);
        })
        .catch(err => {
            console.error('Error sending message:', err);
        });
    });
}

if (textarea) {
    textarea.addEventListener('input', () => autoResize(textarea));
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) {
                form.dispatchEvent(new Event('submit'));
            }
        }
    });

    // Android/Mobile responsiveness: Auto-scroll when keyboard focuses to keep input visible
    textarea.addEventListener('focus', () => {
        setTimeout(scrollToBottom, 250);
    });
}

function refreshMessages(forceScrollToBottom = false) {
    fetch('{{ route("chat.messages") }}')
        .then(response => response.json())
        .then(messages => {
            const box = document.getElementById('chatbox');
            if (!box) return;
            
            const authUser = @json(auth()->check() ? auth()->user()->name : 'Guest');
            const wasAtBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 12;

            if (!messages.length) {
                box.innerHTML = '<div class="chat-empty"><p>No messages yet. Start the conversation.</p></div>';
                return;
            }

            box.innerHTML = messages.map(msg => {
                const isOwn = msg.user_name === authUser;
                const initial = escapeHtml((msg.user_name || '?').charAt(0).toUpperCase());
                const displayName = isOwn ? 'You' : escapeHtml(msg.user_name);
                const time = escapeHtml(msg.created_at || '');
                return `
                    <div class="chat-message ${isOwn ? 'chat-own' : 'chat-other'}">
                        <div class="chat-msg-avatar ${isOwn ? 'chat-avatar-own' : 'chat-avatar-other'}">${initial}</div>
                        <div class="chat-msg-content">
                            <div class="chat-msg-header">
                                <strong class="chat-msg-author ${isOwn ? 'chat-author-own' : ''}">${displayName}</strong>
                                <span class="chat-msg-time">${time}</span>
                            </div>
                            <div class="chat-bubble ${isOwn ? 'chat-bubble-own' : 'chat-bubble-other'}">${linkifyMessage(msg.message)}</div>
                        </div>
                    </div>`;
            }).join('');

            if (wasAtBottom || forceScrollToBottom) {
                scrollToBottom();
            }
        })
        .catch(() => {});
}

// Perform initial load
refreshMessages(true);

// Poll for updates every 4 seconds for a snappier, real-time feel
setInterval(refreshMessages, 4000);
</script>
@endpush