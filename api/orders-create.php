<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$data = getPostData();
if (!isset($data['type']) || !isset($data['input'])) errorResponse("缺少字段: type, input");

try {
    $order = addOrder($data);
    jsonResponse(['success' => true, 'order' => $order]);
} catch (Exception $e) {
    errorResponse('创建订单失败: ' . $e->getMessage(), 500);
}
