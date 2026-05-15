<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// ─── Public Controllers ───
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductAutocompleteController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutAjaxController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StockNotificationController;

// ─── Auth Controllers ───
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\TwoFactorAuthController;

// ─── Account Controllers ───
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\AccountAddressController;
use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\AccountReviewController;
use App\Http\Controllers\SavedCardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\SubscriptionController;


// ─── Admin Controllers ───
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\InfoPageController as AdminInfoPageController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BulkPricingController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\VariantAttributeController as AdminVariantAttributeController;
use App\Http\Controllers\Admin\StockNotificationController as AdminStockNotificationController;
use App\Http\Controllers\Admin\ShippingController;

// ==========================================
// GLOBALS & PREFERENCES
// ==========================================
Route::post('/currency', [PreferenceController::class, 'updateCurrency'])->name('currency.update');
Route::post('/locale', [PreferenceController::class, 'updateLocale'])->name('locale.update');
Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/test-cards', function () { return view('test-cards'); })->name('test.cards');

// ==========================================
// PUBLIC: CORE PAGES
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// ==========================================
// PUBLIC: PRODUCTS & CATALOG
// ==========================================
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/variant', [ProductController::class, 'getVariant'])->name('products.getVariant');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/product-autocomplete', [ProductAutocompleteController::class, 'index'])->name('products.autocomplete');
Route::get('/category/{slug}', [ProductController::class, 'category'])->name('products.category');

Route::get('/search', function(Request $request) {
    return redirect()->route('products.index', ['search' => $request->q]);
})->name('search');

Route::get('/brand/{brand}', function($brand) {
    return redirect()->route('products.index', ['brand' => $brand]);
})->name('brand.show');

Route::get('/category/id/{category}', function($category) {
    return redirect()->route('products.index', ['category' => $category]);
})->name('category.show');

Route::post('/stock/notify', [StockNotificationController::class, 'subscribe'])->name('stock.notify');

// ==========================================
// PUBLIC: CART
// ==========================================
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{lineId}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{lineId}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// ==========================================
// PUBLIC: WISHLIST (SHARED/GUEST)
// ==========================================
Route::get('/w/{slug}', [WishlistController::class, 'shared'])->name('wishlist.shared');
Route::post('/wishlist/gift-add', [WishlistController::class, 'giftAddToCart'])->name('wishlist.giftAdd');

// ==========================================
// AUTHENTICATION
// ==========================================
Route::get('/auth/check', [AuthController::class, 'showEmailCheck'])->name('auth.emailCheck');
Route::post('/auth/check', [AuthController::class, 'checkEmail'])->name('auth.checkEmail');
Route::post('/password/check-breach', [RegisterController::class, 'checkBreach'])->name('password.checkBreach');

// 2FA Challenge & Verification (Outside auth middleware for login flow)
Route::get('/2fa/challenge', [TwoFactorAuthController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorAuthController::class, 'verify'])->name('2fa.verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.sendResetLink');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.showReset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');
});

// Logout handlers
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');
Route::post('/logout', function () {
    session()->forget('user_id');
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->name('logout');


// ==========================================
// AUTHENTICATED CUSTOMER ROUTES
// ==========================================
Route::middleware('auth')->group(function () {

    // ── Checkout ──
    Route::prefix('checkout')->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::post('/totals', [CheckoutAjaxController::class, 'totals'])->name('checkout.totals');
        Route::post('/discount/apply', [CheckoutAjaxController::class, 'applyDiscount'])->name('checkout.discount.apply');
        Route::post('/discount/remove', [CheckoutAjaxController::class, 'removeDiscount'])->name('checkout.discount.remove');
    });

    // ── Product Reviews ──
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/{review}/helpful', [ReviewController::class, 'markHelpful'])->name('reviews.helpful');
    Route::post('/reviews/{review}/report', [ReviewController::class, 'report'])->name('reviews.report');

    // ── Account Dashboard & Settings ──
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/login-detail', [AccountController::class, 'loginDetail'])->name('loginDetail');
        
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::post('/newsletter', [NewsletterController::class, 'updatePreference'])->name('newsletter.update');
        
        // Orders & Refunds
        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders');
        Route::get('/orders/{order_id}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{orderId}/refund', [AccountOrderController::class, 'requestRefund'])->name('orders.refund');
        Route::post('/orders/{order_id}/reorder', [AccountOrderController::class, 'reorder'])->name('orders.reorder');
        Route::get('/orders/{order}/invoice', [InvoiceController::class, 'download'])->name('orders.invoice');
        
        // Addresses & Cards
        Route::get('/addresses', [AccountAddressController::class, 'index'])->name('addresses');
        Route::post('/addresses', [AccountAddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address_id}', [AccountAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address_id}', [AccountAddressController::class, 'destroy'])->name('addresses.destroy');
        
        Route::get('/cards', [SavedCardController::class, 'index'])->name('cards');
        Route::delete('/cards/{cardId}', [SavedCardController::class, 'destroy'])->name('cards.destroy');
        Route::post('/cards/{cardId}/default', [SavedCardController::class, 'setDefault'])->name('cards.default');
        Route::post('/cards', [SavedCardController::class, 'store'])->name('cards.store');

        // Reviews
        Route::get('/reviews', [AccountReviewController::class, 'index'])->name('reviews');
        Route::get('/reviews/{review_id}/edit', [AccountReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{review_id}', [AccountReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review_id}', [AccountReviewController::class, 'destroy'])->name('reviews.destroy');

        // Security & 2FA
        Route::get('/security', [AccountSecurityController::class, 'index'])->name('security');
        Route::post('/change-password', [AccountSecurityController::class, 'changePassword'])->name('security.password');
        Route::post('/change-email', [AccountSecurityController::class, 'changeEmail'])->name('security.email');
        Route::post('/delete-account', [AccountSecurityController::class, 'deleteAccount'])->name('security.delete');
        
        Route::get('/2fa', [TwoFactorController::class, 'index'])->name('2fa');
        Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])->name('2fa.recovery');

        // Messages
        Route::get('/messages', [MessageController::class, 'index'])->name('messages');
        Route::post('/messages/{message}/read', [MessageController::class, 'markRead'])->name('messages.read');
        Route::post('/messages/read-all', [MessageController::class, 'markAllRead'])->name('messages.readAll');

        // Subscriptions
        Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
        Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('/subscription/save-item', [SubscriptionController::class, 'saveSsItem'])->name('subscription.ss.store');
        Route::patch('/subscription/save-item/{ssItem}', [SubscriptionController::class, 'updateSsItem'])->name('subscription.ss.update');
        Route::delete('/subscription/save-item/{ssItem}', [SubscriptionController::class, 'cancelSsItem'])->name('subscription.ss.cancel');

        // Payment History
        Route::get('/payments', [PaymentHistoryController::class, 'index'])->name('payments');
    });

    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // ── Wishlist ──
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::get('/wishlist/picker', [WishlistController::class, 'picker'])->name('wishlist.picker');
    Route::post('/wishlist/quick-create', [WishlistController::class, 'quickCreate'])->name('wishlist.quickCreate');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
    
    // Wildcard wishlist routes at the bottom
    Route::get('/wishlist/{wishlistId}', [WishlistController::class, 'show'])->name('wishlist.show');
    Route::put('/wishlist/{wishlistId}', [WishlistController::class, 'update'])->name('wishlist.update');
    Route::delete('/wishlist/{wishlistId}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{wishlistId}/share', [WishlistController::class, 'toggleShare'])->name('wishlist.toggleShare');
});


// ==========================================
// ADMIN DASHBOARD
// ==========================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');


	// ── Testing Error Pages (Admin Only) ──
    Route::get('/test-error/{page}', function ($page) {
        $viewName = 'errors.' . $page;

        if (view()->exists($viewName)) {
            return view($viewName);
        }

        return "The view [{$viewName}.blade.php] does not exist.";
    })->name('test-error');

    // ── Orders & Refunds ──
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');

    Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/{refund}', [AdminRefundController::class, 'show'])->name('refunds.show');
    Route::post('/refunds/{refund}/approve', [AdminRefundController::class, 'approve'])->name('refunds.approve');
    Route::post('/refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');
    Route::post('/refunds/{refund}/reply', [AdminRefundController::class, 'reply'])->name('refunds.reply');

    // ── Products & Inventory ──
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/toggle', [AdminProductController::class, 'toggleActive'])->name('products.toggle');
    
    // Variant CRUD
    Route::post('/products/{product}/variants', [AdminProductController::class, 'storeVariant'])->name('products.variants.store');
    Route::put('/products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant'])->name('products.variants.update');
    Route::delete('/products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant'])->name('products.variants.destroy');
    Route::post('/products/{product}/variants/{variant}/images', [AdminProductController::class, 'storeImage'])->name('products.images.store');
    Route::delete('/products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::post('/products/{product}/specs', [AdminProductController::class, 'updateSpecs'])->name('products.specs.update');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::put('/inventory/{variant}', [InventoryController::class, 'update'])->name('inventory.update');

    // Bulk Pricing
    Route::get('/bulk-pricing/{variantId}', [BulkPricingController::class, 'index'])->name('bulk-pricing.index');
    Route::post('/bulk-pricing/{variantId}', [BulkPricingController::class, 'store'])->name('bulk-pricing.store');
    Route::put('/bulk-pricing/update/{tierId}', [BulkPricingController::class, 'update'])->name('bulk-pricing.update');
    Route::delete('/bulk-pricing/delete/{tierId}', [BulkPricingController::class, 'destroy'])->name('bulk-pricing.destroy');

    // Attributes & Options
    Route::get('/variant-attributes', [AdminVariantAttributeController::class, 'index'])->name('variant-attributes.index');
    Route::post('/variant-attributes', [AdminVariantAttributeController::class, 'storeAttribute'])->name('variant-attributes.store');
    Route::put('/variant-attributes/{attribute}', [AdminVariantAttributeController::class, 'updateAttribute'])->name('variant-attributes.update');
    Route::delete('/variant-attributes/{attribute}', [AdminVariantAttributeController::class, 'destroyAttribute'])->name('variant-attributes.destroy');
    Route::post('/variant-attributes/{attribute}/options', [AdminVariantAttributeController::class, 'storeOption'])->name('variant-attributes.options.store');
    Route::put('/variant-options/{option}', [AdminVariantAttributeController::class, 'updateOption'])->name('variant-attributes.options.update');
    Route::delete('/variant-options/{option}', [AdminVariantAttributeController::class, 'destroyOption'])->name('variant-attributes.options.destroy');

    Route::get('/brands', [AdminBrandController::class, 'index'])->name('brands.index');
    Route::post('/brands', [AdminBrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [AdminBrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [AdminBrandController::class, 'destroy'])->name('brands.destroy');

    // ── Customers & Reviews ──
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/toggle', [CustomerController::class, 'toggleActive'])->name('customers.toggle');

    // Admin Review Management
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::post('/reviews/reports/{report}/resolve', [AdminReviewController::class, 'markReportReviewed'])->name('reviews.reports.resolve');
    Route::post('/reviews/reports/{report}/dismiss', [AdminReviewController::class, 'dismissReport'])->name('reviews.reports.dismiss');

    // ── Marketing & Operations ──
    Route::get('/discounts', [DiscountController::class, 'index'])->name('discounts.index');
    Route::get('/discounts/create', [DiscountController::class, 'create'])->name('discounts.create');
    Route::post('/discounts', [DiscountController::class, 'store'])->name('discounts.store');
    Route::get('/discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('discounts.edit');
    Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update');
    Route::post('/discounts/{discount}/toggle', [DiscountController::class, 'toggleActive'])->name('discounts.toggle');

    Route::get('/contacts', [AdminContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{contact}', [AdminContactController::class, 'show'])->name('contacts.show');
    Route::post('/contacts/{contact}/status', [AdminContactController::class, 'updateStatus'])->name('contacts.updateStatus');

    Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletters.index');
    
    // Messages
    Route::get('/messages', [AdminMessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [AdminMessageController::class, 'store'])->name('messages.store');

    // Subscriptions
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/plan', [AdminSubscriptionController::class, 'updatePlan'])->name('subscriptions.updatePlan');
    Route::post('/subscriptions/subscribe-save', [AdminSubscriptionController::class, 'updateSsSettings'])->name('subscriptions.updateSs');
    Route::get('/subscriptions/subscribers', [AdminSubscriptionController::class, 'subscribers'])->name('subscriptions.subscribers');
    Route::get('/subscriptions/subscribers/{subscription}', [AdminSubscriptionController::class, 'showSubscriber'])->name('subscriptions.showSubscriber');
    Route::post('/subscriptions/subscribers/{subscription}/cancel', [AdminSubscriptionController::class, 'cancelSubscription'])->name('subscriptions.cancelSubscription');
    Route::get('/subscriptions/save-items', [AdminSubscriptionController::class, 'ssItems'])->name('subscriptions.ssItems');
    Route::post('/subscriptions/save-items/{ssItem}/suspend', [AdminSubscriptionController::class, 'suspendSsItem'])->name('subscriptions.suspendSs');
    Route::post('/subscriptions/save-items/{ssItem}/resume', [AdminSubscriptionController::class, 'resumeSsItem'])->name('subscriptions.resumeSs');
    Route::post('/subscriptions/save-items/{ssItem}/cancel', [AdminSubscriptionController::class, 'cancelSsItem'])->name('subscriptions.cancelSs');

    // ── Configuration & Analytics ──
    Route::get('/shipping', [ShippingController::class, 'index'])->name('shipping.index');
    Route::post('/shipping', [ShippingController::class, 'store'])->name('shipping.store');
    Route::put('/shipping/{rate}', [ShippingController::class, 'update'])->name('shipping.update');
    Route::delete('/shipping/{rate}', [ShippingController::class, 'destroy'])->name('shipping.destroy');

    Route::get('/currencies', [AdminCurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [AdminCurrencyController::class, 'store'])->name('currencies.store');
    Route::put('/currencies/{code}', [AdminCurrencyController::class, 'update'])->name('currencies.update');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/export-csv', [AnalyticsController::class, 'exportCsv'])->name('analytics.csv');
    Route::get('/analytics/export-pdf', [AnalyticsController::class, 'exportPdf'])->name('analytics.pdf');

    // Stock Notifications
    Route::resource('stock-notifications', AdminStockNotificationController::class)->except(['create', 'store', 'edit', 'update']);
    Route::post('/stock-notifications/bulk-delete', [AdminStockNotificationController::class, 'bulkDestroy'])->name('stock-notifications.bulk-destroy');
    Route::post('/stock-notifications/{id}/mark-notified', [AdminStockNotificationController::class, 'markAsNotified'])->name('stock-notifications.mark-notified');
    Route::post('/stock-notifications/trigger-emails', [AdminStockNotificationController::class, 'triggerEmails'])->name('stock-notifications.trigger-emails');
    Route::get('/stock-notifications/product-variants', [AdminStockNotificationController::class, 'productVariants'])->name('stock-notifications.product-variants');

    // Info Pages
    Route::get('/info-pages', [AdminInfoPageController::class, 'index'])->name('info-pages.index');
    Route::get('/info-pages/{id}/edit', [AdminInfoPageController::class, 'edit'])->name('info-pages.edit');
    Route::put('/info-pages/{id}', [AdminInfoPageController::class, 'update'])->name('info-pages.update');
    Route::post('/info-pages/reorder', [AdminInfoPageController::class, 'reorder'])->name('info-pages.reorder');
});

// ==========================================
// INFO, HELP & LEGAL (Static-ish Pages)
// ==========================================
Route::get('/page/{slug}', [InfoController::class, 'show'])->name('page.show');

// Help Section
Route::get('/shipping', [InfoController::class, 'shipping'])->name('shipping');
Route::get('/returns', [InfoController::class, 'returns'])->name('returns');
Route::get('/size-guide', [InfoController::class, 'sizeGuide'])->name('size-guide');
Route::get('/gift-cards', [InfoController::class, 'giftCards'])->name('gift-cards');
Route::get('/discounts', [InfoController::class, 'discounts'])->name('discounts');

Route::get('/help/shipping', [InfoController::class, 'shipping'])->name('help.shipping');
Route::get('/help/returns', [InfoController::class, 'returns'])->name('help.returns');
Route::get('/help/size-guide', [InfoController::class, 'sizeGuide'])->name('help.size_guide');
Route::get('/help/gift-cards', [InfoController::class, 'giftCards'])->name('help.gift_cards');
Route::get('/help/student-discount', [InfoController::class, 'discounts'])->name('help.student_discount');
Route::get('/help/teacher-discount', [InfoController::class, 'discounts'])->name('help.teacher_discount');
Route::get('/help/first-responder-discount', [InfoController::class, 'discounts'])->name('help.first_responder_discount');

// ==========================================
// ABOUT SECTION ROUTES
// ==========================================
Route::get('/about', [InfoController::class, 'about'])->name('about');

Route::get('/sustainability', [InfoController::class, 'sustainability'])->name('sustainability');
Route::get('/careers', [InfoController::class, 'careers'])->name('careers');
Route::get('/affiliates', [InfoController::class, 'affiliates'])->name('affiliates');

Route::get('/about/sustainability', [InfoController::class, 'sustainability'])->name('about.sustainability');
Route::get('/about/careers', [InfoController::class, 'careers'])->name('about.careers');
Route::get('/about/affiliates', [InfoController::class, 'affiliates'])->name('about.affiliates');

// Legal & Policies
Route::get('/privacy', [InfoController::class, 'privacy'])->name('privacy');
Route::get('/gdpr', [InfoController::class, 'gdpr'])->name('gdpr');
Route::get('/terms', [InfoController::class, 'terms'])->name('terms');
Route::get('/cookies', [InfoController::class, 'cookies'])->name('cookies');
Route::get('/faq', [InfoController::class, 'faq'])->name('faq');

// ==========================================
// PUBLIC: CART
// ==========================================
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/preview', [CartController::class, 'preview'])->name('cart.preview');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{lineId}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{lineId}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');   