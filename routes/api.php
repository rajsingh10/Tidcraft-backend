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
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\Api\EmailTemplateController;

Route::post('/login', [AuthController::class, 'login']);

// Public API Routes
Route::post('inquiries', [InquiryController::class, 'store']);
Route::get('products/client', [ProductController::class, 'publicIndex']);
Route::get('settings/client', [SettingController::class, 'publicGeneral']);
Route::get('cms-pages/slug/{slug}', [CmsPageController::class, 'showBySlug']);

Route::apiResource('plans', PlanController::class)->only(['index', 'show']);
Route::apiResource('add-ons', AddOnController::class)->only(['index', 'show']);
Route::get('tenant/plan-status', [\App\Http\Controllers\Api\TenantPlanStatusController::class, 'show']);
Route::get('tenant/check-quota', [\App\Http\Controllers\Api\TenantPlanStatusController::class, 'checkQuota']);

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
        // Route::post('/purchases', [ClientPurchaseController::class, 'store']);
        Route::post('/purchases', [ClientPurchaseController::class, 'checkoutproduct']);
        Route::get('/purchases/{uuid}', [ClientPurchaseController::class, 'show']);
        Route::get('/purchases/{uuid}/provisioning-status', [ClientPurchaseController::class, 'provisioningStatus']);
        Route::get('/purchases/{uuid}/backups', [ClientPurchaseController::class, 'listBackups']);
        Route::get('/purchases/{uuid}/backup/firebase', [ClientPurchaseController::class, 'backupFirebase']);
        Route::post('/purchases/{uuid}/backup/{backupId}/restore', [ClientPurchaseController::class, 'restoreBackup']);
        Route::post('/purchases/{uuid}/verify-payment', [ClientPurchaseController::class, 'verifyPayment']);
        Route::post('/purchases/{uuid}/renew', [ClientPurchaseController::class, 'renew']);
        Route::post('/purchases/{uuid}/upgrade', [ClientPurchaseController::class, 'upgrade']);
        Route::post('/purchases/{uuid}/domain', [ClientPurchaseController::class, 'setupDomain']);
        Route::get('/payments', [ClientPurchaseController::class, 'payments']);
        Route::get('/invoices', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
        Route::get('/payment-methods', [SettingController::class, 'getClientPaymentMethods']);
    });

    // Custom POST route for update to bypass PHP's PUT/multipart limitation
    Route::post('product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::apiResource('product-categories', ProductCategoryController::class);

    // Clients API (Admin Side)
    Route::get('clients/{client}/tenants', [ClientController::class, 'getTenants']);
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
    Route::post('/audit-logs/bulk-delete', [AuditLogController::class, 'destroyBulk']);
    Route::delete('/audit-logs/all', [AuditLogController::class, 'destroyAll']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // System Logs API
    Route::get('/system-logs', [SystemLogController::class, 'index']);
    Route::delete('/system-logs', [SystemLogController::class, 'destroy']);

    Route::post('plans/{plan}', [PlanController::class, 'update']);
    Route::apiResource('plans', PlanController::class)->except(['index', 'show']);

    Route::get('products/{product}/firebase', [ProductController::class, 'getFirebase']);
    Route::post('products/{product}/firebase', [ProductController::class, 'updateFirebase']);
    Route::get('products/{product}/attachments', [ProductController::class, 'getAttachments']);
    Route::post('products/{product}/attachments', [ProductController::class, 'uploadAttachments']);
    Route::post('products/{product}', [ProductController::class, 'update']);
    Route::apiResource('products', ProductController::class);

    Route::post('cms-pages/save', [CmsPageController::class, 'save']);
    Route::get('cms-pages', [CmsPageController::class, 'index']);
    Route::get('cms-pages/{slug}', [CmsPageController::class, 'showAdmin']);
    Route::delete('cms-pages/{slug}', [CmsPageController::class, 'destroyAdmin']);

    Route::post('email-templates/{email_template}', [EmailTemplateController::class, 'update']);
    Route::apiResource('email-templates', EmailTemplateController::class);

    Route::post('add-ons/{add_on}', [AddOnController::class, 'update']);
    Route::apiResource('add-ons', AddOnController::class)->except(['index', 'show']);

    Route::post('inquiries/{inquiry}', [InquiryController::class, 'update']);
    Route::post('inquiries/{inquiry}/status', [InquiryController::class, 'changeStatus']);
    Route::apiResource('inquiries', InquiryController::class)->except('store');

    // Dashboard & Analytics APIs
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/usage-metering', [\App\Http\Controllers\Api\UsageMeteringController::class, 'index']);
    Route::get('/revenue-analytics', [\App\Http\Controllers\Api\DashboardController::class, 'revenueAnalytics']);

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
    Route::post('/tenant-provision/payment-status-change', [TenantProvisionController::class, 'paymentstatuschnage']);
    Route::get('/tenants', [TenantProvisionController::class, 'index']);
    Route::get('/tenants/backups', [TenantProvisionController::class, 'listAllBackups']);
    Route::get('/tenants/{uuid}', [TenantProvisionController::class, 'show']);
    Route::get('/tenants/{uuid}/provisioning-status', [TenantProvisionController::class, 'provisioningStatus']);
    Route::get('/tenants/{uuid}/backups', [TenantProvisionController::class, 'listBackups']);
    Route::get('/tenants/{uuid}/backup/firebase', [TenantProvisionController::class, 'backupFirebase']);
    Route::post('/tenants/{uuid}/backup/{backupId}/restore', [TenantProvisionController::class, 'restoreBackup']);
    Route::post('/tenants/{uuid}', [TenantProvisionController::class, 'update']); // Using POST for form data with files/nested data
    Route::patch('/tenants/{uuid}', [TenantProvisionController::class, 'update']);
    Route::put('/tenants/{uuid}', [TenantProvisionController::class, 'update']);
    Route::delete('/tenants/{uuid}', [TenantProvisionController::class, 'destroy']);
    Route::post('/tenants/{uuid}/renew', [TenantProvisionController::class, 'renewClient']);
    Route::post('/tenants/{uuid}/renew-manual', [TenantProvisionController::class, 'renewManual']);
    Route::post('/tenants/{uuid}/upgrade-manual', [TenantProvisionController::class, 'upgradeManual']);
    Route::post('/tenants/{uuid}/send-setup-email', [TenantProvisionController::class, 'sendSetupEmail']);
    Route::post('/tenants/{uuid}/send-provisioned-email', [TenantProvisionController::class, 'sendProvisionedEmail']);
    Route::post('/tenants/{uuid}/provision', [TenantProvisionController::class, 'manualProvision']);

    // Support Tickets API
    Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'store']);
    Route::post('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'update']);
    Route::post('/support-tickets/{id}/status', [\App\Http\Controllers\Api\SupportTicketController::class, 'changeStatus']);
    // Invoices API
    Route::get('/invoices', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
    Route::get('/invoices/{id}/pdf', [\App\Http\Controllers\Api\InvoiceController::class, 'downloadPdf']);

    // Dedicated APIs for Subscriptions and Payments
    Route::post('subscriptions/{subscription}', [SubscriptionController::class, 'update']);
    Route::apiResource('subscriptions', SubscriptionController::class);

    Route::post('payments/{payment}', [PaymentController::class, 'update']);
    Route::apiResource('payments', PaymentController::class);
});
