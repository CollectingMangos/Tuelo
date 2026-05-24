<?php
$pageTitle = 'Page Not Found';
require_once __DIR__ . '/includes/header.php';
?>

<div style="padding: 80px 0; text-align: center;">
  <div class="container">

    <h1 style="font-size: 6rem; font-weight: 800; color: var(--tuelo-green-dark);
                line-height: 1; margin-bottom: 10px;">404</h1>

    <h2 style="font-size: var(--fs-2); font-weight: 600; color: var(--eerie-black);
                margin-bottom: 12px;">Page not found</h2>

    <p style="font-size: var(--fs-6); color: var(--sonic-silver);
               max-width: 420px; margin: 0 auto 35px; line-height: 1.7;">
      The page you're looking for doesn't exist, was removed, or the link is broken.
    </p>

    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">

      <a href="/tuelo-main/index.php"
         style="background: var(--tuelo-green-dark); color: #fff;
                padding: 12px 28px; border-radius: var(--border-radius-sm);
                font-weight: 600; font-size: var(--fs-7);
                transition: var(--transition-timing);"
         onmouseover="this.style.background='var(--tuelo-green)'"
         onmouseout="this.style.background='var(--tuelo-green-dark)'">
        Go to Homepage
      </a>

      <a href="/tuelo-main/listings.php"
         style="border: 1.5px solid var(--tuelo-green-dark); color: var(--tuelo-green-dark);
                padding: 12px 28px; border-radius: var(--border-radius-sm);
                font-weight: 600; font-size: var(--fs-7);
                transition: var(--transition-timing);"
         onmouseover="this.style.background='var(--tuelo-green-light)'"
         onmouseout="this.style.background='transparent'">
        Browse Listings
      </a>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>