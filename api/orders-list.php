<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$orders = readJsonFile(ORDERS_FILE);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 20;
$offset = ($page - 1) * $pageSize;
$total = count($orders);

jsonResponse([
    'success' => true,
    'data' => array_slice($orders, $offset, $pageSize),
    'pagination' => [
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => ceil($total / $pageSize)
    ]
]);
