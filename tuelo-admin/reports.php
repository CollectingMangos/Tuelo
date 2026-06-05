<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireAdmin();
$currentUser = getUser();

//  Summary stats 
$totalRevenue     = singleQueryDB('SELECT COALESCE(SUM(tuelo_fee),0) AS n FROM transactions WHERE status != "refunded"')['n'];
$totalTransactions = singleQueryDB('SELECT COUNT(*) AS n FROM transactions')['n'];
$completedTx      = singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE status = "completed"')['n'];
$pendingTx        = singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE status = "pending"')['n'];
$refundedTx       = singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE status = "refunded"')['n'];
$avgOrderValue    = singleQueryDB('SELECT ROUND(AVG(amount),2) AS n FROM transactions WHERE status != "refunded"')['n'] ?? 0;

//  Revenue by category 
$byCategory = queryDB(
    'SELECT c.name AS category, COUNT(t.id) AS sales,
            SUM(t.amount) AS revenue, SUM(t.tuelo_fee) AS platform_fee
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN categories c ON l.category_id = c.id
     WHERE t.status != "refunded"
     GROUP BY c.id, c.name
     ORDER BY revenue DESC'
);

//  Top sellers 
$topSellers = queryDB(
    'SELECT u.name, u.surname, u.email, u.profile_image,
            COUNT(t.id) AS sales,
            SUM(t.amount) AS total_revenue
     FROM transactions t
     JOIN users u ON t.seller_id = u.id
     WHERE t.status != "refunded"
     GROUP BY u.id
     ORDER BY total_revenue DESC
     LIMIT 10'
);

//  All transactions 
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$status  = trim($_GET['status'] ?? '');

$where  = $status ? 'WHERE t.status = ?' : '';
$params = $status ? [$status] : [];

$txTotal = singleQueryDB("SELECT COUNT(*) AS n FROM transactions t $where", $params)['n'];
$txPages = (int)ceil($txTotal / $perPage);

$transactions = queryDB(
    "SELECT t.*, l.title AS listing_title,
            b.name AS buyer_name, b.surname AS buyer_surname,
            s.name AS seller_name, s.surname AS seller_surname
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN users b ON t.buyer_id = b.id
     JOIN users s ON t.seller_id = s.id
     $where
     ORDER BY t.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$pageTitle = 'Reports';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Reports & Analytics</h1>
      <p class="admin-page-subtitle">Platform revenue, transaction history and sales breakdown.</p>
    </div>
  </div>

  <!-- Revenue stats -->
  <div class="reports-stats-grid">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#D1FAE5;">
        <ion-icon name="cash-outline" style="color:#059669;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value">R <?= number_format($totalRevenue, 2) ?></p>
        <p class="admin-stat-label">Total Platform Revenue</p>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#DBEAFE;">
        <ion-icon name="receipt-outline" style="color:#1E40AF;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= number_format($totalTransactions) ?></p>
        <p class="admin-stat-label">Total Transactions</p>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#FEF9C3;">
        <ion-icon name="trending-up-outline" style="color:#CA8A04;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value">R <?= number_format($avgOrderValue, 2) ?></p>
        <p class="admin-stat-label">Average Order Value</p>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#D1FAE5;">
        <ion-icon name="checkmark-circle-outline" style="color:#059669;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= $completedTx ?></p>
        <p class="admin-stat-label">Completed</p>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#FEF3C7;">
        <ion-icon name="time-outline" style="color:#D97706;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= $pendingTx ?></p>
        <p class="admin-stat-label">Pending</p>
      </div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#FEE2E2;">
        <ion-icon name="return-down-back-outline" style="color:#DC2626;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= $refundedTx ?></p>
        <p class="admin-stat-label">Refunded</p>
      </div>
    </div>
  </div>

  <div class="reports-tables-grid">

    <!-- Revenue by category -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">Revenue by Category</h3>
      </div>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr><th>Category</th><th>Sales</th><th>Revenue</th><th>Platform Fee</th></tr>
          </thead>
          <tbody>
            <?php if (empty($byCategory)): ?>
            <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--admin-muted);">No data yet</td></tr>
            <?php else: ?>
            <?php foreach ($byCategory as $row): ?>
            <tr>
              <td style="font-weight:500;"><?= htmlspecialchars($row['category']) ?></td>
              <td><?= $row['sales'] ?></td>
              <td style="font-weight:700;color:var(--admin-green);">R <?= number_format($row['revenue'], 2) ?></td>
              <td style="color:var(--admin-muted);">R <?= number_format($row['platform_fee'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Top sellers -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">Top Sellers</h3>
      </div>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr><th>Seller</th><th>Sales</th><th>Revenue</th></tr>
          </thead>
          <tbody>
            <?php if (empty($topSellers)): ?>
            <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--admin-muted);">No data yet</td></tr>
            <?php else: ?>
            <?php foreach ($topSellers as $seller): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <img src="<?= $seller['profile_image'] ? htmlspecialchars($seller['profile_image']) : '/tuelo-main/assets/img/default-avatar.png' ?>"
                       style="width:28px;height:28px;border-radius:50%;object-fit:cover;" />
                  <div>
                    <p style="font-weight:500;font-size:var(--admin-fs-sm);"><?= htmlspecialchars($seller['name'].' '.$seller['surname']) ?></p>
                    <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);"><?= htmlspecialchars($seller['email']) ?></p>
                  </div>
                </div>
              </td>
              <td><?= $seller['sales'] ?></td>
              <td style="font-weight:700;color:var(--admin-green);">R <?= number_format($seller['total_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- All transactions -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">All Transactions</h3>
      <form method="GET" action="/tuelo-admin/reports.php" style="display:flex;gap:8px;">
        <select name="status" class="admin-input" style="width:140px;cursor:pointer;"
                onchange="this.form.submit()">
          <option value="">All statuses</option>
          <?php foreach (['pending','paid','completed','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr><th>Item</th><th>Buyer</th><th>Seller</th><th>Amount</th><th>Fee</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php if (empty($transactions)): ?>
          <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--admin-muted);">No transactions found.</td></tr>
          <?php else: ?>
          <?php foreach ($transactions as $tx): ?>
          <tr>
            <td style="font-weight:500;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($tx['listing_title']) ?>
            </td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($tx['buyer_name'].' '.$tx['buyer_surname']) ?></td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($tx['seller_name'].' '.$tx['seller_surname']) ?></td>
            <td style="font-weight:700;color:var(--admin-green);">R <?= number_format($tx['amount'],2) ?></td>
            <td style="color:var(--admin-muted);">R <?= number_format($tx['tuelo_fee'],2) ?></td>
            <td>
              <span class="admin-badge <?= match($tx['status']) {
                  'completed' => 'admin-badge-green',
                  'pending'   => 'admin-badge-yellow',
                  'paid'      => 'admin-badge-blue',
                  'refunded'  => 'admin-badge-red',
                  default     => 'admin-badge-gray'
              } ?>"><?= ucfirst($tx['status']) ?></span>
            </td>
            <td style="color:var(--admin-muted);white-space:nowrap;"><?= date('d M Y', strtotime($tx['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($txPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
      <?php if ($page > 1): ?>
      <a href="?status=<?= $status ?>&page=<?= $page-1 ?>" class="admin-btn admin-btn-secondary" style="padding:6px 12px;">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($txPages,$page+2); $i++): ?>
      <a href="?status=<?= $status ?>&page=<?= $i ?>"
         class="admin-btn <?= $i === $page ? 'admin-btn-primary' : 'admin-btn-secondary' ?>"
         style="padding:6px 12px;min-width:36px;justify-content:center;"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $txPages): ?>
      <a href="?status=<?= $status ?>&page=<?= $page+1 ?>" class="admin-btn admin-btn-secondary" style="padding:6px 12px;">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>