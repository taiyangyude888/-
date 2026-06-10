<?php
// 后台管理API
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 先加载依赖
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// 管理员密码
define('ADMIN_PASSWORD', 'xuanxiang2026');

// 检查登录
function checkAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        jsonResponse(['success' => false, 'error' => '未登录', 'needLogin' => true]);
    }
}

// 登录验证
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';
    
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        jsonResponse(['success' => true, 'message' => '登录成功']);
    } else {
        jsonResponse(['success' => false, 'message' => '密码错误']);
    }
}

// 登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

// 检查登录状态
if (isset($_GET['action']) && $_GET['action'] === 'checkAuth') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        jsonResponse(['success' => true, 'loggedIn' => true]);
    } else {
        jsonResponse(['success' => true, 'loggedIn' => false]);
    }
}

// 后续所有操作都需要登录
checkAuth();

// CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getConfig':
        getConfig();
        break;
    
    case 'saveConfig':
        saveConfig();
        break;
    
    case 'readFile':
        adminReadFile();
        break;
    
    case 'saveFile':
        saveFile();
        break;
    
    case 'deleteOrder':
        deleteOrder();
        break;
    
    case 'clearOrders':
        clearOrders();
        break;
    
    case 'testAPI':
        testAPIConnection();
        break;
    
    case 'listCardkeys':
        listCardkeys();
        break;
    
    case 'generateCardkeys':
        generateCardkeys();
        break;
    
    case 'deleteCardkey':
        deleteCardkey();
        break;
    
    case 'uploadFile':
        uploadFile();
        break;
    
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

// 获取配置
function getConfig() {
    $envFile = __DIR__ . '/../.env';
    $config = [];
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    }
    
    // 不返回API密钥明文
    if (isset($config['AI_API_KEY'])) {
        $config['AI_API_KEY'] = '***已设置***';
    }
    
    jsonResponse(['success' => true, 'config' => $config]);
}

// 保存配置
function saveConfig() {
    $data = json_decode(file_get_contents('php://input'), true);
    $envFile = __DIR__ . '/../.env';
    
    $lines = [];
    $lines[] = '# 玄象 v4.0 PHP版 - API配置';
    
    if (!empty($data['AI_API_URL'])) {
        $lines[] = 'AI_API_URL=' . $data['AI_API_URL'];
    }
    
    // 只有提供了新密钥才更新
    if (!empty($data['AI_API_KEY']) && $data['AI_API_KEY'] !== '***已设置***') {
        $lines[] = 'AI_API_KEY=' . $data['AI_API_KEY'];
    } else {
        // 保留原密钥
        $oldContent = file_exists($envFile) ? file_get_contents($envFile) : '';
        if (preg_match('/AI_API_KEY=(.+)/', $oldContent, $matches)) {
            $lines[] = 'AI_API_KEY=' . trim($matches[1]);
        }
    }
    
    if (!empty($data['AI_MODEL'])) {
        $lines[] = 'AI_MODEL=' . $data['AI_MODEL'];
    }
    
    $content = implode("\n", $lines) . "\n";
    
    if (file_put_contents($envFile, $content)) {
        jsonResponse(['success' => true, 'message' => '配置保存成功']);
    } else {
        jsonResponse(['success' => false, 'message' => '配置保存失败'], 500);
    }
}

// 读取文件（后台管理）
function adminReadFile() {
    $file = $_GET['file'] ?? '';
    $allowedFiles = [
        'index.php',
        'config.php',
        '.env',
        '.htaccess',
        'frontend/index.html',
        'frontend/app-v4.js',
        'shared/styles-v4.css',
        'api/bazi-analyze.php',
        'api/name-analyze.php',
        'api/dream-interpret.php',
        'api/stats.php',
        'api/orders-list.php',
        'includes/functions.php',
        'includes/ai-service.php'
    ];
    
    if (!in_array($file, $allowedFiles)) {
        jsonResponse(['success' => false, 'message' => '不允许访问此文件'], 403);
    }
    
    $filePath = __DIR__ . '/../' . $file;
    
    if (!file_exists($filePath)) {
        jsonResponse(['success' => false, 'message' => '文件不存在'], 404);
    }
    
    $content = file_get_contents($filePath);
    jsonResponse(['success' => true, 'content' => $content]);
}

// 保存文件
function saveFile() {
    $data = json_decode(file_get_contents('php://input'), true);
    $file = $data['file'] ?? '';
    $content = $data['content'] ?? '';
    
    $allowedFiles = [
        'index.php',
        'config.php',
        '.env',
        '.htaccess',
        'frontend/index.html',
        'frontend/app-v4.js',
        'shared/styles-v4.css',
        'api/bazi-analyze.php',
        'api/name-analyze.php',
        'api/dream-interpret.php',
        'api/stats.php',
        'api/orders-list.php',
        'includes/functions.php',
        'includes/ai-service.php'
    ];
    
    if (!in_array($file, $allowedFiles)) {
        jsonResponse(['success' => false, 'message' => '不允许修改此文件'], 403);
    }
    
    $filePath = __DIR__ . '/../' . $file;
    
    // 备份原文件
    if (file_exists($filePath)) {
        $backupDir = __DIR__ . '/../data/backups';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $backupFile = $backupDir . '/' . str_replace('/', '_', $file) . '.' . date('YmdHis') . '.bak';
        copy($filePath, $backupFile);
    }
    
    if (file_put_contents($filePath, $content)) {
        jsonResponse(['success' => true, 'message' => '文件保存成功']);
    } else {
        jsonResponse(['success' => false, 'message' => '文件保存失败'], 500);
    }
}

// 删除订单
function deleteOrder() {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['orderId'] ?? '';
    
    $orders = readJsonFile(ORDERS_FILE);
    $orders = array_filter($orders, function($o) use ($orderId) {
        return $o['id'] !== $orderId;
    });
    
    writeJsonFile(ORDERS_FILE, array_values($orders));
    updateStats();
    
    jsonResponse(['success' => true, 'message' => '删除成功']);
}

// 清空订单
function clearOrders() {
    writeJsonFile(ORDERS_FILE, []);
    updateStats();
    jsonResponse(['success' => true, 'message' => '所有订单已清空']);
}

// 测试API连接
function testAPIConnection() {
    require_once __DIR__ . '/../includes/ai-service.php';
    
    try {
        // 简单测试调用
        $result = callAI('你是测试助手', '回复"连接成功"', 0.1);
        
        if (strpos($result, '成功') !== false || strlen($result) > 0) {
            jsonResponse(['success' => true, 'message' => 'API连接正常 ✓']);
        } else {
            jsonResponse(['success' => false, 'message' => 'API返回异常']);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'API测试失败: ' . $e->getMessage()]);
    }
}

// 列出所有卡密
function listCardkeys() {
    $cardkeysFile = __DIR__ . '/../data/cardkeys.json';
    $cardkeys = readJsonFile($cardkeysFile);
    
    $typeNames = [
        '7days' => '体验卡(7天)',
        '30days' => '月度卡(30天)',
        '365days' => '年度卡(365天)'
    ];
    
    foreach ($cardkeys as &$ck) {
        $ck['typeName'] = $typeNames[$ck['type']] ?? $ck['type'];
    }
    
    jsonResponse(['success' => true, 'cardkeys' => $cardkeys]);
}

// 生成卡密
function generateCardkeys() {
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? '';
    $count = intval($data['count'] ?? 1);
    
    if (!in_array($type, ['7days', '30days', '365days'])) {
        jsonResponse(['success' => false, 'message' => '无效的卡密类型']);
    }
    
    if ($count < 1 || $count > 100) {
        jsonResponse(['success' => false, 'message' => '数量必须在1-100之间']);
    }
    
    $days = ['7days' => 7, '30days' => 30, '365days' => 365];
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $days[$type] . ' days'));
    
    $cardkeysFile = __DIR__ . '/../data/cardkeys.json';
    $cardkeys = readJsonFile($cardkeysFile);
    
    $newCardkeys = [];
    for ($i = 0; $i < $count; $i++) {
        $cardkey = 'XX' . strtoupper(bin2hex(random_bytes(6)));
        $newCardkeys[] = $cardkey;
        
        $cardkeys[] = [
            'cardkey' => $cardkey,
            'type' => $type,
            'status' => 'active',
            'expiresAt' => $expiresAt,
            'createdAt' => date('Y-m-d H:i:s'),
            'activatedAt' => null
        ];
    }
    
    writeJsonFile($cardkeysFile, $cardkeys);
    
    jsonResponse([
        'success' => true,
        'message' => "成功生成{$count}个卡密",
        'cardkeys' => $newCardkeys
    ]);
}

// 删除卡密
function deleteCardkey() {
    $data = json_decode(file_get_contents('php://input'), true);
    $cardkey = $data['cardkey'] ?? '';
    
    $cardkeysFile = __DIR__ . '/../data/cardkeys.json';
    $cardkeys = readJsonFile($cardkeysFile);
    
    $cardkeys = array_filter($cardkeys, function($ck) use ($cardkey) {
        return $ck['cardkey'] !== $cardkey;
    });
    
    writeJsonFile($cardkeysFile, array_values($cardkeys));
    
    jsonResponse(['success' => true, 'message' => '删除成功']);
}

// 上传文件
function uploadFile() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => '文件上传失败']);
        return;
    }
    
    $targetPath = $_POST['path'] ?? '';
    if (empty($targetPath)) {
        jsonResponse(['success' => false, 'message' => '目标路径为空']);
        return;
    }
    
    // 安全检查：只允许上传到特定目录
    $allowedDirs = ['shared/', 'uploads/', 'frontend/images/'];
    $isAllowed = false;
    foreach ($allowedDirs as $dir) {
        if (strpos($targetPath, $dir) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        jsonResponse(['success' => false, 'message' => '不允许上传到此目录']);
        return;
    }
    
    $fullPath = __DIR__ . '/../' . $targetPath;
    $dir = dirname($fullPath);
    
    // 创建目录
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // 移动文件
    if (move_uploaded_file($_FILES['file']['tmp_name'], $fullPath)) {
        jsonResponse(['success' => true, 'message' => '上传成功', 'path' => $targetPath]);
    } else {
        jsonResponse(['success' => false, 'message' => '保存文件失败']);
    }
}

