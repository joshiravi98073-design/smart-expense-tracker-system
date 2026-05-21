<div class="modal-overlay" id="budgetModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">🎯 Set Monthly Budget</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form onsubmit="saveBudget(event)">
        <p style="margin-bottom:18px;color:var(--text-muted)">Set a spending limit to track your budget and get alerts when you're close to exceeding it.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Month</label>
            <!-- BUG FIX: Pre-select current month in the modal (was always showing January) -->
            <select id="budgetMonth" class="form-control form-select">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= (int)date('n') === $m ? 'selected' : '' ?>>
                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
              </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Year</label>
            <input type="number" id="budgetYear" class="form-control" value="<?= date('Y') ?>" min="2020" max="2099">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget Amount *</label>
          <div class="input-group"><span class="input-icon">💰</span>
            <input type="number" id="budgetAmount" class="form-control" placeholder="e.g. 25000" step="0.01" min="1" required>
          </div>
        </div>
        <div class="alert alert-info">⚠️ You'll get a warning alert when expenses reach 75% of your budget, and a danger alert at 100%.</div>
        <button type="submit" class="btn btn-primary btn-block">💾 Save Budget</button>
      </form>
    </div>
  </div>
</div>
