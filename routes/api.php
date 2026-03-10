<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Middleware\BearerTokenAuth;
use Illuminate\Support\Facades\Route;

Route::middleware([BearerTokenAuth::class])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('audit-logs', [AuditLogController::class, 'index']);
});