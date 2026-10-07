@extends('admin.layouts.app')

@section('title', 'Produk ' . $brand->name)

@section('content')
@php $lastSync = \App\Models\SiteSetting::get('digiflazz_last_sync'); @endphp

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <a href="{{ route('admin.products') }}" style="display:inline-flex;align-items:center;gap:.35rem;color:var(--text-dim);font-size:.78rem;margin-bottom:.4rem">
            <i class="fas fa-arrow-left"></i> Semua Game Top Up
        </a>
        <div class="flex items-center space-x-3">
            <h2 class="text-lg font-semibold">Produk {{ $brand->name }}</h2>
            <span class="badge badge-neutral">{{ $products->total() }} nominal</span>
            @if($lastSync)
                <span style="color:var(--text-dim);font-size:.78rem"><i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>
            @endif
        </div>
    </div>
    <a href="{{ route('admin.products') }}" class="btn btn-ghost"><i class="fas fa-gamepad"></i> Pilih Game Lain</a>
</div>

<div class="admin-filter-bar">
    <select class="input-field" id="statusFilter" style="width:auto;min-width:135px;padding:.35rem .75rem;font-size:.82rem">
        <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Produk Aktif</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Produk Nonaktif</option>
        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
    </select>
    <span style="align-self:center;color:var(--text-dim);font-size:.76rem">Harga modal dan detail produk diperbarui otomatis dari Digiflazz.</span>
</div>

<form id="bulkMarkupForm" action="{{ route('admin.products.markup', $brand) }}" method="POST" class="bulk-markup-panel mb-4">
    @csrf
    @method('PATCH')
    <div class="bulk-markup-panel__heading"><i class="fas fa-wand-magic-sparkles"></i><span>Atur Markup</span></div>
    <div class="bulk-markup-panel__fields">
        <select name="mode" id="markupMode" class="input-field" aria-label="Jenis markup">
            <option value="rupiah">Markup Rupiah (Rp)</option>
            <option value="persentase">Markup Persentase (%)</option>
        </select>
        <div class="bulk-markup-value"><span id="markupPrefix">Rp</span><input type="number" name="value" id="markupValue" min="0" step="0.01" placeholder="Nilai markup" required></div>
        <button type="submit" class="btn btn-primary" id="applyMarkupBtn"><i class="fas fa-check"></i> Terapkan ke Semua Produk</button>
    </div>
    <p class="bulk-markup-panel__hint">Markup berlaku untuk semua produk game ini dan akan dihitung ulang dari harga modal terbaru saat sinkronisasi. Simpan harga jual per produk untuk mengganti markupnya.</p>
    <p class="bulk-markup-panel__status" id="bulkMarkupStatus" role="status" aria-live="polite"></p>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full product-pricing-table">
            <colgroup>
                <col class="product-pricing-table__code">
                <col class="product-pricing-table__name">
                <col class="product-pricing-table__region">
                <col class="product-pricing-table__cost">
                <col class="product-pricing-table__selling">
                <col class="product-pricing-table__commission">
                <col class="product-pricing-table__status">
                <col class="product-pricing-table__action">
            </colgroup>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nominal / Produk</th>
                    <th>Region</th>
                    <th class="text-right">Harga Modal</th>
                    <th class="text-right">Harga Jual</th>
                    <th class="text-right">Komisi</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $commission = (float) $product->selling_price - (float) $product->price;
                    $commissionRate = (float) $product->price > 0 ? ($commission / (float) $product->price) * 100 : null;
                @endphp
                <tr data-product-id="{{ $product->id }}" data-cost-price="{{ (float) $product->price }}">
                    <td class="product-code">{{ $product->buyer_sku_code }}</td>
                    <td class="product-name">{{ $product->product_name }}</td>
                    <td style="color:var(--text-muted);font-size:.85rem">{{ $product->region ?: 'Semua' }}</td>
                    <td class="text-right product-cost">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="text-right">
                        <form action="{{ route('admin.products.selling-price', $product) }}" method="POST" class="selling-price-form" data-product-name="{{ $product->product_name }}" data-current-price="{{ (float) $product->selling_price }}">
                            @csrf
                            @method('PATCH')
                            <div class="selling-price-display">
                                <span data-selling-price-value>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                <button type="button" class="selling-price-edit" title="Edit harga jual" aria-label="Edit harga jual {{ $product->product_name }}"><i class="fas fa-pencil"></i></button>
                            </div>
                            <div class="selling-price-control" hidden>
                                <span>Rp</span>
                                <input type="number" name="selling_price" min="0" step="1" value="{{ (int) $product->selling_price }}" aria-label="Harga jual {{ $product->product_name }}">
                                <button type="submit" class="selling-price-save" title="Simpan harga jual" aria-label="Simpan harga jual"><i class="fas fa-check"></i></button>
                                <button type="button" class="selling-price-cancel" title="Batal edit" aria-label="Batal edit"><i class="fas fa-xmark"></i></button>
                            </div>
                            <small class="selling-price-status" aria-live="polite"></small>
                        </form>
                    </td>
                    <td class="text-right product-commission-cell" data-commission-id="{{ $product->id }}">
                        <div class="product-commission {{ $commission > 0 ? 'is-profit' : ($commission < 0 ? 'is-loss' : 'is-even') }}">
                            <strong data-commission-amount>{{ $commission >= 0 ? '+' : '-' }}Rp {{ number_format(abs($commission), 0, ',', '.') }}</strong>
                            @if($commissionRate !== null)
                                <span data-commission-rate>{{ rtrim(rtrim(number_format(abs($commissionRate), 2, '.', ''), '0'), '.') }}% {{ $commission < 0 ? 'di bawah modal' : 'margin' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center"><span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <form action="{{ route('admin.products.toggle', $product) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="fas {{ $product->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="empty-state"><i class="fas fa-box-open"></i><p>Belum ada produk {{ $brand->name }} dengan status ini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $products->links('vendor.pagination.admin') }}
@endsection

@push('styles')
<style>
.selling-price-form{display:inline-flex;flex-direction:column;align-items:flex-end}.selling-price-display{display:inline-flex;align-items:center;justify-content:flex-end;gap:.4rem;font-weight:600;font-variant-numeric:tabular-nums}.selling-price-display[hidden]{display:none}.selling-price-edit,.selling-price-save,.selling-price-cancel{display:inline-grid;place-items:center;width:25px;height:25px;padding:0;border:1px solid var(--glass-border);border-radius:7px;background:rgba(9,135,245,.04);color:var(--text-dim);cursor:pointer;transition:border-color .15s ease,color .15s ease,background .15s ease}.selling-price-edit:hover,.selling-price-edit:focus-visible{border-color:var(--accent);background:rgba(9,135,245,.1);color:var(--accent);outline:0}.selling-price-save:hover,.selling-price-save:focus-visible{border-color:#34d399;color:#6ee7b7;outline:0}.selling-price-cancel:hover,.selling-price-cancel:focus-visible{border-color:#f87171;color:#fca5a5;outline:0}.selling-price-control{display:inline-flex;align-items:center;justify-content:flex-start;gap:.3rem;padding:.18rem .25rem .18rem .5rem;border:1px solid var(--glass-border);border-radius:9px;background:var(--bg-input)}.selling-price-control[hidden]{display:none}
.selling-price-control>span{color:var(--text-dim);font-size:.76rem}.selling-price-control input{width:92px;border:0;outline:0;background:transparent;color:var(--text);font-weight:600;text-align:left;direction:ltr;font-size:.82rem;font-variant-numeric:tabular-nums}.selling-price-control:focus-within{border-color:var(--accent)}
.selling-price-status{display:block;margin-top:.18rem;color:var(--text-dim);font-size:.66rem;text-align:right}.selling-price-status:empty{display:none}.selling-price-status.success{color:#6ee7b7}.selling-price-status.error{color:#fca5a5}
.product-pricing-table{table-layout:fixed;min-width:1070px}.product-pricing-table__code{width:15%}.product-pricing-table__name{width:23%}.product-pricing-table__region{width:7%}.product-pricing-table__cost{width:13%}.product-pricing-table__selling{width:16%}.product-pricing-table__commission{width:13%}.product-pricing-table__status{width:8%}.product-pricing-table__action{width:5%}.product-pricing-table th,.product-pricing-table td{vertical-align:middle}.product-pricing-table th:nth-child(n+4),.product-pricing-table td:nth-child(n+4){white-space:nowrap}.product-code{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.79rem;color:var(--text-muted)}.product-name{font-size:.86rem;font-weight:600;line-height:1.35;overflow-wrap:anywhere}.product-cost{font-weight:600;font-variant-numeric:tabular-nums}.product-commission{display:inline-flex;min-width:90px;flex-direction:column;align-items:flex-end;gap:.08rem;font-variant-numeric:tabular-nums}.product-commission strong{font-size:.83rem}.product-commission span{font-size:.67rem}.product-commission.is-profit strong{color:#6ee7b7}.product-commission.is-profit span{color:#86efac}.product-commission.is-loss strong{color:#fca5a5}.product-commission.is-loss span{color:#fda4af}.product-commission.is-even strong,.product-commission.is-even span{color:var(--text-dim)}
.bulk-markup-panel{padding:.9rem 1rem;border:1px solid rgba(129,140,248,.32);border-radius:14px;background:linear-gradient(100deg,rgba(49,46,129,.19),rgba(13,31,61,.62))}.bulk-markup-panel__heading{display:flex;align-items:center;gap:.45rem;color:#c4b5fd;font-size:.84rem;font-weight:700}.bulk-markup-panel__fields{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-top:.72rem}.bulk-markup-panel select{width:auto;min-width:192px;padding:.43rem .6rem;font-size:.78rem}.bulk-markup-value{display:flex;align-items:center;gap:.3rem;padding:.1rem .55rem;border:1px solid var(--glass-border);border-radius:8px;background:var(--bg-input);color:var(--text-dim);font-size:.78rem}.bulk-markup-value input{width:105px;padding:.33rem 0;border:0;outline:0;background:transparent;color:var(--text);font-size:.82rem}.bulk-markup-panel__hint{margin:.6rem 0 0;color:var(--text-dim);font-size:.72rem}.bulk-markup-panel__status{min-height:17px;margin:.32rem 0 0;color:var(--text-dim);font-size:.72rem}.bulk-markup-panel__status.success{color:#6ee7b7}.bulk-markup-panel__status.error{color:#fca5a5}
@media (max-width:640px){.bulk-markup-panel__fields{align-items:stretch}.bulk-markup-panel select,.bulk-markup-value{flex:1}.bulk-markup-value input{width:100%}}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('statusFilter')?.addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    if (this.value === 'active') url.searchParams.delete('status');
    else url.searchParams.set('status', this.value);
    window.location.href = url.toString();
});

const bulkMarkupForm = document.getElementById('bulkMarkupForm');
const applyMarkupBtn = document.getElementById('applyMarkupBtn');
const bulkMarkupStatus = document.getElementById('bulkMarkupStatus');
const markupMode = document.getElementById('markupMode');
const markupPrefix = document.getElementById('markupPrefix');

function formatRupiah(value) {
    return Math.round(Math.abs(Number(value) || 0)).toLocaleString('id-ID');
}

function closePriceEditor(form, resetValue = true) {
    const display = form.querySelector('.selling-price-display');
    const editor = form.querySelector('.selling-price-control');
    const input = form.querySelector('input[name="selling_price"]');
    if (resetValue && input) input.value = Math.round(Number(form.dataset.currentPrice || 0));
    if (display) display.hidden = false;
    if (editor) editor.hidden = true;
}

function openPriceEditor(form) {
    document.querySelectorAll('.selling-price-form').forEach(other => {
        if (other !== form) closePriceEditor(other);
    });
    const display = form.querySelector('.selling-price-display');
    const editor = form.querySelector('.selling-price-control');
    const input = form.querySelector('input[name="selling_price"]');
    const status = form.querySelector('.selling-price-status');
    if (status) {
        status.className = 'selling-price-status';
        status.textContent = '';
    }
    if (display) display.hidden = true;
    if (editor) editor.hidden = false;
    if (input) {
        input.value = Math.round(Number(form.dataset.currentPrice || input.value || 0));
        input.focus();
        input.select();
    }
}

function setSellingPrice(row, price) {
    const form = row?.querySelector('.selling-price-form');
    if (!form) return;
    const normalized = Math.round(Number(price) || 0);
    form.dataset.currentPrice = normalized;
    const value = form.querySelector('[data-selling-price-value]');
    const input = form.querySelector('input[name="selling_price"]');
    if (value) value.textContent = 'Rp ' + formatRupiah(normalized);
    if (input) input.value = normalized;
    closePriceEditor(form, false);
}

function updateCommission(row, sellingPrice) {
    if (!row) return;
    const cost = Number(row.dataset.costPrice || 0);
    const commission = Number(sellingPrice) - cost;
    const display = row.querySelector('.product-commission');
    const amount = row.querySelector('[data-commission-amount]');
    const rate = row.querySelector('[data-commission-rate]');
    if (!display || !amount) return;

    display.classList.remove('is-profit', 'is-loss', 'is-even');
    display.classList.add(commission > 0 ? 'is-profit' : (commission < 0 ? 'is-loss' : 'is-even'));
    amount.textContent = (commission >= 0 ? '+' : '-') + 'Rp ' + formatRupiah(commission);
    if (rate) {
        rate.textContent = cost > 0
            ? (Math.abs(commission / cost * 100)).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + '% ' + (commission < 0 ? 'di bawah modal' : 'margin')
            : '';
    }
}

markupMode?.addEventListener('change', () => { markupPrefix.textContent = markupMode.value === 'rupiah' ? 'Rp' : '%'; });

bulkMarkupForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const formData = new FormData(bulkMarkupForm);
    applyMarkupBtn.disabled = true;
    bulkMarkupStatus.className = 'bulk-markup-panel__status';
    bulkMarkupStatus.textContent = 'Menerapkan markup…';
    try {
        const response = await fetch(bulkMarkupForm.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData,
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Markup tidak dapat diterapkan.');
        Object.entries(data.prices || {}).forEach(([id, price]) => {
            const row = document.querySelector('[data-product-id="' + id + '"]');
            setSellingPrice(row, price);
            updateCommission(row, price);
        });
        bulkMarkupStatus.classList.add('success');
        bulkMarkupStatus.textContent = data.message;
    } catch (error) {
        bulkMarkupStatus.classList.add('error');
        bulkMarkupStatus.textContent = error.message || 'Gagal menerapkan markup.';
    } finally {
        applyMarkupBtn.disabled = false;
    }
});

document.querySelectorAll('.selling-price-form').forEach(form => {
    form.querySelector('.selling-price-edit')?.addEventListener('click', () => openPriceEditor(form));
    form.querySelector('.selling-price-cancel')?.addEventListener('click', () => {
        closePriceEditor(form);
        const status = form.querySelector('.selling-price-status');
        status.className = 'selling-price-status';
        status.textContent = '';
    });
    form.querySelector('input[name="selling_price"]')?.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closePriceEditor(form);
            form.querySelector('.selling-price-edit')?.focus();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('.selling-price-save');
        const status = form.querySelector('.selling-price-status');
        button.disabled = true;
        status.className = 'selling-price-status';
        status.textContent = 'Menyimpan…';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new FormData(form),
            });
            const data = await response.json();
            if (!response.ok) throw new Error((data.errors?.selling_price || [data.message || 'Harga tidak valid'])[0]);
            const row = form.closest('[data-product-id]');
            setSellingPrice(row, data.selling_price);
            updateCommission(row, data.selling_price);
            status.classList.add('success');
            status.textContent = 'Tersimpan';
            setTimeout(() => {
                if (status.textContent === 'Tersimpan') status.textContent = '';
            }, 1800);
        } catch (error) {
            status.classList.add('error');
            status.textContent = error.message || 'Gagal menyimpan';
        } finally {
            button.disabled = false;
        }
    });
});

</script>
@endpush
