// 玄象 v4.0 - 简化版前端
const API_BASE = '';

document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  setupServiceCards();
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

// 设置服务卡片点击
function setupServiceCards() {
  document.querySelectorAll('.tool-card').forEach(card => {
    const service = card.getAttribute('data-service');
    if (['bazi', 'naming', 'dream'].includes(service)) {
      card.onclick = () => showServiceModal(service);
    }
  });
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
    hour: form.hour.value
  };
  
  await submitAnalysis('/api/bazi/analyze', data, form);
}

// 提交姓名
async function submitName(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    name: form.name.value,
    gender: form.gender.value
  };
  
  await submitAnalysis('/api/name/analyze', data, form);
}

// 提交解梦
async function submitDream(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    dream: form.dream.value,
    userName: form.userName.value || '匿名'
  };
  
  await submitAnalysis('/api/dream/interpret', data, form);
}

// 统一提交分析
async function submitAnalysis(url, data, form) {
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
    
    const text = await res.text();
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('服务器返回:', text);
      throw new Error('服务器返回格式错误，请检查后台配置');
    }
    
    if (result.success) {
      resultDiv.innerHTML = `
        <div class="result-success">
          <h3>✨ 分析结果</h3>
          <div class="result-text">${formatResult(result.result)}</div>
          <p class="result-footer">订单号：${result.orderId}</p>
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

// 格式化结果
function formatResult(text) {
  return text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
}

// 关闭弹窗
function closeModal(element) {
  const modal = element.closest('.service-modal');
  if (modal) {
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 300);
  }
}

// 公益捐赠弹窗（占位）
function showCharityModal() {
  alert('公益捐赠功能正在筹备中\n\n玄象承诺将部分收入用于公益事业');
}

// 添加弹窗样式
const style = document.createElement('style');
style.textContent = `
  .service-modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
  .service-modal.show { opacity: 1; }
  .modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); }
  .modal-content { position: relative; background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
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
`;
document.head.appendChild(style);
