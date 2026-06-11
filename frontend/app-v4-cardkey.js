// 玄象 v4.0 - 卡密版前端
const API_BASE = '';
let cardKey = localStorage.getItem('xuanxiang_cardkey');
let usageToday = JSON.parse(localStorage.getItem('xuanxiang_usage_' + getToday()) || '{}');

document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  setupServiceCards();
  checkCardKeyStatus();
});

// 加载统计
async function loadStats() {
  try {
    const res = await fetch('/api/stats');
    const data = await res.json();
    document.getElementById('totalUsers').textContent = data.totalUsers || 0;
  } catch (err) {
    console.error('统计加载失败:', err);
  }
}

// 检查卡密状态
async function checkCardKeyStatus() {
  if (cardKey) {
    try {
      const res = await fetch('/api/cardkey/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cardkey: cardKey })
      });
      const data = await res.json();
      
      if (!data.valid) {
        localStorage.removeItem('xuanxiang_cardkey');
        cardKey = null;
        alert('您的卡密已过期，请重新购买');
      }
    } catch (err) {
      console.error('卡密验证失败:', err);
    }
  }
}

// 设置服务卡片
function setupServiceCards() {
  document.querySelectorAll('.tool-card').forEach(card => {
    const service = card.getAttribute('data-service');
    if (['bazi', 'naming', 'dream'].includes(service)) {
      card.onclick = () => checkUsageAndShow(service);
    }
  });
}

// 检查使用次数
function checkUsageAndShow(serviceType) {
  // 如果有有效卡密，直接使用
  if (cardKey) {
    showServiceModal(serviceType);
    return;
  }
  
  // 检查今日免费次数
  const serviceKey = serviceType;
  const used = usageToday[serviceKey] || 0;
  const freeLimit = 3;
  
  if (used >= freeLimit) {
    showPurchaseModal();
    return;
  }
  
  // 显示服务弹窗
  showServiceModal(serviceType);
}

// 显示购买弹窗
async function showPurchaseModal() {
  // 读取收款配置
  let wechat = 'xuanxiang_ai'; // 默认值
  let qrcodeHtml = '';
  
  try {
    const configRes = await fetch('/admin/api.php?action=getConfig');
    const config = await configRes.json();
    if (config.PAYMENT_WECHAT) {
      wechat = config.PAYMENT_WECHAT;
    }
    
    // 检查是否有二维码
    const qrcodeCheck = await fetch('/shared/payment-qrcode.jpg', { method: 'HEAD' });
    if (qrcodeCheck.ok) {
      qrcodeHtml = `<div style="text-align: center; margin: 15px 0;">
        <img src="/shared/payment-qrcode.jpg" alt="收款二维码" style="max-width: 200px; border: 1px solid #ddd; border-radius: 8px;">
      </div>`;
    }
  } catch (e) {
    // 使用默认值
  }
  
  const modal = document.createElement('div');
  modal.className = 'service-modal';
  modal.innerHTML = `
    <div class="modal-overlay" onclick="closeModal(this)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="close-btn" onclick="closeModal(this)">×</button>
        <h2>🎫 解锁无限使用</h2>
        <div class="purchase-info">
          <p class="notice">您今日的免费次数已用完（3次/天）</p>
          
          <div class="price-cards">
            <div class="price-card">
              <div class="price-title">体验卡</div>
              <div class="price-amount">¥9.9</div>
              <div class="price-desc">7天无限使用</div>
            </div>
            <div class="price-card featured">
              <div class="badge">推荐</div>
              <div class="price-title">月度卡</div>
              <div class="price-amount">¥29.9</div>
              <div class="price-desc">30天无限使用</div>
            </div>
          </div>
          
          ${qrcodeHtml}
          
          <div class="payment-steps">
            <h3>购买步骤</h3>
            <ol>
              <li>选择套餐，添加微信：<strong>${wechat}</strong></li>
              <li>转账后发送截图 + 选择的套餐</li>
              <li>获取卡密后，返回网站输入即可</li>
            </ol>
          </div>
          
          <button class="submit-btn" onclick="showActivateInput()">我已有卡密，立即激活</button>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  setTimeout(() => modal.classList.add('show'), 10);
}

// 显示激活输入
function showActivateInput() {
  const modal = document.querySelector('.service-modal');
  modal.querySelector('.modal-content').innerHTML = `
    <button class="close-btn" onclick="closeModal(this)">×</button>
    <h2>🔑 激活卡密</h2>
    <form onsubmit="activateCardKey(event)">
      <input type="text" name="cardkey" placeholder="请输入卡密（如：XuanXiang2024ABC123）" required style="text-transform: uppercase;" />
      <button type="submit" class="submit-btn">立即激活</button>
    </form>
    <p style="text-align: center; margin-top: 15px; font-size: 13px; color: #999;">
      还没有卡密？<a href="#" onclick="event.preventDefault(); showPurchaseModal(); closeModal(this);" style="color: #667eea;">立即购买</a>
    </p>
  `;
}

// 激活卡密
async function activateCardKey(e) {
  e.preventDefault();
  const form = e.target;
  const cardkey = form.cardkey.value.trim().toUpperCase();
  const submitBtn = form.querySelector('.submit-btn');
  
  submitBtn.disabled = true;
  submitBtn.textContent = '验证中...';
  
  try {
    const res = await fetch('/api/cardkey/activate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cardkey })
    });
    
    const data = await res.json();
    
    if (data.success) {
      localStorage.setItem('xuanxiang_cardkey', cardkey);
      cardKey = cardkey;
      alert(`✅ 激活成功！\n\n有效期至：${data.expiresAt}\n\n现在可以无限使用所有功能了`);
      closeModal(form);
    } else {
      alert('❌ ' + (data.error || '卡密无效或已过期'));
      submitBtn.disabled = false;
      submitBtn.textContent = '立即激活';
    }
  } catch (err) {
    alert('❌ 网络错误: ' + err.message);
    submitBtn.disabled = false;
    submitBtn.textContent = '立即激活';
  }
}

// 显示服务弹窗
function showServiceModal(serviceType) {
  const modal = document.createElement('div');
  modal.className = 'service-modal';
  modal.innerHTML = getModalContent(serviceType);
  document.body.appendChild(modal);
  setTimeout(() => modal.classList.add('show'), 10);
}

// 获取弹窗内容
function getModalContent(type) {
  const forms = {
    bazi: `
      <div class="modal-overlay" onclick="closeModal(this)">
        <div class="modal-content" onclick="event.stopPropagation()">
          <button class="close-btn" onclick="closeModal(this)">×</button>
          <h2>☯ 八字排盘</h2>
          <form onsubmit="submitBazi(event)">
            <input type="text" name="userName" placeholder="姓名（选填）" />
            <select name="gender" required>
              <option value="">选择性别</option>
              <option value="男">男</option>
              <option value="女">女</option>
            </select>
            <input type="number" name="year" placeholder="出生年份（如1990）" required min="1900" max="2026" />
            <input type="number" name="month" placeholder="出生月份（1-12）" required min="1" max="12" />
            <input type="number" name="day" placeholder="出生日期（1-31）" required min="1" max="31" />
            <input type="number" name="hour" placeholder="出生时辰（0-23）" required min="0" max="23" />
            <button type="submit" class="submit-btn">开始分析</button>
          </form>
          <div id="result"></div>
        </div>
      </div>
    `,
    naming: `
      <div class="modal-overlay" onclick="closeModal(this)">
        <div class="modal-content" onclick="event.stopPropagation()">
          <button class="close-btn" onclick="closeModal(this)">×</button>
          <h2>📝 姓名分析</h2>
          <form onsubmit="submitName(event)">
            <input type="text" name="name" placeholder="请输入姓名" required />
            <select name="gender" required>
              <option value="">选择性别</option>
              <option value="男">男</option>
              <option value="女">女</option>
            </select>
            <button type="submit" class="submit-btn">开始分析</button>
          </form>
          <div id="result"></div>
        </div>
      </div>
    `,
    dream: `
      <div class="modal-overlay" onclick="closeModal(this)">
        <div class="modal-content" onclick="event.stopPropagation()">
          <button class="close-btn" onclick="closeModal(this)">×</button>
          <h2>🌙 周公解梦</h2>
          <form onsubmit="submitDream(event)">
            <textarea name="dream" placeholder="描述你的梦境..." required rows="5"></textarea>
            <input type="text" name="userName" placeholder="姓名（选填）" />
            <button type="submit" class="submit-btn">开始解析</button>
          </form>
          <div id="result"></div>
        </div>
      </div>
    `
  };
  return forms[type] || '';
}

// 提交八字
async function submitBazi(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    userName: form.userName.value || '匿名',
    gender: form.gender.value,
    year: form.year.value,
    month: form.month.value,
    day: form.day.value,
    hour: form.hour.value,
    cardkey: cardKey
  };
  
  await submitAnalysis('/api/bazi/analyze', data, form, 'bazi');
}

// 提交姓名
async function submitName(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    name: form.name.value,
    gender: form.gender.value,
    cardkey: cardKey
  };
  
  await submitAnalysis('/api/name/analyze', data, form, 'naming');
}

// 提交解梦
async function submitDream(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    dream: form.dream.value,
    userName: form.userName.value || '匿名',
    cardkey: cardKey
  };
  
  await submitAnalysis('/api/dream/interpret', data, form, 'dream');
}

// 统一提交分析
async function submitAnalysis(url, data, form, serviceKey) {
  const resultDiv = document.getElementById('result');
  const submitBtn = form.querySelector('.submit-btn');
  
  submitBtn.disabled = true;
  submitBtn.textContent = '分析中...';
  resultDiv.innerHTML = '<div class="loading">🔮 AI正在为您分析，请稍候...</div>';
  
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await res.json();
    
    if (result.success) {
      // 记录使用次数（仅免费用户）
      if (!cardKey) {
        usageToday[serviceKey] = (usageToday[serviceKey] || 0) + 1;
        localStorage.setItem('xuanxiang_usage_' + getToday(), JSON.stringify(usageToday));
      }
      
      resultDiv.innerHTML = `
        <div class="result-success">
          <h3>✨ 分析结果</h3>
          <div class="result-text">${formatResult(result.result)}</div>
          <p class="result-footer">订单号：${result.orderId}</p>
          ${!cardKey ? `<p class="result-footer" style="color: #667eea;">今日剩余免费次数：${3 - usageToday[serviceKey]}</p>` : ''}
        </div>
      `;
      form.style.display = 'none';
    } else {
      resultDiv.innerHTML = `<div class="result-error">❌ ${result.error || '分析失败'}</div>`;
      submitBtn.disabled = false;
      submitBtn.textContent = '重新分析';
    }
  } catch (err) {
    resultDiv.innerHTML = `<div class="result-error">❌ 网络错误: ${err.message}</div>`;
    submitBtn.disabled = false;
    submitBtn.textContent = '重新分析';
  }
}

// 工具函数
function getToday() {
  return new Date().toISOString().split('T')[0];
}

function formatResult(text) {
  return text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
}

function closeModal(element) {
  const modal = element.closest('.service-modal');
  if (modal) {
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 300);
  }
}

function showCharityModal() {
  alert('公益捐赠功能正在筹备中\n\n玄象承诺将部分收入用于公益事业');
}

// 顶部显示卡密状态
function showCardKeyButton() {
  const nav = document.querySelector('.nav-links');
  const btn = document.createElement('button');
  btn.className = 'nav-link user-btn';
  btn.textContent = cardKey ? '已激活 ✓' : '激活卡密';
  btn.onclick = () => cardKey ? showCardKeyInfo() : showActivateInput();
  nav.appendChild(btn);
}

function showCardKeyInfo() {
  alert('您的卡密已激活\n\n卡密：' + cardKey + '\n\n可无限使用所有功能');
}

// 添加样式
const style = document.createElement('style');
style.textContent = `
  .service-modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
  .service-modal.show { opacity: 1; }
  .modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); }
  .modal-content { position: relative; background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
  .close-btn { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 32px; cursor: pointer; color: #999; line-height: 1; }
  .close-btn:hover { color: #333; }
  .modal-content h2 { margin: 0 0 20px; font-size: 24px; color: #333; }
  .modal-content input, .modal-content select, .modal-content textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
  .modal-content input:focus, .modal-content select:focus, .modal-content textarea:focus { outline: none; border-color: #667eea; }
  .submit-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
  .submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.4); }
  .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .loading { text-align: center; padding: 30px; color: #667eea; font-size: 16px; }
  #result { margin-top: 20px; }
  .result-success { padding: 20px; background: #f0f9ff; border-radius: 12px; border-left: 4px solid #667eea; }
  .result-success h3 { margin: 0 0 15px; color: #667eea; font-size: 20px; }
  .result-text { line-height: 1.8; color: #333; white-space: pre-wrap; }
  .result-footer { margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
  .result-error { padding: 20px; background: #fff1f0; border-radius: 12px; color: #cf1322; border-left: 4px solid #ff4d4f; }
  
  .purchase-info { padding: 10px 0; }
  .notice { text-align: center; padding: 15px; background: #fff7e6; border-radius: 8px; color: #d46b08; margin-bottom: 20px; }
  .price-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
  .price-card { padding: 20px; border: 2px solid #e8e8e8; border-radius: 12px; text-align: center; transition: all 0.3s; position: relative; }
  .price-card.featured { border-color: #667eea; background: linear-gradient(135deg, #f5f7ff 0%, #fef5ff 100%); }
  .price-card .badge { position: absolute; top: -10px; right: 10px; background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; }
  .price-title { font-size: 16px; color: #666; margin-bottom: 8px; }
  .price-amount { font-size: 28px; font-weight: bold; color: #667eea; margin-bottom: 8px; }
  .price-desc { font-size: 13px; color: #999; }
  .payment-steps { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
  .payment-steps h3 { font-size: 16px; margin: 0 0 12px; color: #333; }
  .payment-steps ol { margin: 0; padding-left: 20px; }
  .payment-steps li { margin-bottom: 8px; color: #666; font-size: 14px; line-height: 1.6; }
`;
document.head.appendChild(style);

// 初始化卡密按钮
showCardKeyButton();
