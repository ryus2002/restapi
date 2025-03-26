<?php
// 此脚本用于检查 Laravel 应用的 Redis 配置

require_once '/var/www/restapi/vendor/autoload.php';

$app = require_once '/var/www/restapi/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Laravel Redis 配置信息：\n";
echo "------------------------\n";
echo "REDIS_HOST: " . config('database.redis.default.host') . "\n";
echo "REDIS_PORT: " . config('database.redis.default.port') . "\n";
echo "REDIS_PASSWORD: " . (config('database.redis.default.password') ? '已设置 (' . config('database.redis.default.password') . ')' : '未设置') . "\n";
echo "REDIS_CLIENT: " . config('database.redis.client') . "\n\n";

echo "尝试使用 Laravel 配置连接 Redis...\n";
try {
    $result = Illuminate\Support\Facades\Redis::connection()->ping();
    echo "连接结果: 成功 ($result)\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
    echo "异常类型: " . get_class($e) . "\n";
}