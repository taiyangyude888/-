#!/usr/bin/env python3
# 玄象本地测试套件
import subprocess
import time
import requests
import json
from pathlib import Path

print("=" * 60)
print("🧪 玄象本地测试套件")
print("=" * 60)

# 1. 启动模拟AI服务器
print("\n[1/5] 启动模拟AI服务器...")
mock_ai = subprocess.Popen(
    ['python', 'mock-ai-server.py'],
    cwd=Path.home() / 'Desktop' / 'xuanxiang-local-test',
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE
)
time.sleep(1)
print("✓ 模拟AI服务器运行在 http://127.0.0.1:8899")

# 2. 启动PHP内置服务器
print("\n[2/5] 启动PHP内置服务器...")
php_server = subprocess.Popen(
    ['php', '-S', '127.0.0.1:8000', '-t', '.'],
    cwd=Path.home() / 'Desktop' / 'xuanxiang-local-test',
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE
)
time.sleep(2)
print("✓ PHP服务器运行在 http://127.0.0.1:8000")

try:
    # 3. 测试前台页面
    print("\n[3/5] 测试前台页面...")
    r = requests.get('http://127.0.0.1:8000/index.php', timeout=5)
    if r.status_code == 200:
        print(f"✓ 前台页面正常 ({len(r.text)} 字节)")
    else:
        print(f"✗ 前台页面失败: HTTP {r.status_code}")
    
    # 4. 测试后台API
    print("\n[4/5] 测试后台API...")
    tests = [
        ('checkAuth', 'http://127.0.0.1:8000/admin/api.php?action=checkAuth', None),
        ('登录', 'http://127.0.0.1:8000/admin/api.php?action=login', {'password': 'xuanxiang2026'}),
    ]
    
    session = requests.Session()
    for name, url, data in tests:
        if data:
            r = session.post(url, json=data, timeout=5)
        else:
            r = session.get(url, timeout=5)
        
        result = r.json()
        if result.get('success'):
            print(f"✓ {name}: {result}")
        else:
            print(f"✗ {name}: {result}")
    
    # 5. 测试前台API（三大功能）
    print("\n[5/5] 测试三大功能API...")
    api_tests = [
        ('解梦', 'http://127.0.0.1:8000/api/dream-interpret.php', {'dream': '梦见飞翔', 'userName': '测试'}),
        ('八字', 'http://127.0.0.1:8000/api/bazi-analyze.php', {'year': '1990', 'month': '5', 'day': '10', 'hour': '12', 'gender': '男', 'userName': '测试'}),
        ('姓名', 'http://127.0.0.1:8000/api/name-analyze.php', {'name': '张三', 'gender': '男', 'userName': '测试'}),
    ]
    
    for name, url, data in api_tests:
        try:
            r = requests.post(url, json=data, timeout=10)
            result = r.json()
            if result.get('success'):
                content = result.get('result', '')[:50]
                print(f"✓ {name}分析: {content}...")
            else:
                print(f"✗ {name}分析失败: {result.get('error', 'unknown')}")
        except Exception as e:
            print(f"✗ {name}分析异常: {str(e)[:50]}")
    
    # 总结
    print("\n" + "=" * 60)
    print("✅ 测试完成！")
    print("=" * 60)
    print("\n访问地址:")
    print("  前台: http://127.0.0.1:8000")
    print("  后台: http://127.0.0.1:8000/admin/")
    print("  密码: xuanxiang2026")
    print("\n按 Ctrl+C 停止服务器...")
    
    # 保持运行
    php_server.wait()

except KeyboardInterrupt:
    print("\n\n停止服务器...")
except Exception as e:
    print(f"\n✗ 测试失败: {e}")
finally:
    mock_ai.terminate()
    php_server.terminate()
    print("✓ 已停止所有服务")
