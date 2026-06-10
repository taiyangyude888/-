<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai-service.php';

$data = getPostData();
if (!isset($data['message']) || empty($data['message'])) {
    errorResponse("缺少字段: message");
}

try {
    $result = psychologyChat($data['message'], $data['history'] ?? []);
    $order = addOrder([
        'type' => 'psychology',
        'input' => $data,
        'userName' => $data['userName'] ?? '匿名'
    ]);
    jsonResponse([
        'success' => true,
        'result' => $result,
        'orderId' => $order['id']
    ]);
} catch (Exception $e) {
    errorResponse('分析失败: ' . $e->getMessage(), 500);
}
