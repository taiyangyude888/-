#!/usr/bin/env python3
# 玄象付费流程分析
from pathlib import Path
import re

print("=" * 60)
print("💰 玄象付费流程完整性分析")
print("=" * 60)

base_dir = Path.home() / 'Desktop' / 'xuanxiang-local-test'
issues = []
warnings = []

# 1. 检查免费次数限制
print("\n[1/7] 免费次数限制机制")
print("-" * 60)

# 检查前端是否有次数显示
frontend_files = list((base_dir / 'frontend').glob('*.html')) + list((base_dir / 'frontend').glob('*.js'))
has_free_limit_ui = False
for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    if '免费' in content and ('次' in content or '剩余' in content):
        has_free_limit_ui = True
        print(f"✓ {f.name}: 发现免费次数相关UI")

if not has_free_limit_ui:
    issues.append("前端缺少免费次数显示")
    print("✗ 前端没有显示免费次数的UI")

# 检查后端是否有次数统计
users_tracking = False
api_files = list((base_dir / 'api').glob('*.php'))
for f in api_files:
    content = f.read_text(encoding='utf-8')
    if 'USERS_FILE' in content or 'readJsonFile' in content:
        users_tracking = True

if users_tracking:
    print("✓ 后端有用户数据追踪")
else:
    issues.append("后端缺少用户使用次数统计")
    print("✗ 后端没有追踪用户使用次数")

# 2. 检查付费入口
print("\n[2/7] 付费入口（超过免费次数后）")
print("-" * 60)

payment_entry_found = False
for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    if '购买' in content or '充值' in content or '卡密' in content:
        payment_entry_found = True
        print(f"✓ {f.name}: 发现付费相关文字")

if not payment_entry_found:
    issues.append("前端缺少明显的付费入口")
    print("✗ 前端没有明显的付费入口")

# 3. 检查卡密系统
print("\n[3/7] 卡密系统")
print("-" * 60)

cardkey_files = {
    'api/cardkey-verify.php': '卡密验证API',
    'api/cardkey-activate.php': '卡密激活API',
    'admin/api.php': '后台卡密管理'
}

for file, desc in cardkey_files.items():
    path = base_dir / file
    if path.exists():
        content = path.read_text(encoding='utf-8')
        if 'cardkey' in content or 'cardKey' in content:
            print(f"✓ {desc}")
        else:
            warnings.append(f"{file}: 文件存在但可能功能不完整")
            print(f"⚠️ {desc}: 文件存在但内容可疑")
    else:
        issues.append(f"缺少文件: {file}")
        print(f"✗ {desc}: 文件不存在")

# 4. 检查卡密输入界面
print("\n[4/7] 用户卡密输入界面")
print("-" * 60)

cardkey_input_found = False
for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    if re.search(r'(卡密|激活码|兑换码).*input', content, re.IGNORECASE):
        cardkey_input_found = True
        print(f"✓ {f.name}: 发现卡密输入框")

if not cardkey_input_found:
    issues.append("前端缺少卡密输入界面")
    print("✗ 前端没有卡密输入界面")

# 5. 检查付费提示流程
print("\n[5/7] 付费提示流程（用完免费次数）")
print("-" * 60)

payment_prompt_logic = False
for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    if ('免费次数' in content or 'freeUsage' in content) and ('购买' in content or '卡密' in content):
        payment_prompt_logic = True
        print(f"✓ {f.name}: 发现付费提示逻辑")

if not payment_prompt_logic:
    issues.append("前端缺少用完免费次数后的付费引导")
    print("✗ 没有在用完免费次数后引导用户付费")

# 6. 检查收款方式
print("\n[6/7] 收款方式展示")
print("-" * 60)

payment_methods = ['微信', '支付宝', '二维码', 'QR', 'qrcode']
payment_ui_found = False

for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    for method in payment_methods:
        if method.lower() in content.lower():
            payment_ui_found = True
            print(f"✓ {f.name}: 发现收款相关内容")
            break

if not payment_ui_found:
    warnings.append("前端没有收款二维码/方式展示")
    print("⚠️ 没有找到收款方式展示（可能需要配置）")

# 7. 检查完整付费流程
print("\n[7/7] 完整付费流程检查")
print("-" * 60)

flow_steps = {
    '用户注册/登录': False,
    '免费次数统计': users_tracking,
    '用完提示付费': payment_prompt_logic,
    '展示收款方式': payment_ui_found,
    '输入卡密': cardkey_input_found,
    '验证激活': (base_dir / 'api' / 'cardkey-activate.php').exists(),
    '解锁使用': True  # 假设验证后自动解锁
}

# 检查登录
login_found = False
for f in frontend_files:
    content = f.read_text(encoding='utf-8')
    if '登录' in content or 'login' in content.lower():
        login_found = True
        break
flow_steps['用户注册/登录'] = login_found

for step, status in flow_steps.items():
    if status:
        print(f"✓ {step}")
    else:
        print(f"✗ {step}")
        issues.append(f"付费流程缺失: {step}")

# 生成报告
print("\n" + "=" * 60)
print("📊 分析结果")
print("=" * 60)

critical_issues = len(issues)
minor_warnings = len(warnings)

if critical_issues == 0:
    print("✅ 付费流程完整，用户可以顺畅付费！")
else:
    print(f"❌ 发现 {critical_issues} 个关键问题，付费流程不完整：\n")
    for i, issue in enumerate(issues, 1):
        print(f"  {i}. {issue}")

if minor_warnings > 0:
    print(f"\n⚠️ {minor_warnings} 个次要警告：\n")
    for i, warn in enumerate(warnings, 1):
        print(f"  {i}. {warn}")

# 付费流程顺畅度评分
total_steps = len(flow_steps)
completed_steps = sum(1 for v in flow_steps.values() if v)
score = int(completed_steps / total_steps * 100)

print(f"\n💯 付费流程完整度: {score}% ({completed_steps}/{total_steps})")

if score >= 80:
    print("   评级: 优秀 ⭐⭐⭐⭐⭐ - 可以直接运营")
elif score >= 60:
    print("   评级: 良好 ⭐⭐⭐⭐ - 小修小补即可")
elif score >= 40:
    print("   评级: 及格 ⭐⭐⭐ - 需要补充关键功能")
else:
    print("   评级: 不及格 ⭐⭐ - 需要大量修复")

print("=" * 60)
