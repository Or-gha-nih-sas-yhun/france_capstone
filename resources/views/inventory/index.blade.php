@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<style>
    /* ── Inventory-specific styles ── */
    .inv-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
    }

    /* Search bar */
    .inv-search-wrapper {
        position: relative;
        flex: 1;
        max-width: 340px;
    }
    .inv-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-subtle);
        pointer-events: none;
        width: 15px;
        height: 15px;
    }
    .inv-search-input {
        padding-left: 34px !important;
        padding-right: 32px !important;
        height: 38px !important;
        padding-block: 0 !important;
        border-radius: var(--radius-sm) !important;
        font-size: 0.875rem !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #f8fafc !important;
        width: 100%;
        outline: none;
        transition: var(--transition-fast);
    }
    .inv-search-input:focus {
        background: #fff !important;
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1) !important;
    }
    .inv-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 17px;
        height: 17px;
        border-radius: 50%;
        background: rgba(148,163,184,0.2);
        border: none;
        color: var(--color-text-muted);
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: var(--transition-fast);
        cursor: pointer;
    }
    .inv-search-clear:hover { background: var(--color-danger); color: #fff; }

    /* Table */
    .inv-sku-badge {
        display: inline-block;
        padding: 2px 7px;
        background: #f1f5f9;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.72rem;
        color: #475569;
        border: 1px solid #e2e8f0;
        letter-spacing: 0.3px;
    }
    .stock-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
    }
    .stock-ok   { background: #10b981; }
    .stock-low  { background: #f59e0b; }
    .stock-zero { background: #ef4444; }

    .badge-stock-ok   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-stock-low  { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .badge-stock-zero { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .badge-stock {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    /* CSV import modal */
    .csv-error-report {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .csv-error-report-head {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #92400e;
        margin-bottom: 6px;
    }
    .csv-error-report ul {
        margin: 0;
        padding-left: 26px;
        max-height: 180px;
        overflow-y: auto;
    }
    .csv-error-report li {
        font-size: 0.78rem;
        color: #78350f;
        line-height: 1.6;
    }

    .csv-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }
    .csv-modal-overlay.open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .csv-modal {
        width: min(470px, 100%);
        background: var(--color-surface);
        border: 1px solid rgba(226, 232, 240, 0.92);
        border-radius: var(--radius-lg);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.35);
        padding: 26px;
        transform: translateY(12px) scale(0.97);
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .csv-modal-overlay.open .csv-modal { transform: translateY(0) scale(1); }
    .csv-modal-head {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        margin-bottom: 18px;
    }
    .csv-modal-icon {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-primary);
        background: var(--color-primary-light);
        border: 1px solid var(--color-primary-mid);
    }
    .csv-modal-head h3 {
        margin: 0 0 3px;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--color-secondary);
    }
    .csv-modal-head p {
        margin: 0;
        font-size: 0.8rem;
        color: var(--color-text-muted);
        line-height: 1.45;
    }

    .csv-dropzone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 26px 18px;
        border: 2px dashed var(--color-border-strong);
        border-radius: var(--radius-md);
        background: #f8fafc;
        cursor: pointer;
        text-align: center;
        transition: var(--transition-fast);
    }
    .csv-dropzone:hover,
    .csv-dropzone.is-dragging {
        border-color: var(--color-primary);
        background: var(--color-primary-light);
    }
    .csv-dropzone.has-file {
        border-style: solid;
        border-color: var(--color-success);
        background: var(--color-success-light);
    }
    .csv-dropzone input[type="file"] {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }
    .csv-dropzone svg { color: var(--color-text-subtle); margin-bottom: 3px; }
    .csv-dropzone.has-file svg { color: var(--color-success); }
    .csv-dropzone-label {
        font-size: 0.87rem;
        font-weight: 700;
        color: var(--color-secondary);
        word-break: break-all;
    }
    .csv-dropzone-hint {
        font-size: 0.74rem;
        color: var(--color-text-muted);
    }

    .csv-format-note {
        margin-top: 14px;
        padding: 13px 15px;
        border-radius: var(--radius-sm);
        background: #f8fafc;
        border: 1px solid var(--color-border);
    }
    .csv-format-note strong.csv-format-title {
        display: block;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--color-secondary);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 7px;
    }
    .csv-format-note code {
        display: block;
        padding: 8px 11px;
        margin-bottom: 9px;
        border-radius: 6px;
        background: #0f172a;
        color: #a5f3fc;
        font-family: 'Courier New', monospace;
        font-size: 0.78rem;
        letter-spacing: 0.3px;
    }
    .csv-format-note code.csv-inline {
        display: inline;
        padding: 1px 5px;
        margin: 0;
        background: #e2e8f0;
        color: #0f172a;
        font-size: 0.74rem;
    }
    .csv-format-note p {
        margin: 0;
        font-size: 0.76rem;
        color: var(--color-text-muted);
        line-height: 1.6;
    }

    .csv-modal-actions {
        display: flex;
        gap: 9px;
        margin-top: 18px;
    }
    .csv-modal-actions .btn { flex: 1; }
    .csv-modal-actions .btn:disabled { opacity: 0.65; cursor: progress; }

    @media (max-width: 520px) {
        .csv-modal { padding: 22px 18px; }
        .csv-modal-actions { flex-direction: column-reverse; }
    }

    /* SVG action buttons */
    .action-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: var(--color-text-muted);
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition-fast);
    }
    .action-icon-btn:hover { border-color: var(--color-primary); color: var(--color-primary); background: var(--color-primary-light); }
    .action-icon-btn.del:hover { border-color: var(--color-danger); color: var(--color-danger); background: #fee2e2; }

    /* Add/Edit form card */
    .inv-form-card { position: sticky; top: 24px; }
    .inv-form-title { font-size: 1rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .inv-form-group { margin-bottom: 14px; }
    .inv-form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--color-text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
    .inv-form-group input, .inv-form-group select {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: var(--radius-sm);
        padding: 8px 12px;
        font-size: 0.875rem;
        background: #f8fafc;
        outline: none;
        transition: var(--transition-fast);
        color: var(--color-secondary);
    }
    .inv-form-group input:focus, .inv-form-group select:focus {
        border-color: var(--color-primary);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .inv-form-group input[readonly] {
        background: #f1f5f9;
        color: var(--color-text-muted);
        cursor: not-allowed;
    }
    .inv-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .inv-form-row.is-triple { grid-template-columns: 1fr 1fr 0.8fr; }
    .inv-unit-badge {
        display: inline-block;
        margin-left: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--color-text-muted);
    }
    .inv-form-actions { display: flex; gap: 8px; margin-top: 4px; }

    /* Empty state */
    .inv-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; color: var(--color-text-muted); }
    .inv-empty svg { opacity: 0.35; margin-bottom: 14px; }
    .inv-empty p { font-weight: 600; font-size: 0.95rem; color: var(--color-secondary); margin-bottom: 4px; }
    .inv-empty small { font-size: 0.8rem; }

    @media (max-width: 900px) {
        .inv-layout { grid-template-columns: 1fr; }
        .inv-form-card { position: static; }
    }

    .inv-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 14px 18px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .pagination-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        padding: 7px 14px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pagination-btn:hover:not(.disabled) {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f5f3ff;
    }
    .pagination-btn.disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .pagination-info {
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
    }
</style>

<div class="page-header">
    <div class="page-title">
        <h2>Inventory Management</h2>
        <p>Manage your product catalog, stock levels, and pricing.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" id="csvImportOpenBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Import CSV
        </button>
        <a href="{{ route('products.index') }}" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
            Refresh
        </a>
    </div>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
@endif

@if(session('import_errors') && count(session('import_errors')))
    <div class="csv-error-report">
        <div class="csv-error-report-head">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            These rows were skipped
        </div>
        <ul>
            @foreach(session('import_errors') as $importError)
                <li>{{ $importError }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="inv-layout">
    <!-- ── Left: Product List ── -->
    <div class="panel-card">
        <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h3 style="margin:0;">Product List</h3>
                <p style="margin:0;font-size:0.8rem;color:var(--color-text-muted);">
                    Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} product(s)
                </p>
            </div>
            <form method="GET" action="{{ route('products.index') }}" style="display:flex;gap:8px;align-items:center;">
                <div class="inv-search-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" class="inv-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" class="inv-search-input" placeholder="Search name, SKU, category..." value="{{ $search }}">
                    @if($search)
                        <a href="{{ route('products.index') }}" class="inv-search-clear" title="Clear search">&times;</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px;">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="text-align:center;">Stock</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--color-secondary);margin-bottom:3px;">{{ $product->name }}</div>
                                @if($product->sku)
                                    <span class="inv-sku-badge">{{ $product->sku }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="category-pill">{{ $product->category ?: 'General' }}</span>
                            </td>
                            <td style="text-align:center;">
                                @if($product->quantity === 0)
                                    <span class="badge-stock badge-stock-zero">
                                        <span class="stock-dot stock-zero"></span>Out of Stock
                                    </span>
                                @elseif($product->quantity <= 5)
                                    <span class="badge-stock badge-stock-low">
                                        <span class="stock-dot stock-low"></span>{{ $product->quantity }} {{ $product->unit ?: 'pcs' }} left
                                    </span>
                                @else
                                    <span class="badge-stock badge-stock-ok">
                                        <span class="stock-dot stock-ok"></span>{{ $product->quantity }} {{ $product->unit ?: 'pcs' }} in stock
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:700;color:#059669;">
                                &#8369;{{ number_format($product->price, 2) }}<span class="inv-unit-badge">/ {{ $product->unit ?: 'pcs' }}</span>
                                @if($product->hasBulkPricing())
                                    <div style="font-size:0.72rem;font-weight:600;color:var(--color-text-muted);margin-top:2px;">
                                        Bulk &#8369;{{ number_format($product->bulk_price, 2) }} at {{ $product->bulk_min_qty }}+
                                    </div>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex;justify-content:center;gap:6px;">
                                    <a href="{{ route('products.index', ['edit' => $product->id]) }}"
                                       class="action-icon-btn" title="Edit product">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product->id) }}" onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon-btn del" title="Delete product">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:0;">
                                <div class="inv-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="16.5 19.79 16.5 14.6 21 12"/><polyline points="12 22.08 12 16.88 12 12"/></svg>
                                    @if($search)
                                        <p>No products match "{{ $search }}"</p>
                                        <small>Try a different search term or <a href="{{ route('products.index') }}">clear the search</a>.</small>
                                    @else
                                        <p>No products in inventory yet.</p>
                                        <small>Add your first product using the form on the right.</small>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="inv-pagination">
                @if($products->onFirstPage())
                    <span class="pagination-btn disabled">&laquo; Previous</span>
                @else
                    <a href="{{ $products->previousPageUrl() }}" class="pagination-btn">&laquo; Previous</a>
                @endif

                <span class="pagination-info">
                    Page {{ $products->currentPage() }} of {{ $products->lastPage() }} ({{ $products->total() }} items)
                </span>

                @if($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" class="pagination-btn">Next &raquo;</a>
                @else
                    <span class="pagination-btn disabled">Next &raquo;</span>
                @endif
            </div>
        @endif
    </div>

    <!-- ── Right: Add / Edit Form ── -->
    <div class="panel-card inv-form-card">
        @if($editProduct)
            <div class="inv-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Product
            </div>
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="{{ $editProduct->id }}">
                <div class="inv-form-group">
                    <label>Product Name <span style="color:var(--color-danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $editProduct->name) }}" required placeholder="e.g. Coca-Cola 1.5L">
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $editProduct->sku) }}" placeholder="e.g. SKU-001">
                    </div>
                    <div class="inv-form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category', $editProduct->category) }}" placeholder="e.g. Beverages">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Price (PHP) <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $editProduct->price) }}" required placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" list="unitOptions" value="{{ old('unit', $editProduct->unit) }}" placeholder="pcs">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Remaining Quantity</label>
                        <input type="number" value="{{ $editProduct->quantity }}" readonly aria-readonly="true">
                    </div>
                    <div class="inv-form-group">
                        <label>Additional Quantity <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" min="0" name="additional_quantity" value="{{ old('additional_quantity', 0) }}" required placeholder="0">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Bulk Price (PHP)</label>
                        <input type="number" step="0.01" min="0" name="bulk_price" value="{{ old('bulk_price', $editProduct->bulk_price) }}" placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Bulk Starts At (qty)</label>
                        <input type="number" min="2" name="bulk_min_qty" value="{{ old('bulk_min_qty', $editProduct->bulk_min_qty) }}" placeholder="e.g. 12">
                    </div>
                </div>
                <p style="font-size:0.74rem;color:var(--color-text-muted);margin:-4px 0 10px;">
                    Optional. Fill both and the POS charges the bulk price once the cart reaches that quantity. Leave blank for retail only.
                </p>
                @if ($errors->any())
                    <div style="color:var(--color-danger);font-size:0.8rem;margin-bottom:10px;">{{ $errors->first() }}</div>
                @endif
                <div class="inv-form-actions">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        @else
            <div class="inv-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Add New Product
            </div>
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <div class="inv-form-group">
                    <label>Product Name <span style="color:var(--color-danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Coca-Cola 1.5L">
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. SKU-001">
                    </div>
                    <div class="inv-form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Beverages">
                    </div>
                </div>
                <div class="inv-form-row is-triple">
                    <div class="inv-form-group">
                        <label>Price (PHP) <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Stock Qty <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" min="0" name="quantity" value="{{ old('quantity') }}" required placeholder="0">
                    </div>
                    <div class="inv-form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" list="unitOptions" value="{{ old('unit') }}" placeholder="pcs">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Bulk Price (PHP)</label>
                        <input type="number" step="0.01" min="0" name="bulk_price" value="{{ old('bulk_price') }}" placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Bulk Starts At (qty)</label>
                        <input type="number" min="2" name="bulk_min_qty" value="{{ old('bulk_min_qty') }}" placeholder="e.g. 12">
                    </div>
                </div>
                <p style="font-size:0.74rem;color:var(--color-text-muted);margin:-4px 0 10px;">
                    Optional. Fill both and the POS charges the bulk price once the cart reaches that quantity. Leave blank for retail only.
                </p>
                @if ($errors->any())
                    <div style="color:var(--color-danger);font-size:0.8rem;margin-bottom:10px;">{{ $errors->first() }}</div>
                @endif
                <div class="inv-form-actions">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Add Product</button>
                </div>
            </form>
        @endif
    </div>
</div>

<datalist id="unitOptions">
    <option value="pcs">Pieces</option>
    <option value="rms">Reams</option>
    <option value="pck">Packs</option>
    <option value="box">Boxes</option>
    <option value="set">Sets</option>
    <option value="pair">Pairs</option>
    <option value="doz">Dozens</option>
    <option value="bdl">Bundles</option>
    <option value="roll">Rolls</option>
    <option value="btl">Bottles</option>
    <option value="sack">Sacks</option>
    <option value="kg">Kilograms</option>
    <option value="m">Meters</option>
    <option value="yd">Yards</option>
</datalist>

<!-- CSV Import Modal -->
<div class="csv-modal-overlay" id="csvImportModal" aria-hidden="true">
    <div class="csv-modal" role="dialog" aria-modal="true" aria-labelledby="csvImportTitle">
        <div class="csv-modal-head">
            <div class="csv-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
            </div>
            <div>
                <h3 id="csvImportTitle">Import Products from CSV</h3>
                <p>Add or update your catalog in bulk from a spreadsheet.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" id="csvImportForm">
            @csrf
            <label class="csv-dropzone" id="csvDropzone">
                <input type="file" name="csv_file" id="csvFileInput" accept=".csv,.txt,text/csv" required>
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span class="csv-dropzone-label" id="csvFileName">Choose a CSV file</span>
                <span class="csv-dropzone-hint">or drag it here &bull; up to 2 MB</span>
            </label>

            <div class="csv-format-note">
                <strong class="csv-format-title">Columns</strong>
                <code>name,price,quantity,unit</code>
                <p>
                    The first row must hold the column names. <code class="csv-inline">unit</code> is optional
                    &mdash; put <em>pcs</em>, <em>rms</em>, <em>pcks</em>, <em>box</em> and the like there, and
                    leave it out to default to <em>pcs</em>.
                </p>
                <p style="margin-top:7px;">
                    <strong>Do not include a SKU column</strong> &mdash; every new product is given one
                    automatically from its name (for example <em>Coca-Cola 1.5L</em> becomes <em>COC-001</em>).
                    A product whose name is already in the catalog has its price, stock and unit updated,
                    keeping the SKU it already has.
                </p>
            </div>

            <div class="csv-modal-actions">
                <button type="button" class="btn btn-secondary" id="csvCancelBtn">Cancel</button>
                <button type="submit" class="btn btn-primary" id="csvSubmitBtn">Import Products</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('csvImportModal');
        var openBtn = document.getElementById('csvImportOpenBtn');
        var cancelBtn = document.getElementById('csvCancelBtn');
        var dropzone = document.getElementById('csvDropzone');
        var fileInput = document.getElementById('csvFileInput');
        var fileName = document.getElementById('csvFileName');
        var form = document.getElementById('csvImportForm');
        var submitBtn = document.getElementById('csvSubmitBtn');

        function open() {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('logout-modal-open');
        }

        function close() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('logout-modal-open');
        }

        function showSelection() {
            if (fileInput.files.length) {
                fileName.textContent = fileInput.files[0].name;
                dropzone.classList.add('has-file');
            } else {
                fileName.textContent = 'Choose a CSV file';
                dropzone.classList.remove('has-file');
            }
        }

        openBtn.addEventListener('click', open);
        cancelBtn.addEventListener('click', close);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) close();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('open')) close();
        });

        fileInput.addEventListener('change', showSelection);

        ['dragenter', 'dragover'].forEach(function (type) {
            dropzone.addEventListener(type, function (event) {
                event.preventDefault();
                dropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach(function (type) {
            dropzone.addEventListener(type, function (event) {
                event.preventDefault();
                dropzone.classList.remove('is-dragging');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (event.dataTransfer.files.length) {
                fileInput.files = event.dataTransfer.files;
                showSelection();
            }
        });

        // A large sheet takes a moment; block the double submit that would
        // otherwise run the whole import twice.
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Importing...';
        });
    })();
</script>
@endsection
