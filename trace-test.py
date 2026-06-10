#!/usr/bin/env python3
# 玄象功能链路追踪分析
import re
from pathlib import Path

print("=" * 60)
print("🔗 玄象功能链路追踪分析")
print("=" * 60)

base_dir = Path.home() / 'Desktop' / 'xuanxiang-local-test'

def extract_functions(content):
    """提取PHP文件中定义的函数"""
    pattern = r'function\s+(\w+)\s*\('
    return set(re.findall(pattern, content))

def extract_function_calls(content):
    """提取PHP文件中调用的函数"""
    # 简单匹配：函数名后跟括号
    pattern = r'(\w+)\s*\('
    calls = re.findall(pattern, content)
    # 过滤掉PHP内置和明显不是函数的
    exclude = {'if', 'for', 'while', 'switch', 'echo', 'return', 'array', 'isset', 'empty', 'define'}
    return set(c for c in calls if c not in exclude)

def trace_api_call(api_name, api_file):
    """追踪一个API调用的完整链路"""
    print(f"\n{'='*60}")
    print(f"📍 {api_name}")
    print(f"{'='*60}")
    
    if not api_file.exists():
        print(f"✗ 文件不存在: {api_file}")
        return False
    
    content = api_file.read_text(encoding='utf-8')
    issues = []
    
    # 1. 检查依赖加载
    print("\n[1] 依赖检查:")
    has_config = 'config.php' in content and 'require_once' in content
    has_functions = 'functions.php' in content and 'require_once' in content
    
    if has_config:
        print("  ✓ 加载 config.php")
    else:
        print("  ✗ 未加载 config.php")
        issues.append("缺少config.php")
    
    if has_functions:
        print("  ✓ 加载 functions.php")
    else:
        print("  ✗ 未加载 functions.php")
        issues.append("缺少functions.php")
    
    # 2. 检查输入处理
    print("\n[2] 输入处理:")
    if 'getPostData()' in content:
        print("  ✓ 使用 getPostData() 获取JSON输入")
    else:
        print("  ✗ 未使用 getPostData()")
        issues.append("缺少输入处理")
    
    # 3. 检查参数验证
    print("\n[3] 参数验证:")
    if 'isset(' in content or '!empty(' in content:
        print("  ✓ 有参数验证逻辑")
        # 提取验证的字段
        fields = re.findall(r"\$data\['(\w+)'\]", content)
        if fields:
            print(f"  ✓ 验证字段: {', '.join(set(fields))}")
    else:
        print("  ⚠️ 缺少参数验证")
    
    # 4. 检查AI调用
    print("\n[4] AI服务调用:")
    ai_functions = ['interpretDream', 'analyzeBazi', 'analyzeName', 'psychologyChat']
    called = [f for f in ai_functions if f in content]
    
    if called:
        print(f"  ✓ 调用AI函数: {', '.join(called)}")
        
        # 检查对应的AI函数是否存在
        ai_service = base_dir / 'includes' / 'ai-service.php'
        if ai_service.exists():
            ai_content = ai_service.read_text(encoding='utf-8')
            for func in called:
                if f'function {func}(' in ai_content:
                    print(f"    ✓ {func} 已定义")
                else:
                    print(f"    ✗ {func} 未定义")
                    issues.append(f"AI函数{func}未定义")
    else:
        print("  ✗ 未调用任何AI函数")
        issues.append("缺少AI调用")
    
    # 5. 检查数据存储
    print("\n[5] 数据存储:")
    if 'addOrder(' in content:
        print("  ✓ 记录订单数据")
    else:
        print("  ⚠️ 未记录订单")
    
    # 6. 检查响应返回
    print("\n[6] 响应返回:")
    if 'jsonResponse(' in content:
        print("  ✓ 使用 jsonResponse() 返回结果")
    else:
        print("  ⚠️ 响应格式可能不标准")
    
    # 7. 检查错误处理
    print("\n[7] 错误处理:")
    if 'try {' in content and 'catch' in content:
        print("  ✓ 有 try-catch 错误捕获")
    else:
        print("  ⚠️ 缺少错误处理")
    
    if 'errorResponse(' in content:
        print("  ✓ 使用 errorResponse() 返回错误")
    
    # 总结
    print(f"\n{'─'*60}")
    if len(issues) == 0:
        print("✅ 链路完整，无问题")
        return True
    else:
        print(f"❌ 发现 {len(issues)} 个问题:")
        for issue in issues:
            print(f"   • {issue}")
        return False

# 测试三大功能
print("\n" + "🎯 测试四大核心功能" + "\n")

tests = [
    ("解梦功能", base_dir / "api" / "dream-interpret.php"),
    ("八字分析", base_dir / "api" / "bazi-analyze.php"),
    ("姓名分析", base_dir / "api" / "name-analyze.php"),
    ("心理陪伴", base_dir / "api" / "psychology-chat.php"),
]

results = []
for name, path in tests:
    result = trace_api_call(name, path)
    results.append((name, result))

# 额外检查：AI服务核心
print(f"\n{'='*60}")
print("🤖 AI服务核心检查")
print(f"{'='*60}")

ai_service = base_dir / 'includes' / 'ai-service.php'
if ai_service.exists():
    content = ai_service.read_text(encoding='utf-8')
    
    print("\n[callAI 核心函数]:")
    
    checks = [
        ('检查API KEY', 'if (empty(AI_API_KEY))'),
        ('构建请求数据', "'model' => AI_MODEL"),
        ('处理URL路径', '/chat/completions'),
        ('初始化CURL', 'curl_init('),
        ('设置POST请求', 'CURLOPT_POST'),
        ('设置请求体', 'CURLOPT_POSTFIELDS'),
        ('设置Authorization', 'Authorization: Bearer'),
        ('执行请求', 'curl_exec('),
        ('检查HTTP状态码', 'curl_getinfo'),
        ('解析JSON响应', 'json_decode('),
        ('验证返回格式', "['choices'][0]['message']['content']"),
        ('返回结果', 'return $result'),
    ]
    
    for name, pattern in checks:
        if pattern in content:
            print(f"  ✓ {name}")
        else:
            print(f"  ✗ {name}")

# 最终总结
print(f"\n{'='*60}")
print("📊 测试总结")
print(f"{'='*60}\n")

passed = sum(1 for _, r in results if r)
total = len(results)

print(f"核心功能测试: {passed}/{total} 通过")
for name, result in results:
    status = "✅" if result else "❌"
    print(f"  {status} {name}")

if passed == total:
    print("\n🎉 所有功能链路完整！代码可以部署！")
else:
    print(f"\n⚠️ 有 {total - passed} 个功能存在问题，需要修复")

print(f"\n{'='*60}")
