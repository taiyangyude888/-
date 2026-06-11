<?php
// GitHub一键同步工具
header('Content-Type: text/html; charset=utf-8');

$githubUrl = 'https://github.com/taiyangyude888/-/archive/refs/heads/main.zip';
$tempZip = sys_get_temp_dir() . '/github-sync.zip';
$extractPath = sys_get_temp_dir() . '/github-extract';
$currentDir = __DIR__;

function log_msg($msg) {
    echo "<div style='padding:8px;margin:5px 0;background:#f0f0f0;border-left:3px solid #4CAF50;'>$msg</div>";
    ob_flush();
    flush();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>GitHub同步工具</title>
    <style>
        body { font-family: 'Microsoft YaHei', Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .btn { background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 5px; 
               font-size: 16px; cursor: pointer; margin: 10px 5px; }
        .btn:hover { background: #45a049; }
        .output { background: #fff; border: 1px solid #ddd; padding: 15px; margin-top: 20px; 
                  border-radius: 5px; min-height: 100px; }
        h1 { color: #333; }
        .info { color: #666; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔄 GitHub自动同步工具</h1>
    <div class="info">
        <strong>仓库：</strong> https://github.com/taiyangyude888/-<br>
        <strong>分支：</strong> main<br>
        <strong>本地路径：</strong> <?php echo $currentDir; ?>
    </div>
    
    <button class="btn" onclick="location.href='?action=sync'">🚀 从GitHub同步最新代码</button>
    <button class="btn" onclick="location.href='?action=check'">✅ 检查当前版本</button>
    
    <div class="output">
<?php
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'sync') {
        log_msg('开始从GitHub下载...');
        
        // 下载ZIP
        $zipContent = @file_get_contents($githubUrl);
        if ($zipContent === false) {
            log_msg('❌ 下载失败，请检查网络或GitHub访问');
            exit;
        }
        file_put_contents($tempZip, $zipContent);
        log_msg('✓ 下载完成：' . round(strlen($zipContent)/1024) . 'KB');
        
        // 解压
        $zip = new ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            if (is_dir($extractPath)) {
                array_map('unlink', glob("$extractPath/*"));
                rmdir($extractPath);
            }
            mkdir($extractPath, 0777, true);
            $zip->extractTo($extractPath);
            $zip->close();
            log_msg('✓ 解压完成');
        } else {
            log_msg('❌ 解压失败');
            exit;
        }
        
        // 复制文件（排除data目录和.git）
        $sourceDir = $extractPath . '/-main';
        if (!is_dir($sourceDir)) {
            log_msg('❌ 解压目录结构异常');
            exit;
        }
        
        $excludeDirs = ['data', '.git'];
        $copied = 0;
        
        function copyRecursive($src, $dst, $exclude) {
            global $copied;
            $dir = opendir($src);
            @mkdir($dst, 0777, true);
            while(($file = readdir($dir)) !== false) {
                if ($file == '.' || $file == '..') continue;
                if (in_array($file, $exclude)) continue;
                
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;
                
                if (is_dir($srcPath)) {
                    copyRecursive($srcPath, $dstPath, $exclude);
                } else {
                    copy($srcPath, $dstPath);
                    $copied++;
                }
            }
            closedir($dir);
        }
        
        copyRecursive($sourceDir, $currentDir, $excludeDirs);
        log_msg("✓ 已更新 $copied 个文件");
        
        // 清理
        unlink($tempZip);
        array_map('unlink', glob("$extractPath/-main/*"));
        rmdir("$extractPath/-main");
        rmdir($extractPath);
        
        log_msg('✅ 同步完成！刷新页面查看最新版本');
        
    } elseif ($_GET['action'] === 'check') {
        log_msg('📋 当前文件列表：');
        $files = array_diff(scandir($currentDir), ['.', '..', 'data']);
        foreach ($files as $file) {
            $time = date('Y-m-d H:i:s', filemtime($currentDir . '/' . $file));
            echo "<div style='padding:5px;'>$file <span style='color:#999;'>($time)</span></div>";
        }
    }
} else {
    echo '<div style="color:#999;padding:20px;text-align:center;">点击上方按钮开始操作</div>';
}
?>
    </div>
    
    <div style="margin-top:30px; padding:15px; background:#fffbcc; border-radius:5px;">
        <strong>⚠️ 使用说明：</strong><br>
        1. 点击"从GitHub同步"会下载最新代码并覆盖本地文件<br>
        2. <code>data/</code> 目录和 <code>.git/</code> 目录会自动保留不被覆盖<br>
        3. 建议先"检查当前版本"，确认文件状态后再同步
    </div>
</body>
</html>
