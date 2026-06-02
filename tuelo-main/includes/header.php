<?php
require_once __DIR__ . '/../../includes/auth.php';
$currentUser = getUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tuelo <?= isset($pageTitle) ? '— ' . htmlspecialchars($pageTitle) : '— E-Commerce for South Africa' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/tuelo-main/assets/css/style.css" />
</head>
<body>

<div class="overlay" data-overlay></div>

<div class="header-top">
  <div class="container">

    <div class="header-alert-news">
      <p><b>Tuelo</b> — South Africa's trusted C2C marketplace. Safe payments. Verified sellers.</p>
    </div>

    <div class="header-top-actions">
      <?php if ($currentUser): ?>
        <span style="color:rgba(255,255,255,0.8);font-size:0.75rem;">
          Welcome, <?= htmlspecialchars($currentUser['name']) ?>
        </span>
        <a href="/tuelo-main/logout.php" style="color:rgba(255,255,255,0.7);font-size:0.75rem;text-transform:uppercase;">Logout</a>
      <?php else: ?>
        <a href="/tuelo-main/login.php" style="color:rgba(255,255,255,0.8);font-size:0.75rem;text-transform:uppercase;">Login</a>
        <a href="/tuelo-main/register.php" style="color:rgba(255,255,255,0.8);font-size:0.75rem;text-transform:uppercase;">Register</a>
      <?php endif; ?>
    </div>

  </div>
</div>

<div class="header-main">
  <div class="container">

    <a href="/tuelo-main/index.php" class="header-logo">
      <span class="header-logo-text">Tuelo</span>
    </a>

    <div class="header-search-container">
      <form action="/tuelo-main/listings.php" method="GET">
        <input type="search" name="q" class="search-field"
               placeholder="Search all listings..."
               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" />
        <button type="submit" class="search-btn">
          <ion-icon name="search-outline"></ion-icon>
        </button>
      </form>
    </div>

    <div class="header-user-actions">

      <?php if ($currentUser): ?>

        <a href="/tuelo-main/profile.php" class="action-btn" title="My Profile">
          <ion-icon name="person-outline"></ion-icon>
        </a>

        <a href="/tuelo-main/messages.php" class="action-btn" title="Messages">
          <ion-icon name="chatbubble-outline"></ion-icon>
        </a>

        <a href="/tuelo-main/transactions.php" class="action-btn" title="Transactions">
          <ion-icon name="receipt-outline"></ion-icon>
        </a>

        <?php if (in_array($currentUser['role_name'], ['admin', 'superuser'])): ?>
        <a href="/tuelo-admin/index.php" class="action-btn" title="Admin Panel">
          <ion-icon name="desktop-outline"></ion-icon>
        </a>
        <?php endif; ?>

      <?php else: ?>

        <a href="/tuelo-main/login.php" class="action-btn" title="Login">
          <ion-icon name="person-outline"></ion-icon>
        </a>

      <?php endif; ?>

    </div>

  </div>
</div>

<nav class="desktop-navigation-menu">
  <div class="container">
    <ul class="desktop-menu-category-list">

      <li class="menu-category">
        <a href="/tuelo-main/index.php"
           class="menu-title <?= $currentPage === 'index.php' ? 'active' : '' ?>">
          Home
        </a>
      </li>

      <li class="menu-category">
        <a href="/tuelo-main/listings.php"
           class="menu-title <?= $currentPage === 'listings.php' ? 'active' : '' ?>">
          Buy
        </a>
        <ul class="dropdown-list">
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=electronics"><ion-icon name="phone-portrait-outline"></ion-icon> Electronics</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=clothing-shoes"><ion-icon name="shirt-outline"></ion-icon> Clothing & Shoes</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=outdoor-garden"><ion-icon name="leaf-outline"></ion-icon> Outdoor & Garden</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=home-goods"><ion-icon name="home-outline"></ion-icon> Home Goods</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=sporting-goods"><ion-icon name="football-outline"></ion-icon> Sporting Goods</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=vehicles-parts"><ion-icon name="car-outline"></ion-icon> Vehicles & Parts</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=appliances"><ion-icon name="tv-outline"></ion-icon> Appliances</a></li>
          <li class="dropdown-item"><a href="/tuelo-main/listings.php?category=other"><ion-icon name="grid-outline"></ion-icon> Other</a></li>
        </ul>
      </li>

      <?php if ($currentUser && hasPermission('create_listing')): ?>
      <li class="menu-category">
        <a href="/tuelo-main/sell.php"
           class="menu-title <?= $currentPage === 'sell.php' ? 'active' : '' ?>">
          Sell
        </a>
      </li>
      <?php endif; ?>

      <?php if ($currentUser): ?>
      <li class="menu-category">
        <a href="/tuelo-main/messages.php"
           class="menu-title <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
          Messages
        </a>
      </li>

      <li class="menu-category">
        <a href="/tuelo-main/transactions.php"
           class="menu-title <?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
          Transactions
        </a>
      </li>
      <?php endif; ?>

    </ul>
  </div>
</nav>
