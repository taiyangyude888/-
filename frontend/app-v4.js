// ============================================
// 玄象 v4.0 - 前端交互逻辑
// ============================================

const API_BASE = 'http://127.0.0.1:8787';
let currentUser = null;

// ============================================
// 页面初始化
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  checkWelcomeModal();
  loadUserSession();
  loadStats();
  loadModuleStatus();
  setupSearch();
  setupEventListeners();
});

// 首次访问弹窗
function checkWelcomeModal() {
  const welcomed = localStorage.getItem('xuanxiang_welcomed');
  if (!welcomed) {
    document.getElementById('welcomeModal').classList.add('show');
  }
}

function acceptWelcome() {
  localStorage.setItem('xuanxiang_welcomed', 'true');
  document.getElementById('welcomeModal').classList.remove('show');
}

// 加载用户会话
function loadUserSession() {
  const userStr = localStorage.getItem('xuanxiang_user');
  if (userStr) {
    try {
      currentUser = JSON.parse(userStr);
      updateUserButton();
    } catch (e) {
      localStorage.removeItem('xuanxiang_user');
    }
  }
}

function updateUserButton() {
  const userBtn = document.getElementById('userBtn');
  if (currentUser) {
    userBtn.textContent = currentUser.username;
    userBtn.onclick = showUserInfo;
  } else {
    userBtn.textContent = '登录';
    userBtn.onclick = showAuthModal;
  }
}

// 加载统计数据
async function loadStats() {
  try {
    const res = await fetch(`${API_BASE}/api/stats`);
    const data = await res.json();
    const totalUsersEl = document.getElementById('totalUsers');
    if (totalUsersEl) {
      totalUsersEl.textContent = data.totalUsers || 0;
    }
    
    // 加载公益数据（如果元素存在）
    const charityAmountEl = document.getElementById('charityAmount');
    const charityBeneficiariesEl = document.getElementById('charityBeneficiaries');
    if (charityAmountEl) charityAmountEl.textContent = '¥1,280';
    if (charityBeneficiariesEl) charityBeneficiariesEl.textContent = '42';
  } catch (err) {
    console.error('Failed to load stats:', err);
  }
}

// 加载模块状态
async function loadModuleStatus() {
  try {
    const res = await fetch(`${API_BASE}/api/modules`);
    const data = await res.json();
    const activeCount = data.modules.filter(m => m.enabled).length;
    document.getElementById('moduleStatus').textContent = 
      `已启用 ${activeCount} 个模块`;
  } catch (err) {
    document.getElementById('moduleStatus').textContent = '模块加载失败';
  }
}

// 搜索功能
function setupSearch() {
  const searchInput = document.getElementById('searchInput');
  const cards = document.querySelectorAll('.tool-card');
  
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    
    cards.forEach(card => {
      const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
      const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';
      const match = title.includes(query) || desc.includes(query);
      card.style.display = match ? 'block' : 'none';
    });
  });
}

// ============================================
// 用户认证
// ============================================

function showAuthModal() {
  document.getElementById('authModal').classList.add('show');
  document.getElementById('loginForm').style.display = 'block';
  document.getElementById('registerForm').style.display = 'none';
  document.getElementById('userInfo').style.display = 'none';
}

function closeAuthModal() {
  document.getElementById('authModal').classList.remove('show');
}

function switchToRegister() {
  document.getElementById('loginForm').style.display = 'none';
  document.getElementById('registerForm').style.display = 'block';
}

function switchToLogin() {
  document.getElementById('registerForm').style.display = 'none';
  document.getElementById('loginForm').style.display = 'block';
}

function showUserInfo() {
  if (!currentUser) {
    showAuthModal();
    return;
  }
  
  document.getElementById('authModal').classList.add('show');
  document.getElementById('loginForm').style.display = 'none';
  document.getElementById('registerForm').style.display = 'none';
  document.getElementById('userInfo').style.display = 'block';
  
  document.getElementById('displayUsername').textContent = currentUser.username;
  document.getElementById('displayBalance').textContent = 
    `¥${(currentUser.balance || 0).toFixed(2)}`;
  document.getElementById('displayCreatedAt').textContent = 
    new Date(currentUser.createdAt).toLocaleDateString('zh-CN');
}

async function login() {
  const username = document.getElementById('loginUsername').value.trim();
  const password = document.getElementById('loginPassword').value;
  
  if (!username || !password) {
    alert('请填写用户名和密码');
    return;
  }
  
  try {
    const res = await fetch(`${API_BASE}/api/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    
    const data = await res.json();
    
    if (data.ok) {
      currentUser = {
        ...data.user,
        token: data.token,
        balance: data.user.credits || 0
      };
      localStorage.setItem('xuanxiang_user', JSON.stringify(currentUser));
      updateUserButton();
      closeAuthModal();
      alert('登录成功！');
    } else {
      alert(data.error || '登录失败');
    }
  } catch (err) {
    alert('网络错误：' + err.message);
  }
}

async function register() {
  const username = document.getElementById('regUsername').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const password = document.getElementById('regPassword').value;
  
  if (!username || !password) {
    alert('请填写用户名和密码');
    return;
  }
  
  try {
    const res = await fetch(`${API_BASE}/api/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, email, password })
    });
    
    const data = await res.json();
    
    if (data.ok) {
      alert('注册成功！已自动登录');
      currentUser = {
        ...data.user,
        token: data.token,
        balance: data.user.credits || 0
      };
      localStorage.setItem('xuanxiang_user', JSON.stringify(currentUser));
      updateUserButton();
      closeAuthModal();
    } else {
      alert(data.error || '注册失败');
    }
  } catch (err) {
    alert('网络错误：' + err.message);
  }
}

function logout() {
  if (confirm('确定要退出登录吗？')) {
    currentUser = null;
    localStorage.removeItem('xuanxiang_user');
    updateUserButton();
    closeAuthModal();
    alert('已退出登录');
  }
}

function showRechargeModal() {
  alert('充值功能开发中，敬请期待！\n\n当前为测试版本，所有功能免费使用。');
}

// ============================================
// 服务导航
// ============================================

function navigateToService(serviceId) {
  if (!currentUser) {
    alert('请先登录！');
    showAuthModal();
    return;
  }
  
  // 心理陪伴直接跳转
  if (serviceId === 'psychology') {
    window.location.href = '/frontend/psychology.html';
    return;
  }
  
  // 其他服务待实现
  alert(`即将进入${getServiceName(serviceId)}\n\n功能正在开发中，敬请期待！`);
}

function getServiceName(serviceId) {
  const names = {
    qimen: '奇门遁甲',
    bazi: '八字排盘',
    ziwei: '紫微斗数',
    dream: '周公解梦',
    naming: '起名测名',
    psychology: '心理陪伴'
  };
  return names[serviceId] || serviceId;
}

function navigateToKnowledge() {
  alert('传统文化宝库正在整理中，敬请期待！');
}

// ============================================
// 服务说明弹窗
// ============================================

const serviceInfo = {
  qimen: {
    title: '⛩ 奇门遁甲精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>奇门遁甲是古代帝王统帅用以指挥军队、决策大事的高级预测术。本工具以当前时空自动起局，结合九宫八卦、天干地支、八门九星等要素，为您的问题提供趋势分析与行动建议。</p>

<h3>使用场景</h3>
<ul>
  <li>重大决策：创业、投资、合作等</li>
  <li>时机选择：签约、开业、搬家等</li>
  <li>问题诊断：困境分析、矛盾化解等</li>
  <li>趋势预测：事态发展、吉凶判断等</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速报告（免费）：</strong>包含起局说明、用神分析、吉凶判断、行动建议</p>
<p><strong>深度解读（¥9.9）：</strong>详细解析九宫布局、星门神组合、支持3次追问</p>

<h3>注意事项</h3>
<p>奇门遁甲依赖精确的时间信息，建议在问题产生的当下起局，效果最佳。结果仅供参考，重大决策请综合多方信息。</p>
    `
  },
  bazi: {
    title: '☯ 八字排盘精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>八字命理以出生年月日时的天干地支为基础，推演人生运势、性格特质、事业财运、婚姻感情等。本工具结合传统命理学与现代心理学，为您提供全面的命理分析。</p>

<h3>使用场景</h3>
<ul>
  <li>自我认知：了解性格、天赋、命格特点</li>
  <li>流年运势：查看当年、近期的运势走向</li>
  <li>事业财运：分析适合的职业方向、财运趋势</li>
  <li>婚姻感情：了解感情模式、婚配建议</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速报告（免费）：</strong>四柱排盘、五行分析、格局判断、核心建议</p>
<p><strong>深度解读（¥9.9）：</strong>十神详解、大运流年、喜用神、支持3次追问</p>

<h3>注意事项</h3>
<p>请提供准确的出生时间（精确到时辰）。若不知道具体时辰，可使用"不确定"选项，系统会给出综合分析。</p>
    `
  },
  ziwei: {
    title: '✨ 紫微斗数精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>紫微斗数号称"天下第一神数"，以紫微星为主，结合十二宫位、108颗星曜，推演人生格局与运势。本工具自动排盘，结合星曜组合与宫位关系，为您解读命运密码。</p>

<h3>使用场景</h3>
<ul>
  <li>命盘解读：了解命宫、财帛、官禄等十二宫</li>
  <li>性格分析：从命宫主星看性格特质</li>
  <li>运势预测：大限、流年、流月运势</li>
  <li>关系分析：夫妻宫、子女宫、兄弟宫等</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速报告（免费）：</strong>命盘排列、主星解析、格局说明、核心建议</p>
<p><strong>深度解读（¥9.9）：</strong>十二宫详解、星曜组合、大限流年、支持3次追问</p>

<h3>注意事项</h3>
<p>紫微斗数需要精确的出生时间。推荐先了解快速报告，若需要深入研究再解锁深度版本。</p>
    `
  },
  dream: {
    title: '🌙 周公解梦精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>结合《周公解梦》等传统典籍与现代心理学，为您的梦境提供文化解读与心理分析。梦是潜意识的语言，也是古人观察心象的智慧结晶。</p>

<h3>使用场景</h3>
<ul>
  <li>梦境解读：了解梦境的象征意义</li>
  <li>心理分析：从心理学角度理解潜意识</li>
  <li>吉凶提示：传统文化视角的吉凶寓意</li>
  <li>生活建议：基于梦境的行动指南</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速报告（免费）：</strong>梦象识别、传统解读、心理分析、生活建议</p>
<p><strong>深度解读（¥9.9）：</strong>详细分析、深层含义、支持3次追问</p>

<h3>注意事项</h3>
<p>请尽量详细描述梦境细节（场景、人物、情绪、颜色等），细节越丰富，解读越准确。</p>
    `
  },
  naming: {
    title: '📝 起名测名精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>以八字喜用神为主导，结合三才五格、音韵美学，为宝宝起一个既符合命理、又好听好记的名字。或为现有姓名进行全面测算评分。</p>

<h3>使用场景</h3>
<ul>
  <li>宝宝起名：根据八字起符合命理的好名字</li>
  <li>姓名测算：评估现有名字的五格、三才</li>
  <li>改名建议：分析现有名字的优缺点</li>
  <li>名字对比：对比多个候选名字</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速报告（免费）：</strong>3-5个候选名字、五格评分、简要说明</p>
<p><strong>深度解读（¥9.9）：</strong>10+候选名字、详细分析、支持3次追问</p>

<h3>注意事项</h3>
<p>起名需要提供宝宝出生时间、性别、姓氏。测名只需提供姓名和性别即可。</p>
    `
  },
  psychology: {
    title: '💗 心理陪伴精心调教说明',
    content: `
<h3>AI 调教理念</h3>
<p>提供温暖的情绪陪伴与心理支持。不诊断、不治疗，只倾听、理解、支持。帮助您表达情绪、梳理思路、发现内在力量。</p>

<h3>使用场景</h3>
<ul>
  <li>情绪倾诉：工作压力、生活烦恼、情感困惑</li>
  <li>心理支持：低落时需要鼓励和陪伴</li>
  <li>自我探索：了解自己的情绪模式</li>
  <li>生活建议：获得积极的视角和建议</li>
</ul>

<h3>报告内容</h3>
<p><strong>快速回复（免费）：</strong>倾听理解、情绪共鸣、简短建议</p>
<p><strong>深度对话（¥9.9）：</strong>深入交流、支持3次追问、持续陪伴</p>

<h3>重要说明</h3>
<p><strong>医疗底线：</strong>若您有严重的心理疾病症状（如严重抑郁、自杀倾向等），请务必寻求专业医疗帮助。本服务不能替代专业心理治疗。</p>
    `
  }
};

function showServiceInfo(serviceId, event) {
  event.stopPropagation();
  
  const info = serviceInfo[serviceId];
  if (!info) return;
  
  const content = document.getElementById('serviceInfoContent');
  content.innerHTML = `
    <h2>${info.title}</h2>
    <div class="service-info-body">${info.content}</div>
  `;
  
  document.getElementById('serviceInfoModal').classList.add('show');
}

function closeServiceInfo() {
  document.getElementById('serviceInfoModal').classList.remove('show');
}

// ============================================
// 其他弹窗
// ============================================

function showWechatModal() {
  document.getElementById('wechatModal').classList.add('show');
}

function closeWechatModal() {
  document.getElementById('wechatModal').classList.remove('show');
}

function showCharityModal() {
  document.getElementById('charityModal').classList.add('show');
}

function closeCharityModal() {
  document.getElementById('charityModal').classList.remove('show');
}

// ============================================
// 事件监听
// ============================================

function setupEventListeners() {
  // 点击模态框背景关闭
  const modals = document.querySelectorAll('.modal');
  modals.forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.remove('show');
      }
    });
  });
  
  // ESC键关闭模态框
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      modals.forEach(modal => modal.classList.remove('show'));
    }
  });
}

// ============================================
// 工具函数
// ============================================

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
}

function formatCurrency(amount) {
  return `¥${(amount || 0).toFixed(2)}`;
}

console.log('玄象 v4.0 已加载 | 超越版');
