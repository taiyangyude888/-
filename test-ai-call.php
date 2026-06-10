<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/ai-service.php';

try {
    $result = callAI('测试系统', '返回12345', 0.1);
    echo json_encode(['success' => true, 'result' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
