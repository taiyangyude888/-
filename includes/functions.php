<?php
function generateId($prefix = '') {
    return $prefix . bin2hex(random_bytes(8));
}

function readJsonFile($filepath) {
    if (!file_exists($filepath)) return [];
    return json_decode(file_get_contents($filepath), true) ?: [];
}

function writeJsonFile($filepath, $data) {
    return file_put_contents($filepath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function addOrder($orderData) {
    $orders = readJsonFile(ORDERS_FILE);
    $order = array_merge($orderData, [
        'id' => generateId('order_'),
        'timestamp' => date('c'),
        'status' => 'completed'
    ]);
    array_unshift($orders, $order);
    if (count($orders) > 100) $orders = array_slice($orders, 0, 100);
    writeJsonFile(ORDERS_FILE, $orders);
    updateStats();
    return $order;
}

function updateStats() {
    $orders = readJsonFile(ORDERS_FILE);
    $users = readJsonFile(USERS_FILE);
    $stats = [
        'totalUsers' => count($users),
        'totalOrders' => count($orders),
        'lastUpdated' => date('c')
    ];
    writeJsonFile(STATS_FILE, $stats);
    return $stats;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse($msg, $code = 400) {
    jsonResponse(['error' => $msg], $code);
}

function getPostData() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

// 验证卡密
function verifyCardKey($cardkey) {
    if (empty($cardkey)) return false;
    
    $cardkeys = readJsonFile(__DIR__ . '/../data/cardkeys.json');
    
    foreach ($cardkeys as $ck) {
        if ($ck['cardkey'] === $cardkey && $ck['status'] === 'used') {
            // 检查是否过期
            if ($ck['expiresAt'] && strtotime($ck['expiresAt']) < time()) {
                return false;
            }
            return true;
        }
    }
    
    return false;
}
