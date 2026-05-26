<?php
http_response_code(403);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Access Denied';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div style="padding: 80px 0; text-align: center;">

    <ion-icon name="shield-outline"
              style="font-size: 80px; color: #9333EA; display:block; margin: 0 auto 20px;">
    </ion-icon>

    <h1 style="font-size: 6rem; font-weight: 800; color: var(--admin-text);
                line-height: 1; margin-bottom: 10px;">403</h1>

    <h2 style="font-size: var(--admin-fs-xl); font-weight: 700; color: var(--admin-text);
                margin-bottom: 12px;">Access denied</h2>

    <p style="font-size: var(--admin-fs-md); color: var(--admin-muted);
               max-width: 420px; margin: 0 auto 35px; line-height: 1.7;">
      You don't have permission to view this page.
      This area is restricted to superusers only.
    </p>

    <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">

      <a href="/tuelo-admin/index.php" class="admin-btn admin-btn-primary">
        <ion-icon name="grid-outline"></ion-icon>
        Back to Dashboard
      </a>

      <a href="/tuelo-admin/logout.php" class="admin-btn admin-btn-secondary">
        <ion-icon name="log-out-outline"></ion-icon>
        Logout
      </a>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>