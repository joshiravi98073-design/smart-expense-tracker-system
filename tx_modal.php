<?php
$catStmt=$GLOBALS['catStmt']??null;
$db2=getDB();$uid2=(int)$_SESSION['user_id'];
$cats=$db2->prepare("SELECT * FROM categories WHERE user_id IS NULL OR user_id=? ORDER BY name");
$cats->execute([$uid2]);$allCats=$cats->fetchAll();
?>
<div class="modal-overlay" id="txModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="txModalTitle">Add Transaction</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="txForm" onsubmit="submitTransaction(event)">
        <input type="hidden" id="txId">
        <input type="hidden" id="txType" value="expense">
        <div class="type-toggle">
          <button type="button" class="type-btn expense active" onclick="setTransactionType('expense')">📉 Expense</button>
          <button type="button" class="type-btn income" onclick="setTransactionType('income')">📈 Income</button>
        </div>
        <div class="form-group">
          <label class="form-label">Amount *</label>
          <div class="input-group"><span class="input-icon">💰</span>
            <input type="number" id="txAmount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select id="txCategory" class="form-control form-select"><option value="">Select Category</option></select>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select id="txCurrency" class="form-control form-select">
              <option value="INR">₹ INR</option><option value="USD">$ USD</option>
              <option value="EUR">€ EUR</option><option value="GBP">£ GBP</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Date *</label>
          <input type="date" id="txDate" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" id="txDesc" class="form-control" placeholder="What was this for?">
        </div>
        <div class="form-group">
          <label class="form-label">Receipt Image (optional)</label>
          <div class="upload-area" onclick="document.getElementById('txReceipt').click()">
            📎 Click to upload receipt (JPG, PNG, PDF — max 5MB)
            <input type="file" id="txReceipt" accept=".jpg,.jpeg,.png,.pdf,.gif" style="display:none" onchange="previewReceipt(this)">
          </div>
          <div id="receiptPreview" style="display:none" class="upload-preview">
            <img id="receiptImg" src="" alt="Receipt preview">
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">💾 Save Transaction</button>
      </form>
    </div>
  </div>
</div>
