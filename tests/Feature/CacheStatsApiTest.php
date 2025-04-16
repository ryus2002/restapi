<?php

namespace Tests\Feature;

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CacheStatsApiTest extends TestCase
{
    protected $cacheServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 創建 CacheService 的模擬
        $this->cacheServiceMock = Mockery::mock(CacheService::class);
        $this->app->instance(CacheService::class, $this->cacheServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCacheStatsApi()
    {
        // 模擬 CacheService 的 getCacheStats 方法
        $this->cacheServiceMock->shouldReceive('getCacheStats')
            ->once()
            ->andReturn([
                'hits' => 150,
                'misses' => 50,
                'hit_rate' => 75,
                'memory_usage' => '1.5M',
                'total_keys' => 3,
                'uptime' => 3600,
                'connected_clients' => 10
            ]);

        // 執行請求
        $response = $this->get('/api/cache-stats');

        // 驗證響應
        $response->assertStatus(200);
        $response->assertJson([
            'hits' => 150,
            'misses' => 50,
            'hit_rate' => 75,
            'memory_usage' => '1.5M',
            'total_keys' => 3,
            'uptime' => 3600,
            'connected_clients' => 10
        ]);
    }
}