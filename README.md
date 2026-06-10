# 玄象 v4.0 PHP版 - 卡密付费系统

纯PHP实现的AI算命系统，无需Node.js，适合小皮面板直接部署。

## ✨ 核心功能

**💰 卡密付费系统**
- 免费体验：每功能每天3次
- 付费解锁：输入卡密后无限使用
- 三种套餐：体验卡/月度卡/年度卡

**🎉 可视化管理后台**
- 🎫 **卡密管理**：批量生成、查看状态、删除管理
- 📊 实时统计仪表盘
- ⚙️ 在线修改API配置
- 📁 在线编辑代码文件
- 📋 订单管理
- 🛠️ 系统工具

**☯ 三大核心服务**
- 八字排盘
- 姓名分析
- 周公解梦

---

## 🚀 部署步骤

### 1. 上传文件
删除服务器 `/www/admin/ai.olbaba.com_80/wwwroot/` 的所有旧文件，上传并解压 `xuanxiang-php-v4.0-final.zip`

### 2. 设置权限
```bash
chmod 755 data
```

### 3. 登录后台
- 地址：`http://ai.olbaba.com/admin/`
- **默认密码**：`xuanxiang2026`
- ⚠️ 首次登录后，请立即修改密码：编辑 `admin/api.php` 第8行

### 4. 配置API
- 系统配置 → 填写 `AI_API_KEY` 和 `AI_API_URL`
- 点击"测试API"确认连接正常

### 5. 生成卡密
- 卡密管理 → 选择类型和数量 → 生成
- 复制保存卡密列表

### 6. 测试激活
- 访问首页：`http://ai.olbaba.com`
- 点击任一功能，免费使用3次
- 点击"激活卡密"，输入测试卡密
- 验证无限使用功能

---

## 💳 卡密套餐

| 类型 | 有效期 | 建议价格 |
|------|--------|----------|
| 体验卡 | 7天 | ¥9.9 |
| 月度卡 | 30天 | ¥29.9 |
| 年度卡 | 365天 | ¥99 |

---

## 📋 运营流程

### 卖卡流程
1. 后台生成卡密（批量1-100个）
2. 用户联系微信购买
3. 收款后发送卡密
4. 用户自行激活

### 卡密格式
`XX + 12位随机字符`，例如：`XX3F8A2B9D1C4E`

### 管理卡密
- **未使用**：绿色显示，可删除
- **已使用**：灰色显示，显示激活时间
- **已过期**：用户激活时自动验证

---

## 📁 目录结构

```
├── index.php              # 入口路由
├── config.php             # 配置管理
├── .env                   # API密钥（可在后台修改）
├── .htaccess             # 伪静态规则
├── admin/                 # 管理后台
│   ├── index.html         # 后台界面
│   └── api.php            # 后台API（含密码验证）
├── includes/
│   ├── functions.php      # 工具函数（含卡密验证）
│   └── ai-service.php     # AI服务
├── api/                   # 前台API
│   ├── stats.php
│   ├── bazi-analyze.php
│   ├── name-analyze.php
│   ├── dream-interpret.php
│   ├── orders-list.php
│   ├── orders-create.php
│   ├── cardkey-verify.php
│   └── cardkey-activate.php
├── frontend/
│   ├── index.html
│   └── app-v4-cardkey.js  # 卡密版前端
├── shared/
│   └── styles-v4.css
└── data/                  # 数据目录（需755权限）
    ├── cardkeys.json      # 卡密数据
    ├── orders.json
    ├── users.json
    └── stats.json
```

---

## 🔐 安全建议

### 修改管理员密码
编辑 `admin/api.php` 第8行：
```php
define('ADMIN_PASSWORD', '你的新密码');
```

### 隐藏后台入口
1. 重命名 `admin` 目录为其他名称（如 `manage-x7y2`）
2. 更新 `index.php` 中的后台路由

### 备份数据
定期备份 `data` 目录

---

## ❓ 常见问题

### 后台无法登录
- 检查密码是否正确（默认：`xuanxiang2026`）
- 清除浏览器Cookie后重试

### API调用失败
- 后台 → 系统配置 → 测试API
- 检查 `.env` 中 `AI_API_KEY` 是否正确
- 确认PHP curl扩展已启用

### 卡密无法激活
- 检查卡密格式是否正确（大写）
- 后台查看卡密状态（未使用/已过期）
- 检查 `data/cardkeys.json` 权限

### 无法写入数据
```bash
chmod 755 data
chmod 644 data/*.json
```

---

## 📞 技术支持

部署问题可微信联系：xuanxiang_ai

---

**部署完成后访问：**
- 前台：http://ai.olbaba.com
- 后台：http://ai.olbaba.com/admin/（密码：xuanxiang2026）
