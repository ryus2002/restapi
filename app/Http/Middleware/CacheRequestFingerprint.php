<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheRequestFingerprint
{
    /**
     * 處理請求
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 只對 GET 請求進行快取
        if (!$request->isMethod('GET')) {
        return $next($request);
    }

        // 產生請求指紋（不受參數順序影響）
        $fingerprint = $this->generateFingerprint($request);
        
        // 檢查快取是否存在
        if (Cache::has($fingerprint)) {
            return response()->json(
                Cache::get($fingerprint),
                200,
                ['X-API-Cache' => 'HIT']
            );
}

        // 繼續請求處理
        $response = $next($request);
        
        // 只快取成功的回應
        if ($response->getStatusCode() === 200) {
            // 根據路由設定不同的過期時間
            $expiresAt = $this->determineExpirationTime($request->path());
            
            // 儲存回應到快取
            Cache::put(
                $fingerprint,
                json_decode($response->getContent(), true),
                $expiresAt
            );
            
            // 為回應添加快取標籤
            $response->headers->set('X-API-Cache', 'MISS');
        }

        return $response;
    }

    private function generateFingerprint(Request $request): string
    {
        $path = $request->path();
        $params = $request->all();
        
        // 對參數進行排序以確保順序不影響指紋
        ksort($params);
        
        // 基本鍵
        $key = 'api:' . $path;
        
        // 添加參數雜湊
        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }
        
        // 如果是產品詳情，添加標籤
        if (preg_match('/products\/(\d+)/', $path, $matches)) {
            $key .= ':product:' . $matches[1];
        }
        
        // 不需要手動添加 Laravel 快取前綴，Cache::put 會自動添加
        return $key;
    }

    /**
     * 智能過期策略：根據數據類型自動設定合理的過期時間
     */
    private function determineExpirationTime(string $path): int
    {
        // 針對不同類型的API端點設定不同的過期時間（秒）
        if (Str::contains($path, 'products')) {
            return 3600; // 產品資料快取1小時
        } elseif (Str::contains($path, 'users')) {
            return 300;  // 用戶資料快取5分鐘
        } elseif (Str::contains($path, 'statistics')) {
            return 86400; // 統計資料快取1天
        }
        
        // 預設快取時間10分鐘
        return 600;
    }
}