<?php
// 此脚本用于修复 Redis 连接问题

// 直接使用 Predis 客户端连接 Redis
require_once '/var/www/restapi/vendor/autoload.php';

echo "尝试直接使用 Predis 客户端连接 Redis...\n";

try {
    $client = new Predis\Client([
        'scheme' => 'tcp',
        'host' => 'redis',
        'port' => 6379,
        'password' => 'secret_redis'
    ]);
    
    $result = $client->ping();
    echo "连接结果: 成功 ($result)\n";
    
    // 测试基本操作
    $client->set('test_key', 'test_value');
    $value = $client->get('test_key');
    echo "基本操作测试: " . ($value === 'test_value' ? '成功' : '失败') . "\n";
    
    echo "\n直接使用 Predis 客户端连接成功。\n";
    echo "请确保在 Laravel 配置中正确设置 Redis 密码。\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
    echo "异常类型: " . get_class($e) . "\n";
}