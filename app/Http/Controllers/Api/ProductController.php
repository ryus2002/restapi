<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CacheService;

class ProductController extends Controller
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    /**
     * 取得產品列表
     */
    public function index(Request $request)
    {
        // 模擬產品資料 (實際應從資料庫獲取)
        $products = [
            ['id' => 1, 'name' => '產品 A', 'price' => 100],
            ['id' => 2, 'name' => '產品 B', 'price' => 200],
            ['id' => 3, 'name' => '產品 C', 'price' => 300],
        ];
        
        return response()->json(['data' => $products]);
    }

    /**
     * 取得單一產品
     */
    public function show($id)
    {
        // 模擬產品資料
        $product = ['id' => $id, 'name' => '產品 ' . $id, 'price' => $id * 100];
        
        return response()->json(['data' => $product]);
    }

    /**
     * 更新產品並清除相關快取
     */
    public function update(Request $request, $id)
    {
        // 模擬產品更新邏輯
        $product = ['id' => $id, 'name' => $request->name, 'price' => $request->price];
        
        // 更新後清除相關快取
        $this->cacheService->clearCacheByTag("product:{$id}");
        
        return response()->json(['data' => $product, 'message' => '產品已更新']);
    }
}
