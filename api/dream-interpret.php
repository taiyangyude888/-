<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$data = getPostData();
if (!isset($data['dream']) || empty($data['dream'])) errorResponse("缺少字段: dream");

try {
    $result = interpretDream($data['dream']);
    $order = addOrder(['type' => 'dream', 'input' => $data, 'userName' => $data['userName'] ?? '匿名']);
    jsonResponse(['success' => true, 'result' => $result, 'orderId' => $order['id']]);
} catch (Exception $e) {
    errorResponse('分析失败: ' . $e->getMessage(), 500);
}
