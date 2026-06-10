<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$data = getPostData();
if (!isset($data['name']) || empty($data['name'])) errorResponse("缺少字段: name");
if (!isset($data['gender']) || empty($data['gender'])) errorResponse("缺少字段: gender");

try {
    $result = analyzeName($data['name'], $data['gender']);
    $order = addOrder(['type' => 'name', 'input' => $data, 'userName' => $data['name']]);
    jsonResponse(['success' => true, 'result' => $result, 'orderId' => $order['id']]);
} catch (Exception $e) {
    errorResponse('分析失败: ' . $e->getMessage(), 500);
}
