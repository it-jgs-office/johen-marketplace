@extends('layouts.topup')
@section('title', 'Joki Mobile Legends — Johen Gaming')
@push('styles')
<link rel="stylesheet" href="{{ pwa_asset('css/joki-mobile-legends.css') }}">
<link rel="preload" as="image" href="{{ $banners->first() }}">
@endpush

@section('content')
@php
  $paymentCategories = [
      'qris' => 'QRIS',
      'ewallet' => 'E-Wallet',
      'va' => 'Virtual Account / Bank',
      'convenience_store' => 'Convenience Store',
  ];
  $groupedPayments = $paymentMethods->groupBy(fn ($method) => $method->category);
  $paymentMinimum = static fn (string $category): int => in_array($category, ['qris', 'ewallet'], true) ? 1000 : 10000;
@endphp
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
              <button type="button" class="rank-option {{ $loop->first ? 'is-active' : '' }}" data-rank-option data-key="{{ $rank['key'] }}" data-label="{{ $rank['label'] }}" data-price="{{ $rank['price_per_star'] }}" data-package-id="{{ $rank['package_id'] }}" data-per-star="1" data-gendong-fee="{{ $rank['gendong_fee'] }}" data-vip-fee="{{ $rank['vip_fee'] }}">
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
        <div class="rank-calculator-field">
          <label><input type="checkbox" id="jokiGendong" disabled> Joki gendong <small id="jokiGendongFee"></small></label>
          <label><input type="checkbox" id="jokiVip" disabled> VIP <small id="jokiVipFee"></small></label>
          <div class="rank-star-input"><button type="button" data-vip-minus aria-label="Kurangi game VIP">&minus;</button><input id="jokiVipGames" type="number" min="1" max="99" value="1" inputmode="numeric" disabled><button type="button" data-vip-plus aria-label="Tambah game VIP">&plus;</button></div>
          <label><input type="checkbox" id="jokiNoHero" disabled> Tanpa request hero <small>bonus +1 star (minimal 10 star)</small></label>
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
              @php($jokiConfig = \App\Services\JokiPricingService::configFor($package))
              <button type="button" class="joki-package" data-package data-id="{{ $package->id }}" data-name="{{ $package->product_name }}" data-price="{{ (int) $package->selling_price }}" data-per-star="{{ $jokiConfig['per_star'] ?? false ? '1' : '0' }}" data-gendong-fee="{{ $jokiConfig['gendong_fee'] ?? 0 }}" data-vip-fee="{{ $jokiConfig['vip_fee'] ?? 0 }}">
                @if($package->photo_url)<img src="{{ $package->photo_url }}" alt="">@endif
                <strong>{{ $package->product_name }}</strong>
                <span class="joki-package-price">Rp {{ number_format($package->selling_price, 0, ',', '.') }}{{ ($jokiConfig['per_star'] ?? false) ? ' / star' : '' }}</span>
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
        <div class="joki-payment-head"><strong>Pilih Pembayaran</strong><span>Diproses aman melalui {{ $gatewayName }}</span></div>
        <div class="gd-pay-group gd-pay-group--cards" id="jokiPayGroup">
          @if($paymentMethods->isEmpty())
            <p class="gd-summary-empty">Metode pembayaran belum tersedia. Silakan hubungi CS.</p>
          @endif
          @foreach($paymentCategories as $categoryKey => $categoryLabel)
            @php($categoryMethods = $groupedPayments->get($categoryKey, collect()))
            @continue($categoryMethods->isEmpty())
            <div class="gd-pay-category" data-category="{{ $categoryKey }}">
              <button type="button" class="gd-pay-cat-head">
                <span class="gd-pay-cat-label">{{ $categoryLabel }}</span>
                <svg class="gd-pay-cat-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              @php($previewLogos = $categoryMethods->map(fn ($method) => payment_logo_asset($method->code))->filter()->values())
              <div class="gd-pay-cat-preview{{ $previewLogos->count() > 3 ? ' is-marquee' : '' }}" aria-hidden="true">
                <div class="gd-pay-cat-preview-track">
                  @foreach($previewLogos as $previewLogo)
                    <span class="gd-pay-cat-preview-logo"><img src="{{ $previewLogo }}" alt=""></span>
                  @endforeach
                  @if($previewLogos->count() > 3)
                    @foreach($previewLogos as $previewLogo)
                      <span class="gd-pay-cat-preview-logo"><img src="{{ $previewLogo }}" alt=""></span>
                    @endforeach
                  @endif
                </div>
              </div>
              <div class="gd-pay-cat-body">
                <div class="gd-pay-card-grid">
                  @foreach($categoryMethods as $method)
                    @php($minimum = $paymentMinimum((string) $method->category))
                    <div class="gd-pay-row" data-key="{{ $method->code }}" data-min="{{ $minimum }}" data-payment-name="{{ $method->name }}">
                      <input type="radio" name="joki_payment" value="{{ $method->code }}" hidden>
                      <button type="button" class="gd-pay-row-head">
                        <span class="gd-pay-icon">
                          @if(payment_logo_asset($method->code))
                            <img src="{{ payment_logo_asset($method->code) }}" alt="{{ $method->name }}" class="pay-badge-img">
                          @else
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                          @endif
                        </span>
                        <span class="gd-pay-label">
                          <span class="gd-pay-t">{{ $method->name }}</span>
                          <span class="gd-pay-card-amount" data-joki-pay-amount></span>
                          @if($minimum > 1000)<span class="gd-pay-min">min. Rp {{ number_format($minimum, 0, ',', '.') }}</span>@endif
                        </span>
                        <span class="gd-pay-radio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                      </button>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </section>
    </div>

    <aside class="joki-panel joki-summary">
      <h3 class="joki-summary-title">Detail Transaksi</h3>
      <div class="joki-summary-art"><img src="{{ $brand->thumbnail_url ?: pwa_asset('assets/joki/mobile-legends/ranks/mythic.png') }}" alt=""><div><span>Joki Mobile Legends</span><strong data-joki-summary="name">Belum ada paket</strong></div></div>
      <div class="joki-summary-lines"><div class="joki-summary-line"><span>Paket</span><strong data-joki-summary="package">-</strong></div><div class="joki-summary-line"><span>Opsi</span><strong data-joki-summary="options">-</strong></div><div class="joki-summary-line"><span>Metode pembayaran</span><strong data-joki-summary="payment">-</strong></div></div>
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
      <div class="joki-summary-line"><span>Opsi</span><strong data-joki-summary="options">-</strong></div>
      <div class="joki-summary-line"><span>Metode pembayaran</span><strong data-joki-summary="payment">-</strong></div>
      <div class="joki-summary-total"><span>Total</span><strong data-joki-summary="total">Rp 0</strong></div>
    </div>
  </div>
  <div class="joki-mobile-summary-bar">
    <button type="button" class="joki-mobile-summary-toggle" data-joki-summary-toggle aria-label="Buka detail transaksi" aria-expanded="false">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <div class="joki-mobile-summary-info"><span>Total Pembayaran</span><strong data-joki-summary="total">Rp 0</strong></div>
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
  const gendong=one('jokiGendong');
  const vip=one('jokiVip');
  const vipGames=one('jokiVipGames');
  const noHero=one('jokiNoHero');
  const payGroup=one('jokiPayGroup');
  let selectedRank=rankOptions[0]||null;

  const selectPaymentRow=row=>{
    if(!row||row.classList.contains('disabled'))return;
    payGroup.querySelectorAll('.gd-pay-row').forEach(item=>{item.classList.remove('selected');const radio=item.querySelector('[name="joki_payment"]');if(radio)radio.checked=false;});
    row.classList.add('selected');
    const radio=row.querySelector('[name="joki_payment"]');
    if(radio)radio.checked=true;
    setSummary('payment',row.dataset.paymentName||'-');
  };

  const syncPaymentCards=total=>{
    const rows=[...payGroup.querySelectorAll('.gd-pay-row')];
    rows.forEach(row=>{
      const amount=row.querySelector('[data-joki-pay-amount]');
      if(amount)amount.textContent=selectedPackage?money(total):'';
      row.classList.toggle('disabled',!!selectedPackage&&total<Number(row.dataset.min||0));
    });
    const selected=payGroup.querySelector('.gd-pay-row.selected');
    if(selected?.classList.contains('disabled'))selectPaymentRow(rows.find(row=>!row.classList.contains('disabled')));
  };

  const packageData=()=>selectedPackage||selectedRank?.dataset||null;
  const stars=()=>Math.max(1,Math.min(99,Number(starInput?.value)||1));
  const games=()=>Math.max(1,Math.min(99,Number(vipGames?.value)||1));

  const syncOptions=()=>{
    const data=packageData();
    const perStar=data?.perStar==='1';
    const gendongFee=Number(data?.gendongFee||0);
    const vipFee=Number(data?.vipFee||0);
    if(gendong){gendong.disabled=!perStar||!gendongFee;if(gendong.disabled)gendong.checked=false;}
    if(vip){vip.disabled=!perStar||!vipFee;if(vip.disabled)vip.checked=false;}
    if(vipGames){vipGames.disabled=!perStar||!vipFee||!vip?.checked;}
    if(noHero){noHero.disabled=!perStar||stars()<10;if(noHero.disabled)noHero.checked=false;}
    one('jokiGendongFee').textContent=gendongFee?'+'+money(gendongFee)+' / star':'';
    one('jokiVipFee').textContent=vipFee?'+'+money(vipFee)+' / game':'';
  };

  const updatePricing=()=>{
    const data=packageData();
    if(!data||!starInput)return;
    const perStar=data.perStar==='1';
    const starCount=perStar?stars():1;
    starInput.value=starCount;
    const base=Number(data.price||0)*starCount;
    const gendongCost=gendong?.checked?Number(data.gendongFee||0)*starCount:0;
    const vipCost=vip?.checked?Number(data.vipFee||0)*games():0;
    const bonus=noHero?.checked&&starCount>=10?' + bonus 1 star':'';
    const total=base+gendongCost+vipCost;
    const optionLabels=[];
    if(gendongCost)optionLabels.push('Gendong');
    if(vipCost)optionLabels.push('VIP '+games()+' game');
    if(bonus)optionLabels.push('Bonus 1 star');
    one('rankEstimate').textContent=money(total);
    one('rankEstimateDetail').textContent=(perStar?starCount+' star ':'')+(data.label||data.name||'')+' × '+money(Number(data.price||0))+bonus;
    if(selectedPackage){
      setSummary('total',money(total));
      setSummary('options',optionLabels.join(', ')||'-');
    }
    syncPaymentCards(total);
    syncOptions();
  };
  rankOptions.forEach(option=>option.addEventListener('click',function(){
    rankOptions.forEach(item=>item.classList.remove('is-active'));
    this.classList.add('is-active');selectedRank=this;updatePricing();
  }));
  document.querySelector('[data-rank-minus]')?.addEventListener('click',()=>{starInput.value=Math.max(1,(Number(starInput.value)||1)-1);updatePricing();});
  document.querySelector('[data-rank-plus]')?.addEventListener('click',()=>{starInput.value=Math.min(99,(Number(starInput.value)||1)+1);updatePricing();});
  document.querySelector('[data-vip-minus]')?.addEventListener('click',()=>{vipGames.value=Math.max(1,(Number(vipGames.value)||1)-1);updatePricing();});
  document.querySelector('[data-vip-plus]')?.addEventListener('click',()=>{vipGames.value=Math.min(99,(Number(vipGames.value)||1)+1);updatePricing();});
  starInput?.addEventListener('input',updatePricing);
  vipGames?.addEventListener('input',updatePricing);
  gendong?.addEventListener('change',updatePricing);
  vip?.addEventListener('change',updatePricing);
  noHero?.addEventListener('change',updatePricing);
  one('rankChoosePackage')?.addEventListener('click',()=>{
    if(!selectedRank)return;
    const packageButton=document.querySelector('[data-package][data-id="'+selectedRank.dataset.packageId+'"]');
    if(packageButton){packageButton.click();packageButton.scrollIntoView({behavior:'smooth',block:'center'});}
  });
  payGroup?.addEventListener('click',event=>{
    const categoryHead=event.target.closest('.gd-pay-cat-head');
    if(categoryHead){categoryHead.closest('.gd-pay-category')?.classList.toggle('open');return;}
    const paymentHead=event.target.closest('.gd-pay-row-head');
    if(paymentHead)selectPaymentRow(paymentHead.closest('.gd-pay-row'));
  });
  selectPaymentRow(payGroup?.querySelector('.gd-pay-row[data-key="qris"]')||payGroup?.querySelector('.gd-pay-row'));
  updatePricing();

  document.querySelectorAll('[data-package]').forEach(button=>button.addEventListener('click',function(){
    document.querySelectorAll('[data-package]').forEach(item=>item.classList.remove('is-selected'));
    this.classList.add('is-selected');
    selectedPackage={...this.dataset};
    setSummary('name',selectedPackage.name);
    setSummary('package',selectedPackage.name);
    updatePricing();
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
    const isPerStar=selectedPackage.perStar==='1';
    body.append('product_id',selectedPackage.id);body.append('quantity',isPerStar?String(stars()):'1');
    body.append('customer_number',one('jokiUserId').value.trim());body.append('zone_id',one('jokiZoneId').value.trim());
    body.append('joki_login_method',one('jokiLoginMethod').value);body.append('joki_login_identifier',one('jokiLoginId').value.trim());
    body.append('joki_login_password',one('jokiPassword').value);body.append('joki_notes',one('jokiNotes').value.trim());
    body.append('joki_stars',isPerStar?String(stars()):'1');body.append('joki_gendong',gendong?.checked?'1':'0');
    body.append('joki_vip_games',vip?.checked?String(games()):'0');body.append('joki_no_hero_request',noHero?.checked?'1':'0');
    body.append('email',one('jokiEmail').value.trim());body.append('phone',one('jokiPhone').value.trim());body.append('payment_method',payment.value);
    try{const response=await fetch(@json(route('orders.store')),{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body});const data=await response.json();if(response.ok&&data.redirect){location.href=data.redirect;return;}showToast(data.message||Object.values(data.errors||{}).flat()[0]||'Pesanan gagal dibuat',false);}catch(error){showToast('Pesanan gagal dibuat. Coba lagi.',false);}this.disabled=false;this.textContent=original;
  }));
})();
</script>
@endpush
