@extends('layouts.guest')

@section('title', "Welcome to " . config('app.name'))

@push('styles')
    <!-- DataTables CSS: only the optional table view needs it, so it must not block first paint -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"
        media="print" onload="this.media='all'">
    <style>
        /* Hundreds of cards are rendered; let the browser skip layout/paint for the off-screen ones. */
        .product-grid .product-card {
            content-visibility: auto;
            contain-intrinsic-size: auto 230px;
        }

        /* ── Catalog View Switcher ── */
        .catalog-view-switcher {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid var(--lp-border, #e2e8f0);
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
            margin-left: auto;
        }
        .btn-catalog-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            background: transparent;
            color: var(--lp-text-muted, #64748b);
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-catalog-toggle:hover {
            color: var(--lp-secondary, #0f172a);
            background: rgba(255, 255, 255, 0.7);
        }
        .btn-catalog-toggle.active {
            background: var(--lp-primary, #4f46e5);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.28);
        }
        .btn-catalog-toggle svg {
            flex-shrink: 0;
        }

        /* ── Customer DataTable Card ── */
        .landing-datatable-card {
            background: #ffffff;
            border: 1px solid var(--lp-border, #e2e8f0);
            border-radius: var(--radius-xl, 20px);
            padding: 24px;
            box-shadow: var(--lp-shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.05));
            margin-bottom: 40px;
        }

        /* DataTable Table Element */
        table.customer-datatable {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            font-family: inherit;
            color: var(--lp-text, #1e293b);
        }
        table.customer-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0 !important;
            vertical-align: middle;
        }
        table.customer-datatable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.92rem;
        }
        table.customer-datatable tbody tr {
            transition: background-color 0.15s ease;
        }
        table.customer-datatable tbody tr:hover {
            background-color: #f8fafc !important;
        }

        /* Cell typography & elements */
        .dt-prod-name {
            font-weight: 600;
            color: var(--lp-secondary, #0f172a);
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .dt-prod-sku {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--lp-text-muted, #64748b);
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 2px 6px;
        }
        .dt-category-pill {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--lp-primary, #4f46e5);
            background: var(--lp-primary-light, #eef2ff);
            border: 1px solid #e0e7ff;
            border-radius: 20px;
            padding: 3px 10px;
        }
        .dt-prod-price {
            font-weight: 700;
            color: var(--lp-secondary, #0f172a);
            font-size: 1.05rem;
        }
        .btn-inquire-table {
            padding: 7px 16px !important;
            font-size: 0.85rem !important;
            border-radius: 8px !important;
            background: var(--lp-primary, #4f46e5) !important;
            color: #fff !important;
            border: none !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-inquire-table:hover {
            background: var(--lp-primary-hover, #4338ca) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
        }

        /* Stock status pills */
        .dt-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .dt-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }
        .dt-stock-in { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .dt-dot-in { background: #10b981; }
        .dt-stock-low { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .dt-dot-low { background: #f59e0b; }
        .dt-stock-out { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .dt-dot-out { background: #ef4444; }

        /* DataTables controls override */
        .dataTables_wrapper {
            padding: 4px 0;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 16px;
            color: #475569;
            font-size: 0.88rem;
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 12px;
            background: #ffffff;
            outline: none;
            color: #1e293b;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .dataTables_wrapper .dataTables_length select:focus,
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--lp-primary, #4f46e5);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 18px;
            color: #64748b;
            font-size: 0.85rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            padding: 5px 12px !important;
            margin: 0 2px !important;
            font-size: 0.85rem !important;
            background: #ffffff !important;
            color: #475569 !important;
            transition: all 0.15s ease !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--lp-primary, #4f46e5) !important;
            color: #ffffff !important;
            border-color: var(--lp-primary, #4f46e5) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            opacity: 0.4;
            cursor: not-allowed;
            background: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    🎁 Welcome to {{ config('app.name') }}
                </div>
                <h1 class="hero-title">Quality <span>Merchandise</span> for Every Occasion</h1>
                <p class="hero-desc">School supplies, fabrics, and general merchandise carefully curated for families and
                    businesses throughout Bantayan and beyond.</p>
                <div class="hero-actions">
                    <a href="#products" class="btn-primary btn-lg">Browse Our Products</a>
                    @empty($hideAppDownload)
                        <a href="{{ route('mobile.download') }}" class="btn-secondary btn-lg btn-app-download-hero" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Download App
                        </a>
                    @endempty
                    <a href="#inquire" class="btn-secondary btn-lg">Send Inquiry</a>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num">{{ $products->count() }}+</div>
                        <div class="stat-label">Quality Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">15+</div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">1,000+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
            </div>

            <div class="hero-image-column">
                <div class="hero-deco-blob"></div>
                <div class="hero-image-wrapper hero-image-clickable" id="heroImageBtn" onclick="openGallery(0)"
                    title="Click to watch the shop video and view photos" role="button" tabindex="0"
                    aria-label="Watch the shop video and view the photo gallery">
                    <img src="{{ asset('images/hero_merchandise.jpg') }}"
                        srcset="{{ asset('images/hero_merchandise-1000.jpg') }} 1000w, {{ asset('images/hero_merchandise.jpg') }} 1536w"
                        sizes="(max-width: 548px) calc(100vw - 48px), 500px"
                        fetchpriority="high" decoding="async"
                        alt="Mera's Merchandise Storefront Showcase">
                    <div class="gallery-hint-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        Watch Our Shop Video
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section-container" id="products">
        <div class="section-header">
            <h2 class="section-title">Our Products</h2>
            <p class="section-desc">Browse our complete catalog of school supplies, fabrics, and general merchandise. Filter
                by category or search for specific items.</p>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <input type="text" id="landingSearch" placeholder="Search products by name or SKU..." class="form-control-lp"
                style="flex: 2; min-width: 240px;">
            <select id="categoryFilter" class="form-control-lp" style="flex: 1; min-width: 180px;">
                <option value="">All Categories</option>
                @php
                    $categories = $products->pluck('category')->unique()->filter();
                @endphp
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            <div class="catalog-view-switcher" role="group" aria-label="Product display mode">
                <button type="button" class="btn-catalog-toggle active" id="btnViewGrid" title="Switch to Cards Grid View">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Grid View</span>
                </button>
                <button type="button" class="btn-catalog-toggle" id="btnViewTable" title="Switch to Data Table View">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    <span>DataTable</span>
                </button>
            </div>
        </div>

        <div class="product-grid" id="landingProductGrid">
            @forelse($products as $product)
                <div class="product-card" id="product-{{ $product->id }}" data-name="{{ strtolower($product->name) }}"
                    data-sku="{{ strtolower($product->sku ?? '') }}" data-cat="{{ $product->category ?? '' }}">
                    <div class="prod-header">
                        @if($product->category)
                            <span class="prod-category">{{ $product->category }}</span>
                        @endif
                        <div class="prod-name">{{ $product->name }}</div>
                        @if($product->sku)
                            <div class="prod-sku">SKU: {{ $product->sku }}</div>
                        @endif
                    </div>
                    <div class="stock-indicator">
                        @if($product->quantity > 10)
                            <span class="indicator-dot dot-in-stock"></span>
                            <span style="color: var(--lp-success);">In Stock</span>
                        @elseif($product->quantity > 0)
                            <span class="indicator-dot dot-low-stock"></span>
                            <span style="color: var(--lp-warning);">Low Stock ({{ $product->quantity }} left)</span>
                        @else
                            <span class="indicator-dot dot-out-of-stock"></span>
                            <span style="color: var(--lp-danger);">Out of Stock</span>
                        @endif
                    </div>
                    <div class="prod-footer">
                        <div>
                            <div class="prod-price">₱{{ number_format($product->price, 2) }}</div>
                            @if($product->hasBulkPricing())
                                <div class="landing-bulk-tag">
                                    Bulk: ₱{{ number_format($product->bulk_price, 2) }} ({{ $product->bulk_min_qty }}+ pcs)
                                </div>
                            @endif
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                            <button type="button" class="btn-inquire"
                                onclick="inquireProduct({{ $product->id }}, '{{ addslashes($product->name) }}', 'general')">Inquire</button>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 80px 24px; color: var(--lp-text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        style="color: var(--lp-text-subtle); margin-bottom: 16px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <p style="font-size: 1.1rem; font-weight: 600;">No products available.</p>
                    <p style="font-size: 0.95rem; margin-top: 4px;">Please check back soon or send an inquiry to check upcoming
                        inventory.</p>
                </div>
            @endforelse
        </div>

        <!-- DataTable View (Interactive Customer Products Table) -->
        <div class="landing-datatable-card" id="landingProductDataTableWrapper" style="display: none;">
            <div class="table-responsive">
                <table id="customerProductsDataTable" class="customer-datatable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th style="text-align:center;">Stock Status</th>
                            <th style="text-align:right;">Price</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr id="table-product-{{ $product->id }}">
                                <td>
                                    <div class="dt-prod-name">{{ $product->name }}</div>
                                    @if($product->sku)
                                        <span class="dt-prod-sku">SKU: {{ $product->sku }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="dt-category-pill">{{ $product->category ?: 'General' }}</span>
                                </td>
                                <td style="text-align:center;" data-order="{{ $product->quantity }}">
                                    @if($product->quantity > 10)
                                        <span class="dt-stock-badge dt-stock-in">
                                            <span class="dt-dot dt-dot-in"></span>In Stock ({{ $product->quantity }} {{ $product->unit ?: 'pcs' }})
                                        </span>
                                    @elseif($product->quantity > 0)
                                        <span class="dt-stock-badge dt-stock-low">
                                            <span class="dt-dot dt-dot-low"></span>Low Stock ({{ $product->quantity }} {{ $product->unit ?: 'pcs' }} left)
                                        </span>
                                    @else
                                        <span class="dt-stock-badge dt-stock-out">
                                            <span class="dt-dot dt-dot-out"></span>Out of Stock
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align:right;" data-order="{{ $product->price }}">
                                    <div class="dt-prod-price">₱{{ number_format($product->price, 2) }}</div>
                                    @if($product->hasBulkPricing())
                                        <div class="landing-bulk-tag" style="display:inline-block;margin-top:2px;">
                                            Bulk: ₱{{ number_format($product->bulk_price, 2) }} ({{ $product->bulk_min_qty }}+ pcs)
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn-inquire btn-inquire-table"
                                        onclick="inquireProduct({{ $product->id }}, '{{ addslashes($product->name) }}', 'general')">
                                        Inquire
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Inquiry Section -->
    <section class="section-container" id="inquire" style="border-top: 1px solid var(--lp-border); padding-top: 100px;">
        <div class="section-header">
            <h2 class="section-title">Send Store Inquiry</h2>
            <p class="section-desc">Submit queries about bulk sales, availability, or pickup schedules directly to Mera's
                Store management team.</p>
        </div>

        @if(session('notice'))
            <div class="alert alert-{{ session('noticeType', 'success') }}">
                @if(session('noticeType') === 'success')
                    <h4 style="margin-top:0;margin-bottom:6px;font-weight:700;">📨 Inquiry Received!</h4>
                    <p style="margin:0;font-size:0.95rem;line-height:1.5;">Thank you for writing. Our store staff will examine your
                        request and follow up at the email address provided.</p>
                @else
                    <p style="margin:0;">{{ session('notice') }}</p>
                @endif
            </div>
        @endif

        <div class="inquiry-split">
            <div class="inquiry-info-column">
                <div class="info-block">
                    <h3 class="info-title">🏬 Visit or Reach Us</h3>
                    <p class="info-text">We are located at Stall No. 18 in the Bantayan Public Market. For custom order
                        pricing lists, drop us an inquiry message directly on this panel and our support team will respond
                        shortly.</p>
                </div>

                <div class="contact-list">
                    <!-- Address -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Store Address</div>
                            <div class="contact-value">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</div>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                </path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Email Support</div>
                            <div class="contact-value">support@merasmerchandise.com</div>
                        </div>
                    </div>
                    <!-- Hours -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Business Hours</div>
                            <div class="contact-value">Sunday - Friday, 8:00 AM – 6:00 PM</div>
                        </div>
                    </div>

                    <!-- Live Messenger -->
                    <div class="contact-item" style="background: rgba(79, 70, 229, 0.04); border-radius: 12px; padding: 12px; border: 1px solid rgba(79, 70, 229, 0.12);">
                        <div class="contact-icon-wrapper" style="background: linear-gradient(135deg, #0084ff 0%, #a033ff 100%); color: #ffffff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.19.16.15.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.53-.04 1.05.29 2.17.44 3.32.44 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm5.6 7.28-2.94 4.66c-.47.74-1.47.93-2.18.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.18-.4l2.34 1.75c.21.16.51.16.72 0l3.16-2.4c.42-.32.97.18.69.63z"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="contact-label">Live Support</div>
                            <div class="contact-value" style="margin-bottom: 6px;">Available directly on Messenger</div>
                            <a href="{{ \App\Http\Controllers\ChatController::MESSENGER_URL }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background: linear-gradient(90deg, #0084ff 0%, #a033ff 100%); color: #fff; font-size: 0.8rem; font-weight: 700; padding: 6px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                Chat live on Messenger &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                @empty($hideStaffLinks)
                    <div
                        style="font-size: 0.85rem; line-height: 1.5; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                        * Registered staff, cashiers, and store operators can access the checkout POS and inventory dashboard by
                        clicking <strong>Staff Login</strong> at the top right of this page.
                    </div>
                @endempty
            </div>

            <div class="inquiry-form-card">
                <form method="POST" action="{{ route('inquiries.store') }}">
                    @csrf
                    @if(!empty($inquirySource))
                        <input type="hidden" name="source" value="{{ $inquirySource }}">
                    @endif

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="customerName">Your Full Name</label>
                        <input type="text" name="customer_name" id="customerName" class="form-control-lp" required
                            placeholder="Juan Dela Cruz" value="{{ Auth::check() ? Auth::user()->name : old('customer_name') }}"
                            {{ Auth::check() ? 'readonly style=background-color:#f1f5f9;cursor:not-allowed;' : '' }}>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="customerEmail">Email Address</label>
                        <input type="email" name="customer_email" id="customerEmail" class="form-control-lp" required
                            placeholder="juan@gmail.com" value="{{ Auth::check() ? Auth::user()->email : old('customer_email') }}"
                            {{ Auth::check() ? 'readonly style=background-color:#f1f5f9;cursor:not-allowed;' : '' }}>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="subject">Subject</label>
                        <input type="text" name="subject" id="subject"
                            class="form-control-lp @error('subject') error @enderror" required
                            placeholder="e.g. Bulk school supplies quote" value="{{ old('subject') }}">
                        @error('subject')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom: 32px;">
                        <label for="message">Your Inquiry Message</label>
                        <textarea name="message" id="message" rows="5"
                            class="form-control-lp @error('message') error @enderror" required
                            placeholder="Describe what products or fabric swatches you'd like details on...">{{ old('message') }}</textarea>
                        @error('message')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                    </div>

                    <button type="submit" class="btn-submit-lp" style="width:100%;">
                        📧 Send Inquiry Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Shop Gallery Lightbox Modal -->
    <div class="gallery-modal-overlay" id="galleryModal" aria-hidden="true" onclick="handleGalleryOverlayClick(event)">
        <div class="gallery-modal" role="dialog" aria-modal="true" aria-label="Shop Photo Gallery">
            <button class="gallery-close-btn" onclick="closeGallery()" aria-label="Close gallery">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="gallery-modal-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Mera's Store &mdash; Shop Gallery
                </h3>
                <p>A glimpse inside our store at Bantayan Public Market, Stall No. 18</p>
            </div>
            <div class="gallery-main-display">
                <button class="gallery-nav-btn gallery-prev" onclick="shiftGallery(-1)" aria-label="Previous photo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <div class="gallery-main-img-wrapper">
                    <img id="galleryMainImg" src="" alt="Shop photo" class="gallery-main-img" hidden>
                    {{-- Gallery media only downloads once the gallery is opened (see hydrateGalleryMedia) --}}
                    <video id="galleryMainVideo" class="gallery-main-video"
                        src="{{ asset('images/shop_video.mp4') }}"
                        data-poster="{{ asset('images/shop_video_poster.jpg') }}"
                        controls playsinline muted preload="none" hidden>
                        <source src="{{ asset('images/shop_video.mp4') }}" type="video/mp4">
                    </video>
                    <div class="gallery-video-status" id="galleryVideoStatus" hidden></div>
                    <div class="gallery-img-caption" id="galleryCaption"></div>
                </div>
                <button class="gallery-nav-btn gallery-next" onclick="shiftGallery(1)" aria-label="Next photo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <div class="gallery-thumbs">
                <div class="gallery-thumb gallery-thumb-video active" onclick="setGallerySlide(0)">
                    <img data-src="{{ asset('images/shop_video_poster.jpg') }}" alt="Shop video tour">
                    <span class="gallery-thumb-badge">Video</span>
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(1)">
                    <img data-src="{{ request()->getBaseUrl() }}/images/shop_gallery_1.jpg" alt="Store interior">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(2)">
                    <img data-src="{{ request()->getBaseUrl() }}/images/shop_gallery_2.jpg" alt="School supplies display">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(3)">
                    <img data-src="{{ request()->getBaseUrl() }}/images/shop_gallery_3.jpg" alt="Fabric and textiles">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(4)">
                    <img data-src="{{ request()->getBaseUrl() }}/images/shop_gallery_4.jpg" alt="Store front">
                </div>
            </div>
            <div class="gallery-dots">
                <span class="gallery-dot active" onclick="setGallerySlide(0)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(1)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(2)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(3)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(4)"></span>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let customerDataTable = null;
            let dataTablesAssets = null;

            // jQuery + DataTables only serve the optional table view, so they are fetched the
            // first time that view is opened instead of on every catalog load.
            function loadDataTablesAssets() {
                if (!dataTablesAssets) {
                    const loadScript = src => new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = src;
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                    dataTablesAssets = loadScript('https://code.jquery.com/jquery-3.7.1.min.js')
                        .then(() => loadScript('https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js'))
                        .catch(error => {
                            dataTablesAssets = null; // allow a retry on the next toggle
                            throw error;
                        });
                }
                return dataTablesAssets;
            }

            function initCustomerDataTable() {
                if (customerDataTable) return Promise.resolve(customerDataTable);

                return loadDataTablesAssets().then(() => {
                    if (customerDataTable) return customerDataTable;
                    customerDataTable = $('#customerProductsDataTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        order: [[0, 'asc']],
                        language: {
                            search: "Filter in Table:",
                            searchPlaceholder: "Search records...",
                            lengthMenu: "Show _MENU_ products",
                            info: "Showing _START_ to _END_ of _TOTAL_ products",
                            infoEmpty: "Showing 0 to 0 of 0 products",
                            emptyTable: "No products available in this catalog",
                            zeroRecords: "No matching products found",
                            paginate: {
                                first: "&laquo;",
                                previous: "&lsaquo;",
                                next: "&rsaquo;",
                                last: "&raquo;"
                            }
                        },
                        columnDefs: [
                            { orderable: false, targets: [4] }
                        ]
                    });
                    // Carry over any search/category picked before the table existed.
                    filterProducts();
                    return customerDataTable;
                });
            }

            function setCatalogView(mode) {
                const grid = document.getElementById('landingProductGrid');
                const tableWrapper = document.getElementById('landingProductDataTableWrapper');
                const btnGrid = document.getElementById('btnViewGrid');
                const btnTable = document.getElementById('btnViewTable');

                if (!grid || !tableWrapper) return;

                if (mode === 'table') {
                    grid.style.display = 'none';
                    tableWrapper.style.display = 'block';
                    if (btnGrid) btnGrid.classList.remove('active');
                    if (btnTable) btnTable.classList.add('active');
                    // If the CDN is unreachable the plain server-rendered table still shows.
                    initCustomerDataTable()
                        .then(table => table.columns.adjust().draw())
                        .catch(() => {});
                    try { localStorage.setItem('meras_catalog_view', 'table'); } catch(e){}
                } else {
                    grid.style.display = 'grid';
                    tableWrapper.style.display = 'none';
                    if (btnTable) btnTable.classList.remove('active');
                    if (btnGrid) btnGrid.classList.add('active');
                    try { localStorage.setItem('meras_catalog_view', 'grid'); } catch(e){}
                }
            }

            // Filter products by category and search
            function filterProducts() {
                const query = document.getElementById('landingSearch').value.toLowerCase().trim();
                const cat = document.getElementById('categoryFilter').value;

                // 1. Filter Grid Cards
                document.querySelectorAll('#landingProductGrid .product-card').forEach(card => {
                    const matchName = card.dataset.name.includes(query) || card.dataset.sku.includes(query);
                    const matchCat = cat === "" || card.dataset.cat === cat;
                    card.style.display = (matchName && matchCat) ? 'flex' : 'none';
                });

                // 2. Filter DataTable if present
                if (customerDataTable) {
                    customerDataTable.search(query);
                    if (cat) {
                        customerDataTable.column(1).search(cat ? '^' + cat + '$' : '', true, false);
                    } else {
                        customerDataTable.column(1).search('');
                    }
                    customerDataTable.draw();
                }
            }

            // Pre-select product and scroll to form
            function inquireProduct(id, name, mode, quantity, price, bulkPrice, bulkMin, unit) {
                const subject = document.getElementById('subject');
                const message = document.getElementById('message');

                unit = unit || 'pcs';

                if (mode === 'purchase_all' && quantity > 0) {
                    let effectivePrice = price;
                    let rateNote = "Regular price ₱" + price.toFixed(2) + " / " + unit;
                    if (bulkPrice && bulkMin && quantity >= bulkMin) {
                        effectivePrice = bulkPrice;
                        rateNote = "Bulk discount price ₱" + bulkPrice.toFixed(2) + " / " + unit + " (applied for " + bulkMin + "+ " + unit + ")";
                    }
                    const totalVal = (quantity * effectivePrice).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    if (subject) {
                        subject.value = 'Bulk Purchase All: ' + name + ' (' + quantity + ' ' + unit + ')';
                    }
                    if (message) {
                        message.value = 'Hello Mera\'s Store Team,\n\n' +
                            'I am inquiring to PURCHASE ALL available stock of this specific item:\n' +
                            '• Product: ' + name + '\n' +
                            '• Quantity to Purchase: ' + quantity + ' ' + unit + ' (Entire Remaining Stock)\n' +
                            '• Unit Pricing: ' + rateNote + '\n' +
                            '• Total Estimated Amount: ₱' + totalVal + '\n\n' +
                            'Please confirm stock reservation, payment methods, and pickup or delivery details.';
                    }
                } else {
                    if (subject) {
                        subject.value = 'Inquiry about ' + name;
                    }
                    if (message) {
                        message.value = 'Hello Mera\'s Store Team,\n\n' +
                            'I would like to inquire about ' + name + ' (current availability, bulk buying quotes, and details).';
                    }
                }

                const element = document.getElementById('inquire');
                if (element) {
                    if (window.innerWidth < 768) {
                        window.location.hash = '#inquire';
                    } else {
                        element.scrollIntoView({ behavior: 'smooth' });
                    }
                }

                setTimeout(() => {
                    const nameField = document.getElementById('customerName');
                    if (nameField && !nameField.value) {
                        nameField.focus();
                    } else if (message) {
                        message.focus();
                    }
                }, 800);
            }

            // ── Mobile App Tab / Hash Switching ───────────────
            function handleTabSwitching() {
                if (window.innerWidth >= 768) {
                    // Desktop: restore normal scrolling behavior and display all sections
                    document.querySelectorAll('.hero-section, #products, #inquire').forEach(el => {
                        if (el) el.style.display = '';
                    });
                    // On desktop, if URL has a #product-{id} hash, scroll to + highlight that card
                    const deskHash = window.location.hash || '';
                    if (deskHash.startsWith('#product-')) {
                        // Ensure grid view is shown (not DataTable) so the card is visible
                        setCatalogView('grid');
                        const targetEl = document.getElementById(deskHash.slice(1));
                        if (targetEl) {
                            setTimeout(() => {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                highlightProductCard(targetEl);
                            }, 200);
                        }
                    }
                    return;
                }

                let hash = window.location.hash || '#home';
                // Normalize since Android app bottom navigation may send #inquiries but HTML section is #inquire
                if (hash === '#inquiries') hash = '#inquire';

                const heroSec = document.querySelector('.hero-section');
                const productsSec = document.getElementById('products');
                const inquireSec = document.getElementById('inquire');

                if (!heroSec || !productsSec || !inquireSec) return;

                // Hide all sections initially
                heroSec.style.display = 'none';
                productsSec.style.display = 'none';
                inquireSec.style.display = 'none';

                // Handle #product-{id} deep-link from chatbot "View Item"
                if (hash.startsWith('#product-')) {
                    productsSec.style.display = 'block';
                    updateBottomNavHighlight('#products');
                    // Ensure grid view so the card element is visible (not DataTable mode)
                    setCatalogView('grid');
                    setTimeout(() => {
                        const card = document.getElementById(hash.slice(1));
                        if (card) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            highlightProductCard(card);
                        }
                    }, 200);
                    return;
                }

                if (hash === '#home') {
                    heroSec.style.display = 'block';
                    window.scrollTo(0, 0);
                } else if (hash === '#products') {
                    productsSec.style.display = 'block';
                    window.scrollTo(0, 0);
                } else if (hash === '#inquire') {
                    inquireSec.style.display = 'block';
                    window.scrollTo(0, 0);
                }

                updateBottomNavHighlight(hash);
            }

            /** Briefly highlight a product card with a glowing ring so the user spots it instantly. */
            function highlightProductCard(card) {
                if (!card) return;
                card.style.transition = 'box-shadow 0.3s ease, outline 0.3s ease';
                card.style.outline = '3px solid #4f46e5';
                card.style.boxShadow = '0 0 0 6px rgba(79,70,229,0.18), 0 8px 32px rgba(79,70,229,0.22)';
                setTimeout(() => {
                    card.style.outline = '';
                    card.style.boxShadow = '';
                }, 2200);
            }

            function updateBottomNavHighlight(activeHash) {
                document.querySelectorAll('.mobile-nav-item').forEach(item => {
                    const href = item.getAttribute('href');
                    if (href) {
                        if (href.includes(activeHash)) {
                            item.classList.add('active');
                        } else if (activeHash === '#home' && href.endsWith('#home')) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    }
                });
            }

            // Handle initial load and events
            window.addEventListener('hashchange', handleTabSwitching);
            window.addEventListener('resize', handleTabSwitching);
            handleTabSwitching();

            // View Switcher event listeners
            const btnViewGrid = document.getElementById('btnViewGrid');
            const btnViewTable = document.getElementById('btnViewTable');
            if (btnViewGrid) btnViewGrid.addEventListener('click', () => setCatalogView('grid'));
            if (btnViewTable) btnViewTable.addEventListener('click', () => setCatalogView('table'));

            const savedCatalogView = (function() {
                try { return localStorage.getItem('meras_catalog_view'); } catch(e) { return null; }
            })();
            if (savedCatalogView === 'table') {
                setCatalogView('table');
            }

            // Attach event listeners
            document.getElementById('categoryFilter').addEventListener('change', filterProducts);
            document.getElementById('landingSearch').addEventListener('input', filterProducts);

            // ── Shop Gallery Lightbox ──────────────────────────
            const gallerySlides = [
                { type: 'video', caption: 'Step inside the store — Stall No. 18, Bantayan Public Market' },
                { src: '{{ asset('images/shop_gallery_1.jpg') }}', caption: 'Store interior — fabrics, school supplies & general merchandise' },
                { src: '{{ asset('images/shop_gallery_2.jpg') }}', caption: 'School supplies — notebooks, pens, folders & more' },
                { src: '{{ asset('images/shop_gallery_3.jpg') }}', caption: 'Fabrics & textiles — a wide range of colors and patterns' },
                { src: '{{ asset('images/shop_gallery_4.jpg') }}', caption: 'Stall No. 18, Bantayan Public Market — visit us anytime!' },
            ];
            let galleryCurrent = 0;

            // ── Reel loading / failure feedback ────────────────
            const galleryVideoStatus = document.getElementById('galleryVideoStatus');

            function showVideoStatus(message, isError) {
                if (!galleryVideoStatus) return;
                galleryVideoStatus.textContent = '';

                if (!isError) {
                    const spinner = document.createElement('div');
                    spinner.className = 'gallery-video-spinner';
                    galleryVideoStatus.appendChild(spinner);
                }

                const label = document.createElement('div');
                label.textContent = message;
                galleryVideoStatus.appendChild(label);

                galleryVideoStatus.classList.toggle('is-error', Boolean(isError));
                galleryVideoStatus.hidden = false;
            }

            function hideVideoStatus() {
                if (!galleryVideoStatus) return;
                galleryVideoStatus.hidden = true;
            }

            (function wireVideoStatus() {
                const video = document.getElementById('galleryMainVideo');
                if (!video) return;

                function reportVideoError() {
                    const reasons = {
                        1: 'loading was cancelled',
                        2: 'a network error occurred',
                        3: 'the video could not be decoded',
                        4: 'the video is missing or in an unsupported format',
                    };
                    const code = video.error ? video.error.code : 0;
                    showVideoStatus('Sorry, the shop video could not load - ' + (reasons[code] || 'unknown error') + '.', true);
                }

                video.addEventListener('waiting', function () {
                    if (gallerySlides[galleryCurrent]?.type === 'video' && !video.paused) {
                        showVideoStatus('Buffering...', false);
                    }
                });
                video.addEventListener('canplay', hideVideoStatus);
                video.addEventListener('canplaythrough', hideVideoStatus);
                video.addEventListener('loadeddata', hideVideoStatus);
                video.addEventListener('playing', hideVideoStatus);
                video.addEventListener('play', hideVideoStatus);
                video.addEventListener('pause', hideVideoStatus);
                video.addEventListener('timeupdate', hideVideoStatus);
                video.addEventListener('error', reportVideoError);

                const source = video.querySelector('source');
                if (source) {
                    source.addEventListener('error', reportVideoError);
                }
            })();

            // The modal is always in the DOM (only faded out), so its thumbnails and video poster
            // carry data-src/data-poster and are only fetched once someone opens the gallery.
            function hydrateGalleryMedia() {
                document.querySelectorAll('#galleryModal img[data-src]').forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
                const video = document.getElementById('galleryMainVideo');
                if (video && video.dataset.poster) {
                    video.poster = video.dataset.poster;
                    video.removeAttribute('data-poster');
                }
            }

            function openGallery(index) {
                hydrateGalleryMedia();
                galleryCurrent = index ?? 0;
                const modal = document.getElementById('galleryModal');
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                renderGallerySlide();
            }

            function closeGallery() {
                const modal = document.getElementById('galleryModal');
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                const video = document.getElementById('galleryMainVideo');
                if (video) {
                    video.pause();
                }
                hideVideoStatus();
            }

            function handleGalleryOverlayClick(e) {
                if (e.target === document.getElementById('galleryModal')) closeGallery();
            }

            function setGallerySlide(index) {
                galleryCurrent = index;
                renderGallerySlide();
            }

            function shiftGallery(dir) {
                galleryCurrent = (galleryCurrent + dir + gallerySlides.length) % gallerySlides.length;
                renderGallerySlide();
            }

            function renderGallerySlide() {
                const slide = gallerySlides[galleryCurrent];
                const mainImg = document.getElementById('galleryMainImg');
                const mainVideo = document.getElementById('galleryMainVideo');
                const caption = document.getElementById('galleryCaption');
                const stage = mainVideo.parentElement;

                stage.classList.toggle('is-video', slide.type === 'video');

                if (slide.type === 'video') {
                    mainImg.hidden = true;
                    mainVideo.hidden = false;
                    caption.textContent = slide.caption;
                    mainVideo.muted = true; // Ensure programmatic autoplay is permitted

                    if (mainVideo.readyState >= 2) {
                        hideVideoStatus();
                    } else if (mainVideo.error) {
                        // Error is handled by wireVideoStatus
                    } else {
                        showVideoStatus('Loading video...', false);
                    }

                    if (mainVideo.readyState > 0) {
                        mainVideo.currentTime = 0;
                    }
                    const playPromise = mainVideo.play();
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                hideVideoStatus();
                            })
                            .catch(() => {
                                // If autoplay policy blocks silent start, hide spinner so user can use controls
                                hideVideoStatus();
                            });
                    }
                } else {
                    mainVideo.pause();
                    mainVideo.hidden = true;
                    hideVideoStatus();
                    mainImg.hidden = false;
                    mainImg.style.opacity = '0';
                    setTimeout(() => {
                        mainImg.src = slide.src;
                        caption.textContent = slide.caption;
                        mainImg.style.opacity = '1';
                    }, 120);
                }

                document.querySelectorAll('.gallery-thumb').forEach((t, i) => t.classList.toggle('active', i === galleryCurrent));
                document.querySelectorAll('.gallery-dot').forEach((d, i) => d.classList.toggle('active', i === galleryCurrent));
            }

            document.addEventListener('keydown', e => {
                const modal = document.getElementById('galleryModal');
                if (!modal.classList.contains('open')) return;
                if (e.key === 'Escape') closeGallery();
                if (e.key === 'ArrowLeft') shiftGallery(-1);
                if (e.key === 'ArrowRight') shiftGallery(1);
            });

            document.getElementById('heroImageBtn').addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(0); }
            });
        </script>
    @endpush
@endsection