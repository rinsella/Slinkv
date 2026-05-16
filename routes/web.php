<?php

use App\Http\Controllers\Admin\AdminAbuseReportController;
use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBlockedDomainController;
use App\Http\Controllers\Admin\AdminBlockedIpController;
use App\Http\Controllers\Admin\AdminBotLogController;
use App\Http\Controllers\Admin\AdminBotRuleController;
use App\Http\Controllers\Admin\AdminClickLogController;
use App\Http\Controllers\Admin\AdminContactMessageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminHealthController;
use App\Http\Controllers\Admin\AdminLinkController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

// =================== Public ===================
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/quick-shorten', [PublicController::class, 'quickShorten'])->middleware('throttle:20,1')->name('quick-shorten');
Route::get('/solusi', [PublicController::class, 'solutions'])->name('solutions');
Route::get('/cara-kerja', [PublicController::class, 'howItWorks'])->name('how-it-works');
Route::get('/paket', [PublicController::class, 'pricing'])->name('pricing');
Route::get('/artikel', [PublicController::class, 'articles'])->name('articles');
Route::get('/artikel/{slug}', [PublicController::class, 'articleShow'])->name('articles.show');
Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
Route::get('/tentang', [PublicController::class, 'about'])->name('about');
Route::get('/kontak', [PublicController::class, 'contact'])->name('contact');
Route::post('/kontak', [PublicController::class, 'contactStore'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/refund-policy', [PublicController::class, 'refund'])->name('refund');
Route::get('/acceptable-use-policy', [PublicController::class, 'aup'])->name('aup');
Route::get('/robots.txt', [PublicController::class, 'robots']);
Route::get('/sitemap.xml', [PublicController::class, 'sitemap']);

// Public abuse report
Route::get('/abuse', [PublicController::class, 'abuse'])->name('abuse');
Route::post('/abuse', [PublicController::class, 'abuseStore'])->middleware('throttle:5,1')->name('abuse.store');

// Password-protected shortlink unlock (handled separately so it doesn't collide with /{slug})
Route::post('/{slug}/unlock', [RedirectController::class, 'unlock'])
    ->where('slug', '[A-Za-z0-9_-]{1,32}')
    ->middleware('throttle:20,1')
    ->name('redirect.unlock');

// =================== Auth ===================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// =================== Dashboard ===================
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::get('/links/create', [LinkController::class, 'create'])->name('links.create');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');
    Route::get('/links/{link}', [LinkController::class, 'show'])->name('links.show');
    Route::get('/links/{link}/edit', [LinkController::class, 'edit'])->name('links.edit');
    Route::put('/links/{link}', [LinkController::class, 'update'])->name('links.update');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');
    Route::patch('/links/{link}/toggle', [LinkController::class, 'toggle'])->name('links.toggle');
    Route::get('/links/{link}/analytics', [LinkController::class, 'analytics'])->name('links.analytics');
    Route::get('/links/{link}/qr', [LinkController::class, 'qr'])->name('links.qr');
    Route::get('/links/{link}/qr.png', [LinkController::class, 'qrPng'])->name('links.qr.png');
    Route::get('/links/{link}/export.csv', [LinkController::class, 'exportCsv'])->name('links.export');
    Route::get('/links/{link}/audit-report', [LinkController::class, 'auditReport'])->name('links.audit-report');

    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('statistics');
    Route::get('/location-device', [DashboardController::class, 'locationDevice'])->name('location-device');
    Route::get('/sources', [DashboardController::class, 'sources'])->name('sources');
    Route::get('/referral', [DashboardController::class, 'referral'])->name('referral');
    Route::get('/billing', [DashboardController::class, 'billing'])->name('billing');
    Route::post('/billing/checkout/{plan}', [DashboardController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/invoices/{payment}', [DashboardController::class, 'invoice'])->name('billing.invoice');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::patch('/settings/profile', [DashboardController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/password', [DashboardController::class, 'updatePassword'])->name('settings.password');
});

// =================== Admin ===================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::resource('users', AdminUserController::class)->except([]);
    Route::patch('users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::patch('users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    Route::patch('users/{user}/change-plan', [AdminUserController::class, 'changePlan'])->name('users.change-plan');

    Route::resource('links', AdminLinkController::class)->except(['create', 'store']);
    Route::patch('links/{link}/toggle', [AdminLinkController::class, 'toggle'])->name('links.toggle');
    Route::patch('links/{link}/flag', [AdminLinkController::class, 'flag'])->name('links.flag');
    Route::patch('links/{link}/unflag', [AdminLinkController::class, 'unflag'])->name('links.unflag');

    Route::get('click-logs', [AdminClickLogController::class, 'index'])->name('click-logs');
    Route::get('bot-logs', [AdminBotLogController::class, 'index'])->name('bot-logs');

    Route::resource('plans', AdminPlanController::class)->except(['show']);
    Route::patch('plans/{plan}/toggle', [AdminPlanController::class, 'toggle'])->name('plans.toggle');

    Route::resource('articles', AdminArticleController::class)->except(['show']);
    Route::patch('articles/{article}/publish', [AdminArticleController::class, 'publish'])->name('articles.publish');
    Route::patch('articles/{article}/draft', [AdminArticleController::class, 'draft'])->name('articles.draft');

    Route::resource('faqs', AdminFaqController::class)->except(['show']);
    Route::patch('faqs/{faq}/toggle', [AdminFaqController::class, 'toggle'])->name('faqs.toggle');

    Route::resource('subscriptions', AdminSubscriptionController::class)->except(['create', 'store', 'destroy']);
    Route::patch('subscriptions/{subscription}/activate', [AdminSubscriptionController::class, 'activate'])->name('subscriptions.activate');
    Route::patch('subscriptions/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::patch('subscriptions/{subscription}/extend', [AdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');

    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::patch('payments/{payment}/mark-paid', [AdminPaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::patch('payments/{payment}/mark-failed', [AdminPaymentController::class, 'markFailed'])->name('payments.mark-failed');
    Route::patch('payments/{payment}/mark-expired', [AdminPaymentController::class, 'markExpired'])->name('payments.mark-expired');
    Route::patch('payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    Route::get('contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('contact-messages/{message}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::patch('contact-messages/{message}/read', [AdminContactMessageController::class, 'markRead'])->name('contact-messages.read');
    Route::patch('contact-messages/{message}/replied', [AdminContactMessageController::class, 'markReplied'])->name('contact-messages.replied');
    Route::delete('contact-messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

    Route::resource('blocked-domains', AdminBlockedDomainController::class)->except(['show']);
    Route::patch('blocked-domains/{blocked_domain}/toggle', [AdminBlockedDomainController::class, 'toggle'])->name('blocked-domains.toggle');

    Route::resource('blocked-ips', AdminBlockedIpController::class)->except(['show']);
    Route::patch('blocked-ips/{blocked_ip}/toggle', [AdminBlockedIpController::class, 'toggle'])->name('blocked-ips.toggle');

    Route::resource('bot-rules', AdminBotRuleController::class)->except(['show']);
    Route::patch('bot-rules/{bot_rule}/toggle', [AdminBotRuleController::class, 'toggle'])->name('bot-rules.toggle');

    Route::get('abuse-reports', [AdminAbuseReportController::class, 'index'])->name('abuse-reports.index');
    Route::get('abuse-reports/{abuse_report}', [AdminAbuseReportController::class, 'show'])->name('abuse-reports.show');
    Route::patch('abuse-reports/{abuse_report}/review', [AdminAbuseReportController::class, 'review'])->name('abuse-reports.review');
    Route::patch('abuse-reports/{abuse_report}/close', [AdminAbuseReportController::class, 'close'])->name('abuse-reports.close');
    Route::patch('abuse-reports/{abuse_report}/disable-link', [AdminAbuseReportController::class, 'disableLink'])->name('abuse-reports.disable-link');
    Route::delete('abuse-reports/{abuse_report}', [AdminAbuseReportController::class, 'destroy'])->name('abuse-reports.destroy');

    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');

    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings');
    Route::match(['post', 'put', 'patch'], 'settings', [AdminSettingController::class, 'update'])->name('settings.update');

    Route::get('health-check', AdminHealthController::class)->name('health-check');
});

// =================== Catch-all redirect (MUST BE LAST) ===================
Route::get('/{slug}', RedirectController::class)
    ->where('slug', '[A-Za-z0-9_-]{1,32}')
    ->name('redirect');
