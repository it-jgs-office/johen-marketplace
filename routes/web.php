<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\PushSubscriptionController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGachaPrizeController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\GachaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JokiController;
use App\Http\Controllers\AdminJokiPackageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\JohenPointController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Service worker & versi PWA dilayani framework (bukan file statis) supaya
// script-nya berubah setiap ada aset publik yang berubah. Tanpa itu, worker
// yang terpasang dianggap identik dan PWA lama tidak pernah picking up update.
// Service worker dilayani tanpa session & tanpa CSRF: script-nya tidak butuh
// state, dan ini menghindari cookie sesi ikut menempel di respons worker.
Route::get('/service-worker.js', [PwaController::class, 'serviceWorker'])
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('pwa.service-worker');

Route::get('/pwa-version.json', [PwaController::class, 'version'])->name('pwa.version');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/company-auth', [App\Http\Controllers\CompanyAuthController::class, 'show'])
    ->middleware(App\Http\Middleware\AllowCompanyAuthFrame::class)
    ->name('company-auth');

Route::get('/artikel', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/artikel/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/products/by-brand', [HomeController::class, 'getProductsByBrand'])->name('products.by-brand');

Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::get('/api/products', [HomeController::class, 'getApiProducts'])->name('api.products');
Route::get('/api/brands/search', [HomeController::class, 'searchBrands'])->name('api.brands.search');
Route::get('/api/search', [HomeController::class, 'globalSearch'])->name('api.search');
Route::get('/api/payment-methods', [HomeController::class, 'getPaymentMethods'])->name('api.payment-methods');
Route::get('/api/orders/check', [HomeController::class, 'checkOrder'])->name('api.orders.check');
Route::post('/api/account/check', [HomeController::class, 'checkAccount'])
    ->name('api.account.check')
    ->middleware('throttle:30,1');
Route::get('/api/push/latest-message', [PushSubscriptionController::class, 'latestNotification'])
    ->name('api.push.latest-message');
Route::get('/games/{brand:name}', [HomeController::class, 'gameDetail'])->name('games.show');
Route::redirect('/joki', '/joki/mobile-legends')->name('joki.index');
Route::get('/joki/mobile-legends', [JokiController::class, 'mobileLegends'])->name('joki.mobile-legends');
Route::get('/cek-transaksi', [HomeController::class, 'checkTransaction'])->name('check.transaction');
Route::get('/jual-beli-akun', [App\Http\Controllers\HomeController::class, 'jualBeliAkun'])->name('jual-beli-akun');
Route::match(['GET', 'POST', 'PATCH', 'DELETE'], '/api/live-chat/{path}', [App\Http\Controllers\CompanyChatController::class, 'handle'])
    ->where('path', '.*')->middleware('throttle:120,1');
Route::get('/jual-beli-akun/{game}', [App\Http\Controllers\HomeController::class, 'jualBeliAkunGame'])
    ->where('game', 'mlbb|pubg|efootball|fcm|ff|roblox|valorant')
    ->name('jual-beli-akun.game');
Route::post('/jual-beli-akun/email-test', [App\Http\Controllers\HomeController::class, 'jualBeliAkunEmailTest'])
    ->name('jual-beli-akun.email-test');
Route::post('/jual-beli-akun/email-confirmation/verify', [App\Http\Controllers\HomeController::class, 'jualBeliAkunEmailConfirmationVerify'])
    ->name('jual-beli-akun.email-confirmation.verify');
Route::get('/jual-beli-akun/pesanan-saya', [App\Http\Controllers\HomeController::class, 'jualBeliAkunOrders'])
    ->name('jual-beli-akun.orders')
    ->middleware('auth:web');
Route::get('/jual-beli-akun/{listing}', [App\Http\Controllers\HomeController::class, 'jualBeliAkunDetail'])->whereNumber('listing')->name('jual-beli-akun.detail');
Route::get('/jual-beli-akun/{listing}/checkout', [App\Http\Controllers\HomeController::class, 'jualBeliAkunCheckout'])->whereNumber('listing')->name('jual-beli-akun.checkout');
Route::post('/jual-beli-akun/{listing}/checkout', [App\Http\Controllers\HomeController::class, 'jualBeliAkunCheckoutStore'])->whereNumber('listing')->name('jual-beli-akun.checkout.store');
Route::get('/jual-beli-akun/order/{accountOrder}/payment', [App\Http\Controllers\HomeController::class, 'jualBeliAkunPayment'])->name('jual-beli-akun.payment');
Route::get('/jual-beli-akun/order/{accountOrder}/status', [App\Http\Controllers\HomeController::class, 'jualBeliAkunPaymentStatus'])->name('jual-beli-akun.payment.status');
Route::post('/jual-beli-akun/order/{accountOrder}/simulate', [App\Http\Controllers\HomeController::class, 'jualBeliAkunSimulatePayment'])->name('jual-beli-akun.payment.simulate');
Route::get('/testimoni', [HomeController::class, 'testimoni'])->name('testimoni');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/kebijakan-privasi', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [HomeController::class, 'terms'])->name('terms');
Route::get('/hubungi-kami', [HomeController::class, 'kontak'])->name('kontak');
Route::post('/hubungi-kami', [HomeController::class, 'kontakStore']);
Route::get('/leaderboard', [HomeController::class, 'leaderboard'])->name('leaderboard');
Route::get('/leaderboard/{period}', [HomeController::class, 'leaderboardDetail'])->name('leaderboard.detail');
Route::get('/api/leaderboard', [HomeController::class, 'leaderboardApi'])->name('api.leaderboard');

Route::middleware('auth:web,admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dituju oleh resources/views/layouts/navigation.blade.php (default Breeze).
    // Area pelanggan saat ini = daftar pesanan + saldo.
    Route::get('/dashboard', fn () => redirect()->route('orders.my'))->name('dashboard');

    Route::get('/orders', [OrderController::class, 'myOrders'])->name('orders.my');
    Route::get('/johen-point', [JohenPointController::class, 'index'])->name('points.index')->middleware('auth:web');
    Route::get('/ulasan-saya', [ReviewController::class, 'index'])->name('reviews.my')->middleware('auth:web');
    Route::get('/pesan-saya', [HomeController::class, 'myInquiries'])->name('my-inquiries');

    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers/claim', [VoucherController::class, 'claim'])->name('vouchers.claim');
});

Route::get('/orders/create/{product}', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::post('/orders/reorder/{order}', [OrderController::class, 'reorder'])->name('orders.reorder');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

/*
 * Keranjang jual-beli akun.
 *
 * Wajib login (auth:web): keranjang disimpan di database supaya tidak hilang
 * saat ganti perangkat.
 */
Route::middleware('auth:web')->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/keranjang', [CartController::class, 'store'])->name('cart.store');
    Route::post('/keranjang/kosongkan', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('/keranjang/jumlah', [CartController::class, 'count'])->name('cart.count');
    Route::get('/keranjang/state', [CartController::class, 'state'])->name('cart.state');
    Route::delete('/keranjang/item/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

});

Route::get('/payment/detail/{order}', [PaymentController::class, 'detail'])->name('payment.detail');
Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/status/{order}', [PaymentController::class, 'status'])->name('payment.status');
Route::post('/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel');

Route::post('/payment/simulate/{order}', [PaymentController::class, 'simulatePay'])
    ->name('payment.simulate');

Route::post('/payment/notification', \App\Http\Controllers\AyolinxNotificationController::class)
    ->name('payment.notification');

Route::post('/v1.0/access-token/b2b', \App\Http\Controllers\AyolinxAccessTokenController::class)
    ->name('ayolinx.access-token');
Route::post('/v1.0/transfer-va/payment', \App\Http\Controllers\AyolinxVaPaymentController::class)
    ->name('ayolinx.va.payment');

Route::post('/reviews/{order}', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth:web');
Route::post('/account-reviews/{accountOrder}', [ReviewController::class, 'storeAccount'])->name('account-reviews.store')->middleware('auth:web');

Route::post('/digiflazz/callback', [PaymentController::class, 'digiflazzCallback'])->name('payment.digiflazz.callback');

Route::get('/johen-admin-secret', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }
    return view('admin.auth.login');
})->name('admin.index');

Route::middleware(['auth:admin', 'admin'])->prefix('johen-admin-secret')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/products/game/{brand}', [AdminController::class, 'productsByGame'])->name('products.game');
    Route::patch('/products/game/{brand}/markup', [AdminController::class, 'productsApplyMarkup'])->name('products.markup');
    Route::patch('/products/{product}/selling-price', [AdminController::class, 'productsUpdateSellingPrice'])->name('products.selling-price');
    Route::patch('/products/{product}/toggle', [AdminController::class, 'productsToggle'])->name('products.toggle');
    Route::post('/products/sync', [AdminController::class, 'productsSync'])->name('products.sync');

    Route::get('/joki-packages', [AdminJokiPackageController::class, 'index'])->name('joki-packages');
    Route::post('/joki-packages', [AdminJokiPackageController::class, 'store'])->name('joki-packages.store');
    Route::put('/joki-packages/{product}', [AdminJokiPackageController::class, 'update'])->name('joki-packages.update');
    Route::delete('/joki-packages/{product}', [AdminJokiPackageController::class, 'destroy'])->name('joki-packages.destroy');

    Route::get('/account-listings', [App\Http\Controllers\AdminAccountListingController::class, 'index'])->name('account-listings');
    Route::get('/account-listings/{listing}', [App\Http\Controllers\AdminAccountListingController::class, 'show'])->whereNumber('listing')->name('account-listings.show');

    Route::get('/brands', [AdminController::class, 'brands'])->name('brands');
    Route::get('/brands/{brand}/edit', [AdminController::class, 'brandsEdit'])->name('brands.edit');
    Route::put('/brands/{brand}', [AdminController::class, 'brandsUpdate'])->name('brands.update');
    Route::patch('/brands/{brand}/toggle', [AdminController::class, 'brandsToggle'])->name('brands.toggle');
    Route::delete('/brands/{brand}', [AdminController::class, 'brandsDestroy'])->name('brands.destroy');

    Route::get('/gateway-status', [AdminController::class, 'gatewayStatus'])->name('gateway-status');

    Route::get('/popup-banners', [App\Http\Controllers\AdminPopupBannerController::class, 'index'])->name('popup-banners');
    Route::post('/popup-banners', [App\Http\Controllers\AdminPopupBannerController::class, 'store'])->name('popup-banners.store');
    Route::put('/popup-banners/{popupBanner}', [App\Http\Controllers\AdminPopupBannerController::class, 'update'])->name('popup-banners.update');
    Route::patch('/popup-banners/{popupBanner}/toggle', [App\Http\Controllers\AdminPopupBannerController::class, 'toggle'])->name('popup-banners.toggle');
    Route::delete('/popup-banners/{popupBanner}', [App\Http\Controllers\AdminPopupBannerController::class, 'destroy'])->name('popup-banners.destroy');

    Route::get('/flash-deals', [App\Http\Controllers\AdminFlashDealController::class, 'index'])->name('flash-deals');
    Route::post('/flash-deals', [App\Http\Controllers\AdminFlashDealController::class, 'store'])->name('flash-deals.store');
    Route::put('/flash-deals/{flashDeal}', [App\Http\Controllers\AdminFlashDealController::class, 'update'])->name('flash-deals.update');
    Route::patch('/flash-deals/{flashDeal}/toggle', [App\Http\Controllers\AdminFlashDealController::class, 'toggle'])->name('flash-deals.toggle');
    Route::delete('/flash-deals/{flashDeal}', [App\Http\Controllers\AdminFlashDealController::class, 'destroy'])->name('flash-deals.destroy');

    Route::get('/flash-sale-banners', [App\Http\Controllers\AdminFlashSaleBannerController::class, 'index'])->name('flash-sale-banners');
    Route::post('/flash-sale-banners', [App\Http\Controllers\AdminFlashSaleBannerController::class, 'store'])->name('flash-sale-banners.store');
    Route::put('/flash-sale-banners/{flashSaleBanner}', [App\Http\Controllers\AdminFlashSaleBannerController::class, 'update'])->name('flash-sale-banners.update');
    Route::patch('/flash-sale-banners/{flashSaleBanner}/toggle', [App\Http\Controllers\AdminFlashSaleBannerController::class, 'toggle'])->name('flash-sale-banners.toggle');
    Route::delete('/flash-sale-banners/{flashSaleBanner}', [App\Http\Controllers\AdminFlashSaleBannerController::class, 'destroy'])->name('flash-sale-banners.destroy');

    Route::get('/gacha-prizes', [AdminGachaPrizeController::class, 'index'])->name('gacha-prizes');
    Route::post('/gacha-prizes', [AdminGachaPrizeController::class, 'store'])->name('gacha-prizes.store');
    Route::put('/gacha-prizes/{gachaPrize}', [AdminGachaPrizeController::class, 'update'])->name('gacha-prizes.update');
    Route::patch('/gacha-prizes/{gachaPrize}/toggle', [AdminGachaPrizeController::class, 'toggle'])->name('gacha-prizes.toggle');
    Route::delete('/gacha-prizes/{gachaPrize}', [AdminGachaPrizeController::class, 'destroy'])->name('gacha-prizes.destroy');
    Route::post('/gacha-prizes/reorder', [AdminGachaPrizeController::class, 'reorder'])->name('gacha-prizes.reorder');
    Route::post('/gacha-prizes/settings', [AdminGachaPrizeController::class, 'settings'])->name('gacha-prizes.settings');

    Route::get('/vouchers', [AdminVoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers', [AdminVoucherController::class, 'store'])->name('vouchers.store');
    Route::patch('/vouchers/{voucher}/renew', [AdminVoucherController::class, 'renew'])->name('vouchers.renew');
    Route::put('/vouchers/{voucher}', [AdminVoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{voucher}', [AdminVoucherController::class, 'destroy'])->name('vouchers.destroy');

    Route::get('/orders', [AdminController::class, 'orders'])->defaults('service_type', 'topup')->name('orders');
    Route::get('/orders/topup', [AdminController::class, 'orders'])->defaults('service_type', 'topup')->name('orders.topup');
    Route::get('/orders/joki', [AdminController::class, 'orders'])->defaults('service_type', 'joki')->name('orders.joki');
    Route::get('/email-preview/order-status/{status}/{order?}', [AdminController::class, 'orderEmailPreview'])
        ->whereIn('status', ['success', 'failed'])
        ->whereNumber('order')
        ->name('email-preview.order-status');
    Route::get('/orders/{order}', [AdminController::class, 'ordersShow'])->name('orders.show');
    Route::post('/orders/{order}/retry', [AdminController::class, 'ordersRetry'])->name('orders.retry');
    Route::post('/orders/{order}/recipient-retry', [AdminController::class, 'ordersUpdateRecipientAndRetry'])->name('orders.recipient-retry');
    Route::post('/orders/{order}/check-provider', [AdminController::class, 'ordersCheckProvider'])->name('orders.check-provider');
    Route::post('/orders/{order}/manual-success', [AdminController::class, 'ordersManualSuccess'])->name('orders.manual-success');
    Route::post('/orders/{order}/refund', [AdminController::class, 'ordersRefund'])->name('orders.refund');

    Route::get('/account-orders', [AdminController::class, 'accountOrders'])->name('account-orders');
    Route::get('/account-orders/{accountOrder}', [AdminController::class, 'accountOrdersShow'])->name('account-orders.show');
    Route::patch('/account-orders/{accountOrder}/status', [AdminController::class, 'accountOrdersUpdateStatus'])->name('account-orders.status');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}/edit', [AdminController::class, 'usersEdit'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'usersUpdate'])->name('users.update');

    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');

    Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
    Route::post('/two-factor/{user}/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/{user}/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/two-factor/{user}', [TwoFactorController::class, 'destroy'])->name('two-factor.destroy');

    Route::get('/contact-inquiries', [AdminController::class, 'contactInquiries'])->name('contact-inquiries');
    Route::get('/contact-inquiries/{contactInquiry}', [AdminController::class, 'contactInquiriesShow'])->name('contact-inquiries.show');
    Route::patch('/contact-inquiries/{contactInquiry}/mark-read', [AdminController::class, 'contactInquiriesMarkRead'])->name('contact-inquiries.mark-read');
    Route::post('/contact-inquiries/{contactInquiry}/reply', [AdminController::class, 'contactInquiriesReply'])->name('contact-inquiries.reply');
    Route::delete('/contact-inquiries/{contactInquiry}', [AdminController::class, 'contactInquiriesDestroy'])->name('contact-inquiries.destroy');

    Route::get('/digiflazz/test', [AdminController::class, 'digiflazzTest'])->name('digiflazz.test');
});

Route::middleware(['auth:web'])->prefix('api')->name('api.')->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribeWeb'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribeWeb'])->name('push.unsubscribe');
    Route::post('/push/test', [PushSubscriptionController::class, 'testWeb'])->name('push.test');
    Route::get('/push/status', [PushSubscriptionController::class, 'statusWeb'])->name('push.status');

    Route::post('/vouchers/validate', [VoucherController::class, 'validateCode'])
        ->middleware('throttle:30,1')
        ->name('vouchers.validate');
});

Route::prefix('api')->name('api.')->group(function () {
    Route::post('/company-auth/verify', [App\Http\Controllers\CompanyAuthController::class, 'verify'])
        ->middleware(['jba-api-key', 'throttle:60,1'])
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
        ->name('company-auth.verify');
    Route::middleware(['jba-api-key', 'throttle:60,1'])->prefix('jba')->name('jba.')->group(function () {
        Route::get('/listings', [App\Http\Controllers\JbaListingApiController::class, 'index'])->name('listings.index');
        Route::get('/listings/{listing}', [App\Http\Controllers\JbaListingApiController::class, 'show'])->name('listings.show');
    });

    Route::get('/gacha', [GachaController::class, 'index'])->name('gacha.index');
    Route::post('/gacha/spin', [GachaController::class, 'spin'])
        ->middleware('throttle:20,1')
        ->name('gacha.spin');

});

Route::get('/johen-admin-secret/login', [AdminAuthController::class, 'showLoginForm'])
    ->middleware('guest:admin')
    ->name('admin.login');
Route::post('/johen-admin-secret/login', [AdminAuthController::class, 'login'])
    ->middleware('guest:admin');

Route::get('/johen-admin-secret/two-factor-challenge', [AdminAuthController::class, 'showTwoFactorChallenge'])
    ->middleware('guest:admin')
    ->name('admin.two-factor.challenge');
Route::post('/johen-admin-secret/two-factor-challenge', [AdminAuthController::class, 'twoFactorChallenge'])
    ->middleware('guest:admin');

Route::post('/johen-admin-secret/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');

require __DIR__.'/auth.php';
