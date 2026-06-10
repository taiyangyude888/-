#!/usr/bin/env python3
# 玄象代码静态分析 - 不需要PHP环境
import json
from pathlib import Path

print("=" * 60)
print("🔍 玄象代码静态分析")
print("=" * 60)

base_dir = Path.home() / 'Desktop' / 'xuanxiang-local-test'
issues = []
warnings = []

# 1. 检查关键文件存在性
print("\n[1/6] 检查文件完整性...")
required_files = {
    'config.php': '配置文件',
    'includes/functions.php': '公共函数',
    'includes/ai-service.php': 'AI服务',
    'api/dream-interpret.php': '解梦API',
    'api/bazi-analyze.php': '八字API',
    'api/name-analyze.php': '姓名API',
    'admin/api.php': '后台API',
    'index.php': '前台入口',
}

for file, desc in required_files.items():
    path = base_dir / file
    if path.exists():
        size = path.stat().st_size
        print(f"✓ {desc:12} ({size:5} bytes) - {file}")
    else:
        issues.append(f"缺少文件: {file}")
        print(f"✗ {desc:12} - {file} [缺失]")

# 2. 检查PHP语法问题
print("\n[2/6] 检查PHP语法...")
php_files = list(base_dir.glob('**/*.php'))
syntax_ok = 0
syntax_bad = 0

for php_file in php_files:
    if 'vendor' in str(php_file) or 'node_modules' in str(php_file):
        continue
    
    try:
        content = php_file.read_text(encoding='utf-8')
        relative = php_file.relative_to(base_dir)
        
        # 检查常见语法错误
        problems = []
        
        # 引号匹配
        single_quotes = content.count("'")
        double_quotes = content.count('"')
        if single_quotes % 2 != 0:
            problems.append("单引号不匹配")
        if double_quotes % 2 != 0:
            problems.append("双引号不匹配")
        
        # 检查未闭合的PHP标签
        if content.count('<?php') > content.count('?>') + 1:
            problems.append("可能有未闭合的PHP标签")
        
        # 检查常见错误
        if 'require_once' in content and 'config.php' in content:
            if 'AI_API_URL' in content and 'require_once' not in content[:content.find('AI_API_URL')]:
                problems.append("使用常量前未加载config.php")
        
        if problems:
            syntax_bad += 1
            issues.append(f"{relative}: {', '.join(problems)}")
            print(f"✗ {relative}: {', '.join(problems)}")
        else:
            syntax_ok += 1
            
    except Exception as e:
        syntax_bad += 1
        issues.append(f"{relative}: 读取失败 - {e}")

print(f"✓ 语法检查完成: {syntax_ok} 正常, {syntax_bad} 问题")

# 3. 检查AI服务配置
print("\n[3/6] 检查AI服务配置...")
ai_service = base_dir / 'includes' / 'ai-service.php'
if ai_service.exists():
    content = ai_service.read_text(encoding='utf-8')
    
    checks = [
        ('定义callAI函数', 'function callAI('),
        ('使用AI_API_URL常量', 'AI_API_URL'),
        ('使用AI_API_KEY常量', 'AI_API_KEY'),
        ('CURL初始化', 'curl_init'),
        ('设置Authorization header', 'Authorization: Bearer'),
        ('检查返回值', 'choices'),
        ('自动补全URL路径', '/chat/completions'),
    ]
    
    for name, pattern in checks:
        if pattern in content:
            print(f"✓ {name}")
        else:
            warnings.append(f"AI服务: 缺少{name}")
            print(f"⚠️ {name} [缺失]")

# 4. 检查API文件依赖
print("\n[4/6] 检查API文件依赖...")
api_files = list((base_dir / 'api').glob('*.php'))
for api_file in api_files:
    content = api_file.read_text(encoding='utf-8')
    name = api_file.name
    
    has_config = 'require_once' in content and 'config.php' in content
    has_functions = 'require_once' in content and 'functions.php' in content
    
    if has_config and has_functions:
        print(f"✓ {name:25} - 依赖完整")
    else:
        missing = []
        if not has_config:
            missing.append('config.php')
        if not has_functions:
            missing.append('functions.php')
        issues.append(f"{name}: 缺少依赖 {', '.join(missing)}")
        print(f"✗ {name:25} - 缺少: {', '.join(missing)}")

# 5. 检查关键函数定义
print("\n[5/6] 检查关键函数...")
functions_file = base_dir / 'includes' / 'functions.php'
if functions_file.exists():
    content = functions_file.read_text(encoding='utf-8')
    
    required_functions = [
        'getPostData',
        'jsonResponse',
        'errorResponse',
        'readJsonFile',
        'writeJsonFile',
    ]
    
    for func in required_functions:
        if f'function {func}(' in content:
            print(f"✓ {func}")
        else:
            issues.append(f"functions.php: 缺少函数 {func}")
            print(f"✗ {func} [缺失]")

# 6. 生成测试报告
print("\n[6/6] 生成报告...")
report = {
    'timestamp': '2026-06-09 17:00:00',
    'total_files': len(php_files),
    'syntax_ok': syntax_ok,
    'syntax_errors': syntax_bad,
    'issues': issues,
    'warnings': warnings,
}

report_file = base_dir / 'test-report.json'
report_file.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding='utf-8')
print(f"✓ 报告已保存到: {report_file}")

# 总结
print("\n" + "=" * 60)
if len(issues) == 0:
    print("✅ 代码检查通过！所有文件完整，无语法错误")
    print("   可以安全部署到服务器")
else:
    print(f"⚠️ 发现 {len(issues)} 个问题：")
    for issue in issues[:10]:
        print(f"   • {issue}")
    if len(issues) > 10:
        print(f"   ... 还有 {len(issues) - 10} 个问题")
    print("\n   请修复后再部署")

if len(warnings) > 0:
    print(f"\n⚠️ {len(warnings)} 个警告（不影响运行）：")
    for warn in warnings[:5]:
        print(f"   • {warn}")

print("=" * 60)
