<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireAdmin();
$currentUser = getUser();

//  Handle deactivate/reactivate 
$message = '';
$msgType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    // Prevent acting on own account
    if ($id === $currentUser['id']) {
        $message = 'You cannot modify your own account.';
        $msgType = 'danger';
    } else {
        if ($action === 'deactivate') {
            updateDB('UPDATE users SET is_verified = -1 WHERE id = ?', [$id]);
            $message = 'User deactivated.';
            $msgType = 'danger';
        } elseif ($action === 'activate') {
            updateDB('UPDATE users SET is_verified = 1 WHERE id = ?', [$id]);
            $message = 'User activated.';
            $msgType = 'success';
        }
    }
    header('Location: /tuelo-admin/users.php?msg=' . urlencode($message) . '&type=' . $msgType);
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msgType = $_GET['type'] ?? 'success';
}

//  Filters 
$search  = trim($_GET['q']      ?? '');
$role    = trim($_GET['role']   ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(u.name LIKE ? OR u.surname LIKE ? OR u.email LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}
if ($role) {
    $where[]  = 'r.name = ?';
    $params[] = $role;
}
if ($status === 'active') {
    $where[] = 'u.is_verified = 1';
} elseif ($status === 'deactivated') {
    $where[] = 'u.is_verified = -1';
} elseif ($status === 'pending') {
    $where[] = 'u.is_verified = 0';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = singleQueryDB(
    "SELECT COUNT(*) AS n FROM users u JOIN roles r ON u.role_id = r.id $whereSQL",
    $params
)['n'];

$totalPages = (int)ceil($total / $perPage);

$users = queryDB(
    "SELECT u.*, r.name AS role_name FROM users u
     JOIN roles r ON u.role_id = r.id
     $whereSQL
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$roles = queryDB('SELECT * FROM roles ORDER BY id ASC');

$pageTitle = 'Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Users</h1>
      <p class="admin-page-subtitle">View, create, edit and manage all platform users.</p>
    </div>
    <a href="/tuelo-admin/user-create.php" class="admin-btn admin-btn-primary">
      <ion-icon name="person-add-outline"></ion-icon>
      Add User
    </a>
  </div>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="admin-filter-bar">
    <form method="GET" action="/tuelo-admin/users.php" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
      <input type="text" name="q" class="admin-input" placeholder="Search name or email..."
             value="<?= htmlspecialchars($search) ?>" style="max-width:260px;" />
      <select name="role" class="admin-input" style="width:140px;cursor:pointer;">
        <option value="">All roles</option>
        <?php foreach ($roles as $r): ?>
        <option value="<?= $r['name'] ?>" <?= $role === $r['name'] ? 'selected' : '' ?>>
          <?= ucfirst($r['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="admin-input" style="width:140px;cursor:pointer;">
        <option value="">All statuses</option>
        <option value="active"      <?= $status === 'active'      ? 'selected' : '' ?>>Active</option>
        <option value="pending"     <?= $status === 'pending'     ? 'selected' : '' ?>>Pending</option>
        <option value="deactivated" <?= $status === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
      </select>
      <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
      <?php if ($search || $role || $status): ?>
      <a href="/tuelo-admin/users.php" class="admin-btn admin-btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
    <p style="font-size:var(--admin-fs-sm);color:var(--admin-muted);">
      <?= number_format($total) ?> user<?= $total != 1 ? 's' : '' ?>
    </p>
  </div>

  <!-- Table -->
  <div class="admin-card">
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Rating</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--admin-muted);">
              No users found.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= $u['profile_image'] ? htmlspecialchars($u['profile_image']) : '/tuelo-main/assets/img/default-avatar.png' ?>"
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;" />
                <span style="font-weight:600;color:var(--admin-text);">
                  <?= htmlspecialchars($u['name'] . ' ' . $u['surname']) ?>
                </span>
              </div>
            </td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($u['email']) ?></td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($u['phone_number'] ?? '—') ?></td>
            <td>
              <span class="admin-badge <?= match($u['role_name']) {
                  'superuser' => 'admin-badge-purple',
                  'admin'     => 'admin-badge-blue',
                  'seller'    => 'admin-badge-green',
                  default     => 'admin-badge-gray'
              } ?>">
                <?= ucfirst($u['role_name']) ?>
              </span>
            </td>
            <td>
              <span class="admin-badge <?= $u['is_verified'] == 1 ? 'admin-badge-green' : ($u['is_verified'] == -1 ? 'admin-badge-red' : 'admin-badge-yellow') ?>">
                <?= $u['is_verified'] == 1 ? 'Active' : ($u['is_verified'] == -1 ? 'Deactivated' : 'Pending') ?>
              </span>
            </td>
            <td style="color:var(--admin-muted);">
              <?= number_format($u['rating_average'], 1) ?> / 5
            </td>
            <td style="color:var(--admin-muted);white-space:nowrap;">
              <?= date('d M Y', strtotime($u['created_at'])) ?>
            </td>
            <td>
              <div style="display:flex;gap:5px;">
                <a href="/tuelo-admin/user-edit.php?id=<?= $u['id'] ?>"
                   class="admin-btn-icon" title="Edit">
                  <ion-icon name="pencil-outline"></ion-icon>
                </a>
                <?php if ($u['id'] !== $currentUser['id']): ?>
                  <?php if ($u['is_verified'] != -1): ?>
                  <a href="/tuelo-admin/users.php?action=deactivate&id=<?= $u['id'] ?>"
                     class="admin-btn-icon admin-btn-icon-danger" title="Deactivate"
                     onclick="return confirm('Deactivate <?= htmlspecialchars($u['name']) ?>?')">
                    <ion-icon name="ban-outline"></ion-icon>
                  </a>
                  <?php else: ?>
                  <a href="/tuelo-admin/users.php?action=activate&id=<?= $u['id'] ?>"
                     class="admin-btn-icon" title="Activate"
                     style="background:#D1FAE5;border-color:#6EE7B7;"
                     onclick="return confirm('Reactivate <?= htmlspecialchars($u['name']) ?>?')">
                    <ion-icon name="checkmark-circle-outline" style="color:#059669;"></ion-icon>
                  </a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
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
      <a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $page-1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
      <a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $i ?>"
         class="admin-btn <?= $i === $page ? 'admin-btn-primary' : 'admin-btn-secondary' ?>"
         style="padding:6px 12px;min-width:36px;justify-content:center;"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $page+1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>