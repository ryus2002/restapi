<?php
// 测试直接使用 IP 地址连接到 Redis

echo "尝试直接使用 IP 地址连接到 Redis...\n";

try {
    $redis = new \Redis();
    $redis->connect('172.19.0.5', 6379);
    $redis->auth('secret_redis');
    $result = $redis->ping();
    echo "连接结果: 成功 ($result)\n";
} catch (\Exception $e) {
    echo "连接异常: " . $e->getMessage() . "\n";
}

// 尝试使用主机名连接
try {
    $redis = new \Redis();
    $redis->connect('redis', 6379);
    $redis->auth('secret_redis');
    $result = $redis->ping();
    echo "使用主机名 'redis' 连接结果: 成功 ($result)\n";
} catch (\Exception $e) {
    echo "使用主机名 'redis' 连接异常: " . $e->getMessage() . "\n";
}

// 尝试使用容器名连接
try {
    $redis = new \Redis();
    $redis->connect('laradock-redis-1', 6379);
    $redis->auth('secret_redis');
    $result = $redis->ping();
    echo "使用容器名 'laradock-redis-1' 连接结果: 成功 ($result)\n";
} catch (\Exception $e) {
    echo "使用容器名 'laradock-redis-1' 连接异常: " . $e->getMessage() . "\n";
}