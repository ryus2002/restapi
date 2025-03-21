<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CacheMonitorController;

Route::get('/', function () {
    return view('welcome');
});

// 管理員快取監控路由
Route::prefix('admin')->group(function () {
    Route::get('/cache-monitor', [CacheMonitorController::class, 'index'])->name('admin.cache.monitor');
    Route::post('/cache-clear', [CacheMonitorController::class, 'clearAll'])->name('admin.cache.clear');
});