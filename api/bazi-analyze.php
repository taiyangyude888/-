<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$data = getPostData();
$required = ['year', 'month', 'day', 'hour', 'gender'];
foreach ($required as $f) {
    if (!isset($data[$f]) || empty($data[$f])) errorResponse("缺少字段: {$f}");
}

try {
    $result = analyzeBazi($data['year'], $data['month'], $data['day'], $data['hour'], $data['gender']);
    $order = addOrder(['type' => 'bazi', 'input' => $data, 'userName' => $data['userName'] ?? '匿名']);
    jsonResponse(['success' => true, 'result' => $result, 'orderId' => $order['id']]);
} catch (Exception $e) {
    errorResponse('分析失败: ' . $e->getMessage(), 500);
}
