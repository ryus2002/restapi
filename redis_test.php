<?php
// 测试 Redis 连接并输出详细信息

echo "开始测试 Redis 连接...\n";

// 尝试使用不同的配置连接 Redis
try {
    $redis = new \Redis();
    $redis->connect('redis', 6379);
    $redis->auth('secret_redis');
    $result = $redis->ping();
    echo "使用 PhpRedis 直接连接结果: " . ($result ? "成功 ($result)" : "失败") . "\n";
} catch (\Exception $e) {
    echo "使用 PhpRedis 直接连接异常: " . $e->getMessage() . "\n";
}

// 尝试连接到 laradock-redis-1
try {
    $redis = new \Redis();
    $redis->connect('laradock-redis-1', 6379);
    $redis->auth('secret_redis');
    $result = $redis->ping();
    echo "连接到 laradock-redis-1 结果: " . ($result ? "成功 ($result)" : "失败") . "\n";
} catch (\Exception $e) {
    echo "连接到 laradock-redis-1 异常: " . $e->getMessage() . "\n";
}

echo "\n测试完成。\n";