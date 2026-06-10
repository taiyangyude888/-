<?php
// 配置文件

// 加载 .env 文件
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // 跳过注释
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

define('DATA_DIR', __DIR__ . '/data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('ORDERS_FILE', DATA_DIR . '/orders.json');
define('STATS_FILE', DATA_DIR . '/stats.json');

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

function initDataFiles() {
    if (!file_exists(USERS_FILE)) file_put_contents(USERS_FILE, '[]');
    if (!file_exists(ORDERS_FILE)) file_put_contents(ORDERS_FILE, '[]');
    if (!file_exists(STATS_FILE)) file_put_contents(STATS_FILE, '{"totalUsers":0,"totalOrders":0}');
}
initDataFiles();

define('AI_API_URL', getenv('AI_API_URL') ?: 'http://127.0.0.1:8899');
define('AI_API_KEY', getenv('AI_API_KEY') ?: 'test-key-12345');
define('AI_MODEL', getenv('AI_MODEL') ?: 'mock-model');
