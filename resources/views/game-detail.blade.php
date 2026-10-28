@extends('layouts.topup')

@php
  $pageType = $brand->service_type === 'joki' ? 'Joki' : 'Top Up';
  $brandName = mb_strtolower($brand->name);
  $isValorant = str_starts_with($brandName, 'valorant');
  $usesServerId = $isMobileLegends || str_starts_with($brandName, 'magic chess');
  $defaultDetailBackground = pwa_asset('assets/backround/default-sampul.png');
  $detailBackground = $brand->detail_bg_url ?: $defaultDetailBackground;
  $detailBackgroundPosition = $brand->detail_bg_url ? ($brand->detail_bg_position ?: 'center') : 'center';
@endphp
@section('title', $brand->name . ' — ' . $pageType . ' ' . $brand->name)

@push('styles')
@if($detailBackground)
<link rel="preload" as="image" href="{{ $detailBackground }}" fetchpriority="high">
@endif
<style>
.gd-header-wrap .gd-header-inner {
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.gd-header-wrap .gd-header-meta {
  flex: 0 1 auto;
  width: 100%;
  text-align: center;
}
</style>
@endpush

@php
  [$specialItems, $regularProducts] = $products->partition(fn ($product) =>
      preg_match('/^(spesial|special)/iu', trim((string) $product->buyer_sku_code)) === 1
      || in_array(mb_strtolower(trim((string) $product->type)), ['special items', 'spesial items'], true)
  );
  $grouped = $regularProducts->groupBy('type');
  $regions = collect();
  $itemGroups = collect();

  if ($specialItems->isNotEmpty()) {
      $itemGroups->put('Spesial Items', $specialItems);
  }

  foreach (['First Topup (Double Diamonds)'] as $specialType) {
      if ($grouped->has($specialType)) {
          $itemGroups->put($specialType, $grouped->get($specialType));
          $grouped->forget($specialType);
      }
  }

  $instantKey = null;
  foreach (['instant', 'Instant'] as $key) {
      if ($grouped->has($key)) { $instantKey = $key; break; }
  }
  if ($instantKey) {
      $regions = $grouped->get($instantKey)->groupBy('region');
  }
  $selectedRegion = $regions->isNotEmpty() ? 'ID' : null;
  $firstProduct = $products->first();
  $topupGameIcon = topup_game_icon_asset($brand->name);
  $productIconAsset = static fn ($product): ?string => topup_product_icon_asset(
      $brand->name,
      $product->product_name,
      $product->type
  ) ?: $topupGameIcon;
  $productDisplayName = static function (?string $name) use ($brand): string {
      $originalName = trim((string) $name);
      $brandPrefix = preg_quote(trim((string) $brand->name), '/');
      $cleanName = preg_replace('/^' . $brandPrefix . '(?:\s*[-:|]\s*|\s+)/iu', '', $originalName, 1);

      return trim((string) $cleanName) !== '' ? trim((string) $cleanName) : $originalName;
  };
  // Mapping eksplisit channel code -> kategori. Tidak bergantung pada kolom
  // `category` di DB (jika data hosting kategori-nya kosong/salah, grouping
  // tetap benar). Daftar ini hanya berisi channel Midtrans yang lolos filter gateway.
  $channelCategory = function (string $code): string {
      $code = strtolower($code);
      if (in_array($code, ['qris'], true)) return 'qris';
      if ($code === 'akulaku') return 'paylater';
      if (str_ends_with($code, '_va')) return 'va';
      if (in_array($code, ['alfamart', 'indomaret'], true)) return 'convenience_store';
      return 'ewallet';
  };
  // Minimal nominal per kategori channel Midtrans (IDR).
  $channelMinAmount = function (string $cat): int {
      return match ($cat) {
          'qris', 'ewallet' => 1000,
          default => 10000,
      };
  };
  $payData = $paymentMethods->map(function ($m) use ($channelCategory, $channelMinAmount) {
      $cat = $channelCategory($m->code);

      return [
          'key' => $m->code,
          'title' => $m->name,
          'category' => $cat,
          'photo' => payment_logo_asset($m->code),
          'provider' => $m->gateway_provider,
          'gateway_code' => $m->gateway_code,
          'fee' => 0,
          'min_amount' => $channelMinAmount($cat),
      ];
  })->values();
  $categories = [
      'qris' => 'QRIS',
      'ewallet' => 'E-Wallet',
      'paylater' => 'PayLater',
      'va' => 'Virtual Account / Bank',
      'convenience_store' => 'Convenience Store',
  ];
  $groupedPay = $paymentMethods->groupBy(fn($m) => $channelCategory($m->code));
@endphp

@section('content')
<!-- ===== BANNER (background only) ===== -->
<section class="game-hero">
  <div class="game-hero-bg"@if($detailBackground) style="background-image:url('{{ $detailBackground }}');background-position:{{ $detailBackgroundPosition }}"@endif></div>
</section>

<!-- ===== THUMBNAIL (overlap banner + content) ===== -->
<div class="gd-header-wrap">
  <div class="gd-header-inner">
    <div class="gd-thumb">
      <div class="gd-thumb-surface">
      @if($brand->thumbnail_url)
        <img src="{{ $brand->thumbnail_url }}" alt="{{ $brand->name }}">
      @else
        <span style="font-size:2.5rem">{{ $brand->icon ?? '🎮' }}</span>
      @endif
      </div>
    </div>
    <div class="gd-header-meta">
      <h1>{{ $brand->name }}</h1>
      @if($brand->category)
        <span class="gd-header-cat">{{ ucfirst($brand->category) }}</span>
      @endif
    </div>
  </div>
  <div class="gd-header-badges">
    <span class="gd-header-badge">
      <img src="{{ asset('assets/icon/lightning.gif') }}" alt="" aria-hidden="true">
      Proses Cepat
    </span>
    <span class="gd-header-badge">
      <img src="{{ asset('assets/icon/contact-support.gif') }}" alt="" aria-hidden="true">
      Layanan 24/7
    </span>
    <span class="gd-header-badge">
      <img src="{{ asset('assets/icon/secure.gif') }}" alt="" aria-hidden="true">
      Transaksi Aman
    </span>
  </div>
</div>

<div class="gd-wrap">
  @if($products->isEmpty())
  <div class="gd-empty-catalog" style="max-width:640px;margin:3rem auto;padding:2.5rem 2rem;text-align:center;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md)">
    <h2 style="font-size:1.25rem;font-weight:700;margin:0 0 .75rem">Nominal {{ $pageType }} {{ $brand->name }} Belum Tersedia</h2>
    <p>
      Katalog untuk {{ $brand->name }} sedang kami lengkapi. Silakan hubungi Customer Service
      untuk ketersediaan produk, harga, dan estimasi proses top up.
    </p>
    <div class="gd-empty-actions" style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem">
      <a class="btn btn-solid" href="{{ route('kontak') }}">Hubungi Customer Service</a>
      <a class="btn btn-outline" href="{{ route('jual-beli-akun') }}">Lihat Stock Akun</a>
    </div>
  </div>
  @else
  <div class="gd-detail-grid">
    <div class="gd-detail-main">
  <!-- ===== STEP 1: Data Akun ===== -->
  <div class="gd-step" id="accountDataStep">
    <div class="gd-step-head"><div class="gd-step-num">1</div><div class="gd-step-title">Masukan Data Akun</div></div>
    <div class="gd-step1-grid">
      <div class="gd-step1-left">
        <div class="gd-card">
          <div class="gd-field-row">
            <div class="gd-field">
              <label for="userId">{{ $isValorant ? 'Riot ID' : 'User ID' }}</label>
              <input type="text" id="userId" placeholder="{{ $isValorant ? 'NamaPemain#TAG' : '12345678' }}" autocomplete="off" @if($isValorant) inputmode="text" autocapitalize="off" @endif aria-describedby="accountLockNotice">
              <div class="gd-field-error" id="userIdError">{{ $isValorant ? 'Riot ID wajib diisi dengan format NamaPemain#TAG.' : 'User ID wajib diisi.' }}</div>
            </div>
            @if($brand->requires_zone_id || $usesServerId)
            <div class="gd-field">
              <label for="zoneId">{{ $usesServerId ? 'Server ID' : 'Zone ID' }}</label>
              <input type="text" id="zoneId" placeholder="1234" autocomplete="off" aria-describedby="accountLockNotice">
              <div class="gd-field-error" id="zoneIdError">{{ $usesServerId ? 'Server ID wajib diisi.' : 'Zone ID wajib diisi.' }}</div>
            </div>
            @endif
          </div>
          <div class="gd-field-ok gd-account-result" id="userIdOk" role="status" aria-live="polite"></div>
          <p class="gd-account-lock-notice" id="accountLockNotice" role="alert" aria-live="assertive" hidden></p>
          @if($isValorant)
            <p class="gd-field-hint">Masukkan Riot ID lengkap beserta tagline, contoh: JohenGaming#ID1.</p>
          @endif
        </div>

      </div>
    </div>
  </div>

  <!-- ===== STEP 2: Pilih Nominal ===== -->
  <div>
  <div class="gd-step">
    <div class="gd-step-head"><div class="gd-step-num">2</div><div class="gd-step-title">Pilih Nominal</div></div>

    {{-- Item type groups (Spesial Items, First Topup, etc.) --}}
    @foreach($itemGroups as $itemType => $typeItems)
      <div class="gd-group">
        <div class="gd-group-head">
          <div class="gd-group-title">
            {{ $itemType }}
            @if($itemType !== 'Spesial Items')
              <span class="gd-spark">✨</span>
            @endif
          </div>
        </div>
        <div class="gd-pkg-grid gd-pkg-instant" data-type="{{ Str::slug($itemType) }}" data-no-region-filter="true">
          @foreach($typeItems as $p)
            @php
              $deal = $flashDeals->get($p->id);
              $productIcon = $productIconAsset($p);
            @endphp
            <a href="{{ route('orders.create', $p->id) }}"
              class="gd-pkg-card{{ $deal ? ' gd-pkg-card-flash' : '' }}"
              data-id="{{ $p->id }}"
              data-label="{{ $productDisplayName($p->product_name) }}"
              data-price="{{ $deal ? $deal->flash_price : $p->selling_price }}"
              data-region="{{ $p->region ?: 'ALL' }}">
              @if($deal)
                <span class="gd-pkg-flash"><svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> FLASH -{{ rtrim(rtrim(number_format($deal->discount_percent, 0), '0'), ',') }}%</span>
              @endif
              <span class="gd-pkg-main">
              @if($productIcon)
                <img class="gd-pkg-img" src="{{ $productIcon }}" alt="{{ $p->product_name }}">
              @elseif($p->photo_url)
                <img class="gd-pkg-img" src="{{ $p->photo_url }}" alt="{{ $p->product_name }}">
              @else
                <svg class="gd-gem" viewBox="0 0 32 32" width="30" height="30">
                  <defs><linearGradient id="gemGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="var(--purple-light)"/><stop offset="100%" stop-color="var(--purple-dark)"/></linearGradient></defs>
                  <polygon points="16,2 27,11 22,30 10,30 5,11" fill="url(#gemGrad)"/>
                  <polygon points="16,2 27,11 16,14" fill="#e4d9ff" opacity=".55"/>
                  <polygon points="16,2 5,11 16,14" fill="#f4eeff" opacity=".8"/>
                  <polygon points="5,11 16,14 10,30" fill="#6d33d6" opacity=".7"/>
                  <polygon points="27,11 16,14 22,30" fill="#4c1d95" opacity=".75"/>
                </svg>
              @endif
              <span class="gd-pkg-info">
                <span class="gd-pkg-amt">{{ $productDisplayName($p->product_name) }}</span>
                @if($deal)
                  <span class="gd-pkg-stock"><svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> Sisa {{ $deal->stock }}</span>
                @endif
              </span>
              <span class="gd-pkg-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
              </span>
              <span class="gd-pkg-footer">
                <span class="gd-pkg-price">
                  @if($deal)
                    <span class="gd-pkg-price-old">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</span>
                  @endif
                  Rp {{ number_format($deal ? $deal->flash_price : $p->selling_price, 0, ',', '.') }}
                </span>
                <span class="gd-pkg-delivery"><img src="{{ asset('assets/icon/pengiriman-cepat.png') }}" alt="Pengiriman cepat"></span>
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endforeach

    {{-- Default type groups (region-based for instant, plain for joki) --}}
    @foreach($grouped as $type => $items)
      @if($items->isEmpty()) @continue @endif
      @php
        $typeKey = Str::slug($type);
        $isInstant = strtolower($type) === 'instant';
      @endphp
      <div class="gd-group">
        <div class="gd-group-head">
          <div class="gd-group-title">{{ $isInstant ? 'Topup Instant' : ucwords($type) }}</div>
          @if($isInstant && $regions->count() > 1)
            <div class="gd-region-tabs" data-group="{{ $typeKey }}">
              <button class="gd-region-btn{{ $selectedRegion === 'ID' ? ' active' : '' }}" data-region="ID">Indonesia</button>
              <button class="gd-region-btn{{ $selectedRegion === 'MY' ? ' active' : '' }}" data-region="MY">Malaysia</button>
              <button class="gd-region-btn{{ $selectedRegion === 'PH' ? ' active' : '' }}" data-region="PH">Philippines</button>
            </div>
          @endif
        </div>
        <div class="gd-pkg-grid{{ $isInstant ? ' gd-pkg-instant' : '' }}"
             data-type="{{ $typeKey }}"
             @if($isInstant) data-has-regions="true"@endif>
          @foreach($items as $p)
            @php
              $deal = $flashDeals->get($p->id);
              $productIcon = $productIconAsset($p);
            @endphp
            <a href="{{ route('orders.create', $p->id) }}"
              class="gd-pkg-card{{ $deal ? ' gd-pkg-card-flash' : '' }}"
                data-id="{{ $p->id }}"
                data-label="{{ $productDisplayName($p->product_name) }}"
              data-price="{{ $deal ? $deal->flash_price : $p->selling_price }}"
              @if($isInstant) data-region="{{ $p->region ?: 'ALL' }}"@endif>
              @if($deal)
                <span class="gd-pkg-flash"><svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> FLASH -{{ rtrim(rtrim(number_format($deal->discount_percent, 0), '0'), ',') }}%</span>
              @endif
              <span class="gd-pkg-main">
              @if($productIcon)
                <img class="gd-pkg-img" src="{{ $productIcon }}" alt="{{ $p->product_name }}">
              @elseif($p->photo_url)
                <img class="gd-pkg-img" src="{{ $p->photo_url }}" alt="{{ $p->product_name }}">
              @else
                <svg class="gd-gem" viewBox="0 0 32 32" width="30" height="30">
                  <defs><linearGradient id="gemGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="var(--purple-light)"/><stop offset="100%" stop-color="var(--purple-dark)"/></linearGradient></defs>
                  <polygon points="16,2 27,11 22,30 10,30 5,11" fill="url(#gemGrad)"/>
                  <polygon points="16,2 27,11 16,14" fill="#e4d9ff" opacity=".55"/>
                  <polygon points="16,2 5,11 16,14" fill="#f4eeff" opacity=".8"/>
                  <polygon points="5,11 16,14 10,30" fill="#6d33d6" opacity=".7"/>
                  <polygon points="27,11 16,14 22,30" fill="#4c1d95" opacity=".75"/>
                </svg>
              @endif
              <span class="gd-pkg-info">
                <span class="gd-pkg-amt">{{ $productDisplayName($p->product_name) }}</span>
                @if($deal)
                  <span class="gd-pkg-stock"><svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> Sisa {{ $deal->stock }}</span>
                @endif
              </span>
              <span class="gd-pkg-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
              </span>
              <span class="gd-pkg-footer">
                <span class="gd-pkg-price">
                  @if($deal)
                    <span class="gd-pkg-price-old">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</span>
                  @endif
                  Rp {{ number_format($deal ? $deal->flash_price : $p->selling_price, 0, ',', '.') }}
                </span>
                <span class="gd-pkg-delivery"><img src="{{ asset('assets/icon/pengiriman-cepat.png') }}" alt="Pengiriman cepat"></span>
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
  </div>

  <div class="gd-step-pair">
  <!-- ===== STEP 3: Quantity ===== -->
  <div class="gd-step" id="quantityStep">
    <div class="gd-step-head"><div class="gd-step-num">3</div><div class="gd-step-title">Masukan Jumlah Pembelian</div></div>
    <div class="gd-card gd-quantity-card">
      <div class="gd-qty-row">
      <button class="gd-qty-btn" id="qtyMinus">–</button>
      <input type="text" id="qtyInput" value="1" inputmode="numeric">
      <button class="gd-qty-btn gd-qty-plus" id="qtyPlus">+</button>
      </div>
    </div>
  </div>

  <!-- ===== STEP 4: Promo ===== -->
  <div class="gd-step">
    <div class="gd-step-head"><div class="gd-step-num">4</div><div class="gd-step-title">Kode Promo / Voucher</div></div>
    <div class="gd-card">
      <div class="gd-field">
        <div class="gd-promo-row">
          <input type="text" id="promoInput" placeholder="JHN-XXXX-XXXX">
          <button class="btn btn-solid" id="promoBtn" type="button">Terapkan</button>
        </div>
      </div>
      <p class="gd-promo-msg" id="promoMsg"></p>
    </div>
  </div>
  </div>

  <!-- ===== STEP 5: Pembayaran ===== -->
  <div class="gd-step" id="paymentStep">
    <div class="gd-step-head"><div class="gd-step-num">5</div><div><div class="gd-step-title">Pilih Pembayaran</div><div class="gd-step-subtitle">Diproses aman melalui {{ $gatewayName }}</div></div></div>
    <div class="gd-pay-group gd-pay-group--cards" id="payGroup">
      @if($paymentMethods->isEmpty())
        <p class="gd-summary-empty">Metode pembayaran belum tersedia. Silakan hubungi CS.</p>
      @endif
      @foreach($categories as $catKey => $catLabel)
        @php $catMethods = $groupedPay->get($catKey, collect()); @endphp
        @continue($catMethods->isEmpty())
        <div class="gd-pay-category" data-category="{{ $catKey }}">
          <button type="button" class="gd-pay-cat-head">
            <span class="gd-pay-cat-label">{{ $catLabel }}</span>
            <svg class="gd-pay-cat-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          @php($previewLogos = $catMethods->map(fn($pm) => payment_logo_asset($pm->code))->filter()->values())
          <div class="gd-pay-cat-preview{{ $previewLogos->count() > 3 ? ' is-marquee' : '' }}" aria-hidden="true">
            <div class="gd-pay-cat-preview-track">
              @foreach($previewLogos as $previewLogo)
                <span class="gd-pay-cat-preview-logo">
                  <img src="{{ $previewLogo }}" alt="">
                </span>
              @endforeach
              @if($previewLogos->count() > 3)
                @foreach($previewLogos as $previewLogo)
                  <span class="gd-pay-cat-preview-logo">
                    <img src="{{ $previewLogo }}" alt="">
                  </span>
                @endforeach
              @endif
            </div>
          </div>
          <div class="gd-pay-cat-body">
            <div class="gd-pay-card-grid">
            @foreach($catMethods as $pm)
              <div class="gd-pay-row" data-key="{{ $pm->code }}" data-category="{{ $catKey }}" data-provider="{{ $pm->gateway_provider }}" data-gateway-code="{{ $pm->gateway_code }}">
                <button type="button" class="gd-pay-row-head">
                  <span class="gd-pay-icon">
                    @if(payment_logo_asset($pm->code))
                      <img src="{{ payment_logo_asset($pm->code) }}" alt="{{ $pm->name }}" class="pay-badge-img">
                    @else
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    @endif
                  </span>
                  <span class="gd-pay-label">
                    <span class="gd-pay-t">{{ $pm->name }}</span>
                    <span class="gd-pay-card-amount" data-pay-amount></span>
                    @php($pmMin = $channelMinAmount($channelCategory($pm->code)))
                    @if($pmMin > 1000)
                      <span class="gd-pay-min">min. Rp {{ number_format($pmMin, 0, ',', '.') }}</span>
                    @endif
                  </span>
                  <span class="gd-pay-radio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                </button>
              </div>
            @endforeach
            </div>
          </div>
        </div>
      @endforeach
      @auth
        <div class="gd-pay-points">
          <label for="usePointsInput" class="gd-pay-points-main">
            <span class="gd-pay-icon gd-pay-points-icon"><x-johen-point-icon size="30" /></span>
            <span class="gd-pay-points-copy">
              <span class="gd-pay-points-name">Gunakan Johen Point</span>
              <span class="gd-pay-points-balance">Potong total pembayaran dengan point</span>
            </span>
          </label>
          <div class="gd-pay-points-control">
            <label for="pointAmountInput">Jumlah digunakan</label>
            <div class="gd-pay-points-input-wrap">
              <input id="pointAmountInput" class="gd-pay-points-amount" type="number" value="{{ (int) $pointBalance }}" min="1" max="{{ (int) $pointBalance }}" aria-label="Jumlah Johen Point" {{ $pointBalance < 1 ? 'disabled' : '' }}>
              <span>JP</span>
            </div>
          </div>
          <label for="usePointsInput" class="gd-pay-points-choice">
            <input id="usePointsInput" class="gd-pay-points-checkbox" type="checkbox" {{ $pointBalance < 1 ? 'disabled' : '' }}>
            <span class="gd-pay-points-action">Maks. {{ number_format($pointBalance, 0, ',', '.') }} JP</span>
            <span class="gd-pay-radio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
          </label>
        </div>
      @endauth
    </div>
  </div>

  <!-- ===== STEP 6: Kontak ===== -->
  <div class="gd-step" id="contactStep">
    <div class="gd-step-head"><div class="gd-step-num">6</div><div class="gd-step-title">Detail Kontak</div></div>
    <div class="gd-card">
      <div class="gd-contact-grid">
        <div class="gd-field">
          <label for="emailInput">Email <span aria-hidden="true">*</span></label>
          <input type="email" id="emailInput" placeholder="example@gmail.com" required aria-required="true">
          <div class="gd-field-error" id="emailError">Masukan email yang valid.</div>
        </div>
        <div class="gd-field">
          <label for="waInput">WhatsApp Number <span class="gd-field-optional">(Opsional)</span></label>
          <div class="gd-wa-input">
            <span class="gd-wa-prefix">+62</span>
            <input type="tel" id="waInput" placeholder="812xxxxxxx">
          </div>
          <div class="gd-field-error" id="waError">Masukkan nomor WhatsApp yang valid.</div>
        </div>
      </div>
      <p class="gd-field-hint">*Nomor ini akan dihubungi jika terjadi masalah.</p>
    </div>
  </div>

    </div>
    <div class="gd-detail-side">
      <div class="gd-card">
        <div class="gd-review-section">
          <div class="gd-review-head">
            <h4>Ulasan & Rating</h4>
          </div>
          <div class="gd-review-body">
            <span class="gd-review-num">5.0</span>
            <span class="gd-stars">★★★★★</span>
          </div>
        </div>
      </div>
      <div class="gd-card">
        <div class="gd-help-section">
          <strong>Butuh Bantuan?</strong>
          <p>Kamu Bisa Hubungi Admin <a href="{{ route('kontak') }}" class="gd-help-link">Disini</a></p>
        </div>
      </div>
      <div class="gd-card gd-summary-card gd-summary-desktop-only" id="summaryCard">
        <div class="gd-summary-head">
          <div class="gd-summary-icon">
            @if($brand->thumbnail_url)
              <img src="{{ $brand->thumbnail_url }}" alt="{{ $brand->name }}">
            @else
              <span>{{ $brand->icon ?? '🎮' }}</span>
            @endif
          </div>
          <div>
            <p class="gd-summary-game">{{ $brand->name }}</p>
            <p class="gd-summary-pkg" id="sumPkgNameDesktop">-</p>
          </div>
        </div>
        <div class="gd-summary-empty" id="sumEmptyDesktop">Tidak ada produk yang dipilih</div>
        <div class="gd-summary-details" id="sumDetailsDesktop" style="display:none">
          <div class="gd-summary-line"><span>Harga</span><strong id="sumHargaDesktop">Rp 0</strong></div>
          <div class="gd-summary-line"><span>Jumlah Pembelian</span><strong id="sumQtyDesktop">1</strong></div>
          <div class="gd-summary-line"><span>Biaya Layanan</span><strong id="sumFeeDesktop">Rp 0</strong></div>
          <div class="gd-summary-total"><span>Total Pembayaran</span><span id="sumTotalDesktop">Rp 0</span></div>
        </div>
        <div style="margin-top:1rem;">
          <button class="btn btn-solid btn-full" id="orderNowBtn">Pesan Sekarang</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

@if(!$products->isEmpty())
<!-- ===== Mobile sticky bottom bar (with accordion) ===== -->
<div class="gd-mobile-bar" id="mobileOrderBar">
  <div class="gd-mobile-panel" id="mobilePanel">
    <div class="gd-mobile-panel-inner">
      <div class="gd-summary-head">
        <div class="gd-summary-icon">
          @if($brand->thumbnail_url)
            <img src="{{ $brand->thumbnail_url }}" alt="{{ $brand->name }}">
          @else
            <span>{{ $brand->icon ?? '🎮' }}</span>
          @endif
        </div>
        <div>
          <p class="gd-summary-game">{{ $brand->name }}</p>
          <p class="gd-summary-pkg" id="sumPkgName">-</p>
        </div>
      </div>
      <div class="gd-summary-empty" id="sumEmpty">Tidak ada produk yang dipilih</div>
      <div class="gd-summary-details" id="sumDetails" style="display:none">
        <div class="gd-summary-line"><span>Harga</span><strong id="sumHarga">Rp 0</strong></div>
        <div class="gd-summary-line"><span>Jumlah Pembelian</span><strong id="sumQty">1</strong></div>
        <div class="gd-summary-line"><span>Biaya Layanan</span><strong id="sumFee">Rp 0</strong></div>
        <div class="gd-summary-total"><span>Total Pembayaran</span><span id="sumTotal">Rp 0</span></div>
      </div>
    </div>
  </div>
  <div class="gd-mobile-bar-inner">
    <button class="gd-mobile-toggle" id="mobileToggle" aria-label="Buka detail pesanan" aria-expanded="false">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <div class="gd-mobile-bar-info">
      <span class="gd-mobile-bar-label">Total Pembayaran</span>
      <strong class="gd-mobile-bar-total" id="mobileSumTotal">Rp 0</strong>
    </div>
    <button class="btn btn-solid gd-mobile-bar-btn" id="orderNowBtnMobile">Pesan Sekarang</button>
  </div>
</div>
@endif

@endsection

@push('scripts')
<style>
@media(max-width:640px){
  body.gd-summary-open #gc-fab,
  body.gd-summary-open #lc-fab,
  body.gd-summary-open #lc-fab-greeting{
    opacity:0!important;
    visibility:hidden!important;
    pointer-events:none!important;
  }
}
</style>
@if(!$products->isEmpty())
<script>
(function(){
'use strict';

const $ = (s, ctx) => (ctx||document).querySelector(s);
const $$ = (s, ctx) => Array.from((ctx||document).querySelectorAll(s));

const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function scrollToOrderStep(stepId) {
    const target = document.getElementById(stepId);
    if (!target) return;
    requestAnimationFrame(() => target.scrollIntoView({
        behavior: reducedMotion ? 'auto' : 'smooth',
        block: 'start'
    }));
}

const brandName = @json($brand->name);
const isMobileLegends = @json($isMobileLegends);
const accountCheckEnabled = @json($accountCheckEnabled);
const paymentMethods = @json($payData);
const pointBalance = @json((int) $pointBalance);
const usePointsInput = document.getElementById('usePointsInput');
const pointAmountInput = document.getElementById('pointAmountInput');

let selectedPkg = null;
let selectedRegion = @json($selectedRegion);
let detectedRegion = null;
let detectedNickname = null;
let qty = 1;
let promoDiscount = 0;
// Kode + subtotal yang terakhir berhasil diverifikasi, dipakai untuk melepas
// diskon otomatis ketika pesanan atau kode yang diketik berubah.
let appliedPromo = null;
/* ---------- auto-select from URL ---------- */
(function(){
    const params = new URLSearchParams(window.location.search);
    const preselectedId = params.get('product');
    if (preselectedId) {
        const card = document.querySelector('.gd-pkg-card[data-id="' + preselectedId + '"]');
        if (card) {
            setTimeout(function(){
                card.click();
            }, 300);
        }
    }
})();

/* ---------- package click ---------- */
document.addEventListener('click', e => {
    const card = e.target.closest('.gd-pkg-card');
    if (!card) return;
    // Kartu produk juga link ke halaman checkout supaya bisa dibuka/di-crawl
    // tanpa JavaScript. Di sini alur pemesanan inline yang tetap berjalan.
    e.preventDefault();
    if (card.closest('.gd-pkg-instant') && !card.closest('[data-no-region-filter="true"]')) {
        const region = card.dataset.region;
        if (region && selectedRegion && region !== selectedRegion && region !== 'ALL') return;
    }
    $$('.gd-pkg-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedPkg = { id: Number(card.dataset.id), label: card.dataset.label, price: Number(card.dataset.price), region: card.dataset.region || null };
    updateSummary();
    scrollToOrderStep('quantityStep');
});

/* ---------- region tabs ---------- */
$$('.gd-region-tabs').forEach(tabs => {
    tabs.addEventListener('click', e => {
        const btn = e.target.closest('.gd-region-btn');
        if (!btn) return;
        $$('.gd-region-btn', tabs).forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedRegion = btn.dataset.region;
        $$('.gd-pkg-card', tabs.closest('.gd-group')).forEach(c => {
            if (c.dataset.region) {
                c.style.display = (c.dataset.region === selectedRegion || c.dataset.region === 'ALL') ? '' : 'none';
            }
        });
        const visible = $$('.gd-pkg-card:not([style*="display:none"])', tabs.closest('.gd-group'));
        if (visible.length) {
            visible.forEach(c => c.classList.remove('selected'));
        }
    });
});

/* init: hide non-default region packages */
$$('.gd-pkg-instant').forEach(grid => {
    if (grid.dataset.noRegionFilter) return;
    $$('.gd-pkg-card', grid).forEach(c => {
        if (c.dataset.region && c.dataset.region !== selectedRegion && c.dataset.region !== 'ALL') {
            c.style.display = 'none';
        }
    });
});

/* ---------- quantity ---------- */
const qtyInput = $('#qtyInput');
function setQty(v) {
    v = Math.max(1, Math.min(99, Math.round(v)||1));
    qty = v;
    qtyInput.value = v;
    $('#qtyMinus').disabled = v <= 1;
    updateSummary();
}
function qtyLockCheck() {
    if (!accountDetailsReady()) {
        requireAccountDetails();
        return true;
    }
    return false;
}
$('#qtyMinus').addEventListener('click', () => { if (qtyLockCheck()) return; setQty(qty-1); });
$('#qtyPlus').addEventListener('click', () => { if (qtyLockCheck()) return; setQty(qty+1); });
qtyInput.addEventListener('change', () => { if (qtyLockCheck()) return; setQty(parseInt(qtyInput.value,10)); });
qtyInput.addEventListener('input', () => { qtyInput.value = qtyInput.value.replace(/[^0-9]/g,''); });

/* ---------- payment methods ---------- */
let selectedPayKey = 'qris';
let selectedPayFee = 0;
let selectedPayLabel = '';
const payGroup = $('#payGroup');
const userIdInput = $('#userId'), zoneIdInput = $('#zoneId');
const zoneRequired = !!zoneIdInput;
const accountLockNotice = $('#accountLockNotice');

function accountDetailsReady() {
    return userIdInput.value.trim().length > 0
        && (!zoneRequired || zoneIdInput.value.trim().length > 0)
        && (!accountCheckEnabled || (!detectedRegion || detectedRegion === 'Indonesia'));
}

function requireAccountDetails() {
    const missingUserId = userIdInput.value.trim().length === 0;
    const missingZoneId = zoneRequired && zoneIdInput.value.trim().length === 0;
    const message = (missingUserId || missingZoneId)
        ? 'Harap isi ID game terlebih dahulu.'
        : accountLockMessage();

    if (accountLockNotice) {
        accountLockNotice.textContent = message;
        accountLockNotice.hidden = false;
        accountLockNotice.classList.add('show');
    }

    [
        [userIdInput, missingUserId],
        [zoneIdInput, missingZoneId],
    ].forEach(([input, missing]) => {
        if (!input || !missing) return;
        input.classList.remove('gd-input-attention');
        void input.offsetWidth;
        input.classList.add('error', 'gd-input-attention');
        input.setAttribute('aria-invalid', 'true');
        const errorEl = input === userIdInput ? $('#userIdError') : $('#zoneIdError');
        if (errorEl) errorEl.classList.add('show');
    });

    showToast(message, false);
    const inputToFocus = missingUserId ? userIdInput : (missingZoneId ? zoneIdInput : userIdInput);
    inputToFocus.scrollIntoView({behavior:'smooth', block:'center'});
    window.setTimeout(() => {
        inputToFocus.focus({preventScroll:true});
    }, 400);
}

function accountLockMessage() {
    if (detectedRegion && detectedRegion !== 'Indonesia') return 'Region ' + detectedRegion + ' belum tersedia saat ini.';
    return 'Isi Data Akun terlebih dahulu';
}

function togglePayLock() {
    // Metode pembayaran tetap boleh dilihat sebelum data akun diisi.
    // Validasi baru dijalankan saat pengguna benar-benar memilih metodenya.
    payGroup.classList.remove('gd-pay-group--locked');
}
userIdInput.addEventListener('input', togglePayLock);
if (zoneIdInput) zoneIdInput.addEventListener('input', togglePayLock);
togglePayLock();

/* Tampilkan seluruh metode dan pilih QRIS atau metode pertama sebagai default. */
(function(){
    const qrRow = document.querySelector('.gd-pay-row[data-key="qris"]') || document.querySelector('.gd-pay-row');
    if (qrRow) {
        const method = paymentMethods.find(m => m.key === qrRow.dataset.key);
        if (method) {
            selectedPayKey = method.key;
            selectedPayFee = method.fee || 0;
            selectedPayLabel = method.title;
        }
        qrRow.classList.add('selected');
    }
})();

/* ---------- deteksi akun real-time (indikator hijau) ---------- */
const userIdOk = $('#userIdOk');
const accountCheckUrl = @json(route('api.account.check'));
const MIN_UID_LEN = 5;
let accountCheckTimer = null;
let accountCheckAbort = null;

function clearAccountFeedback() {
    detectedNickname = null;
    detectedRegion = null;
    userIdInput.classList.remove('valid', 'gd-input-attention');
    if (zoneIdInput) zoneIdInput.classList.remove('valid', 'gd-input-attention');
    if (userIdOk) { userIdOk.className = 'gd-field-ok gd-account-result'; userIdOk.textContent = ''; }
    if (accountLockNotice) {
        accountLockNotice.hidden = true;
        accountLockNotice.classList.remove('show');
        accountLockNotice.textContent = '';
    }
    togglePayLock();
}

function showAccountLoading() {
    if (!userIdOk) return;
    userIdOk.className = 'gd-field-ok gd-account-result show loading';
    userIdOk.textContent = '';
    const s = document.createElement('span');
    s.className = 'gd-spinner';
    userIdOk.append(s, ' Mencari akun...');
}

function showAccountValid(nickname, region) {
    detectedNickname = nickname || null;
    detectedRegion = region || null;
    userIdInput.classList.add('valid');
    if (zoneIdInput) zoneIdInput.classList.add('valid');
    if (userIdOk) {
        userIdOk.className = 'gd-field-ok gd-account-result show';
        userIdOk.textContent = '';

        const introEl = document.createElement('span');
        introEl.className = 'gd-account-result-intro';
        introEl.textContent = 'Akun kamu adalah';

        const identityEl = document.createElement('span');
        identityEl.className = 'gd-account-result-identity';

        const nicknameEl = document.createElement('strong');
        nicknameEl.textContent = detectedNickname || 'Valid';

        const checkEl = document.createElement('span');
        checkEl.className = 'gd-account-result-check';
        checkEl.textContent = '✓';

        identityEl.append(nicknameEl, checkEl);
        userIdOk.append(introEl, identityEl);

        if (detectedRegion) {
            const regionGroup = document.createElement('span');
            regionGroup.className = 'gd-account-result-region';
            regionGroup.append('dari ');

            const regionEl = document.createElement('strong');
            regionEl.textContent = detectedRegion;
            regionGroup.append(regionEl);

            if (detectedRegion === 'Indonesia') {
                const flagEl = document.createElement('span');
                flagEl.className = 'gd-account-result-flag';
                flagEl.textContent = '🇮🇩';
                regionGroup.append(flagEl);
            }

            userIdOk.append(regionGroup);
        }
    }
    togglePayLock();
}

function showAccountNotFound() {
    detectedNickname = null;
    detectedRegion = null;
    userIdInput.classList.remove('valid');
    if (zoneIdInput) zoneIdInput.classList.remove('valid');
    if (userIdOk) {
        userIdOk.className = 'gd-field-ok gd-account-result bad show';
        userIdOk.textContent = 'Akun tidak ditemukan atau profil privat.';
    }
    togglePayLock();
}

function showAccountCheckUnavailable() {
    clearAccountFeedback();
    const message = 'Pengecekan akun sedang tidak tersedia. Coba lagi beberapa saat.';
    if (userIdOk) {
        userIdOk.className = 'gd-field-ok gd-account-result bad show';
        userIdOk.textContent = message;
    }
}

function scheduleAccountCheck() {
    clearTimeout(accountCheckTimer);
    if (accountCheckAbort) accountCheckAbort.abort();
    clearAccountFeedback();
    if (!accountCheckEnabled) return;

    const uid = userIdInput.value.trim();
    const zid = zoneIdInput ? zoneIdInput.value.trim() : '';

    if (uid.length < MIN_UID_LEN || (zoneRequired && zid.length === 0)) {
        return;
    }
    accountCheckTimer = setTimeout(runAccountCheck, 450);
}

async function runAccountCheck() {
    const uid = userIdInput.value.trim();
    const zid = zoneIdInput ? zoneIdInput.value.trim() : '';

    if (accountCheckAbort) accountCheckAbort.abort();
    const myAbort = accountCheckAbort = new AbortController();
    showAccountLoading();
    try {
        const res = await fetch(accountCheckUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: JSON.stringify({ brand: brandName, user_id: uid, zone_id: zid }),
            signal: myAbort.signal,
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (myAbort !== accountCheckAbort) return; /* respons basi */
        if (data.checked === false) {
            showAccountCheckUnavailable();
            return;
        }
        if (data.valid) showAccountValid(data.nickname, data.region);
        else showAccountNotFound();
    } catch (e) {
        if (e.name !== 'AbortError' && myAbort === accountCheckAbort) {
            showAccountCheckUnavailable();
        }
    }
}

userIdInput.addEventListener('input', scheduleAccountCheck);
if (zoneIdInput) zoneIdInput.addEventListener('input', scheduleAccountCheck);

/* category accordion toggle */
$('#payGroup').addEventListener('click', e => {
    const catHead = e.target.closest('.gd-pay-cat-head');
    if (catHead) {
        const cat = catHead.closest('.gd-pay-category');
        cat.classList.toggle('open');
        return;
    }
    /* method select */
    const head = e.target.closest('.gd-pay-row-head');
    if (!head) return;
    const row = head.closest('.gd-pay-row');
    if (!row || row.classList.contains('disabled')) return;
    if (!accountDetailsReady()) {
        requireAccountDetails();
        return;
    }
    if (row.classList.contains('selected')) {
        scrollToOrderStep('contactStep');
        return;
    }
    $$('.gd-pay-row').forEach(r => r.classList.remove('selected'));
    row.classList.add('selected');
    const method = paymentMethods.find(m => m.key === row.dataset.key);
    if (method) {
        selectedPayKey = method.key;
        selectedPayFee = method.fee || 0;
        selectedPayLabel = method.title;
        updateSummary();
        scrollToOrderStep('contactStep');
    }
});

/* ---------- summary ---------- */
function computePayTotal() {
    const total = selectedPkg ? Math.max(0, selectedPkg.price * qty - promoDiscount) : 0;
    const requested = pointAmountInput ? parseInt(pointAmountInput.value || '0', 10) : pointBalance;
    return usePointsInput && usePointsInput.checked ? Math.max(0, total - Math.min(total, pointBalance, Math.max(0, requested))) : total;
}

function updatePaymentCardAmounts() {
    const total = computePayTotal();
    $$('.gd-pay-row').forEach(row => {
        const amount = $('[data-pay-amount]', row);
        if (!amount) return;
        const method = paymentMethods.find(m => m.key === row.dataset.key);
        amount.textContent = selectedPkg ? rupiah(total + (method?.fee || 0)) : '';
    });
}

/* Sembunyikan metode yang nominalnya di bawah minimal channel (VA & minimarket min Rp 10.000,
   QRIS & e-wallet min Rp 1.000).
   Metode tetap tampil agar pengguna tahu batasannya, tapi dinonaktifkan (abu-abu). */
function ensureValidPayMethod() {
    const total = computePayTotal();
    const rows = $$('.gd-pay-row');
    rows.forEach(row => {
        const method = paymentMethods.find(m => m.key === row.dataset.key);
        const below = total > 0 && !!method && total < (method.min_amount || 0);
        row.classList.toggle('disabled', below);
    });

    /* jika metode terpilih tidak tersedia lagi, alihkan ke metode valid pertama */
    const selRow = document.querySelector('.gd-pay-row.selected');
    const selMethod = selRow && paymentMethods.find(m => m.key === selRow.dataset.key);
    if (total > 0 && selMethod && total < (selMethod.min_amount || 0)) {
        const next = rows.find(r => !r.classList.contains('disabled'));
        if (next) {
            $$('.gd-pay-row').forEach(r => r.classList.remove('selected'));
            next.classList.add('selected');
            const method = paymentMethods.find(m => m.key === next.dataset.key);
            if (method) {
                selectedPayKey = method.key;
                selectedPayFee = method.fee || 0;
                selectedPayLabel = method.title;
            }
        } else {
            selectedPayKey = 'qris';
            selectedPayFee = 0;
            selectedPayLabel = '';
        }
    }
}

function updateSummary() {
    ensureValidPayMethod();
    syncPromoState();
    updatePaymentCardAmounts();

    const empty = !selectedPkg;
    // mobile accordion
    $('#sumEmpty').style.display = empty ? '' : 'none';
    $('#sumDetails').style.display = empty ? 'none' : '';
    // desktop sidebar
    const de = $('#sumEmptyDesktop');
    if (de) de.style.display = empty ? '' : 'none';
    const dd = $('#sumDetailsDesktop');
    if (dd) dd.style.display = empty ? 'none' : '';

    if (empty) {
        $('#sumPkgName').textContent = '-';
        if ($('#sumPkgNameDesktop')) $('#sumPkgNameDesktop').textContent = '-';
        updateMobileTotal(0);
        return;
    }
    const subtotal = selectedPkg.price * qty;
    const beforePoints = Math.max(0, subtotal + selectedPayFee - promoDiscount);
    const requestedPoints = pointAmountInput ? parseInt(pointAmountInput.value || '0', 10) : pointBalance;
    const total = usePointsInput && usePointsInput.checked ? Math.max(0, beforePoints - Math.min(beforePoints, pointBalance, Math.max(0, requestedPoints))) : beforePoints;
    // mobile accordion
    $('#sumPkgName').textContent = selectedPkg.label;
    $('#sumHarga').textContent = rupiah(selectedPkg.price);
    $('#sumQty').textContent = qty;
    $('#sumFee').textContent = rupiah(selectedPayFee);
    $('#sumTotal').textContent = rupiah(total);
    // desktop sidebar
    if ($('#sumPkgNameDesktop')) $('#sumPkgNameDesktop').textContent = selectedPkg.label;
    if ($('#sumHargaDesktop')) $('#sumHargaDesktop').textContent = rupiah(selectedPkg.price);
    if ($('#sumQtyDesktop')) $('#sumQtyDesktop').textContent = qty;
    if ($('#sumFeeDesktop')) $('#sumFeeDesktop').textContent = rupiah(selectedPayFee);
    if ($('#sumTotalDesktop')) $('#sumTotalDesktop').textContent = rupiah(total);

    updateMobileTotal(total);
}
function updateMobileTotal(v) {
    const el = $('#mobileSumTotal');
    if (el) el.textContent = rupiah(v);
}
updateSummary();
if (usePointsInput) usePointsInput.addEventListener('change', updateSummary);
if (pointAmountInput) pointAmountInput.addEventListener('input', updateSummary);

/* ---------- mobile accordion toggle ---------- */
$('#mobileToggle').addEventListener('click', function() {
    const bar = document.getElementById('mobileOrderBar');
    bar.classList.toggle('open');
    const isOpen = bar.classList.contains('open');
    document.body.classList.toggle('gd-summary-open', isOpen);
    this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    this.setAttribute('aria-label', isOpen ? 'Tutup detail pesanan' : 'Buka detail pesanan');
});
// auto close accordion on product select
document.addEventListener('click', e => {
    const card = e.target.closest('.gd-pkg-card');
    if (card) {
        const bar = document.getElementById('mobileOrderBar');
        if (bar) bar.classList.remove('open');
        document.body.classList.remove('gd-summary-open');
    }
});

async function handleOrder(btn) {
    if (!selectedPkg) {
      showToast('Pilih produk terlebih dahulu', false);
      return;
    }
    const okUserId = validateField(userIdInput, $('#userIdError'), v => v.length > 0);
    const okZone = !zoneRequired || validateField(zoneIdInput, $('#zoneIdError'), v => v.length > 0);
    const okEmail = validateField(emailInput, $('#emailError'), v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v));
    const okWa = validateField(waInput, $('#waError'), v => v.length === 0 || v.length >= 8);
    if (!okUserId || !okZone || (accountCheckEnabled && detectedRegion && detectedRegion !== 'Indonesia')) {
      requireAccountDetails();
      return;
    }
    if (!okEmail || !okWa) {
      showToast('Periksa kembali Detail Kontak kamu', false);
      return;
    }
    btn.disabled = true; btn.textContent = 'Memproses...';
    const fd = new FormData();
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.append('product_id', selectedPkg.id);
    fd.append('customer_number', userIdInput.value.trim());
    if (detectedNickname) fd.append('customer_name', detectedNickname);
    if (zoneIdInput) fd.append('zone_id', zoneIdInput.value.trim());
    fd.append('quantity', qty);
    fd.append('promo_code', promoInput.value.trim());
    fd.append('email', emailInput.value.trim());
    fd.append('phone', waInput.value.trim());
    if (selectedPayKey) fd.append('payment_method', selectedPayKey);
    if (usePointsInput && usePointsInput.checked) fd.append('use_points', '1');
    if (pointAmountInput) fd.append('points_amount', pointAmountInput.value || '0');
    try {
      const res = await fetch('{{ route('orders.store') }}', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body:fd});
      if (res.status === 401) { window.location.href = @json(route('login')); return; }
      if (!res.ok) {
        const error = await res.json().catch(() => null);
        const message = error?.message || Object.values(error?.errors || {}).flat()[0] || ('Server error: ' + res.status);
        showToast(message, false);
        btn.disabled = false;
        btn.textContent = 'Pesan Sekarang';
        return;
      }
      const data = await res.json();
      if (data.success && data.redirect) { window.location.href = data.redirect; }
      else { btn.disabled = false; btn.textContent = 'Pesan Sekarang'; showToast(data.message || 'Gagal memproses pesanan', false); }
    } catch(e) { btn.disabled = false; btn.textContent = 'Pesan Sekarang'; showToast('Terjadi kesalahan: ' + e.message, false); }
}

$('#orderNowBtn').addEventListener('click', function() { handleOrder(this); });
$('#orderNowBtnMobile').addEventListener('click', function() { handleOrder(this); });


/* ---------- promo ---------- */
const promoInput = $('#promoInput'), promoMsg = $('#promoMsg');
let promoChecking = false;

// Diskon hanya sah untuk kode + subtotal yang sama ketika diverifikasi.
// Kalau produk/jumlah berubah atau kodenya diedit, diskon dilepas supaya
// tampilan tidak lagi menampilkan nominal yang sudah tidak berlaku.
function syncPromoState() {
    if (!appliedPromo) return;

    const subtotal = selectedPkg ? selectedPkg.price * qty : 0;
    if (appliedPromo.code === promoInput.value.trim().toUpperCase() && appliedPromo.subtotal === subtotal) return;

    appliedPromo = null;
    promoDiscount = 0;
    promoMsg.textContent = 'Kode promo dilepas karena pesanan berubah. Terapkan lagi.';
    promoMsg.className = 'gd-promo-msg show bad';
}

$('#promoBtn').addEventListener('click', async () => {
    if (!selectedPkg) { showToast('Pilih produk terlebih dahulu', false); return; }
    const code = promoInput.value.trim().toUpperCase();
    if (!code) {
        promoMsg.textContent = 'Masukan kode promo terlebih dahulu.';
        promoMsg.className = 'gd-promo-msg show bad';
        return;
    }
    if (promoChecking) return;
    promoChecking = true;
    const btn = $('#promoBtn');
    const originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Memeriksa...';
    try {
        const res = await fetch('{{ route('api.vouchers.validate') }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ code: code, subtotal: selectedPkg.price * qty })
        });
        const data = await res.json().catch(() => ({}));
        if (data.valid) {
            promoDiscount = data.discount || 0;
            appliedPromo = { code: code, subtotal: selectedPkg.price * qty };
            promoMsg.textContent = data.message;
            promoMsg.className = 'gd-promo-msg show ok';
        } else {
            promoDiscount = 0;
            appliedPromo = null;
            promoMsg.textContent = data.message || 'Kode promo tidak valid atau sudah kedaluwarsa.';
            promoMsg.className = 'gd-promo-msg show bad';
        }
        updateSummary();
    } catch (e) {
        promoDiscount = 0;
        appliedPromo = null;
        promoMsg.textContent = 'Gagal memeriksa kode promo. Coba lagi.';
        promoMsg.className = 'gd-promo-msg show bad';
        updateSummary();
    } finally {
        btn.disabled = false;
        btn.textContent = originalLabel;
        promoChecking = false;
    }
});

promoInput.addEventListener('input', syncPromoState);

/* ---------- validation + modal ---------- */
const emailInput = $('#emailInput'), waInput = $('#waInput');

function validateField(input, errorEl, testFn) {
    const ok = testFn(input.value.trim());
    input.classList.toggle('error', !ok);
    errorEl.classList.toggle('show', !ok);
    return ok;
}



/* ---------- toast ---------- */
function showToast(msg, ok) {
    const el = document.createElement('div');
    el.className = 'toast' + (ok === false ? ' error' : ' success') + ' show';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 300);
    }, 2600);
}

/* live-clear errors */
[userIdInput, zoneIdInput, emailInput, waInput].filter(Boolean).forEach(inp => {
    inp.addEventListener('input', () => {
        inp.classList.remove('error', 'gd-input-attention');
        inp.removeAttribute('aria-invalid');
        const errorEl = inp.id === 'userId' ? $('#userIdError')
            : inp.id === 'zoneId' ? $('#zoneIdError')
            : inp.id === 'emailInput' ? $('#emailError')
            : $('#waError');
        if (errorEl) errorEl.classList.remove('show');
    });
});

/* theme-aware payment images */
function swapPayImages() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    document.querySelectorAll('.gd-pay-icon img[data-light]').forEach(img => {
        if (isLight) {
            img.dataset.dark = img.src;
            img.src = img.dataset.light;
        } else if (img.dataset.dark) {
            img.src = img.dataset.dark;
        }
    });
}
swapPayImages();
document.addEventListener('themeChanged', swapPayImages);

})();
</script>
@endif
@endpush
