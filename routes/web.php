<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\LiveChatChannelController;
use App\Http\Controllers\Admin\LiveChatAdminController;
use App\Http\Controllers\Admin\LiveChatChatController;
use App\Http\Controllers\Admin\LiveChatConversationController;
use App\Http\Controllers\Admin\LiveChatDashboardController;
use App\Http\Controllers\Admin\LiveChatOperatorController;
use App\Http\Controllers\Admin\PushSubscriptionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGachaPrizeController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\GachaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\LCAdmin\LCAdminDashboardController;
use App\Http\Controllers\LCAdmin\LCAdminConversationController;
use App\Http\Controllers\CsAdmin\CsConversationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReviewController;
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

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/products/by-brand', [HomeController::class, 'getProductsByBrand'])->name('products.by-brand');

Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::get('/api/products', [HomeController::class, 'getApiProducts'])->name('api.products');
Route::get('/api/brands/search', [HomeController::class, 'searchBrands'])->name('api.brands.search');
Route::get('/api/payment-methods', [HomeController::class, 'getPaymentMethods'])->name('api.payment-methods');
Route::get('/api/orders/check', [HomeController::class, 'checkOrder'])->name('api.orders.check');
Route::post('/api/account/check', [HomeController::class, 'checkAccount'])
    ->name('api.account.check')
    ->middleware('throttle:30,1');
Route::get('/api/push/latest-message', [PushSubscriptionController::class, 'latestNotification'])
    ->name('api.push.latest-message');
Route::get('/games/{brand:name}', [HomeController::class, 'gameDetail'])->name('games.show');
Route::get('/cek-transaksi', [HomeController::class, 'checkTransaction'])->name('check.transaction');
Route::get('/jual-beli-akun', [App\Http\Controllers\HomeController::class, 'jualBeliAkun'])->name('jual-beli-akun');
Route::get('/jual-beli-akun/{game}', [App\Http\Controllers\HomeController::class, 'jualBeliAkunGame'])
    ->where('game', 'mlbb|pubg|efootball|fcm|ff|roblox|valorant')
    ->name('jual-beli-akun.game');
Route::get('/jual-beli-akun/pesanan-saya', [App\Http\Controllers\HomeController::class, 'jualBeliAkunOrders'])
    ->name('jual-beli-akun.orders')
    ->middleware('auth:web');
Route::get('/jual-beli-akun/{listing}', [App\Http\Controllers\HomeController::class, 'jualBeliAkunDetail'])->name('jual-beli-akun.detail');
Route::get('/jual-beli-akun/{listing}/checkout', [App\Http\Controllers\HomeController::class, 'jualBeliAkunCheckout'])->name('jual-beli-akun.checkout');
Route::post('/jual-beli-akun/{listing}/checkout', [App\Http\Controllers\HomeController::class, 'jualBeliAkunCheckoutStore'])->name('jual-beli-akun.checkout.store');
Route::get('/jual-beli-akun/order/{accountOrder}/payment', [App\Http\Controllers\HomeController::class, 'jualBeliAkunPayment'])->name('jual-beli-akun.payment');
Route::get('/jual-beli-akun/order/{accountOrder}/status', [App\Http\Controllers\HomeController::class, 'jualBeliAkunPaymentStatus'])->name('jual-beli-akun.payment.status');
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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dituju oleh resources/views/layouts/navigation.blade.php (default Breeze).
    // Area pelanggan saat ini = daftar pesanan + saldo.
    Route::get('/dashboard', fn () => redirect()->route('orders.my'))->name('dashboard');

    Route::get('/orders', [OrderController::class, 'myOrders'])->name('orders.my');
    Route::get('/pesan-saya', [HomeController::class, 'myInquiries'])->name('my-inquiries');

    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers/claim', [VoucherController::class, 'claim'])->name('vouchers.claim');
});

Route::get('/orders/create/{product}', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::post('/orders/reorder/{order}', [OrderController::class, 'reorder'])->name('orders.reorder');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

/*
 * Keranjang belanja + checkout.
 *
 * Wajib login (auth:web): keranjang disimpan di database supaya tidak hilang
 * saat ganti perangkat, dan isi keranjang selalu punya user_id untuk retrace
 * pesanan. Alur top-up yang lebih lama (Beli Sekarang / pesan inline di
 * game-detail) tetap terbuka untuk guest.
 */
Route::middleware('auth:web')->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/keranjang', [CartController::class, 'store'])->name('cart.store');
    Route::post('/keranjang/kosongkan', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('/keranjang/jumlah', [CartController::class, 'count'])->name('cart.count');
Route::get('/keranjang/state', [CartController::class, 'state'])->name('cart.state');
    Route::patch('/keranjang/item/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/keranjang/item/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/{checkout}/status', [CheckoutController::class, 'status'])->name('checkout.status');
    Route::post('/checkout/{checkout}/simulasi', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
    Route::get('/checkout/{checkout}/pembayaran', [CheckoutController::class, 'payment'])->name('checkout.payment');
});

Route::get('/payment/detail/{order}', [PaymentController::class, 'detail'])->name('payment.detail');
Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/status/{order}', [PaymentController::class, 'status'])->name('payment.status');

Route::post('/payment/simulate/{order}', [PaymentController::class, 'simulatePay'])
    ->name('payment.simulate');

Route::post('/payment/notification', [PaymentController::class, 'notificationHandler'])->name('payment.notification');

Route::post('/reviews/{order}', [ReviewController::class, 'store'])->name('reviews.store');

Route::post('/digiflazz/callback', [PaymentController::class, 'digiflazzCallback'])->name('payment.digiflazz.callback');

Route::get('/admin', function () {
    if (Auth::guard('admin')->check()) {
        $user = Auth::guard('admin')->user();
        if ($user->isLiveChatAdmin()) {
            return redirect()->route('lcadmin.conversations');
        }
        if ($user->isLiveChatCs()) {
            return redirect()->route('csadmin.conversations');
        }
        return redirect()->route('admin.dashboard');
    }
    return view('admin.auth.login');
})->name('admin.index');

Route::middleware(['auth:admin', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/products/game/{brand}', [AdminController::class, 'productsByGame'])->name('products.game');
    Route::patch('/products/game/{brand}/markup', [AdminController::class, 'productsApplyMarkup'])->name('products.markup');
    Route::patch('/products/{product}/selling-price', [AdminController::class, 'productsUpdateSellingPrice'])->name('products.selling-price');
    Route::patch('/products/{product}/toggle', [AdminController::class, 'productsToggle'])->name('products.toggle');
    Route::post('/products/sync', [AdminController::class, 'productsSync'])->name('products.sync');

    Route::get('/account-listings', [App\Http\Controllers\AdminAccountListingController::class, 'index'])->name('account-listings');
    Route::post('/account-listings/sync', [App\Http\Controllers\AdminAccountListingController::class, 'sync'])->name('account-listings.sync');
    Route::get('/account-listings/sync/status', [App\Http\Controllers\AdminAccountListingController::class, 'syncStatus'])->name('account-listings.sync-status');
    Route::get('/account-listings/create', [App\Http\Controllers\AdminAccountListingController::class, 'create'])->name('account-listings.create');
    Route::post('/account-listings', [App\Http\Controllers\AdminAccountListingController::class, 'store'])->name('account-listings.store');
    Route::get('/account-listings/{accountListing}/edit', [App\Http\Controllers\AdminAccountListingController::class, 'edit'])->name('account-listings.edit');
    Route::put('/account-listings/{accountListing}', [App\Http\Controllers\AdminAccountListingController::class, 'update'])->name('account-listings.update');
    Route::patch('/account-listings/{accountListing}/toggle', [App\Http\Controllers\AdminAccountListingController::class, 'toggle'])->name('account-listings.toggle');
    Route::delete('/account-listings/{accountListing}', [App\Http\Controllers\AdminAccountListingController::class, 'destroy'])->name('account-listings.destroy');

    Route::get('/jba-game-cards', [App\Http\Controllers\Admin\JbaGameCardController::class, 'index'])->name('jba-game-cards');
    Route::put('/jba-game-cards/{brand}', [App\Http\Controllers\Admin\JbaGameCardController::class, 'update'])->name('jba-game-cards.update');
    Route::delete('/jba-game-cards/{brand}', [App\Http\Controllers\Admin\JbaGameCardController::class, 'destroy'])->name('jba-game-cards.destroy');

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

    Route::get('/event-themes', [App\Http\Controllers\Admin\EventThemeController::class, 'index'])->name('event-themes');
    Route::get('/event-themes/create', [App\Http\Controllers\Admin\EventThemeController::class, 'create'])->name('event-themes.create');
    Route::post('/event-themes', [App\Http\Controllers\Admin\EventThemeController::class, 'store'])->name('event-themes.store');
    Route::get('/event-themes/{theme}/edit', [App\Http\Controllers\Admin\EventThemeController::class, 'edit'])->name('event-themes.edit');
    Route::put('/event-themes/{theme}', [App\Http\Controllers\Admin\EventThemeController::class, 'update'])->name('event-themes.update');
    Route::patch('/event-themes/{theme}/toggle', [App\Http\Controllers\Admin\EventThemeController::class, 'toggle'])->name('event-themes.toggle');
    Route::post('/event-themes/{theme}/set-default', [App\Http\Controllers\Admin\EventThemeController::class, 'setDefault'])->name('event-themes.set-default');
    Route::post('/event-themes/reset-default', [App\Http\Controllers\Admin\EventThemeController::class, 'resetDefault'])->name('event-themes.reset-default');
    Route::get('/event-themes/{theme}/preview', [App\Http\Controllers\Admin\EventThemeController::class, 'preview'])->name('event-themes.preview');
    Route::delete('/event-themes/{theme}', [App\Http\Controllers\Admin\EventThemeController::class, 'destroy'])->name('event-themes.destroy');

    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AdminController::class, 'ordersShow'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminController::class, 'ordersUpdateStatus'])->name('orders.status');

    Route::get('/account-orders', [AdminController::class, 'accountOrders'])->name('account-orders');
    Route::get('/account-orders/{accountOrder}', [AdminController::class, 'accountOrdersShow'])->name('account-orders.show');
    Route::patch('/account-orders/{accountOrder}/status', [AdminController::class, 'accountOrdersUpdateStatus'])->name('account-orders.status');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'usersStore'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'usersEdit'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'usersUpdate'])->name('users.update');

    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');

    Route::get('/contact-inquiries', [AdminController::class, 'contactInquiries'])->name('contact-inquiries');
    Route::get('/contact-inquiries/{contactInquiry}', [AdminController::class, 'contactInquiriesShow'])->name('contact-inquiries.show');
    Route::patch('/contact-inquiries/{contactInquiry}/mark-read', [AdminController::class, 'contactInquiriesMarkRead'])->name('contact-inquiries.mark-read');
    Route::post('/contact-inquiries/{contactInquiry}/reply', [AdminController::class, 'contactInquiriesReply'])->name('contact-inquiries.reply');
    Route::delete('/contact-inquiries/{contactInquiry}', [AdminController::class, 'contactInquiriesDestroy'])->name('contact-inquiries.destroy');

    Route::get('/digiflazz/test', [AdminController::class, 'digiflazzTest'])->name('digiflazz.test');
});

Route::middleware(['auth:admin', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/live-chat', [LiveChatDashboardController::class, 'index'])->name('live-chat.dashboard');

    Route::get('/live-chat/channels', [LiveChatChannelController::class, 'index'])->name('live-chat.channels');
    Route::put('/live-chat/channels/{channel}', [LiveChatChannelController::class, 'update'])->name('live-chat.channels.update');
    Route::patch('/live-chat/channels/{channel}/toggle', [LiveChatChannelController::class, 'toggle'])->name('live-chat.channels.toggle');

    Route::get('/live-chat/conversations', [LiveChatConversationController::class, 'index'])->name('live-chat.conversations');
    Route::get('/live-chat/conversations/{conversation}', [LiveChatConversationController::class, 'show'])->name('live-chat.conversations.show');
    Route::get('/live-chat/conversations/{conversation}/poll', [LiveChatConversationController::class, 'poll'])->name('live-chat.conversations.poll');
    Route::post('/live-chat/conversations/{conversation}/reply', [LiveChatConversationController::class, 'reply'])->name('live-chat.conversations.reply');
    Route::patch('/live-chat/conversations/{conversation}/close', [LiveChatConversationController::class, 'close'])->name('live-chat.conversations.close');
    Route::patch('/live-chat/conversations/{conversation}/reopen', [LiveChatConversationController::class, 'reopen'])->name('live-chat.conversations.reopen');
    Route::delete('/live-chat/messages/{message}', [LiveChatChatController::class, 'deleteMessage'])->name('live-chat.messages.delete');
    Route::post('/live-chat/messages/{message}/hide', [LiveChatChatController::class, 'hideMessage'])->name('live-chat.messages.hide');

    Route::post('/push/{guard}/subscribe', [PushSubscriptionController::class, 'subscribe'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.subscribe');
    Route::post('/push/{guard}/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.unsubscribe');
    Route::post('/push/{guard}/test', [PushSubscriptionController::class, 'test'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.test');
    Route::get('/push/{guard}/status', [PushSubscriptionController::class, 'status'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.status');

    Route::get('/live-chat/operators', [LiveChatOperatorController::class, 'index'])->name('live-chat.operators');
    Route::post('/live-chat/operators', [LiveChatOperatorController::class, 'store'])->name('live-chat.operators.store');
    Route::patch('/live-chat/operators/{operator}/toggle', [LiveChatOperatorController::class, 'toggle'])->name('live-chat.operators.toggle');
    Route::delete('/live-chat/operators/{operator}', [LiveChatOperatorController::class, 'destroy'])->name('live-chat.operators.destroy');
    Route::put('/live-chat/operators/{operator}/schedule', [LiveChatOperatorController::class, 'schedule'])->name('live-chat.operators.schedule');

    Route::get('/live-chat/admins', [LiveChatAdminController::class, 'index'])->name('live-chat.admins');
    Route::get('/live-chat/admins/{channel}/edit', [LiveChatAdminController::class, 'edit'])->name('live-chat.admins.edit');
    Route::put('/live-chat/admins/{channel}', [LiveChatAdminController::class, 'update'])->name('live-chat.admins.update');
});

Route::middleware(['auth:admin', 'live-chat-admin'])->prefix('lcadmin')->name('lcadmin.')->group(function () {
    Route::get('/dashboard', [LCAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/conversations', [LCAdminConversationController::class, 'index'])->name('conversations');
    Route::get('/conversations/archived', [LCAdminConversationController::class, 'archived'])->name('conversations.archived');
    Route::get('/conversations/archived-unread', [LCAdminConversationController::class, 'archivedUnread'])->name('conversations.archived-unread');
    Route::patch('/conversations/{conversation}/restore', [LCAdminConversationController::class, 'restore'])->name('conversations.restore');
    Route::get('/conversations/{conversation}', [LCAdminConversationController::class, 'show'])->name('conversations.show');
    Route::get('/conversations/{conversation}/poll', [LCAdminConversationController::class, 'poll'])->name('conversations.poll');
    Route::post('/conversations/{conversation}/reply', [LCAdminConversationController::class, 'reply'])->name('conversations.reply');
    Route::patch('/conversations/{conversation}/close', [LCAdminConversationController::class, 'close'])->name('conversations.close');
    Route::patch('/conversations/{conversation}/reopen', [LCAdminConversationController::class, 'reopen'])->name('conversations.reopen');
            Route::patch('/conversations/{conversation}/favorite', [LCAdminConversationController::class, 'toggleFavorite'])->name('conversations.favorite');
            Route::patch('/conversations/{conversation}/pin', [LCAdminConversationController::class, 'togglePin'])->name('conversations.pin');
            Route::delete('/conversations/{conversation}', [LCAdminConversationController::class, 'deleteConversation'])->name('conversations.delete');
            Route::get('/conversations/{conversation}/messages', [LCAdminConversationController::class, 'loadMessages'])->name('conversations.messages');
            Route::patch('/conversations/{conversation}/archive', [LCAdminConversationController::class, 'archive'])->name('conversations.archive');
    Route::delete('/messages/{message}', [LCAdminConversationController::class, 'deleteMessage'])->name('messages.delete');
    Route::post('/messages/{message}/hide', [LCAdminConversationController::class, 'hideMessage'])->name('messages.hide');

    Route::post('/push/{guard}/subscribe', [PushSubscriptionController::class, 'subscribe'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.subscribe');
    Route::post('/push/{guard}/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.unsubscribe');
    Route::post('/push/{guard}/test', [PushSubscriptionController::class, 'test'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.test');
    Route::get('/push/{guard}/status', [PushSubscriptionController::class, 'status'])->whereIn('guard', ['admin', 'lcadmin'])->name('push.status');
});

Route::middleware(['auth:admin', 'live-chat-cs'])->prefix('csadmin')->name('csadmin.')->group(function () {
    Route::get('/conversations', [CsConversationController::class, 'index'])->name('conversations');
    Route::get('/conversations/archived', [CsConversationController::class, 'archived'])->name('conversations.archived');
    Route::get('/conversations/archived-count', [CsConversationController::class, 'archivedCount'])->name('conversations.archived-count');
    Route::get('/conversations/{conversation}', [CsConversationController::class, 'show'])->name('conversations.show');
    Route::get('/conversations/{conversation}/poll', [CsConversationController::class, 'poll'])->name('conversations.poll');
    Route::get('/conversations/{conversation}/messages', [CsConversationController::class, 'loadMessages'])->name('conversations.messages');
    Route::post('/conversations/{conversation}/reply', [CsConversationController::class, 'reply'])->name('conversations.reply');
    Route::patch('/conversations/{conversation}/close', [CsConversationController::class, 'close'])->name('conversations.close');
    Route::patch('/conversations/{conversation}/reopen', [CsConversationController::class, 'reopen'])->name('conversations.reopen');
    Route::patch('/conversations/{conversation}/archive', [CsConversationController::class, 'archive'])->name('conversations.archive');
    Route::patch('/conversations/{conversation}/restore', [CsConversationController::class, 'restore'])->name('conversations.restore');
    Route::delete('/conversations/{conversation}', [CsConversationController::class, 'deleteConversation'])->name('conversations.delete');
    Route::delete('/messages/{message}', [CsConversationController::class, 'deleteMessage'])->name('messages.delete');
    Route::post('/messages/{message}/hide', [CsConversationController::class, 'hideMessage'])->name('messages.hide');

    Route::post('/push/{guard}/subscribe', [PushSubscriptionController::class, 'subscribe'])->whereIn('guard', ['csadmin'])->name('push.subscribe');
    Route::post('/push/{guard}/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->whereIn('guard', ['csadmin'])->name('push.unsubscribe');
    Route::post('/push/{guard}/test', [PushSubscriptionController::class, 'test'])->whereIn('guard', ['csadmin'])->name('push.test');
    Route::get('/push/{guard}/status', [PushSubscriptionController::class, 'status'])->whereIn('guard', ['csadmin'])->name('push.status');
});

Route::middleware(['auth:web'])->prefix('api')->name('api.')->group(function () {
    Route::get('/live-chat/channels', [LiveChatController::class, 'channels'])->name('live-chat.channels');
    Route::get('/live-chat/conversation/{channelSlug}', [LiveChatController::class, 'getConversation'])->name('live-chat.conversation');
    Route::get('/live-chat/messages/{conversation}', [LiveChatController::class, 'messages'])->name('live-chat.messages');
    Route::post('/live-chat/messages', [LiveChatController::class, 'sendMessage'])->name('live-chat.messages.store');
    Route::post('/live-chat/media/upload', [LiveChatController::class, 'uploadMedia'])->name('live-chat.media.upload');
    Route::patch('/live-chat/conversation/{conversation}/read', [LiveChatController::class, 'markRead'])->name('live-chat.mark-read');
    Route::get('/live-chat/unread', [LiveChatController::class, 'unreadCount'])->name('live-chat.unread');
    Route::delete('/live-chat/messages/{message}', [LiveChatController::class, 'deleteMessage'])->name('live-chat.messages.delete');
    Route::post('/live-chat/messages/{message}/hide', [LiveChatController::class, 'hideMessage'])->name('live-chat.messages.hide');
    Route::post('/live-chat/messages/{message}/reaction', [LiveChatController::class, 'toggleReaction'])->name('live-chat.messages.reaction');
    Route::post('/live-chat/messages/{message}/star', [LiveChatController::class, 'toggleStar'])->name('live-chat.messages.star');

    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribeWeb'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribeWeb'])->name('push.unsubscribe');
    Route::post('/push/test', [PushSubscriptionController::class, 'testWeb'])->name('push.test');
    Route::get('/push/status', [PushSubscriptionController::class, 'statusWeb'])->name('push.status');

    Route::post('/vouchers/validate', [VoucherController::class, 'validateCode'])
        ->middleware('throttle:30,1')
        ->name('vouchers.validate');
});

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/gacha', [GachaController::class, 'index'])->name('gacha.index');
    Route::post('/gacha/spin', [GachaController::class, 'spin'])
        ->middleware('throttle:20,1')
        ->name('gacha.spin');

    Route::get('/live-chat/guest/channels', [LiveChatController::class, 'guestChannels'])->name('live-chat.guest.channels');
    Route::post('/live-chat/guest/conversation', [LiveChatController::class, 'guestConversation'])->name('live-chat.guest.conversation');
    Route::get('/live-chat/guest/messages/{conversation}', [LiveChatController::class, 'guestMessages'])->name('live-chat.guest.messages');
    Route::post('/live-chat/guest/messages', [LiveChatController::class, 'guestSendMessage'])->name('live-chat.guest.messages.store');
    Route::patch('/live-chat/guest/conversation/{conversation}/read', [LiveChatController::class, 'guestMarkRead'])->name('live-chat.guest.mark-read');
    Route::post('/live-chat/guest/conversation/{conversation}/route-back', [LiveChatController::class, 'guestRouteBack'])->name('live-chat.guest.route-back');
    Route::get('/live-chat/guest/unread', [LiveChatController::class, 'guestUnreadCount'])->name('live-chat.guest.unread');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])
    ->middleware('guest:admin')
    ->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('guest:admin');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');

require __DIR__.'/auth.php';
