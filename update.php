<?php
// 远程更新接收脚本
// 安全密钥（请修改为你自己的）
define('UPDATE_KEY', 'xuanxiang2026');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证密钥
$key = $_POST['key'] ?? '';
if ($key !== UPDATE_KEY) {
    respond(false, '密钥错误');
}

// 检查上传文件
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    respond(false, '文件上传失败');
}

$uploadFile = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];

// 验证是ZIP文件
if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'zip') {
    respond(false, '只支持ZIP文件');
}

try {
    $rootDir = __DIR__;
    $backupDir = $rootDir . '/data_backup_' . date('YmdHis');
    
    // 1. 备份数据目录
    if (is_dir($rootDir . '/data')) {
        mkdir($backupDir, 0755, true);
        exec("cp -r {$rootDir}/data/* {$backupDir}/");
    }
    
    // 2. 解压到临时目录
    $tempDir = sys_get_temp_dir() . '/xuanxiang_update_' . time();
    mkdir($tempDir, 0755, true);
    
    $zip = new ZipArchive;
    if ($zip->open($uploadFile) !== true) {
        respond(false, 'ZIP文件解压失败');
    }
    
    $zip->extractTo($tempDir);
    $zip->close();
    
    // 3. 删除旧文件（保留data目录）
    $excludeDirs = ['data', basename($backupDir)];
    $files = scandir($rootDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || in_array($file, $excludeDirs)) continue;
        $path = $rootDir . '/' . $file;
        if (is_dir($path)) {
            exec("rm -rf " . escapeshellarg($path));
        } else {
            @unlink($path);
        }
    }
    
    // 4. 复制新文件（跳过data目录）
    $newFiles = scandir($tempDir);
    foreach ($newFiles as $file) {
        if ($file === '.' || $file === '..' || $file === 'data') continue;
        $src = $tempDir . '/' . $file;
        $dst = $rootDir . '/' . $file;
        if (is_dir($src)) {
            exec("cp -r " . escapeshellarg($src) . " " . escapeshellarg($dst));
        } else {
            copy($src, $dst);
        }
    }
    
    // 5. 清理临时文件
    exec("rm -rf " . escapeshellarg($tempDir));
    
    // 6. 设置权限
    @chmod($rootDir . '/data', 0755);
    
    respond(true, '更新成功！备份目录：' . basename($backupDir));
    
} catch (Exception $e) {
    respond(false, '更新失败: ' . $e->getMessage());
}
