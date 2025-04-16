<?php

namespace Tests\Unit;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    protected $cacheService;
    protected $redisMock;
    protected $redisClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 創建 Redis 客戶端的模擬
        $this->redisClientMock = Mockery::mock('RedisClient');
        $this->redisMock = Mockery::mock('Illuminate\Redis\Connections\Connection');
        $this->redisMock->shouldReceive('client')->andReturn($this->redisClientMock);
        
        // 替換 Redis facade 為模擬
        Redis::shouldReceive('connection')->andReturn($this->redisMock);
        
        $this->cacheService = new CacheService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetCacheStats()
    {
        // 模擬 Redis 響應
        $this->redisClientMock->shouldReceive('ping')->once()->andReturn(true);
        $this->redisClientMock->shouldReceive('keys')->with('*')->once()->andReturn(['key1', 'key2', 'key3']);
        $this->redisClientMock->shouldReceive('info')->once()->andReturn([
            'Stats' => [
                'keyspace_hits' => 150,
                'keyspace_misses' => 50
            ],
            'Memory' => [
                'used_memory_human' => '1.5M'
            ],
            'Server' => [
                'uptime_in_seconds' => 3600,
                'redis_version' => '6.0.9'
            ],
            'Clients' => [
                'connected_clients' => 10
            ],
            'Keyspace' => [
                'db0' => 'keys=3,expires=1'
            ]
        ]);

        // 執行測試
        $stats = $this->cacheService->getCacheStats();

        // 驗證結果
        $this->assertEquals(150, $stats['hits']);
        $this->assertEquals(50, $stats['misses']);
        $this->assertEquals(75, $stats['hit_rate']); // (150/(150+50))*100
        $this->assertEquals('1.5M', $stats['memory_usage']);
        $this->assertEquals(3, $stats['total_keys']);
        $this->assertEquals(3600, $stats['uptime']);
        $this->assertEquals(10, $stats['connected_clients']);
        $this->assertTrue($stats['debug']['redis_connected']);
    }

    public function testGetCacheStatsWithException()
    {
        // 模擬 Redis 拋出異常
        $this->redisClientMock->shouldReceive('ping')->once()->andThrow(new \Exception('Connection refused'));

        // 執行測試
        $stats = $this->cacheService->getCacheStats();

        // 驗證結果
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0, $stats['hit_rate']);
        $this->assertEquals('0B', $stats['memory_usage']);
        $this->assertEquals(0, $stats['total_keys']);
        $this->assertEquals('Connection refused', $stats['error']);
    }

    public function testClearCacheByTag()
    {
        // 設置 Redis 前綴
        config(['cache.prefix' => 'laravel_cache']);

        // 模擬 Redis 響應
        $this->redisClientMock->shouldReceive('keys')
            ->with('*api*')
            ->once()
            ->andReturn(['laravel_cache:api:test1', 'laravel_cache:api:test2']);

        // 模擬 Cache facade
        Cache::shouldReceive('forget')
            ->with('api:test1')
            ->once()
            ->andReturn(true);
        
        Cache::shouldReceive('forget')
            ->with('api:test2')
            ->once()
            ->andReturn(true);

        // 執行測試
        $result = $this->cacheService->clearCacheByTag('api');

        // 驗證結果
        $this->assertTrue($result);
    }

    public function testUpdateCache()
    {
        $key = 'test:key';
        $originalData = ['name' => 'Original', 'value' => 100];
        $newData = ['value' => 200, 'extra' => 'New'];
        $expectedData = ['name' => 'Original', 'value' => 200, 'extra' => 'New'];

        // 模擬 Cache facade
        Cache::shouldReceive('has')
            ->with($key)
            ->once()
            ->andReturn(true);
        
        Cache::shouldReceive('get')
            ->with($key)
            ->once()
            ->andReturn($originalData);
        
        Cache::shouldReceive('put')
            ->with($key, $expectedData, 600)
            ->once()
            ->andReturn(true);

        // 執行測試
        $result = $this->cacheService->updateCache($key, $newData);

        // 驗證結果
        $this->assertTrue($result);
    }

    public function testUpdateCacheWhenKeyNotExists()
    {
        $key = 'nonexistent:key';
        $newData = ['value' => 200];

        // 模擬 Cache facade
        Cache::shouldReceive('has')
            ->with($key)
            ->once()
            ->andReturn(false);

        // Cache::get 和 Cache::put 不應該被調用
        Cache::shouldNotReceive('get');
        Cache::shouldNotReceive('put');

        // 執行測試
        $result = $this->cacheService->updateCache($key, $newData);

        // 驗證結果
        $this->assertFalse($result);
    }

    public function testAnalyzeCacheKeys()
    {
        // 模擬 Redis 響應
        $this->redisClientMock->shouldReceive('keys')
            ->with('*')
            ->once()
            ->andReturn(['api_cache:test1', 'laravel_test:test2', 'direct_test_123']);

        // 模擬 Redis 類型檢查
        $this->redisClientMock->shouldReceive('type')
            ->with('api_cache:test1')
            ->once()
            ->andReturn('string');
        
        $this->redisClientMock->shouldReceive('type')
            ->with('laravel_test:test2')
            ->once()
            ->andReturn('hash');
        
        $this->redisClientMock->shouldReceive('type')
            ->with('direct_test_123')
            ->once()
            ->andReturn('string');

        // 模擬 TTL 檢查
        $this->redisClientMock->shouldReceive('ttl')
            ->with('api_cache:test1')
            ->once()
            ->andReturn(300); // 5分鐘
        
        $this->redisClientMock->shouldReceive('ttl')
            ->with('laravel_test:test2')
            ->once()
            ->andReturn(3600); // 1小時
        
        $this->redisClientMock->shouldReceive('ttl')
            ->with('direct_test_123')
            ->once()
            ->andReturn(-1); // 永久

        // 模擬大小檢查
        $this->redisClientMock->shouldReceive('strlen')
            ->with('api_cache:test1')
            ->once()
            ->andReturn(500); // 500 bytes
        
        $this->redisClientMock->shouldReceive('strlen')
            ->with('laravel_test:test2')
            ->once()
            ->andReturn(5000); // 5KB
        
        $this->redisClientMock->shouldReceive('strlen')
            ->with('direct_test_123')
            ->once()
            ->andReturn(100); // 100 bytes

        // 模擬獲取值
        $this->redisClientMock->shouldReceive('get')
            ->with('api_cache:test1')
            ->once()
            ->andReturn('test value 1');
        
        $this->redisClientMock->shouldReceive('get')
            ->with('direct_test_123')
            ->once()
            ->andReturn('test value 2');

        // 模擬 hash 類型
        $this->redisClientMock->shouldReceive('hlen')
            ->with('laravel_test:test2')
            ->once()
            ->andReturn(3); // 3個欄位

        // 執行測試
        $analysis = $this->cacheService->analyzeCacheKeys();

        // 驗證結果
        $this->assertEquals(3, $analysis['total_keys']);
        
        // 驗證鍵類型分佈
        $this->assertEquals(1, $analysis['key_types']['api_cache']);
        $this->assertEquals(1, $analysis['key_types']['laravel_test']);
        $this->assertEquals(1, $analysis['key_types']['direct_test']);
        
        // 驗證 TTL 分佈
        $this->assertEquals(1, $analysis['ttl_distribution']['no_expiry']);
        $this->assertEquals(1, $analysis['ttl_distribution']['1_to_10_minutes']);
        $this->assertEquals(1, $analysis['ttl_distribution']['1_to_6_hours']);
        
        // 驗證大小分佈
        $this->assertEquals(2, $analysis['size_distribution']['small']);
        $this->assertEquals(1, $analysis['size_distribution']['medium']);
        
        // 驗證樣本鍵
        $this->assertCount(3, $analysis['sample_keys']);
    }
}