<?php
// 玄象健康检查脚本

header('Content-Type: application/json; charset=utf-8');

$checks = [];

// 1. 检查依赖文件
$files = [
    'config.php',
    'includes/functions.php',
    'includes/ai-service.php',
    'api/stats.php',
    'admin/api.php',
    'data'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $checks[$file] = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'writable' => is_dir($path) ? is_writable($path) : null
    ];
}

// 2. 检查API端点
$apiTests = [];
$ch = curl_init('http://ai.olbaba.com/api/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$apiTests['stats'] = curl_exec($ch);
$apiTests['stats_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ch = curl_init('http://ai.olbaba.com/admin/api.php?action=checkAuth');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$apiTests['checkAuth'] = curl_exec($ch);
$apiTests['checkAuth_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3. 检查数据文件
$dataFiles = glob(__DIR__ . '/data/*.json');
$dataStatus = [];
foreach ($dataFiles as $file) {
    $name = basename($file);
    $content = @file_get_contents($file);
    $dataStatus[$name] = [
        'size' => filesize($file),
        'valid_json' => json_decode($content) !== null,
        'writable' => is_writable($file)
    ];
}

// 4. 总体状态
$allOk = true;
foreach ($checks as $check) {
    if (!$check['exists'] || !$check['readable']) {
        $allOk = false;
        break;
    }
}

if ($apiTests['stats_code'] != 200 || $apiTests['checkAuth_code'] != 200) {
    $allOk = false;
}

echo json_encode([
    'status' => $allOk ? 'healthy' : 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'files' => $checks,
    'api_tests' => [
        'stats' => [
            'code' => $apiTests['stats_code'],
            'response' => substr($apiTests['stats'], 0, 200)
        ],
        'checkAuth' => [
            'code' => $apiTests['checkAuth_code'],
            'response' => $apiTests['checkAuth']
        ]
    ],
    'data_files' => $dataStatus
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
