<?php

use App\Http\Controllers\AdminController;
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

    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('statistics');
    Route::get('/location-device', [DashboardController::class, 'locationDevice'])->name('location-device');
    Route::get('/sources', [DashboardController::class, 'sources'])->name('sources');
    Route::get('/referral', [DashboardController::class, 'referral'])->name('referral');
    Route::get('/billing', [DashboardController::class, 'billing'])->name('billing');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::patch('/settings/profile', [DashboardController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/password', [DashboardController::class, 'updatePassword'])->name('settings.password');
});

// =================== Admin ===================
Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'userShow'])->name('users.show');
    Route::patch('/users/{user}/suspend', [AdminController::class, 'userSuspend'])->name('users.suspend');
    Route::patch('/users/{user}/plan', [AdminController::class, 'userPlan'])->name('users.plan');
    Route::get('/links', [AdminController::class, 'links'])->name('links');
    Route::patch('/links/{link}/toggle', [AdminController::class, 'linkToggle'])->name('links.toggle');
    Route::delete('/links/{link}', [AdminController::class, 'linkDestroy'])->name('links.destroy');
    Route::get('/click-logs', [AdminController::class, 'clickLogs'])->name('click-logs');
    Route::get('/bot-logs', [AdminController::class, 'botLogs'])->name('bot-logs');
    Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    Route::patch('/payments/{payment}/mark-paid', [AdminController::class, 'paymentMarkPaid'])->name('payments.mark-paid');
    Route::get('/articles', [AdminController::class, 'articles'])->name('articles');
    Route::get('/faqs', [AdminController::class, 'faqs'])->name('faqs');
    Route::get('/contact-messages', [AdminController::class, 'contactMessages'])->name('contact-messages');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
    Route::get('/health-check', [AdminController::class, 'healthCheck'])->name('health-check');
});

// =================== Catch-all redirect (MUST BE LAST) ===================
Route::get('/{slug}', RedirectController::class)
    ->where('slug', '[A-Za-z0-9_-]{1,32}')
    ->name('redirect');
