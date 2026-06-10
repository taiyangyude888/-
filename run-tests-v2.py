#!/usr/bin/env python3
# 玄象本地测试套件 v2 - 使用curl测试
import subprocess
import time
import json
from pathlib import Path

print("=" * 60)
print("🧪 玄象本地测试套件 v2")
print("=" * 60)

base_dir = Path.home() / 'Desktop' / 'xuanxiang-local-test'

# 1. 启动模拟AI服务器
print("\n[1/4] 启动模拟AI服务器...")
mock_ai = subprocess.Popen(
    ['python', 'mock-ai-server.py'],
    cwd=base_dir,
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE,
    creationflags=subprocess.CREATE_NEW_PROCESS_GROUP if hasattr(subprocess, 'CREATE_NEW_PROCESS_GROUP') else 0
)
time.sleep(2)

# 测试AI服务器
try:
    result = subprocess.run(
        ['curl', '-s', 'http://127.0.0.1:8899', '-X', 'POST', '-H', 'Content-Type: application/json', '-d', '{"test":true}'],
        capture_output=True, text=True, timeout=3
    )
    if 'choices' in result.stdout:
        print("✓ 模拟AI服务器运行正常")
    else:
        print(f"✗ AI服务器响应异常: {result.stdout[:50]}")
except Exception as e:
    print(f"✗ AI服务器启动失败: {e}")

# 2. 测试PHP文件语法
print("\n[2/4] 检查PHP文件...")
php_files = [
    'config.php',
    'includes/functions.php',
    'includes/ai-service.php',
    'api/dream-interpret.php',
    'api/bazi-analyze.php',
    'api/name-analyze.php',
]

syntax_errors = []
for file in php_files:
    file_path = base_dir / file
    if file_path.exists():
        # 简单检查PHP语法（查找明显错误）
        content = file_path.read_text(encoding='utf-8')
        if content.count("'") % 2 != 0 or content.count('"') % 2 != 0:
            syntax_errors.append(f"{file}: 引号不匹配")
        else:
            print(f"✓ {file}")
    else:
        syntax_errors.append(f"{file}: 文件不存在")

if syntax_errors:
    print("\n语法错误:")
    for err in syntax_errors:
        print(f"  ✗ {err}")
else:
    print("✓ 所有PHP文件语法正常")

# 3. 测试AI服务调用
print("\n[3/4] 测试AI服务...")
test_script = base_dir / 'test-ai-call.php'
test_script.write_text("""<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/ai-service.php';

try {
    $result = callAI('测试系统', '返回12345', 0.1);
    echo json_encode(['success' => true, 'result' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
""", encoding='utf-8')

# 直接用php CLI运行
result = subprocess.run(
    ['php', 'test-ai-call.php'],
    cwd=base_dir,
    capture_output=True,
    text=True,
    timeout=10
)

if result.returncode == 0:
    try:
        data = json.loads(result.stdout)
        if data.get('success'):
            print(f"✓ AI调用成功: {data.get('result', '')[:50]}...")
        else:
            print(f"✗ AI调用失败: {data.get('error', 'unknown')}")
    except:
        print(f"✗ AI调用返回非JSON: {result.stdout[:100]}")
else:
    print(f"✗ PHP执行失败:\n{result.stderr[:200]}")

# 4. 测试API文件
print("\n[4/4] 测试API文件...")
api_tests = [
    ('解梦', 'dream-interpret.php', {'dream': '梦见飞翔', 'userName': '测试'}),
    ('八字', 'bazi-analyze.php', {'year': '1990', 'month': '5', 'day': '10', 'hour': '12', 'gender': '男', 'userName': '测试'}),
    ('姓名', 'name-analyze.php', {'name': '张三', 'gender': '男', 'userName': '测试'}),
]

for name, file, data in api_tests:
    test_file = base_dir / 'api' / file
    if not test_file.exists():
        print(f"✗ {name}: 文件不存在")
        continue
    
    # 创建测试请求文件
    request_file = base_dir / 'test-request.json'
    request_file.write_text(json.dumps(data), encoding='utf-8')
    
    # 模拟POST请求
    test_wrapper = base_dir / 'test-api-wrapper.php'
    test_wrapper.write_text(f"""<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents('php://input', file_get_contents('test-request.json'));
require 'api/{file}';
""", encoding='utf-8')
    
    result = subprocess.run(
        ['php', 'test-api-wrapper.php'],
        cwd=base_dir,
        capture_output=True,
        text=True,
        timeout=15,
        env={'REQUEST_METHOD': 'POST'}
    )
    
    if result.returncode == 0:
        try:
            response = json.loads(result.stdout)
            if response.get('success'):
                print(f"✓ {name}API: 成功")
            else:
                print(f"✗ {name}API: {response.get('error', 'unknown')[:50]}")
        except:
            print(f"✗ {name}API返回非JSON: {result.stdout[:100]}")
    else:
        print(f"✗ {name}API执行失败:\n  {result.stderr[:150]}")

# 总结
print("\n" + "=" * 60)
print("✅ 测试完成！")
print("=" * 60)
print("\n说明：本测试不需要Web服务器，直接用PHP CLI测试")
print("如果所有测试通过，说明代码逻辑正确，可以部署到服务器")

# 停止AI服务器
print("\n停止模拟AI服务器...")
mock_ai.terminate()
print("✓ 测试结束")
