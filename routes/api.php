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
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AddOnController;
use App\Http\Controllers\Api\InquiryController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Custom POST route for update to bypass PHP's PUT/multipart limitation
    Route::post('product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::apiResource('product-categories', ProductCategoryController::class);
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

    Route::post('plans/{plan}', [PlanController::class, 'update']);
    Route::apiResource('plans', PlanController::class);

    Route::post('products/{product}', [ProductController::class, 'update']);
    Route::apiResource('products', ProductController::class);

    Route::post('add-ons/{add_on}', [AddOnController::class, 'update']);
    Route::apiResource('add-ons', AddOnController::class);

    Route::post('inquiries/{inquiry}', [InquiryController::class, 'update']);
    Route::apiResource('inquiries', InquiryController::class);

    // Tenant Provisioning API
    Route::post('/tenant-provision', [TenantProvisionController::class, 'store']);
    Route::get('/tenants', [TenantProvisionController::class, 'index']);
    Route::get('/tenants/{uuid}', [TenantProvisionController::class, 'show']);
    Route::post('/tenants/{uuid}', [TenantProvisionController::class, 'update']); // Using POST for form data with files/nested data
    Route::delete('/tenants/{uuid}', [TenantProvisionController::class, 'destroy']);
});
