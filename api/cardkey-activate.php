<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// 卡密激活API

$data = json_decode(file_get_contents('php://input'), true);
$cardkey = strtoupper(trim($data['cardkey'] ?? ''));

if (empty($cardkey)) {
    jsonResponse(['success' => false, 'error' => '卡密不能为空']);
}

// 读取卡密数据
$cardkeysFile = __DIR__ . '/../../data/cardkeys.json';
$cardkeys = readJsonFile($cardkeysFile);

// 查找卡密
$found = null;
$foundIndex = -1;
foreach ($cardkeys as $index => $ck) {
    if ($ck['cardkey'] === $cardkey) {
        $found = $ck;
        $foundIndex = $index;
        break;
    }
}

if (!$found) {
    jsonResponse(['success' => false, 'error' => '卡密不存在或已失效']);
}

if ($found['status'] === 'used') {
    jsonResponse(['success' => false, 'error' => '卡密已被使用']);
}

// 检查过期时间
if ($found['expiresAt'] && strtotime($found['expiresAt']) < time()) {
    jsonResponse(['success' => false, 'error' => '卡密已过期']);
}

// 激活卡密
$cardkeys[$foundIndex]['status'] = 'used';
$cardkeys[$foundIndex]['activatedAt'] = date('Y-m-d H:i:s');
writeJsonFile($cardkeysFile, $cardkeys);

jsonResponse([
    'success' => true,
    'expiresAt' => $found['expiresAt'],
    'type' => $found['type'],
    'message' => '激活成功！'
]);
