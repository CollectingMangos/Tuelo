<?php
$pageTitle = 'Browse Listings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$categories = queryDB('SELECT * FROM categories ORDER BY name ASC');

$search    = trim($_GET['q']        ?? '');
$catSlug   = trim($_GET['category'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$sort      = trim($_GET['sort']     ?? 'newest');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

$activeCat = null;
if ($catSlug) {
    $activeCat = singleQueryDB('SELECT * FROM categories WHERE slug = ?', [$catSlug]);
}

$where  = ["l.status = 'active'"];
$params = [];

if ($activeCat) {
    $where[]  = 'l.category_id = ?';
    $params[] = $activeCat['id'];
}

if ($condition && in_array($condition, ['new', 'used', 'refurbished'])) {
    $where[]  = 'l.condition_type = ?';
    $params[] = $condition;
}

if ($search) {
    $where[]  = '(l.title LIKE ? OR l.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderSQL = match($sort) {
    'price_asc'  => 'ORDER BY l.price ASC',
    'price_desc' => 'ORDER BY l.price DESC',
    'oldest'     => 'ORDER BY l.created_at ASC',
    default      => 'ORDER BY l.created_at DESC',
};

$totalRow = singleQueryDB(
    "SELECT COUNT(*) AS total
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     $whereSQL",
    $params
);
$total     = (int)($totalRow['total'] ?? 0);
$totalPages = (int)ceil($total / $perPage);

$listings = queryDB(
    "SELECT l.*, u.name AS seller_name, u.profile_image AS seller_avatar,
            c.name AS category_name, c.slug AS category_slug,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     $whereSQL
     $orderSQL
     LIMIT $perPage OFFSET $offset",
    $params
);

function filterUrl(array $overrides = []): string {
    $params = array_merge([
        'q'         => $_GET['q']        ?? '',
        'category'  => $_GET['category'] ?? '',
        'condition' => $_GET['condition'] ?? '',
        'sort'      => $_GET['sort']      ?? 'newest',
        'page'      => 1,
    ], $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return '/tuelo-main/listings.php?' . http_build_query($params);
}
?>

<div style="background:var(--tuelo-green-light);padding:25px 0;border-bottom:1px solid hsl(152,51%,80%);">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;">

      <div>
        <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--tuelo-green-dark);margin-bottom:4px;">
          <?php if ($activeCat): ?>
            <?= htmlspecialchars($activeCat['name']) ?>
          <?php elseif ($search): ?>
            Results for "<?= htmlspecialchars($search) ?>"
          <?php else: ?>
            All Listings
          <?php endif; ?>
        </h1>
        <p style="font-size:var(--fs-8);color:var(--sonic-silver);">
          <?= number_format($total) ?> listing<?= $total !== 1 ? 's' : '' ?> found
        </p>
      </div>

      <form method="GET" action="/tuelo-main/listings.php" id="sortForm">
        <?php if ($search):    ?><input type="hidden" name="q"         value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
        <?php if ($catSlug):   ?><input type="hidden" name="category"  value="<?= htmlspecialchars($catSlug) ?>"><?php endif; ?>
        <?php if ($condition): ?><input type="hidden" name="condition" value="<?= htmlspecialchars($condition) ?>"><?php endif; ?>
        <select name="sort" onchange="document.getElementById('sortForm').submit()"
                style="padding:8px 14px;border:1.5px solid var(--cultured);border-radius:var(--border-radius-sm);
                       font-family:inherit;font-size:var(--fs-8);color:var(--onyx);cursor:pointer;">
          <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest first</option>
          <option value="oldest"     <?= $sort === 'oldest'     ? 'selected' : '' ?>>Oldest first</option>
          <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
      </form>

    </div>

    <?php if ($catSlug || $condition || $search): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
      <?php if ($activeCat): ?>
        <a href="<?= filterUrl(['category' => '']) ?>"
           style="display:inline-flex;align-items:center;gap:5px;background:var(--tuelo-green-dark);
                  color:#fff;padding:4px 12px;border-radius:20px;font-size:var(--fs-9);font-weight:500;">
          <?= htmlspecialchars($activeCat['name']) ?>
          <ion-icon name="close-outline" style="font-size:14px;"></ion-icon>
        </a>
      <?php endif; ?>
      <?php if ($condition): ?>
        <a href="<?= filterUrl(['condition' => '']) ?>"
           style="display:inline-flex;align-items:center;gap:5px;background:var(--tuelo-gold);
                  color:#fff;padding:4px 12px;border-radius:20px;font-size:var(--fs-9);font-weight:500;">
          <?= ucfirst($condition) ?>
          <ion-icon name="close-outline" style="font-size:14px;"></ion-icon>
        </a>
      <?php endif; ?>
      <?php if ($search): ?>
        <a href="<?= filterUrl(['q' => '']) ?>"
           style="display:inline-flex;align-items:center;gap:5px;background:var(--davys-gray);
                  color:#fff;padding:4px 12px;border-radius:20px;font-size:var(--fs-9);font-weight:500;">
          "<?= htmlspecialchars($search) ?>"
          <ion-icon name="close-outline" style="font-size:14px;"></ion-icon>
        </a>
      <?php endif; ?>
      <a href="/tuelo-main/listings.php"
         style="display:inline-flex;align-items:center;gap:5px;background:transparent;
                color:var(--sonic-silver);padding:4px 12px;border-radius:20px;
                font-size:var(--fs-9);border:1px solid var(--cultured);">
        Clear all
        <ion-icon name="close-outline" style="font-size:14px;"></ion-icon>
      </a>
    </div>
    <?php endif; ?>

  </div>
</div>

<div class="product-container" style="padding-top:30px;">
  <div class="container">

    <div class="sidebar">

      <div class="sidebar-category">
        <div class="sidebar-top">
          <h2 class="sidebar-title">Categories</h2>
        </div>
        <ul class="sidebar-menu-category-list">
          <li class="sidebar-menu-category">
            <a href="/tuelo-main/listings.php"
               style="display:block;padding:8px 0;font-size:var(--fs-7);
                      color:<?= !$catSlug ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;
                      font-weight:<?= !$catSlug ? '600' : '400' ?>;">
              All Categories
            </a>
          </li>
          <?php foreach ($categories as $cat): ?>
          <li class="sidebar-menu-category">
            <a href="<?= filterUrl(['category' => $cat['slug'], 'page' => 1]) ?>"
               style="display:block;padding:8px 0;font-size:var(--fs-7);
                      color:<?= $catSlug === $cat['slug'] ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;
                      font-weight:<?= $catSlug === $cat['slug'] ? '600' : '400' ?>;
                      transition:var(--transition-timing);">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="sidebar-category">
        <div class="sidebar-top">
          <h2 class="sidebar-title">Condition</h2>
        </div>
        <ul style="padding:10px 0;">
          <?php foreach (['new' => 'New', 'used' => 'Used', 'refurbished' => 'Refurbished'] as $val => $label): ?>
          <li style="padding:6px 0;">
            <a href="<?= filterUrl(['condition' => $condition === $val ? '' : $val, 'page' => 1]) ?>"
               style="display:flex;align-items:center;gap:8px;font-size:var(--fs-7);
                      color:<?= $condition === $val ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;
                      font-weight:<?= $condition === $val ? '600' : '400' ?>;">
              <ion-icon name="<?= $condition === $val ? 'checkbox-outline' : 'square-outline' ?>"
                        style="font-size:16px;"></ion-icon>
              <?= $label ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>

    <div class="product-box">
      <div class="product-main">

        <?php if (empty($listings)): ?>
          <div style="text-align:center;padding:80px 0;color:var(--sonic-silver);">
            <ion-icon name="search-outline"
                      style="font-size:70px;color:var(--cultured);display:block;margin:0 auto 20px;">
            </ion-icon>
            <h3 style="font-size:var(--fs-3);font-weight:600;color:var(--onyx);margin-bottom:10px;">
              No listings found
            </h3>
            <p style="font-size:var(--fs-7);margin-bottom:25px;">
              Try adjusting your filters or search term.
            </p>
            <a href="/tuelo-main/listings.php"
               style="display:inline-block;background:var(--tuelo-green-dark);color:#fff;
                      padding:10px 25px;border-radius:var(--border-radius-sm);font-weight:600;">
              View all listings
            </a>
          </div>

        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($listings as $listing): ?>
            <div class="showcase">
              <div class="showcase-banner">
                <img src="<?= $listing['cover_img']
                    ? htmlspecialchars($listing['cover_img'])
                    : '/tuelo-main/assets/img/no-image.png' ?>"
                     alt="<?= htmlspecialchars($listing['title']) ?>"
                     class="product-img default" />

                <p class="showcase-badge <?= $listing['condition_type'] ?>">
                  <?= ucfirst($listing['condition_type']) ?>
                </p>

                <div class="showcase-actions">
                  <a href="/tuelo-main/product.php?id=<?= $listing['id'] ?>"
                     class="btn-action" title="View listing">
                    <ion-icon name="eye-outline"></ion-icon>
                  </a>
                  <?php if ($currentUser && hasPermission('send_message')): ?>
                  <a href="/tuelo-main/messages.php?listing=<?= $listing['id'] ?>"
                     class="btn-action" title="Message seller">
                    <ion-icon name="chatbubble-outline"></ion-icon>
                  </a>
                  <?php endif; ?>
                  <?php if ($currentUser && hasPermission('purchase_item')): ?>
                  <a href="/tuelo-main/product.php?id=<?= $listing['id'] ?>#buy"
                     class="btn-action" title="Buy now">
                    <ion-icon name="bag-handle-outline"></ion-icon>
                  </a>
                  <?php endif; ?>
                </div>
              </div>

              <div class="showcase-content">
                <a href="<?= filterUrl(['category' => $listing['category_slug']]) ?>"
                   class="showcase-category">
                  <?= htmlspecialchars($listing['category_name']) ?>
                </a>
                <a href="/tuelo-main/product.php?id=<?= $listing['id'] ?>">
                  <h3 class="showcase-title"><?= htmlspecialchars($listing['title']) ?></h3>
                </a>
                <div class="price-box">
                  <p class="price">R <?= number_format($listing['price'], 2) ?></p>
                </div>
                <p class="seller-name" style="display:flex;align-items:center;gap:6px;margin-top:6px;">
                    <img src="<?= $listing['seller_avatar']
                        ? htmlspecialchars($listing['seller_avatar'])
                        : '/tuelo-main/assets/img/default-avatar.png' ?>"
                        style="width:22px;height:22px;border-radius:50%;object-fit:cover;border:1px solid var(--cultured);"
                        alt="<?= htmlspecialchars($listing['seller_name']) ?>" />
                    <?= htmlspecialchars($listing['seller_name']) ?>
                </p>
                <?php if ($listing['location']): ?>
                  <?= htmlspecialchars($listing['location']) ?>
                </p>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
          <div style="display:flex;justify-content:center;align-items:center;
                      gap:6px;margin-top:40px;flex-wrap:wrap;">

            <?php if ($page > 1): ?>
            <a href="<?= filterUrl(['page' => $page - 1]) ?>"
               style="display:inline-flex;align-items:center;gap:4px;padding:8px 16px;
                      border:1.5px solid var(--cultured);border-radius:var(--border-radius-sm);
                      font-size:var(--fs-8);color:var(--onyx);transition:var(--transition-timing);"
               onmouseover="this.style.borderColor='var(--tuelo-green)';this.style.color='var(--tuelo-green-dark)'"
               onmouseout="this.style.borderColor='var(--cultured)';this.style.color='var(--onyx)'">
              <ion-icon name="chevron-back-outline"></ion-icon> Prev
            </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="<?= filterUrl(['page' => $i]) ?>"
               style="display:inline-flex;align-items:center;justify-content:center;
                      width:38px;height:38px;border-radius:var(--border-radius-sm);
                      font-size:var(--fs-8);font-weight:<?= $i === $page ? '600' : '400' ?>;
                      border:1.5px solid <?= $i === $page ? 'var(--tuelo-green-dark)' : 'var(--cultured)' ?>;
                      background:<?= $i === $page ? 'var(--tuelo-green-dark)' : 'transparent' ?>;
                      color:<?= $i === $page ? '#fff' : 'var(--onyx)' ?>;
                      transition:var(--transition-timing);">
              <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="<?= filterUrl(['page' => $page + 1]) ?>"
               style="display:inline-flex;align-items:center;gap:4px;padding:8px 16px;
                      border:1.5px solid var(--cultured);border-radius:var(--border-radius-sm);
                      font-size:var(--fs-8);color:var(--onyx);transition:var(--transition-timing);"
               onmouseover="this.style.borderColor='var(--tuelo-green)';this.style.color='var(--tuelo-green-dark)'"
               onmouseout="this.style.borderColor='var(--cultured)';this.style.color='var(--onyx)'">
              Next <ion-icon name="chevron-forward-outline"></ion-icon>
            </a>
            <?php endif; ?>

          </div>

          <p style="text-align:center;font-size:var(--fs-9);color:var(--sonic-silver);margin-top:10px;">
            Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= number_format($total) ?> listings
          </p>
          <?php endif; ?>

        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
