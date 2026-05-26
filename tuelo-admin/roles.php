<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireSuperuser();
$currentUser = getUser();

$message = '';
$msgType = '';

//  Handle role assignment for a user 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_role'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $roleId = (int)($_POST['role_id'] ?? 0);

    if ($userId && $roleId) {
        if ($userId === $currentUser['id']) {
            $message = 'You cannot change your own role.';
            $msgType = 'danger';
        } else {
            updateDB('UPDATE users SET role_id = ?, updated_at = NOW() WHERE id = ?', [$roleId, $userId]);
            $message = 'Role updated successfully.';
            $msgType = 'success';
        }
    }
}

//  Fetch all roles with user counts and permissions 
$roles = queryDB('SELECT * FROM roles ORDER BY id ASC');

foreach ($roles as &$role) {
    $role['user_count'] = singleQueryDB(
        'SELECT COUNT(*) AS n FROM users WHERE role_id = ? AND is_verified != -1',
        [$role['id']]
    )['n'];

    $role['permissions'] = queryDB(
        'SELECT p.name, p.description FROM permissions p
         JOIN role_permissions rp ON rp.permission_id = p.id
         WHERE rp.role_id = ?
         ORDER BY p.name ASC',
        [$role['id']]
    );
}
unset($role);

//  All permissions 
$allPermissions = queryDB('SELECT * FROM permissions ORDER BY name ASC');

//  Users for role assignment 
$users = queryDB(
    'SELECT u.id, u.name, u.surname, u.email, r.name AS role_name, r.id AS role_id
     FROM users u JOIN roles r ON u.role_id = r.id
     WHERE u.is_verified = 1
     ORDER BY u.name ASC'
);

$allRoles = queryDB('SELECT * FROM roles ORDER BY id ASC');

$pageTitle = 'Roles & Permissions';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Roles & Permissions</h1>
      <p class="admin-page-subtitle">Manage RBAC — assign roles and view permission mappings. Superuser only.</p>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Roles overview -->
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:28px;">
    <?php foreach ($roles as $role): ?>
    <div class="admin-card">
      <div class="admin-card-header">
        <div style="display:flex;align-items:center;gap:10px;">
          <span class="admin-badge <?= match($role['name']) {
              'superuser' => 'admin-badge-purple',
              'admin'     => 'admin-badge-blue',
              'seller'    => 'admin-badge-green',
              default     => 'admin-badge-gray'
          } ?>" style="font-size:var(--admin-fs-sm);padding:4px 12px;">
            <?= ucfirst($role['name']) ?>
          </span>
          <span style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
            <?= $role['user_count'] ?> user<?= $role['user_count'] != 1 ? 's' : '' ?>
          </span>
        </div>
      </div>
      <div class="admin-card-body">
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);margin-bottom:12px;">
          <?= htmlspecialchars($role['description'] ?? '') ?>
        </p>
        <p style="font-size:var(--admin-fs-xs);font-weight:600;color:var(--admin-text);
                  text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
          Permissions (<?= count($role['permissions']) ?>)
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:5px;">
          <?php if ($role['name'] === 'superuser'): ?>
            <span class="admin-badge admin-badge-purple">All permissions</span>
          <?php else: ?>
            <?php foreach ($role['permissions'] as $perm): ?>
            <span class="admin-badge admin-badge-gray" title="<?= htmlspecialchars($perm['description']) ?>">
              <?= htmlspecialchars(str_replace('_', ' ', $perm['name'])) ?>
            </span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Assign role to user -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Assign Role to User</h3>
    </div>
    <div class="admin-card-body">
      <form method="POST" action="/tuelo-admin/roles.php"
            style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="assign_role" value="1" />

        <div class="admin-form-group" style="flex:1;min-width:220px;margin-bottom:0;">
          <label class="admin-label">Select User</label>
          <select name="user_id" class="admin-input" style="cursor:pointer;" required>
            <option value="">Choose a user...</option>
            <?php foreach ($users as $u): ?>
            <?php if ($u['id'] === $currentUser['id']) continue; ?>
            <option value="<?= $u['id'] ?>">
              <?= htmlspecialchars($u['name'] . ' ' . $u['surname']) ?> (<?= $u['email'] ?>) — <?= ucfirst($u['role_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admin-form-group" style="min-width:160px;margin-bottom:0;">
          <label class="admin-label">New Role</label>
          <select name="role_id" class="admin-input" style="cursor:pointer;" required>
            <option value="">Select role...</option>
            <?php foreach ($allRoles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= ucfirst($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="admin-btn admin-btn-primary">
          <ion-icon name="shield-checkmark-outline"></ion-icon>
          Assign Role
        </button>
      </form>
    </div>
  </div>

  <!-- Full permissions table -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">All Permissions</h3>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Permission</th>
            <th>Description</th>
            <th>Buyer</th>
            <th>Seller</th>
            <th>Admin</th>
            <th>Superuser</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allPermissions as $perm): ?>
          <tr>
            <td style="font-weight:500;color:var(--admin-text);">
              <?= htmlspecialchars(str_replace('_', ' ', $perm['name'])) ?>
            </td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($perm['description']) ?></td>
            <?php foreach ($roles as $role): ?>
            <td style="text-align:center;">
              <?php
              $hasIt = $role['name'] === 'superuser' || in_array(
                  $perm['name'],
                  array_column($role['permissions'], 'name')
              );
              ?>
              <?php if ($hasIt): ?>
                <ion-icon name="checkmark-circle" style="color:#059669;font-size:18px;"></ion-icon>
              <?php else: ?>
                <ion-icon name="close-circle" style="color:#D1D5DB;font-size:18px;"></ion-icon>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>