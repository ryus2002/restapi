<?php
// 此脚本用于检查 Laravel 应用的 Redis 配置

require_once '/var/www/restapi/vendor/autoload.php';

$app = require_once '/var/www/restapi/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Laravel 配置中的 Redis 设置：\n";
echo "----------------------------\n";
echo "REDIS_HOST: " . config('database.redis.default.host') . "\n";
echo "REDIS_PORT: " . config('database.redis.default.port') . "\n";
echo "REDIS_PASSWORD: " . (config('database.redis.default.password') ? '已设置 (' . config('database.redis.default.password') . ')' : '未设置') . "\n";
echo "REDIS_CLIENT: " . config('database.redis.client') . "\n\n";

echo "环境变量中的 Redis 设置：\n";
echo "----------------------\n";
echo "REDIS_HOST: " . env('REDIS_HOST') . "\n";
echo "REDIS_PORT: " . env('REDIS_PORT') . "\n";
echo "REDIS_PASSWORD: " . (env('REDIS_PASSWORD') ? '已设置 (' . env('REDIS_PASSWORD') . ')' : '未设置') . "\n";
echo "REDIS_CLIENT: " . env('REDIS_CLIENT') . "\n\n";

// 尝试使用 config 值连接
echo "尝试使用 config 值连接 Redis...\n";【
try {
    $redis = new \Redis();
    $redis->connect(
        config('database.redis.default.host'),
        config('database.redis.default.port'),
        2.0
    );
    
    if (config('database.redis.default.password')) {
        $redis->auth(config('database.redis.default.password'));
    }
    
    $result = $redis->ping();
    echo "连接结果: 成功 ($result)\n";
    
    // 测试基本操作
    $testKey = 'test_key_' . time();
    $redis->set($testKey, 'test_value');
    $value = $redis->get($testKey);
    echo "基本操作测试: " . ($value === 'test_value' ? '成功' : '失败') . "\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
}

// 尝试使用 Laravel Redis Facade
echo "\n尝试使用 Laravel Redis Facade 连接...\n";
try {
    $result = Illuminate\Support\Facades\Redis::connection()->ping();
    echo "连接结果: 成功 ($result)\n";
    
    // 测试基本操作
    $testKey = 'laravel_test_key_' . time();
    Illuminate\Support\Facades\Redis::set($testKey, 'laravel_test_value');
    $value = Illuminate\Support\Facades\Redis::get($testKey);
    echo "基本操作测试: " . ($value === 'laravel_test_value' ? '成功' : '失败') . "\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
    echo "异常类型: " . get_class($e) . "\n";
}