<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CacheService;

class CacheMonitorController extends Controller
{
    protected $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 顯示快取監控儀表板
     */
    public function index()
    {
        $stats = $this->cacheService->getCacheStats();
        return view('admin.cache-monitor', compact('stats'));
    }
    
    /**
     * 清除所有快取
     */
    public function clearAll()
    {
        Cache::flush();
        return back()->with('message', '所有快取已清除');
    }
}