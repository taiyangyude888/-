<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$stats = readJsonFile(STATS_FILE);
$orders = readJsonFile(ORDERS_FILE);
$today = date('Y-m-d');
$todayOrders = array_filter($orders, function($o) use ($today) {
    return strpos($o['timestamp'], $today) === 0;
});

jsonResponse([
    'totalUsers' => $stats['totalUsers'] ?? 0,
    'totalOrders' => $stats['totalOrders'] ?? 0,
    'todayOrders' => count($todayOrders),
    'recentOrders' => array_slice($orders, 0, 10)
]);
