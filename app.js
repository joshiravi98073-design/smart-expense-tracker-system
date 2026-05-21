/* ExpenseTracker Pro - Main JS */
// BUG FIX: Removed duplicate theme init from bottom DOMContentLoaded;
// theme is already applied via inline script in pageHead() to prevent flash.
(function initTheme(){
  const s = localStorage.getItem('et_theme') || document.documentElement.getAttribute('data-theme') || 'light';
  document.documentElement.setAttribute('data-theme', s);
})();

function toggleTheme(){
  const c = document.documentElement.getAttribute('data-theme');
  const n = c === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', n);
  localStorage.setItem('et_theme', n);
  // Sync cookie so PHP layout reads correct theme on next page load
  document.cookie = 'et_theme=' + n + ';path=/;max-age=' + (60*60*24*365);
}

function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarBackdrop').classList.toggle('show');
}

document.addEventListener('click', e => {
  const b = document.getElementById('sidebarBackdrop');
  if (b && b.classList.contains('show') && e.target === b) {
    document.getElementById('sidebar').classList.remove('open');
    b.classList.remove('show');
  }
});

function showToast(msg, type = 'success', dur = 3500){
  const icons = {success:'✅', error:'❌', warning:'⚠️', info:'ℹ️'};
  let c = document.getElementById('toastContainer');
  if (!c){ c = document.createElement('div'); c.id = 'toastContainer'; c.className = 'toast-container'; document.body.appendChild(c); }
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type]||'💬'}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(100px)'; t.style.transition='all .3s'; setTimeout(()=>t.remove(),300); }, dur);
}

function openModal(id){ const m=document.getElementById(id); if(m){ m.classList.add('open'); document.body.style.overflow='hidden'; } }
function closeModal(id){ const m=document.getElementById(id); if(m){ m.classList.remove('open'); document.body.style.overflow=''; } }

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')){ e.target.classList.remove('open'); document.body.style.overflow=''; }
  if (e.target.classList.contains('modal-close')){ const o=e.target.closest('.modal-overlay'); if(o){ o.classList.remove('open'); document.body.style.overflow=''; } }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m=>{ m.classList.remove('open'); document.body.style.overflow=''; });
});

// BUG FIX: apiRequest path detection — works from any subdirectory (user/, admin/, root).
// Instead of hardcoding 'api/transactions.php', we detect the current depth.
function getApiBase(){
  const parts = window.location.pathname.split('/').filter(Boolean);
  // If last segment is a .php file, go up one level
  const lastPart = parts[parts.length - 1] || '';
  if (lastPart.endsWith('.php') && parts.length > 1) {
    return '../api/';
  }
  return 'api/';
}

async function apiRequest(url, data = {}, method = 'POST'){
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  try {
    const r = await fetch(url, { method, body: method !== 'GET' ? fd : undefined });
    // BUG FIX: Guard against non-JSON error responses (e.g. PHP fatal errors)
    const text = await r.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('Non-JSON response:', text);
      return { success: false, message: 'Server error. Check console.' };
    }
  } catch(e) {
    return { success: false, message: 'Network error' };
  }
}

function openAddTransaction(type = 'expense'){
  openModal('txModal');
  setTransactionType(type);
  document.getElementById('txForm').reset();
  document.getElementById('txId').value = '';
  document.getElementById('txModalTitle').textContent = 'Add Transaction';
  const rp = document.getElementById('receiptPreview');
  if (rp) rp.style.display = 'none';
  document.getElementById('txDate').value = new Date().toISOString().split('T')[0];
  loadCategories(type);
}

function setTransactionType(type){
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  const b = document.querySelector('.type-btn.' + type);
  if (b) b.classList.add('active');
  document.getElementById('txType').value = type;
  loadCategories(type);
}

async function loadCategories(type){
  const sel = document.getElementById('txCategory');
  const base = getApiBase();
  const res = await apiRequest(base + 'categories.php', { action:'list', type }, 'POST');
  if (res.success){
    sel.innerHTML = '<option value="">Select Category</option>';
    res.data.forEach(c => {
      const o = document.createElement('option');
      o.value = c.id; o.textContent = c.icon + ' ' + c.name;
      sel.appendChild(o);
    });
  }
}

async function submitTransaction(e){
  e.preventDefault();
  const form = document.getElementById('txForm');
  const btn  = form.querySelector('[type=submit]');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving...'; btn.disabled = true;
  const fd = new FormData();
  const fields = {
    action:      document.getElementById('txId').value ? 'update' : 'create',
    id:          document.getElementById('txId').value,
    type:        document.getElementById('txType').value,
    amount:      document.getElementById('txAmount').value,
    category_id: document.getElementById('txCategory').value,
    description: document.getElementById('txDesc').value,
    date:        document.getElementById('txDate').value,
    currency:    document.getElementById('txCurrency').value,
  };
  Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
  const rf = document.getElementById('txReceipt');
  if (rf && rf.files[0]) fd.append('receipt', rf.files[0]);
  try {
    const base = getApiBase();
    const r = await fetch(base + 'transactions.php', { method:'POST', body:fd });
    const text = await r.text();
    let j;
    try { j = JSON.parse(text); } catch { j = { success: false, message: 'Server error. Check PHP logs.' }; }
    if (j.success){
      showToast(j.message || 'Transaction saved!', 'success');
      closeModal('txModal');
      refreshDashboard();
      refreshTransactions();
    } else {
      showToast(j.message || 'Error saving transaction', 'error');
    }
  } catch(err) { showToast('Network error', 'error'); }
  btn.innerHTML = orig; btn.disabled = false;
}

async function editTransaction(id){
  const base = getApiBase();
  const res = await apiRequest(base + 'transactions.php', { action:'get', id }, 'POST');
  if (!res.success){ showToast('Could not load transaction', 'error'); return; }
  const tx = res.data;
  document.getElementById('txId').value    = tx.id;
  document.getElementById('txType').value  = tx.type;
  document.getElementById('txAmount').value = tx.amount;
  document.getElementById('txDesc').value  = tx.description || '';
  document.getElementById('txDate').value  = tx.date;
  const curr = document.getElementById('txCurrency');
  if (curr) curr.value = tx.currency || 'INR';
  document.getElementById('txModalTitle').textContent = 'Edit Transaction';
  setTransactionType(tx.type);
  setTimeout(() => { const s = document.getElementById('txCategory'); if(s) s.value = tx.category_id; }, 350);
  const rp = document.getElementById('receiptPreview');
  const ri = document.getElementById('receiptImg');
  // BUG FIX: Build receipt URL relative to current page depth
  const uploadsBase = getApiBase().replace('api/', '') + 'assets/uploads/';
  if (rp && ri && tx.receipt){ rp.style.display='block'; ri.src = uploadsBase + tx.receipt; }
  openModal('txModal');
}

async function deleteTransaction(id){
  if (!confirm('Delete this transaction?')) return;
  const base = getApiBase();
  const res = await apiRequest(base + 'transactions.php', { action:'delete', id }, 'POST');
  if (res.success){ showToast('Transaction deleted', 'success'); refreshDashboard(); refreshTransactions(); }
  else showToast(res.message || 'Error', 'error');
}

function previewReceipt(input){
  if (input.files && input.files[0]){
    if (input.files[0].size > 5*1024*1024){ showToast('File too large (max 5MB)', 'warning'); return; }
    const r = new FileReader();
    r.onload = e => { document.getElementById('receiptPreview').style.display='block'; document.getElementById('receiptImg').src=e.target.result; };
    r.readAsDataURL(input.files[0]);
  }
}

function formatCurrency(amount, currency = 'INR'){
  const s = { INR:'₹', USD:'$', EUR:'€', GBP:'£' };
  return (s[currency] || currency + ' ') + parseFloat(amount||0).toLocaleString('en-IN', { minimumFractionDigits:2, maximumFractionDigits:2 });
}

function setText(id, val){ const el = document.getElementById(id); if(el) el.textContent = val; }

async function refreshDashboard(){
  const base = getApiBase();
  const res = await apiRequest(base + 'dashboard.php', { action:'stats' }, 'POST');
  if (!res.success) return;
  const d = res.data;
  setText('statBalance', formatCurrency(d.balance, d.currency));
  setText('statIncome',  formatCurrency(d.income,  d.currency));
  setText('statExpense', formatCurrency(d.expense, d.currency));
  setText('statBudget',  formatCurrency(d.budget_remaining, d.currency));
  if (d.budget > 0){
    const pct = Math.min((d.expense / d.budget) * 100, 100);
    const bar = document.getElementById('budgetBar');
    if (bar){ bar.style.width = pct + '%'; bar.className = 'budget-bar-fill' + (pct >= 100 ? ' danger' : pct >= 75 ? ' warning' : ''); }
    setText('budgetPct',   Math.round(pct) + '%');
    setText('budgetLabel', formatCurrency(d.expense, d.currency) + ' of ' + formatCurrency(d.budget, d.currency));
    if (d.expense >= d.budget) showToast('⚠️ Budget exceeded this month!', 'warning', 6000);
  }
  if (d.category_data) updatePieChart(d.category_data);
  if (d.monthly_data)  updateBarChart(d.monthly_data);
  updateInsights(d);
}

async function refreshTransactions(page = 1){
  const f = gatherFilters(); f.action = 'list'; f.page = page;
  const base = getApiBase();
  const res = await apiRequest(base + 'transactions.php', f, 'POST');
  if (!res.success) return;
  renderTransactions(res.data, res.pagination);
}

function gatherFilters(){
  return {
    search:      getVal('filterSearch'),
    date_from:   getVal('filterDateFrom'),
    date_to:     getVal('filterDateTo'),
    category_id: getVal('filterCategory'),
    type:        getVal('filterType'),
  };
}

function getVal(id){ const el = document.getElementById(id); return el ? el.value : ''; }
function escHtml(str){ const d = document.createElement('div'); d.appendChild(document.createTextNode(String(str||''))); return d.innerHTML; }

function renderTransactions(txns, pagination){
  const tbody = document.getElementById('txTableBody');
  if (!tbody) return;
  if (!txns || !txns.length){
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><span class="emoji">💸</span><h3>No transactions found</h3><p>Add your first transaction to get started</p></div></td></tr>';
    return;
  }
  // BUG FIX: receipt URL uses correct relative path
  const uploadsBase = getApiBase().replace('api/', '') + 'assets/uploads/';
  tbody.innerHTML = txns.map(tx => `
    <tr class="fade-in">
      <td>${escHtml(tx.date)}</td>
      <td><span class="tx-type-badge tx-${tx.type}">${tx.type==='income'?'↑':'↓'} ${tx.type}</span></td>
      <td><span class="amount-cell amount-${tx.type}">${tx.type==='income'?'+':'-'}${formatCurrency(tx.amount, tx.currency)}</span></td>
      <td><span class="category-pill">${escHtml(tx.cat_icon||'📦')} ${escHtml(tx.cat_name||'Other')}</span></td>
      <td>${escHtml(tx.description||'—')}</td>
      ${tx.show_user ? `<td>${escHtml(tx.user_name||'')}</td>` : ''}
      <td><div class="action-btns">
        ${tx.receipt ? `<a href="${uploadsBase}${escHtml(tx.receipt)}" target="_blank" class="btn btn-ghost btn-sm">🧾</a>` : ''}
        <button onclick="editTransaction(${tx.id})" class="btn btn-ghost btn-sm">✏️</button>
        <button onclick="deleteTransaction(${tx.id})" class="btn btn-danger btn-sm">🗑️</button>
      </div></td>
    </tr>`).join('');
  renderPagination(pagination);
}

function renderPagination(p){
  const el = document.getElementById('txPagination');
  if (!el || !p || p.total_pages <= 1){ if(el) el.innerHTML=''; return; }
  let h = '<div style="display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap">';
  // BUG FIX: Limit pagination buttons to avoid rendering 1000+ buttons on large datasets
  const maxBtns = 10;
  const half    = Math.floor(maxBtns / 2);
  let start = Math.max(1, p.page - half);
  let end   = Math.min(p.total_pages, start + maxBtns - 1);
  if (end - start < maxBtns - 1) start = Math.max(1, end - maxBtns + 1);
  if (start > 1)              h += `<button onclick="refreshTransactions(1)" class="btn btn-sm btn-ghost">« First</button>`;
  for (let i = start; i <= end; i++) h += `<button onclick="refreshTransactions(${i})" class="btn btn-sm ${i===p.page?'btn-primary':'btn-ghost'}">${i}</button>`;
  if (end < p.total_pages)    h += `<button onclick="refreshTransactions(${p.total_pages})" class="btn btn-sm btn-ghost">Last »</button>`;
  h += `<span style="align-self:center;color:var(--text-muted);font-size:.8rem">Page ${p.page}/${p.total_pages} · ${p.total} records</span></div>`;
  el.innerHTML = h;
}

let searchDebounce;
function onSearchInput(){ clearTimeout(searchDebounce); searchDebounce = setTimeout(refreshTransactions, 350); }

async function saveBudget(e){
  e.preventDefault();
  const base = getApiBase();
  const res = await apiRequest(base + 'budget.php', {
    action: 'set',
    amount: document.getElementById('budgetAmount').value,
    month:  document.getElementById('budgetMonth').value,
    year:   document.getElementById('budgetYear').value,
  }, 'POST');
  if (res.success){ showToast('Budget saved!', 'success'); closeModal('budgetModal'); refreshDashboard(); }
  else showToast(res.message || 'Error', 'error');
}

function exportCSV(){
  const f = gatherFilters();
  const base = getApiBase();
  window.location.href = base + 'export.php?' + new URLSearchParams({...f, action:'export_csv'});
}
function exportPDF(){
  const f = gatherFilters();
  const base = getApiBase();
  window.open(base + 'export.php?' + new URLSearchParams({...f, action:'export_pdf'}), '_blank');
}

async function shareWhatsApp(){
  const base = getApiBase();
  const res = await apiRequest(base + 'dashboard.php', { action:'summary' }, 'POST');
  if (!res.success){ showToast('Could not generate summary', 'error'); return; }
  const d   = res.data;
  const msg = `💰 *Expense Tracker Summary*\n\n📅 Month: ${d.month_name}\n💵 Income: ${formatCurrency(d.income, d.currency)}\n💸 Expenses: ${formatCurrency(d.expense, d.currency)}\n🏦 Balance: ${formatCurrency(d.balance, d.currency)}\n`
            + (d.budget > 0 ? `🎯 Budget: ${formatCurrency(d.budget, d.currency)} (${d.budget_pct}% used)\n` : '')
            + '\n_Shared via ExpenseTracker Pro_';
  window.open('https://wa.me/?text=' + encodeURIComponent(msg), '_blank');
}

let pieChart, barChart;
function initCharts(){
  const pieCtx = document.getElementById('pieChart');
  const barCtx = document.getElementById('barChart');
  if (!pieCtx || !barCtx || typeof Chart === 'undefined') return;
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const grid   = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const tc     = isDark ? '#94a3b8' : '#475569';
  pieChart = new Chart(pieCtx, {
    type: 'doughnut',
    data: { labels:[], datasets:[{data:[], backgroundColor:[]}] },
    options: { responsive:true, maintainAspectRatio:false,
      plugins: { legend:{ position:'right', labels:{color:tc,font:{family:"'DM Sans'"},padding:12} },
                 tooltip:{ callbacks:{ label: c => ' '+c.label+': '+formatCurrency(c.parsed) } } },
      cutout:'65%' }
  });
  barChart = new Chart(barCtx, {
    type: 'bar',
    data: { labels:[], datasets:[] },
    options: { responsive:true, maintainAspectRatio:false,
      plugins: { legend:{ labels:{color:tc,font:{family:"'DM Sans'"}} } },
      scales: { x:{grid:{color:grid},ticks:{color:tc}},
                y:{grid:{color:grid},ticks:{color:tc,callback:v=>'₹'+v.toLocaleString('en-IN')}} } }
  });
}

function updatePieChart(data){
  if (!pieChart) return;
  const colors = ['#6366f1','#10b981','#ef4444','#f59e0b','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#a855f7','#84cc16','#94a3b8'];
  pieChart.data.labels = data.map(d => d.name);
  pieChart.data.datasets[0].data  = data.map(d => d.total);
  pieChart.data.datasets[0].backgroundColor = colors.slice(0, data.length);
  pieChart.update('active');
}

function updateBarChart(data){
  if (!barChart) return;
  barChart.data.labels = data.map(d => d.month);
  barChart.data.datasets = [
    { label:'Income',  data:data.map(d=>d.income),  backgroundColor:'rgba(16,185,129,0.7)', borderRadius:6 },
    { label:'Expense', data:data.map(d=>d.expense), backgroundColor:'rgba(239,68,68,0.7)',  borderRadius:6 },
  ];
  barChart.update('active');
}

function toggleNotifPanel(){ const p = document.getElementById('notifPanel'); if(p) p.classList.toggle('open'); }
document.addEventListener('click', e => {
  const p = document.getElementById('notifPanel');
  const b = document.getElementById('notifBtn');
  if (p && !p.contains(e.target) && b && !b.contains(e.target)) p.classList.remove('open');
});

async function loadNotifications(){
  const base = getApiBase();
  const res  = await apiRequest(base + 'notifications.php', { action:'list' }, 'POST');
  if (!res.success) return;
  const panel  = document.getElementById('notifList');
  const dot    = document.querySelector('.notif-dot');
  const unread = res.data.filter(n => !n.is_read).length;
  if (dot) dot.style.display = unread ? 'block' : 'none';
  setText('notifCount', unread > 0 ? unread + ' new' : 'All read');
  if (!panel) return;
  if (!res.data.length){ panel.innerHTML = '<div class="notif-item"><span>No notifications</span></div>'; return; }
  panel.innerHTML = res.data.slice(0, 8).map(n =>
    `<div class="notif-item ${n.is_read ? '' : 'unread'}">
       <div class="notif-dot-item"></div>
       <div><div>${escHtml(n.message)}</div>
       <small style="color:var(--text-muted)">${escHtml(n.created_at)}</small></div>
     </div>`).join('');
}

async function markAllRead(){
  const base = getApiBase();
  await apiRequest(base + 'notifications.php', { action:'mark_all_read' }, 'POST');
  loadNotifications();
}

function updateInsights(data){
  const el = document.getElementById('insightText'); if (!el) return;
  const msgs = [];
  if (data.income > 0) msgs.push('💡 You are saving ' + ((data.balance / data.income) * 100).toFixed(1) + '% of your income this month.');
  if (data.top_category) msgs.push('📊 Highest spend: "' + data.top_category + '" — review your spending here.');
  if (data.budget > 0){
    const rem = data.budget - data.expense;
    msgs.push(rem > 0
      ? '🎯 ' + formatCurrency(rem, data.currency) + ' left in budget.'
      : '⚠️ Budget exceeded by ' + formatCurrency(-rem, data.currency) + '.');
  }
  el.textContent = msgs[0] || '📈 Keep tracking expenses to see smart insights!';
}

async function deleteUser(id){
  if (!confirm('Delete this user and ALL their data? Cannot be undone.')) return;
  const base = getApiBase();
  const res  = await apiRequest(base + 'admin.php', { action:'delete_user', id }, 'POST');
  if (res.success){ showToast('User deleted', 'success'); location.reload(); }
  else showToast(res.message || 'Error', 'error');
}

async function toggleUserRole(id, currentRole){
  const nr = currentRole === 'admin' ? 'user' : 'admin';
  if (!confirm('Change role to "' + nr + '"?')) return;
  const base = getApiBase();
  const res  = await apiRequest(base + 'admin.php', { action:'change_role', id, role:nr }, 'POST');
  if (res.success){ showToast('Role updated', 'success'); location.reload(); }
  else showToast(res.message || 'Error', 'error');
}

async function saveCategory(e){
  e.preventDefault();
  const base = getApiBase();
  const res  = await apiRequest(base + 'categories.php', {
    action: document.getElementById('catId').value ? 'update' : 'create',
    id:     document.getElementById('catId').value,
    name:   document.getElementById('catName').value,
    icon:   document.getElementById('catIcon').value,
    color:  document.getElementById('catColor').value,
    type:   document.getElementById('catType').value,
  }, 'POST');
  if (res.success){ showToast('Category saved!', 'success'); closeModal('catModal'); location.reload(); }
  else showToast(res.message || 'Error', 'error');
}

async function deleteCategory(id){
  if (!confirm('Delete this category?')) return;
  const base = getApiBase();
  const res  = await apiRequest(base + 'categories.php', { action:'delete', id }, 'POST');
  if (res.success){ showToast('Deleted', 'success'); location.reload(); }
  else showToast(res.message || 'Error', 'error');
}

async function loadTotals(){
  const year = document.getElementById('totalsYear')?.value || new Date().getFullYear();
  const base = getApiBase();
  const res  = await apiRequest(base + 'dashboard.php', { action:'totals', year }, 'POST');
  if (!res.success) return;
  const d = res.data;
  setText('totalMonthIncome',  formatCurrency(d.month_income,  d.currency));
  setText('totalMonthExpense', formatCurrency(d.month_expense, d.currency));
  setText('totalMonthBalance', formatCurrency(d.month_balance, d.currency));
  setText('totalYearIncome',   formatCurrency(d.year_income,   d.currency));
  setText('totalYearExpense',  formatCurrency(d.year_expense,  d.currency));
  setText('totalYearBalance',  formatCurrency(d.year_balance,  d.currency));
}

document.addEventListener('DOMContentLoaded', function(){
  if (document.getElementById('pieChart'))          initCharts();
  if (document.getElementById('statBalance'))       refreshDashboard();
  if (document.getElementById('txTableBody'))       refreshTransactions();
  loadNotifications();
  if (document.getElementById('totalMonthIncome'))  loadTotals();

  ['filterSearch','filterDateFrom','filterDateTo','filterCategory','filterType'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', refreshTransactions);
  });

  // BUG FIX: budgetMonth/budgetYear init moved here (was redundant with PHP pre-selection in budget_modal.php)
  // Only set if budget modal IS present on the page
  const bm = document.getElementById('budgetMonth');
  const by = document.getElementById('budgetYear');
  if (bm) bm.value = new Date().getMonth() + 1;
  if (by) by.value = new Date().getFullYear();
});
