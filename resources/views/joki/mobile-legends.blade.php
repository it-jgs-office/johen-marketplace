@extends('layouts.topup')
@section('title', 'Joki Mobile Legends — Johen Gaming')
@push('styles')
<link rel="stylesheet" href="{{ pwa_asset('css/joki-mobile-legends.css') }}">
<link rel="preload" as="image" href="{{ $banners->first() }}">
@endpush

@section('content')
<main class="joki-page">
  <section class="hero-section hero-peek-carousel joki-hero-carousel" id="joki" aria-label="Banner Joki Mobile Legends">
    <div class="hero-banner-track{{ $banners->count() === 1 ? ' is-single' : '' }}" data-banners='@json($banners->all())'>
      <button type="button" class="hero-banner-card hero-banner-card-prev" data-banner-slot="prev" aria-label="Lihat banner sebelumnya">
        <img src="{{ $banners->get($banners->count() > 1 ? $banners->count() - 1 : 0) }}" alt="Banner Joki Mobile Legends sebelumnya" loading="lazy" fetchpriority="low" decoding="async">
      </button>
      <button type="button" class="hero-banner-card hero-banner-card-active" data-banner-slot="active" aria-live="polite" tabindex="-1">
        <img src="{{ $banners->first() }}" alt="Banner Joki Mobile Legends 1" loading="eager" fetchpriority="high" decoding="async">
      </button>
      <button type="button" class="hero-banner-card hero-banner-card-next" data-banner-slot="next" aria-label="Lihat banner selanjutnya">
        <img src="{{ $banners->get(1, $banners->first()) }}" alt="Banner Joki Mobile Legends selanjutnya" loading="lazy" fetchpriority="low" decoding="async">
      </button>
    </div>
    @if($banners->count() > 1)
      <div class="hero-banner-dots" data-banner-dots aria-label="Pilih banner"></div>
      <button class="hero-arrow hero-arrow-left" data-banner-prev aria-label="Sebelumnya">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="hero-arrow hero-arrow-right" data-banner-next aria-label="Selanjutnya">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    @endif
  </section>

  <section class="rank-calculator" data-rank-calculator>
    <div class="rank-calculator-head">
      <span>BotakStore Boost</span>
      <h1>Rank Boost Calculator</h1>
      <p>Hitung estimasi biaya berdasarkan rank dan jumlah star yang dibutuhkan.</p>
    </div>
    @if($rankCalculator->isEmpty())
      <div class="joki-empty">Kalkulator belum tersedia karena paket joki belum aktif.</div>
    @else
      <div class="rank-calculator-body">
        <div class="rank-calculator-field is-rank">
          <label>Pilih rank</label>
          <div class="rank-options">
            @foreach($rankCalculator as $rank)
              <button type="button" class="rank-option {{ $loop->first ? 'is-active' : '' }}" data-rank-option data-key="{{ $rank['key'] }}" data-label="{{ $rank['label'] }}" data-price="{{ $rank['price_per_star'] }}" data-package-id="{{ $rank['package_id'] }}">
                <img src="{{ $rank['icon'] }}" alt="">
                <span>{{ $rank['label'] }}</span>
              </button>
            @endforeach
          </div>
        </div>
        <div class="rank-calculator-field">
          <label for="rankStarAmount">Jumlah star</label>
          <div class="rank-star-input">
            <button type="button" data-rank-minus aria-label="Kurangi star">&minus;</button>
            <input id="rankStarAmount" type="number" min="1" max="1000" value="10" inputmode="numeric">
            <button type="button" data-rank-plus aria-label="Tambah star">&plus;</button>
          </div>
        </div>
        <div class="rank-calculator-result">
          <span>Estimasi biaya</span>
          <strong id="rankEstimate">Rp 0</strong>
          <small id="rankEstimateDetail">-</small>
          <button type="button" id="rankChoosePackage">Pilih Paket Rank Ini</button>
        </div>
      </div>
    @endif
  </section>

  <div class="joki-layout">
    <div class="joki-main">
      <section class="joki-panel">
        <div class="joki-panel-head"><span class="joki-step">1</span><div><h2>Pilih paket joki</h2><p>Paket aktif diatur langsung dari halaman admin.</p></div></div>
        @if($packages->isEmpty())
          <div class="joki-empty">Paket joki sedang belum tersedia.</div>
        @else
          <div class="joki-packages">
            @foreach($packages as $package)
              <button type="button" class="joki-package" data-package data-id="{{ $package->id }}" data-name="{{ $package->product_name }}" data-price="{{ (int) $package->selling_price }}">
                @if($package->photo_url)<img src="{{ $package->photo_url }}" alt="">@endif
                <strong>{{ $package->product_name }}</strong>
                <span class="joki-package-price">Rp {{ number_format($package->selling_price, 0, ',', '.') }}</span>
                <span class="joki-package-time">Estimasi {{ $package->estimated_duration ?: 'menyesuaikan antrean' }}</span>
              </button>
            @endforeach
          </div>
        @endif
      </section>

      <section class="joki-panel">
        <div class="joki-panel-head"><span class="joki-step">2</span><div><h2>Data akun game</h2><p>Pastikan data benar agar pengerjaan tidak tertunda.</p></div></div>
        <div class="joki-grid">
          <div class="joki-field"><label for="jokiUserId">User ID</label><input id="jokiUserId" inputmode="numeric" placeholder="123456789"></div>
          <div class="joki-field"><label for="jokiZoneId">Server ID</label><input id="jokiZoneId" inputmode="numeric" placeholder="1234"></div>
          <div class="joki-field"><label for="jokiLoginMethod">Metode login</label><select id="jokiLoginMethod"><option value="">Pilih metode</option><option value="moonton">Moonton</option><option value="facebook">Facebook</option><option value="google">Google</option><option value="tiktok">TikTok</option><option value="vk">VK</option></select></div>
          <div class="joki-field"><label for="jokiLoginId">Email / nomor login</label><input id="jokiLoginId" autocomplete="off" placeholder="Data untuk login akun"></div>
          <div class="joki-field is-wide"><label for="jokiPassword">Password akun</label><input id="jokiPassword" type="password" autocomplete="new-password" placeholder="Masukkan password akun"></div>
          <div class="joki-field is-wide"><label for="jokiNotes">Catatan untuk penjoki (opsional)</label><textarea id="jokiNotes" placeholder="Contoh: gunakan role Gold Lane, jangan ubah pengaturan akun"></textarea></div>
        </div>
        <p class="joki-security">Data login disimpan terenkripsi dan hanya dapat dilihat admin untuk mengerjakan pesanan. Jangan login saat proses joki berlangsung.</p>
      </section>

      <section class="joki-panel">
        <div class="joki-panel-head"><span class="joki-step">3</span><div><h2>Kontak dan pembayaran</h2><p>Invoice dan pembaruan pesanan dikirim ke kontak ini.</p></div></div>
        <div class="joki-grid" style="margin-bottom:1rem">
          <div class="joki-field"><label for="jokiEmail">Email</label><input id="jokiEmail" type="email" value="{{ auth()->user()?->email }}" placeholder="nama@email.com"></div>
          <div class="joki-field"><label for="jokiPhone">WhatsApp</label><input id="jokiPhone" type="tel" placeholder="0812xxxxxxxx"></div>
        </div>
        <div class="joki-payments">
          @foreach($paymentMethods as $method)
            <label class="joki-payment"><input type="radio" name="joki_payment" value="{{ $method->code }}">@if(payment_logo_asset($method->code))<img src="{{ payment_logo_asset($method->code) }}" alt="">@endif<span>{{ $method->name }}</span></label>
          @endforeach
        </div>
      </section>
    </div>

    <aside class="joki-panel joki-summary">
      <h3 class="joki-summary-title">Detail Transaksi</h3>
      <div class="joki-summary-art"><img src="{{ $brand->thumbnail_url ?: pwa_asset('assets/joki/mobile-legends/ranks/mythic.png') }}" alt=""><div><span>Joki Mobile Legends</span><strong data-joki-summary="name">Belum ada paket</strong></div></div>
      <div class="joki-summary-lines"><div class="joki-summary-line"><span>Paket</span><strong data-joki-summary="package">-</strong></div><div class="joki-summary-line"><span>Metode pembayaran</span><strong data-joki-summary="payment">-</strong></div></div>
      <div class="joki-summary-total"><span>Total</span><strong data-joki-summary="total">Rp 0</strong></div>
      <button type="button" class="joki-order-btn" data-joki-order {{ $packages->isEmpty() ? 'disabled' : '' }}>Buat Pesanan Joki</button>
    </aside>
  </div>
</main>

<div class="joki-mobile-summary" id="jokiMobileSummary">
  <div class="joki-mobile-summary-panel">
    <div class="joki-mobile-summary-panel-inner">
      <h3>Detail Transaksi</h3>
      <div class="joki-summary-line"><span>Paket</span><strong data-joki-summary="package">-</strong></div>
      <div class="joki-summary-line"><span>Metode pembayaran</span><strong data-joki-summary="payment">-</strong></div>
      <div class="joki-summary-total"><span>Total</span><strong data-joki-summary="total">Rp 0</strong></div>
    </div>
  </div>
  <div class="joki-mobile-summary-bar">
    <button type="button" class="joki-mobile-summary-toggle" data-joki-summary-toggle aria-label="Buka detail transaksi" aria-expanded="false">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <div class="joki-mobile-summary-info"><span>Total Pembayaran</span><strong data-joki-summary="total">Rp 0</strong></div>
    <a href="{{ route('cart.index') }}" class="joki-mobile-cart-btn" aria-label="Buka keranjang">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    </a>
    <button type="button" class="joki-mobile-order-btn" data-joki-order {{ $packages->isEmpty() ? 'disabled' : '' }}>Pesan Sekarang</button>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  let selectedPackage=null;
  const money=value=>'Rp '+Number(value||0).toLocaleString('id-ID');
  const one=id=>document.getElementById(id);
  const setSummary=(key,value)=>document.querySelectorAll('[data-joki-summary="'+key+'"]').forEach(item=>item.textContent=value);
  const rankOptions=[...document.querySelectorAll('[data-rank-option]')];
  const starInput=one('rankStarAmount');
  let selectedRank=rankOptions[0]||null;
  const updateRankEstimate=()=>{
    if(!selectedRank||!starInput)return;
    const stars=Math.max(1,Math.min(1000,Number(starInput.value)||1));
    starInput.value=stars;
    one('rankEstimate').textContent=money(Number(selectedRank.dataset.price)*stars);
    one('rankEstimateDetail').textContent=stars+' star '+selectedRank.dataset.label+' × '+money(selectedRank.dataset.price);
  };
  rankOptions.forEach(option=>option.addEventListener('click',function(){
    rankOptions.forEach(item=>item.classList.remove('is-active'));
    this.classList.add('is-active');selectedRank=this;updateRankEstimate();
  }));
  document.querySelector('[data-rank-minus]')?.addEventListener('click',()=>{starInput.value=Math.max(1,(Number(starInput.value)||1)-1);updateRankEstimate();});
  document.querySelector('[data-rank-plus]')?.addEventListener('click',()=>{starInput.value=Math.min(1000,(Number(starInput.value)||1)+1);updateRankEstimate();});
  starInput?.addEventListener('input',updateRankEstimate);
  one('rankChoosePackage')?.addEventListener('click',()=>{
    if(!selectedRank)return;
    const packageButton=document.querySelector('[data-package][data-id="'+selectedRank.dataset.packageId+'"]');
    if(packageButton){packageButton.click();packageButton.scrollIntoView({behavior:'smooth',block:'center'});}
  });
  updateRankEstimate();

  document.querySelectorAll('[data-package]').forEach(button=>button.addEventListener('click',function(){
    document.querySelectorAll('[data-package]').forEach(item=>item.classList.remove('is-selected'));
    this.classList.add('is-selected');
    selectedPackage={id:this.dataset.id,name:this.dataset.name,price:Number(this.dataset.price)};
    setSummary('name',selectedPackage.name);
    setSummary('package',selectedPackage.name);
    setSummary('total',money(selectedPackage.price));
  }));
  document.querySelectorAll('[name="joki_payment"]').forEach(input=>input.addEventListener('change',function(){
    setSummary('payment',this.closest('label').querySelector('span').textContent);
  }));
  document.querySelector('[data-joki-summary-toggle]')?.addEventListener('click',function(){
    const summary=one('jokiMobileSummary');
    summary.classList.toggle('is-open');
    const isOpen=summary.classList.contains('is-open');
    document.body.classList.toggle('joki-summary-open',isOpen);
    this.setAttribute('aria-expanded',isOpen?'true':'false');
    this.setAttribute('aria-label',isOpen?'Tutup detail transaksi':'Buka detail transaksi');
  });
  document.querySelectorAll('[data-joki-order]').forEach(button=>button.addEventListener('click',async function(){
    const required=[['jokiUserId','User ID'],['jokiZoneId','Server ID'],['jokiLoginMethod','metode login'],['jokiLoginId','data login'],['jokiPassword','password'],['jokiEmail','email']];
    if(!selectedPackage){showToast('Pilih paket joki terlebih dahulu',false);return;}
    for(const [id,label] of required){if(!one(id).value.trim()){showToast('Lengkapi '+label,false);one(id).focus();return;}}
    const payment=document.querySelector('[name="joki_payment"]:checked');
    if(!payment){showToast('Pilih metode pembayaran',false);return;}
    const original=this.textContent;this.disabled=true;this.textContent='Memproses...';
    const body=new FormData();
    body.append('_token',document.querySelector('meta[name="csrf-token"]').content);
    body.append('product_id',selectedPackage.id);body.append('quantity','1');
    body.append('customer_number',one('jokiUserId').value.trim());body.append('zone_id',one('jokiZoneId').value.trim());
    body.append('joki_login_method',one('jokiLoginMethod').value);body.append('joki_login_identifier',one('jokiLoginId').value.trim());
    body.append('joki_login_password',one('jokiPassword').value);body.append('joki_notes',one('jokiNotes').value.trim());
    body.append('email',one('jokiEmail').value.trim());body.append('phone',one('jokiPhone').value.trim());body.append('payment_method',payment.value);
    try{const response=await fetch(@json(route('orders.store')),{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body});const data=await response.json();if(response.ok&&data.redirect){location.href=data.redirect;return;}showToast(data.message||Object.values(data.errors||{}).flat()[0]||'Pesanan gagal dibuat',false);}catch(error){showToast('Pesanan gagal dibuat. Coba lagi.',false);}this.disabled=false;this.textContent=original;
  }));
})();
</script>
@endpush
