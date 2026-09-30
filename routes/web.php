<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

// Public catalog and mobile download routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/user-app', [HomeController::class, 'mobile'])->name('mobile.home');
Route::post('/user-app/fcm-token', [HomeController::class, 'storeFcmToken'])->name('mobile.fcm-token');
Route::get('/download/android-app', function () {
    return redirect()->to(asset('downloads/meras-user-app-v2.apk?v=' . time()))
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('mobile.download');
Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');

// Reachability probe polled by partials/offline-overlay. Session middleware is
// excluded so the poll never queues behind the file-session lock held by a real
// request, and so a poll cannot keep an idle session alive forever.
Route::get('/connection-check', function () {
    return response()->noContent()
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
])->name('connection.check');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/social-login', [AuthController::class, 'socialLogin'])->name('social.login');

    // Password Reset Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

// Authenticated Routes (Customers & Admins)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [InquiryController::class, 'profile'])->name('profile.index');
    Route::get('/notifications', [InquiryController::class, 'notifications'])->name('profile.notifications');
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Live Support Chat Routes (Full Page UI)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');
});

// Live Support Chat Bot AJAX Endpoint (Public & Mobile App)
Route::post('/chat/bot-response', [ChatController::class, 'botResponse'])->name('chat.bot-response');

// Administrative & Management Routes (Strict Admin Role Required)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Registered before the resource so it is not shadowed by products/{product}.
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/cart/update', [PosController::class, 'updateCart'])->name('pos.updateCart');
    Route::post('/pos/cart/clear', [PosController::class, 'clearCart'])->name('pos.clearCart');
    Route::get('/sales/{sale}/print', [PosController::class, 'printSale'])->name('sales.print');

    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::match(['post', 'patch'], '/inquiries/{inquiry}/toggle', [InquiryController::class, 'toggle'])->name('inquiries.toggle');
    Route::post('/inquiries/{inquiry}/respond', [InquiryController::class, 'respond'])->name('inquiries.respond');
    Route::delete('/inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/change-password', [SettingsController::class, 'adminChangePassword'])->name('settings.change-password');
    Route::get('/settings/export', [SettingsController::class, 'export'])->name('settings.export');
    Route::post('/settings/action', [SettingsController::class, 'action'])->name('settings.action');

    Route::get('/users', [SettingsController::class, 'usersIndex'])->name('users.index');
    Route::post('/users', [SettingsController::class, 'usersStore'])->name('users.store');
    Route::match(['post', 'patch'], '/users/{user}/role', [SettingsController::class, 'usersUpdateRole'])->name('users.role');
    Route::delete('/users/{user}', [SettingsController::class, 'usersDestroy'])->name('users.destroy');

    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('/test-mail', [AuthController::class, 'testMail'])->name('admin.test-mail');
});
