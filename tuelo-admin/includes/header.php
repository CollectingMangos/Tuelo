<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentUser = $currentUser ?? getUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tuelo Admin <?= isset($pageTitle) ? '— ' . htmlspecialchars($pageTitle) : '' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/tuelo-admin/assets/css/admin.css" />
</head>
<body>

<div class="admin-layout">

  <!--  SIDEBAR  -->
  <aside class="admin-sidebar">

    <!-- Logo -->
    <div class="admin-sidebar-logo">
      <a href="/tuelo-admin/index.php">
        <span class="admin-logo-text">Tuelo</span>
        <span class="admin-logo-badge">Admin</span>
      </a>
    </div>

    <!-- Nav -->
    <nav class="admin-nav">

      <p class="admin-nav-section">Main</p>

      <a href="/tuelo-admin/index.php"
         class="admin-nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <ion-icon name="grid-outline"></ion-icon>
        Dashboard
      </a>

      <a href="/tuelo-admin/users.php"
         class="admin-nav-item <?= $currentPage === 'users.php' || $currentPage === 'user-edit.php' || $currentPage === 'user-create.php' ? 'active' : '' ?>">
        <ion-icon name="people-outline"></ion-icon>
        Users
      </a>

      <a href="/tuelo-admin/listings.php"
         class="admin-nav-item <?= $currentPage === 'listings.php' ? 'active' : '' ?>">
        <ion-icon name="grid-outline"></ion-icon>
        Listings
      </a>

      <a href="/tuelo-admin/reports.php"
         class="admin-nav-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
        <ion-icon name="bar-chart-outline"></ion-icon>
        Reports
      </a>

      <?php if ($currentUser['role_name'] === 'superuser'): ?>
      <p class="admin-nav-section">Superuser</p>

      <a href="/tuelo-admin/roles.php"
         class="admin-nav-item <?= $currentPage === 'roles.php' ? 'active' : '' ?>">
        <ion-icon name="shield-outline"></ion-icon>
        Roles
      </a>

      <a href="/tuelo-admin/logs.php"
         class="admin-nav-item <?= $currentPage === 'logs.php' ? 'active' : '' ?>">
        <ion-icon name="document-text-outline"></ion-icon>
        Audit Logs
      </a>
      <?php endif; ?>

      <p class="admin-nav-section">Account</p>

      <a href="/tuelo-main/index.php" class="admin-nav-item" target="_blank">
        <ion-icon name="storefront-outline"></ion-icon>
        View Main Site
      </a>

      <a href="/tuelo-admin/logout.php" class="admin-nav-item admin-nav-danger">
        <ion-icon name="log-out-outline"></ion-icon>
        Logout
      </a>

    </nav>

  </aside>

  <!--  MAIN WRAPPER  -->
  <div class="admin-main">

    <!-- Top bar -->
    <header class="admin-topbar">
      <div class="admin-topbar-left">
        <h2 class="admin-topbar-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></h2>
      </div>
      <div class="admin-topbar-right">
        <div style="display:flex;align-items:center;gap:10px;">
          <img src="<?= $currentUser['profile_image']
              ? htmlspecialchars($currentUser['profile_image'])
              : '/tuelo-main/assets/img/default-avatar.png' ?>"
               style="width:34px;height:34px;border-radius:50%;object-fit:cover;
                      border:2px solid var(--admin-border);" />
          <div>
            <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);">
              <?= htmlspecialchars($currentUser['name'] . ' ' . $currentUser['surname']) ?>
            </p>
            <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);text-transform:capitalize;">
              <?= htmlspecialchars($currentUser['role_name']) ?>
            </p>
          </div>
        </div>
      </div>
    </header>