<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TenantProvisionController;
use App\Http\Controllers\Api\SystemLogController;
use App\Http\Controllers\Api\AddOnController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\client\ClientAuthController;
use App\Http\Controllers\Api\client\ClientPurchaseController;
use App\Http\Controllers\Admin\ClientController;

Route::post('/login', [AuthController::class, 'login']);

// Public API Routes
Route::post('inquiries', [InquiryController::class, 'store']);

// Client Public Routes
Route::prefix('client')->group(function () {
    Route::post('/register', [ClientAuthController::class, 'register']);
    Route::post('/login', [ClientAuthController::class, 'login']);
    Route::post('/forgot-password', [ClientAuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [ClientAuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [ClientAuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Client Protected Routes
    Route::prefix('client')->group(function () {
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::post('/profile', [ClientAuthController::class, 'updateProfile']);
        Route::post('/change-password', [ClientAuthController::class, 'changePassword']);

        // Client Purchases & Payments 
        Route::get('/purchases', [ClientPurchaseController::class, 'index']);
        Route::post('/purchases', [ClientPurchaseController::class, 'store']);
        Route::get('/purchases/{uuid}', [ClientPurchaseController::class, 'show']);
        Route::get('/payments', [ClientPurchaseController::class, 'payments']);
    });

    // Custom POST route for update to bypass PHP's PUT/multipart limitation
    Route::post('product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::apiResource('product-categories', ProductCategoryController::class);

    // Clients API (Admin Side)
    Route::post('clients/{client}', [ClientController::class, 'update']);
    Route::apiResource('clients', ClientController::class);
    // Settings API
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'store']);
    
    // SMTP Specific API
    Route::get('/settings/smtp', [SettingController::class, 'getSmtp']);
    Route::post('/settings/smtp', [SettingController::class, 'storeSmtp']);

    // General Settings API
    Route::get('/settings/general', [SettingController::class, 'getGeneral']);
    Route::post('/settings/general', [SettingController::class, 'storeGeneral']);

    // Payment Methods Settings API
    Route::get('/settings/payment-methods', [SettingController::class, 'getPaymentMethods']);
    Route::post('/settings/payment-methods', [SettingController::class, 'storePaymentMethods']);

    // Audit Logs API
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // System Logs API
    Route::get('/system-logs', [SystemLogController::class, 'index']);
    Route::delete('/system-logs', [SystemLogController::class, 'destroy']);

    Route::post('plans/{plan}', [PlanController::class, 'update']);
    Route::apiResource('plans', PlanController::class);

    Route::get('products/{product}/firebase', [ProductController::class, 'getFirebase']);
    Route::post('products/{product}/firebase', [ProductController::class, 'updateFirebase']);
    Route::post('products/{product}', [ProductController::class, 'update']);
    Route::apiResource('products', ProductController::class);

    Route::post('add-ons/{add_on}', [AddOnController::class, 'update']);
    Route::apiResource('add-ons', AddOnController::class);

    Route::post('inquiries/{inquiry}', [InquiryController::class, 'update']);
    Route::apiResource('inquiries', InquiryController::class)->except('store');

    // Admin Notifications API
    Route::get('/notifications', [\App\Http\Controllers\Api\AdminNotificationController::class, 'index']);
    Route::get('/notifications/unread', [\App\Http\Controllers\Api\AdminNotificationController::class, 'unread']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\AdminNotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\AdminNotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/unread', [\App\Http\Controllers\Api\AdminNotificationController::class, 'markAsUnread']);

    // Tenant Provisioning API
    Route::post('/tenant-provision/check-subdomain', [TenantProvisionController::class, 'checkSubdomain']);
    Route::post('/tenant-provision', [TenantProvisionController::class, 'store']);
    Route::post('/tenant-provision/{uuid}/verify-payment', [TenantProvisionController::class, 'verifyPayment']);
    Route::get('/tenants', [TenantProvisionController::class, 'index']);
    Route::get('/tenants/{uuid}', [TenantProvisionController::class, 'show']);
    Route::post('/tenants/{uuid}', [TenantProvisionController::class, 'update']); // Using POST for form data with files/nested data
    Route::delete('/tenants/{uuid}', [TenantProvisionController::class, 'destroy']);

    // Support Tickets API
    Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'store']);
    Route::post('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'update']);

    // Dedicated APIs for Subscriptions and Payments
    Route::post('subscriptions/{subscription}', [SubscriptionController::class, 'update']);
    Route::apiResource('subscriptions', SubscriptionController::class);

    Route::post('payments/{payment}', [PaymentController::class, 'update']);
    Route::apiResource('payments', PaymentController::class);
});
