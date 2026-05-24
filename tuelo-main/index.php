<?php
$pageTitle = 'Buy & Sell in South Africa';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$listings = queryDB(
    'SELECT l.*, u.name AS seller_name, c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     WHERE l.status = "active"
     ORDER BY l.created_at DESC
     LIMIT 8'
);

// Fetch all categories
$categories = queryDB('SELECT * FROM categories ORDER BY name ASC');
?>

<!-- HERO SECTION -->
<div class="banner">
  <div class="container">
    <div class="slider-container has-scrollbar">

      <div class="slider-item">
        <img src="/tuelo-main/assets/img/banner-1.png" alt="Buy and sell anything" class="banner-img" />
        <div class="banner-content">
          <p class="banner-subtitle">South Africa's C2C Marketplace</p>
          <h2 class="banner-title">Buy & Sell Directly With Your Local Community</h2>
          <p class="banner-text">Just people trading with people!</p>
          <a href="/tuelo-main/listings.php" class="banner-btn">Browse Listings</a>
        </div>
      </div>

      <div class="slider-item">
        <img src="/tuelo-main/assets/img/banner-2.png" alt="Start selling today" class="banner-img" />
        <div class="banner-content">
          <p class="banner-subtitle">Become a Seller on Tuelo!</p>
          <h2 class="banner-title">Sell with confidence</h2>
          <p class="banner-text">List your item/s in minutes for all of South Africa.</p>
          <a href="/tuelo-main/sell.php" class="banner-btn">Start Selling!</a>
        </div>
      </div>

      <div class="slider-item">
        <img src="/tuelo-main/assets/img/banner-3.png" alt="Verified and secure" class="banner-img" />
        <div class="banner-content">
          <p class="banner-subtitle">Safe & Verified</p>
          <h2 class="banner-title">Every Seller Verified. Every Payment Secured.</h2>
          <p class="banner-text">Tuelo protects buyers and sellers.</p>
          <a href="/tuelo-main/register.php" class="banner-btn">Join Tuelo</a>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- CATEGORY STRIP SECTION -->
<div class="category">
  <div class="container">
    <div class="category-item-container has-scrollbar">
      <?php foreach ($categories as $cat): ?>
      <div class="category-item">
        <div class="category-img-box">
          <ion-icon name="grid-outline" style="font-size:28px;color:var(--tuelo-green-dark)"></ion-icon>
        </div>
        <div class="category-content-box">
          <div class="category-content-flex">
            <h3 class="category-item-title"><?= htmlspecialchars($cat['name']) ?></h3>
          </div>
          <a href="/tuelo-main/listings.php?category=<?= urlencode($cat['slug']) ?>" class="category-btn">Show all</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- PRODUCT GRID SECTION -->
<div class="product-container">
  <div class="container">

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-category">
        <div class="sidebar-top">
          <h2 class="sidebar-title">Categories</h2>
        </div>
        <ul class="sidebar-menu-category-list">
          <?php foreach ($categories as $cat): ?>
          <li class="sidebar-menu-category">
            <button class="sidebar-accordion-menu" data-accordion-btn>
              <div style="display:flex;align-items:center;gap:8px;">
                <ion-icon name="chevron-forward-outline" class="add-icon"></ion-icon>
                <ion-icon name="chevron-down-outline" class="remove-icon"></ion-icon>
                <p class="menu-title"><?= htmlspecialchars($cat['name']) ?></p>
              </div>
            </button>
            <ul class="sidebar-submenu-category-list" data-accordion>
              <li><a href="/tuelo-main/listings.php?category=<?= urlencode($cat['slug']) ?>&condition=new" class="sidebar-submenu-title">New</a></li>
              <li><a href="/tuelo-main/listings.php?category=<?= urlencode($cat['slug']) ?>&condition=used" class="sidebar-submenu-title">Used</a></li>
              <li><a href="/tuelo-main/listings.php?category=<?= urlencode($cat['slug']) ?>&condition=refurbished" class="sidebar-submenu-title">Refurbished</a></li>
            </ul>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Product box -->
    <div class="product-box">
      <div class="product-main">
        <h2 class="title">Latest Listings</h2>

        <?php if (empty($listings)): ?>
          <div style="text-align:center;padding:60px 0;color:var(--sonic-silver);">
            <ion-icon name="cube-outline" style="font-size:60px;display:block;margin:0 auto 15px;"></ion-icon>
            <p style="font-size:var(--fs-5);margin-bottom:15px;">No listings yet — be the first to sell something!</p>
            <a href="/tuelo-main/sell.php" style="display:inline-block;background:var(--tuelo-green-dark);color:#fff;padding:10px 25px;border-radius:var(--border-radius-sm);font-weight:600;">Post a Listing</a>
          </div>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($listings as $listing): ?>
            <div class="showcase">
              <div class="showcase-banner">
                <img src="<?= $listing['cover_img'] ? htmlspecialchars($listing['cover_img']) : '/tuelo-main/assets/img/no-image.png' ?>"
                     alt="<?= htmlspecialchars($listing['title']) ?>"
                     class="product-img default" />
                <p class="showcase-badge <?= $listing['condition_type'] ?>">
                  <?= ucfirst($listing['condition_type']) ?>
                </p>
                <div class="showcase-actions">
                  <a href="/tuelo-main/product.php?id=<?= $listing['id'] ?>" class="btn-action" title="View">
                    <ion-icon name="eye-outline"></ion-icon>
                  </a>
                  <?php if ($currentUser && hasPermission('send_message')): ?>
                  <a href="/tuelo-main/messages.php?listing=<?= $listing['id'] ?>" class="btn-action" title="Message seller">
                    <ion-icon name="chatbubble-outline"></ion-icon>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="showcase-content">
                <a href="/tuelo-main/listings.php?category=<?= urlencode($listing['slug'] ?? '') ?>" class="showcase-category">
                  <?= htmlspecialchars($listing['category_name']) ?>
                </a>
                <a href="/tuelo-main/product.php?id=<?= $listing['id'] ?>">
                  <h3 class="showcase-title"><?= htmlspecialchars($listing['title']) ?></h3>
                </a>
                <div class="price-box">
                  <p class="price">R <?= number_format($listing['price'], 2) ?></p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="text-align:center;margin-top:30px;">
            <a href="/tuelo-main/listings.php"
               style="display:inline-block;border:1.5px solid var(--tuelo-green-dark);color:var(--tuelo-green-dark);padding:10px 30px;border-radius:var(--border-radius-sm);font-weight:600;transition:0.2s ease;">
              View All Listings
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- HOW IT WORKS SECTION -->
<div style="padding: 40px 0 50px;">
  <div class="container">
    <h2 class="title">How Tuelo Works</h2>
    <div class="service-container">
      <a href="/tuelo-main/register.php" class="service-item">
        <div class="service-icon"><ion-icon name="person-add-outline"></ion-icon></div>
        <div>
          <h3 class="service-title">1. Create an account</h3>
          <p class="service-desc">Register as a buyer or seller in minutes</p>
        </div>
      </a>
      <a href="/tuelo-main/sell.php" class="service-item">
        <div class="service-icon"><ion-icon name="camera-outline"></ion-icon></div>
        <div>
          <h3 class="service-title">2. List your item</h3>
          <p class="service-desc">Post photos, set your price, go live instantly</p>
        </div>
      </a>
      <a href="/tuelo-main/listings.php" class="service-item">
        <div class="service-icon"><ion-icon name="bag-handle-outline"></ion-icon></div>
        <div>
          <h3 class="service-title">3. Buy safely</h3>
          <p class="service-desc">Browse verified listings and pay securely</p>
        </div>
      </a>
      <a href="/tuelo-main/register.php" class="service-item">
        <div class="service-icon"><ion-icon name="cash-outline"></ion-icon></div>
        <div>
          <h3 class="service-title">4. Get paid</h3>
          <p class="service-desc">Funds released once buyer confirms delivery</p>
        </div>
      </a>
      <a href="/tuelo-main/listings.php" class="service-item">
        <div class="service-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
        <div>
          <h3 class="service-title">Buyer protection</h3>
          <p class="service-desc">Every transaction covered by Tuelo's guarantee</p>
        </div>
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>