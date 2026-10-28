@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $statusTotal = max(1, (int) $stats['total_orders']);
    $successDeg = ($stats['success_orders'] / $statusTotal) * 360;
    $processingDeg = (($stats['success_orders'] + $stats['processing_orders']) / $statusTotal) * 360;
    $pendingDeg = (($stats['success_orders'] + $stats['processing_orders'] + $stats['pending_orders']) / $statusTotal) * 360;
@endphp

<style>
.dash-shell{display:grid;gap:1.45rem}.dash-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem}.dash-intro h2{font-size:1.25rem;font-weight:800;letter-spacing:-.025em}.dash-intro p,.dash-section-head p{margin-top:.2rem;color:var(--text-muted);font-size:.78rem}.dash-controls{display:flex;align-items:end;gap:.65rem}.dash-control{display:grid;gap:.25rem}.dash-control label{color:var(--text-dim);font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.dash-control select{min-width:180px;height:38px;padding:0 2rem 0 .8rem;border:1px solid var(--border);border-radius:9px;background:var(--bg-card);color:var(--text);font-size:.76rem}.dash-refresh{display:inline-flex;height:38px;align-items:center;gap:.45rem;padding:0 .8rem;border:1px solid var(--border);border-radius:9px;background:var(--bg-card);color:var(--text-muted);font-size:.74rem;font-weight:600}.dash-refresh:hover{border-color:var(--accent);color:var(--accent)}.dash-section-head{margin-bottom:.7rem}.dash-section-head h3{font-size:.98rem;font-weight:750;letter-spacing:-.015em}.dash-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem}.dash-summary-card,.dash-panel,.dash-op-card{border:1px solid var(--border);background:var(--bg-card);box-shadow:var(--card-shadow)}.dash-summary-card{position:relative;display:grid;grid-template-columns:44px minmax(0,1fr);align-items:center;gap:.75rem;min-height:91px;padding:1rem;border-radius:14px;overflow:hidden}.dash-summary-card::after{content:'';position:absolute;right:-25px;bottom:-38px;width:86px;height:86px;border-radius:50%;background:color-mix(in srgb,var(--card-accent) 9%,transparent)}.dash-summary-icon{display:grid;width:44px;height:44px;place-items:center;border-radius:11px;background:color-mix(in srgb,var(--card-accent) 11%,transparent);color:var(--card-accent);font-size:1.05rem}.dash-summary-label{color:var(--text-muted);font-size:.7rem;font-weight:600}.dash-summary-value{margin-top:.2rem;font-size:clamp(1rem,1.65vw,1.35rem);font-weight:800;line-height:1.2;white-space:nowrap}.dash-summary-meta{margin-top:.2rem;color:var(--text-dim);font-size:.62rem}.dash-monitor-grid{display:grid;grid-template-columns:minmax(0,2.2fr) minmax(280px,.9fr);gap:1rem}.dash-panel{border-radius:14px;padding:1.15rem}.dash-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.dash-panel-head h3{font-size:.95rem;font-weight:750}.dash-panel-head p{margin-top:.18rem;color:var(--text-muted);font-size:.7rem}.dash-periods{display:flex;gap:.15rem;padding:.2rem;border-radius:8px;background:var(--bg-input)}.dash-period{padding:.38rem .65rem;border-radius:6px;color:var(--text-muted);font-size:.68rem;font-weight:700}.dash-period.is-active{background:var(--bg-card);color:var(--accent);box-shadow:0 1px 5px rgba(0,0,0,.1)}.dash-chart-wrap{position:relative;height:315px;margin-top:1rem}.dash-chart-wrap canvas{display:block;width:100%;height:100%}.dash-legend{display:flex;justify-content:center;gap:1rem;margin-top:.4rem;color:var(--text-muted);font-size:.67rem}.dash-legend span{display:inline-flex;align-items:center;gap:.35rem}.dash-legend i{display:block;width:9px;height:9px;border-radius:3px}.dash-donut-wrap{display:grid;place-items:center;margin:2.1rem 0 1.7rem}.dash-donut{position:relative;width:174px;aspect-ratio:1;border-radius:50%;background:conic-gradient(#10b981 0deg {{ $successDeg }}deg,#3b82f6 {{ $successDeg }}deg {{ $processingDeg }}deg,#f59e0b {{ $processingDeg }}deg {{ $pendingDeg }}deg,#ef4444 {{ $pendingDeg }}deg 360deg)}.dash-donut::after{content:'';position:absolute;inset:27px;border-radius:50%;background:var(--bg-card)}.dash-donut-center{position:absolute;z-index:1;inset:0;display:grid;place-content:center;text-align:center}.dash-donut-center strong{font-size:1.45rem}.dash-donut-center span{color:var(--text-muted);font-size:.64rem}.dash-status-list{display:grid;gap:.65rem}.dash-status-row{display:grid;grid-template-columns:9px 1fr auto;align-items:center;gap:.55rem;color:var(--text-muted);font-size:.75rem}.dash-status-row i{width:9px;height:9px;border-radius:50%}.dash-status-row strong{color:var(--text);font-size:.78rem}.dash-op-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.7rem}.dash-op-card{min-height:112px;padding:.9rem;border-radius:12px}.dash-op-card i{color:var(--op-accent);font-size:1rem}.dash-op-card strong{display:block;margin-top:.85rem;font-size:1.05rem}.dash-op-card span{display:block;margin-top:.22rem;color:var(--text-muted);font-size:.64rem;line-height:1.4}.dash-bottom-grid{display:grid;grid-template-columns:minmax(0,1.8fr) minmax(300px,1fr);gap:1rem}.dash-table-panel{padding:0;overflow:hidden}.dash-table-head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.1rem;border-bottom:1px solid var(--border)}.dash-table-head h3{font-size:.9rem;font-weight:750}.dash-table-head a{color:var(--accent);font-size:.68rem;font-weight:700}.dash-table-scroll{overflow-x:auto}.dash-table{width:100%;min-width:670px}.dash-table th{padding:.65rem .85rem;background:color-mix(in srgb,var(--bg-input) 58%,transparent);color:var(--text-dim);font-size:.62rem;font-weight:700;letter-spacing:.055em;text-align:left;text-transform:uppercase}.dash-table td{padding:.72rem .85rem;border-top:1px solid var(--glass-border);font-size:.72rem}.dash-order-id{font-family:monospace;color:var(--text-muted)}.dash-money{font-weight:750;white-space:nowrap}.dash-badge{display:inline-flex;align-items:center;padding:.25rem .5rem;border-radius:999px;font-size:.61rem;font-weight:750;text-transform:capitalize}.dash-badge.success{background:rgba(16,185,129,.12);color:#10b981}.dash-badge.pending{background:rgba(245,158,11,.12);color:#f59e0b}.dash-badge.processing{background:rgba(59,130,246,.12);color:#3b82f6}.dash-badge.failed,.dash-badge.cancelled,.dash-badge.expired{background:rgba(239,68,68,.12);color:#ef4444}.dash-account-list{display:grid;gap:.65rem;padding:1rem}.dash-account-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.7rem;padding:.75rem;border:1px solid var(--border);border-radius:10px;background:color-mix(in srgb,var(--bg-input) 42%,transparent)}.dash-account-item strong{display:block;overflow:hidden;font-size:.73rem;text-overflow:ellipsis;white-space:nowrap}.dash-account-item small{display:block;margin-top:.22rem;color:var(--text-muted);font-size:.62rem}.dash-account-side{text-align:right}.dash-account-side b{display:block;font-size:.72rem;white-space:nowrap}.dash-account-side .dash-badge{margin-top:.3rem}.dash-empty{padding:2rem;text-align:center;color:var(--text-muted);font-size:.75rem}@media(max-width:1180px){.dash-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-op-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.dash-monitor-grid,.dash-bottom-grid{grid-template-columns:1fr}}@media(max-width:640px){.dash-shell{gap:1.1rem}.dash-toolbar{align-items:stretch;flex-direction:column}.dash-controls{display:grid;grid-template-columns:minmax(0,1fr) auto}.dash-control select{width:100%;min-width:0}.dash-summary-grid{grid-template-columns:1fr 1fr;gap:.55rem}.dash-summary-card{grid-template-columns:34px minmax(0,1fr);min-height:82px;padding:.72rem;gap:.55rem}.dash-summary-icon{width:34px;height:34px;border-radius:9px;font-size:.85rem}.dash-summary-value{font-size:.92rem}.dash-summary-meta{display:none}.dash-monitor-grid{gap:.7rem}.dash-panel{padding:.85rem}.dash-panel-head{align-items:stretch;flex-direction:column}.dash-periods{align-self:flex-end}.dash-chart-wrap{height:245px}.dash-donut{width:148px}.dash-donut-wrap{margin:1.3rem 0}.dash-op-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem}.dash-op-card{min-height:98px;padding:.75rem}.dash-op-card strong{margin-top:.65rem;font-size:.9rem}.dash-bottom-grid{gap:.7rem}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto}.dash-summary-card,.dash-op-card{transition:none}}
</style>

<div class="dash-shell">
    <div class="dash-toolbar">
        <div class="dash-intro">
            <h2>Monitoring Top Up</h2>
            <p>Rekap transaksi dan kesehatan operasional secara menyeluruh.</p>
        </div>
        <form class="dash-controls" method="GET" action="{{ route('admin.dashboard') }}">
            <div class="dash-control">
                <label for="dashboardBrand">Brand</label>
                <select name="brand" id="dashboardBrand" onchange="this.form.submit()">
                    <option value="">Semua Brand</option>
                    @foreach($brands as $brand)<option value="{{ $brand }}" @selected($selectedBrand === $brand)>{{ $brand }}</option>@endforeach
                </select>
            </div>
            <button class="dash-refresh" type="button" onclick="window.location.reload()"><i class="fas fa-rotate"></i> Refresh</button>
        </form>
    </div>

    <section>
        <div class="dash-section-head"><h3>Ringkasan Keseluruhan</h3><p>{{ $selectedBrand ?: 'Akumulasi seluruh brand top-up' }}</p></div>
        <div class="dash-summary-grid">
            <article class="dash-summary-card" style="--card-accent:#2563eb"><div class="dash-summary-icon"><i class="fas fa-cart-shopping"></i></div><div><div class="dash-summary-label">Total Omzet</div><div class="dash-summary-value">Rp {{ number_format($stats['total_revenue'],0,',','.') }}</div><div class="dash-summary-meta">Transaksi top-up sukses</div></div></article>
            <article class="dash-summary-card" style="--card-accent:#10b981"><div class="dash-summary-icon"><i class="fas fa-sack-dollar"></i></div><div><div class="dash-summary-label">Estimasi Profit</div><div class="dash-summary-value">Rp {{ number_format($stats['estimated_profit'],0,',','.') }}</div><div class="dash-summary-meta">Omzet dikurangi harga modal saat ini</div></div></article>
            <article class="dash-summary-card" style="--card-accent:#8b5cf6"><div class="dash-summary-icon"><i class="fas fa-receipt"></i></div><div><div class="dash-summary-label">Rata-rata Transaksi</div><div class="dash-summary-value">Rp {{ number_format($stats['average_order'],0,',','.') }}</div><div class="dash-summary-meta">Per transaksi sukses</div></div></article>
            <article class="dash-summary-card" style="--card-accent:#06b6d4"><div class="dash-summary-icon"><i class="fas fa-wave-square"></i></div><div><div class="dash-summary-label">Saldo Digiflazz</div><div class="dash-summary-value">{{ $stats['digiflazz_balance'] === null ? 'Tidak tersedia' : 'Rp '.number_format($stats['digiflazz_balance'],0,',','.') }}</div><div class="dash-summary-meta">Cache maksimal 5 menit</div></div></article>
        </div>
    </section>

    <section class="dash-monitor-grid">
        <article class="dash-panel">
            <div class="dash-panel-head"><div><h3>Tren Omzet & Profit</h3><p>Perbandingan kinerja top-up harian</p></div><div class="dash-periods" aria-label="Periode grafik"><button type="button" class="dash-period" data-days="7">7H</button><button type="button" class="dash-period is-active" data-days="14">14H</button><button type="button" class="dash-period" data-days="30">30H</button></div></div>
            <div class="dash-chart-wrap"><canvas id="topupTrendChart" aria-label="Grafik omzet dan profit top-up"></canvas></div>
            <div class="dash-legend"><span><i style="background:#0ea5e9"></i>Omzet</span><span><i style="background:#10b981"></i>Profit</span></div>
        </article>
        <article class="dash-panel">
            <div class="dash-panel-head"><div><h3>Komposisi Transaksi</h3><p>Distribusi status transaksi saat ini</p></div></div>
            <div class="dash-donut-wrap"><div class="dash-donut"><div class="dash-donut-center"><strong>{{ $stats['total_orders'] }}</strong><span>Total transaksi</span></div></div></div>
            <div class="dash-status-list">
                <div class="dash-status-row"><i style="background:#10b981"></i><span>Berhasil</span><strong>{{ $stats['success_orders'] }}</strong></div>
                <div class="dash-status-row"><i style="background:#3b82f6"></i><span>Diproses</span><strong>{{ $stats['processing_orders'] }}</strong></div>
                <div class="dash-status-row"><i style="background:#f59e0b"></i><span>Menunggu</span><strong>{{ $stats['pending_orders'] }}</strong></div>
                <div class="dash-status-row"><i style="background:#ef4444"></i><span>Gagal/Batal</span><strong>{{ $stats['failed_orders'] }}</strong></div>
            </div>
        </article>
    </section>

    <section>
        <div class="dash-section-head"><h3>Kinerja Operasional</h3><p>Indikator utama untuk pengambilan keputusan</p></div>
        <div class="dash-op-grid">
            <article class="dash-op-card" style="--op-accent:#2563eb"><i class="fas fa-chart-line"></i><strong>{{ number_format($stats['total_orders'],0,',','.') }}</strong><span>Total transaksi top-up</span></article>
            <article class="dash-op-card" style="--op-accent:#10b981"><i class="fas fa-percent"></i><strong>{{ number_format($stats['success_rate'],1,',','.') }}%</strong><span>Tingkat transaksi berhasil</span></article>
            <article class="dash-op-card" style="--op-accent:#06b6d4"><i class="fas fa-box-open"></i><strong>{{ number_format($stats['active_products'],0,',','.') }}</strong><span>Produk aktif dari {{ $stats['total_products'] }}</span></article>
            <article class="dash-op-card" style="--op-accent:#f59e0b"><i class="fas fa-clock"></i><strong>{{ $stats['pending_orders'] }}</strong><span>Menunggu pembayaran</span></article>
            <article class="dash-op-card" style="--op-accent:#3b82f6"><i class="fas fa-spinner"></i><strong>{{ $stats['processing_orders'] }}</strong><span>Top-up sedang diproses</span></article>
            <article class="dash-op-card" style="--op-accent:#ef4444"><i class="fas fa-triangle-exclamation"></i><strong>{{ $stats['failed_orders'] }}</strong><span>Transaksi gagal atau batal</span></article>
        </div>
    </section>

    <section class="dash-bottom-grid">
        <article class="dash-panel dash-table-panel">
            <div class="dash-table-head"><h3>Transaksi Top Up Terbaru</h3><a href="{{ route('admin.orders.topup') }}">Lihat semua</a></div>
            <div class="dash-table-scroll"><table class="dash-table"><thead><tr><th>Order ID</th><th>Pelanggan</th><th>Produk</th><th>Status</th><th>Total</th></tr></thead><tbody>
                @forelse($recentOrders as $order)
                    <tr><td class="dash-order-id">{{ $order->order_id }}</td><td>{{ $order->user->name ?? $order->customer_name ?? 'Guest' }}</td><td>{{ $order->product_name }}</td><td><span class="dash-badge {{ $order->status }}">{{ $order->status }}</span></td><td class="dash-money">Rp {{ number_format($order->price,0,',','.') }}</td></tr>
                @empty<tr><td colspan="5" class="dash-empty">Belum ada transaksi top-up.</td></tr>@endforelse
            </tbody></table></div>
        </article>
        <article class="dash-panel dash-table-panel">
            <div class="dash-table-head"><h3>Stock Akun Terbaru</h3><a href="{{ route('admin.account-orders') }}">Lihat semua</a></div>
            <div class="dash-account-list">
                @forelse($recentAccountOrders as $order)
                    <div class="dash-account-item"><div><strong>{{ $order->listing->product_name ?? 'Akun game' }}</strong><small>{{ $order->customer_name }} · {{ $order->order_ref }}</small></div><div class="dash-account-side"><b>Rp {{ number_format((float)$order->total_price,0,',','.') }}</b><span class="dash-badge {{ $order->status }}">{{ $order->status }}</span></div></div>
                @empty<div class="dash-empty">Belum ada pesanan Stock Akun.</div>@endforelse
            </div>
        </article>
    </section>
</div>

@push('scripts')
<script>
(function(){
    const source = @json($trend);
    const canvas = document.getElementById('topupTrendChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let days = 14;

    function money(value){
        if (value >= 1000000000) return 'Rp' + (value / 1000000000).toFixed(1).replace('.0','') + 'M';
        if (value >= 1000000) return 'Rp' + (value / 1000000).toFixed(1).replace('.0','') + 'jt';
        if (value >= 1000) return 'Rp' + Math.round(value / 1000) + 'rb';
        return 'Rp' + Math.round(value);
    }

    function draw(){
        const data = source.slice(-days);
        const box = canvas.getBoundingClientRect();
        const ratio = Math.max(1, window.devicePixelRatio || 1);
        canvas.width = Math.round(box.width * ratio); canvas.height = Math.round(box.height * ratio);
        ctx.setTransform(ratio,0,0,ratio,0,0);
        const width = box.width, height = box.height, pad = {l:58,r:14,t:18,b:34};
        const plotW = Math.max(10,width-pad.l-pad.r), plotH = Math.max(10,height-pad.t-pad.b);
        const max = Math.max(1,...data.map(row => Number(row.revenue)||0));
        const styles = getComputedStyle(document.documentElement);
        const text = styles.getPropertyValue('--text-muted').trim() || '#94a3b8';
        const grid = styles.getPropertyValue('--border').trim() || '#e2e8f0';
        ctx.clearRect(0,0,width,height); ctx.font = '10px Poppins, sans-serif'; ctx.textBaseline='middle';
        for(let i=0;i<=4;i++){
            const y=pad.t+(plotH/4)*i; ctx.strokeStyle=grid; ctx.lineWidth=1; ctx.setLineDash([3,4]); ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(width-pad.r,y); ctx.stroke(); ctx.setLineDash([]); ctx.fillStyle=text; ctx.textAlign='right'; ctx.fillText(money(max*(1-i/4)),pad.l-8,y);
        }
        const step = plotW/Math.max(1,data.length-1); const barW=Math.max(3,Math.min(13,step*.42));
        data.forEach((row,i)=>{const x=pad.l+i*step;const ph=(Number(row.profit)||0)/max*plotH;ctx.fillStyle='#10b981';ctx.fillRect(x-barW/2,pad.t+plotH-ph,barW,ph);if(i===0||i===data.length-1||i%Math.max(1,Math.ceil(data.length/7))===0){ctx.fillStyle=text;ctx.textAlign='center';ctx.fillText(row.label,x,height-12)}});
        ctx.strokeStyle='#0ea5e9';ctx.lineWidth=2.2;ctx.lineJoin='round';ctx.lineCap='round';ctx.beginPath();
        data.forEach((row,i)=>{const x=pad.l+i*step;const y=pad.t+plotH-(Number(row.revenue)||0)/max*plotH;i?ctx.lineTo(x,y):ctx.moveTo(x,y)});ctx.stroke();
    }

    document.querySelectorAll('.dash-period').forEach(btn=>btn.addEventListener('click',function(){days=parseInt(this.dataset.days,10)||14;document.querySelectorAll('.dash-period').forEach(item=>item.classList.toggle('is-active',item===this));draw()}));
    new ResizeObserver(draw).observe(canvas.parentElement);
    new MutationObserver(draw).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});
    draw();
})();
</script>
@endpush
@endsection
