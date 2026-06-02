<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireSuperuser();
$currentUser = getUser();

//  Filters 
$search  = trim($_GET['q']      ?? '');
$adminId = (int)($_GET['admin'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(l.action LIKE ? OR l.category_type LIKE ? OR u.name LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}
if ($adminId) {
    $where[]  = 'l.admin_id = ?';
    $params[] = $adminId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = singleQueryDB(
    "SELECT COUNT(*) AS n FROM logs l
     LEFT JOIN users u ON l.admin_id = u.id
     $whereSQL", $params
)['n'];

$totalPages = (int)ceil($total / $perPage);

$logs = queryDB(
    "SELECT l.*, u.name AS admin_name, u.surname AS admin_surname,
            u.profile_image AS admin_avatar, u.email AS admin_email
     FROM logs l
     LEFT JOIN users u ON l.admin_id = u.id
     $whereSQL
     ORDER BY l.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

//  Admins for filter dropdown 
$admins = queryDB(
    'SELECT u.id, u.name, u.surname FROM users u
     JOIN roles r ON u.role_id = r.id
     WHERE r.name IN ("admin","superuser")
     ORDER BY u.name ASC'
);

$pageTitle = 'Audit Logs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Audit Logs</h1>
      <p class="admin-page-subtitle">Complete record of all admin actions on the platform. Superuser only.</p>
    </div>
    <div style="background:#F3E8FF;border:1px solid #DDD6FE;border-radius:var(--admin-radius-sm);
                padding:8px 14px;display:flex;align-items:center;gap:6px;">
      <ion-icon name="shield-outline" style="color:#9333EA;font-size:16px;"></ion-icon>
      <span style="font-size:var(--admin-fs-xs);font-weight:600;color:#7C3AED;">
        <?= number_format($total) ?> log entries
      </span>
    </div>
  </div>

  <!-- Filters -->
  <div class="admin-filter-bar">
    <form method="GET" action="/tuelo-admin/logs.php" style="display:flex;gap:10px;flex-wrap:wrap;">
      <input type="text" name="q" class="admin-input" placeholder="Search action or category..."
             value="<?= htmlspecialchars($search) ?>" style="max-width:280px;" />
      <select name="admin" class="admin-input" style="width:200px;cursor:pointer;">
        <option value="">All admins</option>
        <?php foreach ($admins as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $adminId == $a['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($a['name'].' '.$a['surname']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
      <?php if ($search || $adminId): ?>
      <a href="/tuelo-admin/logs.php" class="admin-btn admin-btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Logs table -->
  <div class="admin-card">
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Category</th>
            <th>Record ID</th>
            <th>IP Address</th>
            <th>Date & Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:40px;color:var(--admin-muted);">
              No log entries found.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td style="color:var(--admin-muted);font-size:var(--admin-fs-xs);">#<?= $log['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <img src="<?= $log['admin_avatar'] ? htmlspecialchars($log['admin_avatar']) : '/tuelo-main/assets/img/default-avatar.png' ?>"
                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;" />
                <div>
                  <p style="font-weight:500;font-size:var(--admin-fs-sm);color:var(--admin-text);">
                    <?= $log['admin_name'] ? htmlspecialchars($log['admin_name'].' '.$log['admin_surname']) : 'System' ?>
                  </p>
                  <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
                    <?= $log['admin_email'] ? htmlspecialchars($log['admin_email']) : '' ?>
                  </p>
                </div>
              </div>
            </td>
            <td>
              <span style="font-family:monospace;font-size:var(--admin-fs-xs);
                           background:var(--admin-bg);border:1px solid var(--admin-border);
                           padding:2px 8px;border-radius:4px;color:var(--admin-text);">
                <?= htmlspecialchars($log['action']) ?>
              </span>
            </td>
            <td style="color:var(--admin-muted);">
              <?= htmlspecialchars($log['category_type'] ?? '—') ?>
            </td>
            <td style="color:var(--admin-muted);">
              <?= $log['category_pk'] ? '#' . $log['category_pk'] : '—' ?>
            </td>
            <td style="font-family:monospace;font-size:var(--admin-fs-xs);color:var(--admin-muted);">
              <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
            </td>
            <td style="color:var(--admin-muted);white-space:nowrap;font-size:var(--admin-fs-xs);">
              <?= date('d M Y, H:i', strtotime($log['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
      <?php if ($page > 1): ?>
      <a href="?q=<?= urlencode($search) ?>&admin=<?= $adminId ?>&page=<?= $page-1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
      <a href="?q=<?= urlencode($search) ?>&admin=<?= $adminId ?>&page=<?= $i ?>"
         class="admin-btn <?= $i === $page ? 'admin-btn-primary' : 'admin-btn-secondary' ?>"
         style="padding:6px 12px;min-width:36px;justify-content:center;"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?q=<?= urlencode($search) ?>&admin=<?= $adminId ?>&page=<?= $page+1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>