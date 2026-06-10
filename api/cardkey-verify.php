<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// 卡密验证API

$data = json_decode(file_get_contents('php://input'), true);
$cardkey = $data['cardkey'] ?? '';

if (empty($cardkey)) {
    jsonResponse(['valid' => false, 'error' => '卡密不能为空']);
}

// 读取卡密数据
$cardkeys = readJsonFile(__DIR__ . '/../../data/cardkeys.json');

// 查找卡密
$found = null;
foreach ($cardkeys as $ck) {
    if ($ck['cardkey'] === $cardkey) {
        $found = $ck;
        break;
    }
}

if (!$found) {
    jsonResponse(['valid' => false, 'error' => '卡密不存在']);
}

if ($found['status'] !== 'active') {
    jsonResponse(['valid' => false, 'error' => '卡密已被使用或已过期']);
}

// 检查过期时间
if ($found['expiresAt'] && strtotime($found['expiresAt']) < time()) {
    jsonResponse(['valid' => false, 'error' => '卡密已过期']);
}

jsonResponse([
    'valid' => true,
    'expiresAt' => $found['expiresAt'],
    'type' => $found['type']
]);
