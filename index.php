<?php
// 玄象 v4.0 - PHP版本统一入口

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/data/php_errors.log');
date_default_timezone_set('Asia/Shanghai');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 请求路径
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 后台管理入口
if (strpos($requestPath, '/admin') === 0) {
    if ($requestPath === '/admin' || $requestPath === '/admin/') {
        readfile(__DIR__ . '/admin/index.html');
        exit;
    }
    // 后台API和静态资源
    $adminFile = __DIR__ . $requestPath;
    if (file_exists($adminFile) && is_file($adminFile)) {
        $ext = pathinfo($adminFile, PATHINFO_EXTENSION);
        if ($ext === 'php') {
            require $adminFile;
        } else {
            $mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'html' => 'text/html'];
            if (isset($mimes[$ext])) header('Content-Type: ' . $mimes[$ext]);
            readfile($adminFile);
        }
        exit;
    }
}

// 静态文件服务
$staticPaths = [
    __DIR__ . $requestPath,
    __DIR__ . '/frontend' . $requestPath,
    __DIR__ . '/shared' . $requestPath
];

foreach ($staticPaths as $file) {
    if (file_exists($file) && is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        if (isset($mimes[$ext])) header('Content-Type: ' . $mimes[$ext]);
        readfile($file);
        exit;
    }
}

// API路由
if (strpos($requestPath, '/api/') === 0) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/ai-service.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $apiPath = substr($requestPath, 4);
    
    switch ($apiPath) {
        case '/health':
            jsonResponse(['ok' => true, 'service' => '玄象 v4.0 PHP', 'version' => '4.0.0', 'timestamp' => date('c')]);
            break;
        case '/stats':
            if ($method === 'GET') require __DIR__ . '/api/stats.php';
            break;
        case '/bazi/analyze':
            if ($method === 'POST') require __DIR__ . '/api/bazi-analyze.php';
            break;
        case '/name/analyze':
            if ($method === 'POST') require __DIR__ . '/api/name-analyze.php';
            break;
        case '/dream/interpret':
            if ($method === 'POST') require __DIR__ . '/api/dream-interpret.php';
            break;
        case '/psychology/chat':
            if ($method === 'POST') require __DIR__ . '/api/psychology-chat.php';
            break;
        case '/orders':
            if ($method === 'GET') require __DIR__ . '/api/orders-list.php';
            elseif ($method === 'POST') require __DIR__ . '/api/orders-create.php';
            break;
        case '/cardkey/verify':
            if ($method === 'POST') require __DIR__ . '/api/cardkey-verify.php';
            break;
        case '/cardkey/activate':
            if ($method === 'POST') require __DIR__ . '/api/cardkey-activate.php';
            break;
        default:
            http_response_code(404);
            jsonResponse(['error' => 'API not found', 'path' => $apiPath]);
    }
    exit;
}

// 首页
if ($requestPath === '/' || empty($requestPath)) {
    readfile(__DIR__ . '/frontend/index.html');
    exit;
}

// 404
http_response_code(404);
echo '404 Not Found';
