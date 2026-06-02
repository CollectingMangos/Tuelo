<?php
$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';

redirectIfLoggedIn('/tuelo-main/index.php');

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedReferers = [
    '/tuelo-main/login.php',
    '/tuelo-main/register.php',
    'localhost:8888'
];

$allowed = false;
foreach ($allowedReferers as $ref) {
    if (str_contains($referer, $ref)) {
        $allowed = true;
        break;
    }
}

if (isset($_GET['ref']) && $_GET['ref'] === 'login') {
    $allowed = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = true;
}

if (!$allowed) {
    header('Location: /tuelo-main/login.php');
    exit;
}

$error   = '';
$success = '';
$fields  = ['name' => '', 'surname' => '', 'email' => '', 'phone_number' => '', 'role' => 'buyer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields['name']         = trim($_POST['name']         ?? '');
    $fields['surname']      = trim($_POST['surname']       ?? '');
    $fields['email']        = trim($_POST['email']         ?? '');
    $fields['phone_number'] = trim($_POST['phone_number']  ?? '');
    $fields['role']         = trim($_POST['role']          ?? 'buyer');
    $password               = trim($_POST['password']      ?? '');
    $confirmPassword        = trim($_POST['confirm_password'] ?? '');

    if (empty($fields['name']) || empty($fields['surname']) || empty($fields['email']) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($fields['role'], ['buyer', 'seller'])) {
        $error = 'Please select a valid account type.';
    } else {
        $existing = singleQueryDB('SELECT id FROM users WHERE email = ?', [$fields['email']]);

        if ($existing) {
            $error = 'An account with that email address already exists. <a href="/tuelo-main/login.php">Log in instead?</a>';
        } else {
            $role = singleQueryDB('SELECT id FROM roles WHERE name = ?', [$fields['role']]);

            if (!$role) {
                $error = 'Invalid account type selected.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $newId = insertIntoDB(
                    'INSERT INTO users (role_id, name, surname, email, password, phone_number, is_verified, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 1, NOW())',
                    [
                        $role['id'],
                        $fields['name'],
                        $fields['surname'],
                        $fields['email'],
                        $hash,
                        $fields['phone_number'] ?: null
                    ]
                );

                if ($newId) {
                    $newUser = singleQueryDB('SELECT * FROM users WHERE id = ?', [$newId]);
                    loginUser($newUser);
                    header('Location: /tuelo-main/index.php?welcome=1');
                    exit;
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}
?>

<div class="form-page">
  <div class="container">
    <div class="form-card" style="max-width:560px;">

      <h2 class="form-title">Create your account</h2>
      <p class="form-subtitle">Join Tuelo! South Africa's C2C marketplace</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" action="/tuelo-main/register.php" id="registerForm">

        <div style="display:flex;gap:15px;">
          <div class="form-group" style="flex:1;">
            <label class="form-label" for="name">First name <span style="color:red">*</span></label>
            <input type="text" id="name" name="name" class="form-input"
                   placeholder="Thabo"
                   value="<?= htmlspecialchars($fields['name']) ?>" required />
          </div>
          <div class="form-group" style="flex:1;">
            <label class="form-label" for="surname">Last name <span style="color:red">*</span></label>
            <input type="text" id="surname" name="surname" class="form-input"
                   placeholder="Nkosi"
                   value="<?= htmlspecialchars($fields['surname']) ?>" required />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email address <span style="color:red">*</span></label>
          <input type="email" id="email" name="email" class="form-input"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($fields['email']) ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="phone_number">Phone number <span style="color:var(--sonic-silver);font-size:var(--fs-9)">(optional)</span></label>
          <input type="tel" id="phone_number" name="phone_number" class="form-input"
                 placeholder="071 234 5678"
                 value="<?= htmlspecialchars($fields['phone_number']) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">I want to <span style="color:red">*</span></label>
          <div style="display:flex;gap:12px;margin-top:6px;">

            <label style="flex:1;cursor:pointer;">
              <input type="radio" name="role" value="buyer"
                     <?= $fields['role'] === 'buyer' ? 'checked' : '' ?>
                     style="display:none;" class="role-radio" />
              <div class="role-option <?= $fields['role'] === 'buyer' ? 'selected' : '' ?>" id="opt-buyer">
                <ion-icon name="bag-handle-outline" style="font-size:22px;color:var(--tuelo-green-dark)"></ion-icon>
                <span style="font-weight:600;font-size:var(--fs-7);">Buy</span>
                <span style="font-size:var(--fs-9);color:var(--sonic-silver);">Browse and purchase listings</span>
              </div>
            </label>

            <label style="flex:1;cursor:pointer;">
              <input type="radio" name="role" value="seller"
                     <?= $fields['role'] === 'seller' ? 'checked' : '' ?>
                     style="display:none;" class="role-radio" />
              <div class="role-option <?= $fields['role'] === 'seller' ? 'selected' : '' ?>" id="opt-seller">
                <ion-icon name="pricetag-outline" style="font-size:22px;color:var(--tuelo-green-dark)"></ion-icon>
                <span style="font-weight:600;font-size:var(--fs-7);">Buy & Sell</span>
                <span style="font-size:var(--fs-9);color:var(--sonic-silver);">Post listings and make sales</span>
              </div>
            </label>

          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password <span style="color:red">*</span></label>
          <input type="password" id="password" name="password" class="form-input"
                 placeholder="Minimum 8 characters" required />
          <p id="pw-strength" style="font-size:var(--fs-9);margin-top:4px;"></p>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm password <span style="color:red">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                 placeholder="Repeat your password" required />
          <p id="pw-match" style="font-size:var(--fs-9);margin-top:4px;"></p>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>

      </form>

      <p class="form-footer-text" style="margin-top:20px;">
        Already have an account?
        <a href="/tuelo-main/login.php">Log in here</a>
      </p>

    </div>
  </div>
</div>

<style>
.role-option {
    display: flex; flex-direction: column; align-items: center;
    gap: 4px; padding: 14px 10px; text-align: center;
    border: 1.5px solid var(--cultured);
    border-radius: var(--border-radius-md);
    transition: var(--transition-timing);
}
.role-option:hover { border-color: var(--tuelo-green); background: var(--tuelo-green-light); }
.role-option.selected { border-color: var(--tuelo-green-dark); background: var(--tuelo-green-light); }
</style>

<script>
document.querySelectorAll('.role-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        this.closest('label').querySelector('.role-option').classList.add('selected');
    });
});

document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    const el  = document.getElementById('pw-strength');
    if (val.length === 0) { el.textContent = ''; return; }
    if (val.length < 8) {
        el.style.color = '#e74c3c';
        el.textContent = 'Too short — minimum 8 characters';
    } else if (val.length < 12) {
        el.style.color = 'var(--tuelo-gold)';
        el.textContent = 'Okay — consider making it longer';
    } else {
        el.style.color = 'var(--tuelo-green-dark)';
        el.textContent = 'Strong password';
    }
});

document.getElementById('confirm_password').addEventListener('input', function() {
    const pw    = document.getElementById('password').value;
    const el    = document.getElementById('pw-match');
    if (this.value.length === 0) { el.textContent = ''; return; }
    if (this.value === pw) {
        el.style.color = 'var(--tuelo-green-dark)';
        el.textContent = 'Passwords match';
    } else {
        el.style.color = '#e74c3c';
        el.textContent = 'Passwords do not match';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
