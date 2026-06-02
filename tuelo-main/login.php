<?php
$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';

redirectIfLoggedIn('/tuelo-main/index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $user = singleQueryDB(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE u.email = ?',
            [$email]
        );

        if (!$user) {
            $error = 'No account found with that email address.';
        } elseif ($user['is_verified'] == -1) {
            $error = 'Your account has been deactivated. Please contact support.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            loginUser($user);

            $redirect = $_GET['redirect'] ?? '';
            if (!empty($redirect)) {
                header('Location: ' . urldecode($redirect));
            } elseif (in_array($user['role_name'], ['admin', 'superuser'])) {
                header('Location: /tuelo-admin/index.php');
            } else {
                header('Location: /tuelo-main/index.php');
            }
            exit;
        }
    }
}
?>

<div class="form-page">
  <div class="container">
    <div class="form-card">

      <h2 class="form-title">Welcome back!</h2>
      <p class="form-subtitle">Log in to your Tuelo account</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/tuelo-main/login.php" id="loginForm">

        <div class="form-group">
          <label class="form-label" for="email">Email address</label>
          <input type="email" id="email" name="email" class="form-input"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($email) ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input"
                 placeholder="Enter your password" required />
        </div>

        <button type="submit" class="btn-submit">Log In</button>

      </form>

      <p class="form-footer-text" style="margin-top:20px;">
        Don't have an account?
        <a href="/tuelo-main/register.php">Create one here</a>
      </p>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
