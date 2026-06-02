<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireAdmin();
$currentUser = getUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /tuelo-admin/users.php');
    exit;
}

$user = singleQueryDB(
    'SELECT u.*, r.name AS role_name FROM users u
     JOIN roles r ON u.role_id = r.id WHERE u.id = ?',
    [$id]
);

if (!$user) {
    header('Location: /tuelo-admin/users.php');
    exit;
}

$roles   = queryDB('SELECT * FROM roles ORDER BY id ASC');
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']         ?? '');
    $surname = trim($_POST['surname']      ?? '');
    $phone   = trim($_POST['phone_number'] ?? '');
    $roleId  = (int)($_POST['role_id']     ?? 0);
    $status  = (int)($_POST['is_verified'] ?? 0);

    if (empty($name) || empty($surname)) {
        $error = 'Name and surname are required.';
    } else {
        // Prevent non-superusers from assigning superuser role
        if ($currentUser['role_name'] !== 'superuser') {
            $superuserRole = singleQueryDB('SELECT id FROM roles WHERE name = "superuser"');
            if ($roleId === (int)$superuserRole['id']) {
                $error = 'Only superusers can assign the superuser role.';
            }
        }

        if (!$error) {
            updateDB(
                'UPDATE users SET name = ?, surname = ?, phone_number = ?,
                 role_id = ?, is_verified = ?, updated_at = NOW() WHERE id = ?',
                [$name, $surname, $phone ?: null, $roleId, $status, $id]
            );

            // Bust session cache if editing own account
            if ($id === $currentUser['id']) {
                unset($_SESSION['user_cache']);
            }

            $success = 'User updated successfully.';

            // Refresh user data after update
            $user = singleQueryDB(
                'SELECT u.*, r.name AS role_name FROM users u
                 JOIN roles r ON u.role_id = r.id WHERE u.id = ?',
                [$id]
            );
        }
    }
}

// Fetch account stats for sidebar
$userListings  = singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE seller_id = ?',     [$id])['n'];
$userPurchases = singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE buyer_id = ?',  [$id])['n'];
$userReviews   = singleQueryDB('SELECT COUNT(*) AS n FROM reviews WHERE reviewee_id = ?',    [$id])['n'];

$pageTitle = 'Edit User';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <a href="/tuelo-admin/users.php"
         style="font-size:var(--admin-fs-sm);color:var(--admin-muted);
                display:inline-flex;align-items:center;gap:4px;margin-bottom:6px;">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Users
      </a>
      <h1 class="admin-page-title">Edit User</h1>
      <p class="admin-page-subtitle"><?= htmlspecialchars($user['email']) ?></p>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:flex-start;">

    <!-- Edit form -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">User Details</h3>
      </div>
      <div class="admin-card-body">
        <form method="POST" action="/tuelo-admin/user-edit.php?id=<?= $id ?>">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="admin-form-group">
              <label class="admin-label">First name</label>
              <input type="text" name="name" class="admin-input"
                     value="<?= htmlspecialchars($user['name']) ?>" required />
            </div>
            <div class="admin-form-group">
              <label class="admin-label">Last name</label>
              <input type="text" name="surname" class="admin-input"
                     value="<?= htmlspecialchars($user['surname']) ?>" required />
            </div>
          </div>

          <div class="admin-form-group">
            <label class="admin-label">Email address</label>
            <input type="email" class="admin-input"
                   value="<?= htmlspecialchars($user['email']) ?>" disabled />
            <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);margin-top:4px;">
              Email cannot be changed.
            </p>
          </div>

          <div class="admin-form-group">
            <label class="admin-label">Phone number</label>
            <input type="tel" name="phone_number" class="admin-input"
                   value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                   placeholder="071 234 5678" />
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="admin-form-group">
              <label class="admin-label">Role</label>
              <select name="role_id" class="admin-input" style="cursor:pointer;"
                      <?= $id === $currentUser['id'] ? 'disabled' : '' ?>>
                <?php foreach ($roles as $r): ?>
                  <?php if ($r['name'] === 'superuser' && $currentUser['role_name'] !== 'superuser') continue; ?>
                  <option value="<?= $r['id'] ?>"
                    <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>>
                    <?= ucfirst($r['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if ($id === $currentUser['id']): ?>
                <input type="hidden" name="role_id" value="<?= $user['role_id'] ?>" />
                <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);margin-top:4px;">
                  You cannot change your own role.
                </p>
              <?php endif; ?>
            </div>

            <div class="admin-form-group">
              <label class="admin-label">Account status</label>
              <select name="is_verified" class="admin-input" style="cursor:pointer;"
                      <?= $id === $currentUser['id'] ? 'disabled' : '' ?>>
                <option value="1"  <?= $user['is_verified'] == 1  ? 'selected' : '' ?>>Active</option>
                <option value="0"  <?= $user['is_verified'] == 0  ? 'selected' : '' ?>>Pending</option>
                <option value="-1" <?= $user['is_verified'] == -1 ? 'selected' : '' ?>>Deactivated</option>
              </select>
              <?php if ($id === $currentUser['id']): ?>
                <input type="hidden" name="is_verified" value="<?= $user['is_verified'] ?>" />
              <?php endif; ?>
            </div>
          </div>

          <div style="display:flex;gap:10px;margin-top:8px;">
            <button type="submit" class="admin-btn admin-btn-primary">
              <ion-icon name="save-outline"></ion-icon>
              Save Changes
            </button>
            <a href="/tuelo-admin/users.php" class="admin-btn admin-btn-secondary">Cancel</a>
          </div>

        </form>
      </div>
    </div>

    <!-- User info sidebar -->
    <div>
      <!-- Profile card -->
      <div class="admin-card">
        <div class="admin-card-body" style="text-align:center;padding:24px;">
          <img src="<?= $user['profile_image']
              ? htmlspecialchars($user['profile_image'])
              : '/tuelo-main/assets/img/default-avatar.png' ?>"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                      border:3px solid var(--admin-border);margin:0 auto 12px;" />
          <p style="font-weight:700;font-size:var(--admin-fs-md);color:var(--admin-text);">
            <?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?>
          </p>
          <p style="font-size:var(--admin-fs-sm);color:var(--admin-muted);margin:4px 0;">
            <?= htmlspecialchars($user['email']) ?>
          </p>
          <span class="admin-badge <?= match($user['role_name']) {
              'superuser' => 'admin-badge-purple',
              'admin'     => 'admin-badge-blue',
              'seller'    => 'admin-badge-green',
              default     => 'admin-badge-gray'
          } ?>" style="margin-top:8px;">
            <?= ucfirst($user['role_name']) ?>
          </span>
        </div>
      </div>

      <!-- Account stats -->
      <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
          <h3 class="admin-card-title">Account Info</h3>
        </div>
        <div class="admin-card-body" style="padding:16px;">
          <div style="display:flex;justify-content:space-between;padding:6px 0;
                      border-bottom:1px solid var(--admin-border);font-size:var(--admin-fs-sm);">
            <span style="color:var(--admin-muted);">Member since</span>
            <span style="font-weight:500;"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;
                      border-bottom:1px solid var(--admin-border);font-size:var(--admin-fs-sm);">
            <span style="color:var(--admin-muted);">Listings posted</span>
            <span style="font-weight:500;"><?= $userListings ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;
                      border-bottom:1px solid var(--admin-border);font-size:var(--admin-fs-sm);">
            <span style="color:var(--admin-muted);">Purchases made</span>
            <span style="font-weight:500;"><?= $userPurchases ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;
                      font-size:var(--admin-fs-sm);">
            <span style="color:var(--admin-muted);">Reviews received</span>
            <span style="font-weight:500;"><?= $userReviews ?></span>
          </div>
        </div>
      </div>

      <!-- Rating -->
      <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-body" style="padding:16px;text-align:center;">
          <p style="font-size:var(--admin-fs-xs);font-weight:600;color:var(--admin-muted);
                    text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
            Seller Rating
          </p>
          <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);">
            <?= number_format($user['rating_average'], 1) ?> / 5
          </p>
          <div style="display:flex;justify-content:center;gap:3px;margin-top:6px;">
            <?php
            $rating = round($user['rating_average'] * 2) / 2;
            for ($i = 1; $i <= 5; $i++):
                if ($rating >= $i):
            ?>
              <ion-icon name="star" style="color:#F59E0B;font-size:16px;"></ion-icon>
            <?php elseif ($rating >= $i - 0.5): ?>
              <ion-icon name="star-half" style="color:#F59E0B;font-size:16px;"></ion-icon>
            <?php else: ?>
              <ion-icon name="star-outline" style="color:#D1D5DB;font-size:16px;"></ion-icon>
            <?php endif; endfor; ?>
          </div>
          <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);margin-top:6px;">
            Based on <?= $userReviews ?> review<?= $userReviews != 1 ? 's' : '' ?>
          </p>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>