<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireAdmin();
$currentUser = getUser();

$roles = queryDB('SELECT * FROM roles ORDER BY id ASC');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']         ?? '');
    $surname  = trim($_POST['surname']      ?? '');
    $email    = trim($_POST['email']        ?? '');
    $phone    = trim($_POST['phone_number'] ?? '');
    $roleId   = (int)($_POST['role_id']     ?? 0);
    $password = trim($_POST['password']     ?? '');

    if (empty($name) || empty($surname) || empty($email) || empty($password)) {
        $error = 'Name, surname, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!$roleId) {
        $error = 'Please select a role.';
    } else {
        if ($currentUser['role_name'] !== 'superuser') {
            $superuserRole = singleQueryDB('SELECT id FROM roles WHERE name = "superuser"');
            if ($roleId === (int)$superuserRole['id']) {
                $error = 'Only superusers can create superuser accounts.';
            }
        }

        if (!$error) {
            $existing = singleQueryDB('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing) {
                $error = 'An account with that email already exists.';
            } else {
                $hash  = password_hash($password, PASSWORD_BCRYPT);
                $newId = insertIntoDB(
                    'INSERT INTO users (role_id, name, surname, email, password, phone_number, is_verified, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
                    [$roleId, $name, $surname, $email, $hash, $phone ?: null]
                );

                if ($newId) {
                    logAction('user_created', 'user', $newId);
                    header('Location: /tuelo-admin/users.php?msg=' . urlencode('User created successfully.') . '&type=success');
                    exit;
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Create User';
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
      <h1 class="admin-page-title">Create User</h1>
      <p class="admin-page-subtitle">Add a new user account to the platform.</p>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div style="max-width:600px;">
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">New User Details</h3>
      </div>
      <div class="admin-card-body">
        <form method="POST" action="/tuelo-admin/user-create.php">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="admin-form-group">
              <label class="admin-label">First name <span style="color:red">*</span></label>
              <input type="text" name="name" class="admin-input"
                     placeholder="Thabo"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
            </div>
            <div class="admin-form-group">
              <label class="admin-label">Last name <span style="color:red">*</span></label>
              <input type="text" name="surname" class="admin-input"
                     placeholder="Nkosi"
                     value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" required />
            </div>
          </div>

          <div class="admin-form-group">
            <label class="admin-label">Email address <span style="color:red">*</span></label>
            <input type="email" name="email" class="admin-input"
                   placeholder="user@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
          </div>

          <div class="admin-form-group">
            <label class="admin-label">Phone number</label>
            <input type="tel" name="phone_number" class="admin-input"
                   placeholder="071 234 5678"
                   value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" />
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="admin-form-group">
              <label class="admin-label">Role <span style="color:red">*</span></label>
              <select name="role_id" class="admin-input" style="cursor:pointer;" required>
                <option value="">Select a role</option>
                <?php foreach ($roles as $r): ?>
                  <?php if ($r['name'] === 'superuser' && $currentUser['role_name'] !== 'superuser') continue; ?>
                  <option value="<?= $r['id'] ?>"
                    <?= ($_POST['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                    <?= ucfirst($r['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-form-group">
              <label class="admin-label">Password <span style="color:red">*</span></label>
              <input type="password" name="password" class="admin-input"
                     placeholder="Min 8 characters" required />
            </div>
          </div>

          <div style="display:flex;gap:10px;margin-top:8px;">
            <button type="submit" class="admin-btn admin-btn-primary">
              <ion-icon name="person-add-outline"></ion-icon>
              Create User
            </button>
            <a href="/tuelo-admin/users.php" class="admin-btn admin-btn-secondary">Cancel</a>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>