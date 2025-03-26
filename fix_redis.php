<?php
// 此脚本用于修复 Redis 连接问题

require_once '/var/www/restapi/vendor/autoload.php';

$app = require_once '/var/www/restapi/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 手动设置 Redis 密码
config(['database.redis.default.password' => 'secret_redis']);

echo "已手动设置 Redis 密码为 'secret_redis'\n";
echo "尝试连接 Redis...\n";

try {
    $result = Illuminate\Support\Facades\Redis::connection()->ping();
    echo "连接结果: 成功 ($result)\n";
    
    // 测试缓存功能
    Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    $value = Illuminate\Support\Facades\Cache::get('test_key');
    echo "缓存测试: " . ($value === 'test_value' ? '成功' : '失败') . "\n";
    
    echo "\n问题已修复。请更新您的配置文件，确保 REDIS_PASSWORD 正确设置。\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
    echo "异常类型: " . get_class($e) . "\n";
}