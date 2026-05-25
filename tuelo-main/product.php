<?php
$pageTitle = 'Listing';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: /tuelo-main/listings.php');
    exit;
}

//  Fetch listing with seller and category info 
$listing = singleQueryDB(
    'SELECT l.*, 
            u.id AS seller_id, u.name AS seller_name, u.surname AS seller_surname,
            u.profile_image AS seller_avatar, u.rating_average AS seller_rating,
            u.created_at AS seller_joined,
            c.name AS category_name, c.slug AS category_slug
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     WHERE l.id = ? AND l.status = "active"',
    [$id]
);

if (!$listing) {
    header('Location: /tuelo-main/404.php');
    exit;
}

$pageTitle = htmlspecialchars($listing['title']);

//  Fetch all images for this listing 
$images = queryDB(
    'SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_main_image DESC',
    [$id]
);

//  Fetch seller review count 
$reviewCount = singleQueryDB(
    'SELECT COUNT(*) AS total FROM reviews WHERE reviewee_id = ?',
    [$listing['seller_id']]
);

//  Fetch related listings (same category, exclude current) 
$related = queryDB(
    'SELECT l.*, c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN categories c ON l.category_id = c.id
     WHERE l.category_id = ? AND l.id != ? AND l.status = "active"
     ORDER BY l.created_at DESC
     LIMIT 4',
    [$listing['category_id'], $id]
);

//  Determine cover image 
$coverImg = '/tuelo-main/assets/img/no-image.png';
foreach ($images as $img) {
    if ($img['is_main_image']) {
        $coverImg = $img['image_path'];
        break;
    }
}
if ($coverImg === '/tuelo-main/assets/img/no-image.png' && !empty($images)) {
    $coverImg = $images[0]['image_path'];
}
?>

<!--  PRODUCT DETAIL  -->
<div style="padding:40px 0;">
  <div class="container">
    <div style="display:flex;gap:40px;align-items:flex-start;flex-wrap:wrap;">

      <!--  LEFT: Images  -->
      <div style="flex:1;min-width:300px;max-width:520px;">

        <!-- Main image -->
        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);
                    overflow:hidden;margin-bottom:12px;background:var(--cultured);">
          <img id="mainImage"
               src="<?= htmlspecialchars($coverImg) ?>"
               alt="<?= htmlspecialchars($listing['title']) ?>"
               style="width:100%;height:420px;object-fit:cover;display:block;" />
        </div>

        <!-- Thumbnail strip -->
        <?php if (count($images) > 1): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <?php foreach ($images as $img): ?>
          <div onclick="document.getElementById('mainImage').src='<?= htmlspecialchars($img['image_path']) ?>'"
               style="width:80px;height:80px;border:2px solid var(--cultured);
                      border-radius:var(--border-radius-sm);overflow:hidden;cursor:pointer;
                      transition:border-color 0.2s ease;">
            <img src="<?= htmlspecialchars($img['image_path']) ?>"
                 style="width:100%;height:100%;object-fit:cover;"
                 onmouseover="this.parentElement.style.borderColor='var(--tuelo-green)'"
                 onmouseout="this.parentElement.style.borderColor='var(--cultured)'" />
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>

      <!--  RIGHT: Details  -->
      <div style="flex:1;min-width:280px;">

        <!-- Category + condition -->
        <div style="display:flex;gap:8px;margin-bottom:12px;">
          <a href="/tuelo-main/listings.php?category=<?= urlencode($listing['category_slug']) ?>"
             style="font-size:var(--fs-9);font-weight:600;color:var(--tuelo-green);
                    text-transform:uppercase;letter-spacing:0.5px;">
            <?= htmlspecialchars($listing['category_name']) ?>
          </a>
          <span style="color:var(--cultured);">|</span>
          <span style="font-size:var(--fs-9);padding:2px 10px;border-radius:20px;font-weight:600;
                background:<?= $listing['condition_type'] === 'new' ? 'var(--tuelo-green)' : ($listing['condition_type'] === 'refurbished' ? 'var(--davys-gray)' : 'var(--sandy-brown)') ?>;
                color:#fff;">
            <?= ucfirst($listing['condition_type']) ?>
          </span>
        </div>

        <!-- Title -->
        <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--eerie-black);
                   line-height:1.3;margin-bottom:16px;">
          <?= htmlspecialchars($listing['title']) ?>
        </h1>

        <!-- Price -->
        <div style="margin-bottom:20px;">
          <p style="font-size:2rem;font-weight:800;color:var(--tuelo-green-dark);">
            R <?= number_format($listing['price'], 2) ?>
          </p>
        </div>

        <!-- Location -->
        <?php if ($listing['location']): ?>
        <p style="font-size:var(--fs-8);color:var(--sonic-silver);margin-bottom:16px;
                  display:flex;align-items:center;gap:5px;">
          <ion-icon name="location-outline" style="font-size:16px;"></ion-icon>
          <?= htmlspecialchars($listing['location']) ?>
        </p>
        <?php endif; ?>

        <!-- Description -->
        <div style="margin-bottom:24px;">
          <h3 style="font-size:var(--fs-7);font-weight:600;color:var(--onyx);margin-bottom:8px;">
            Description
          </h3>
          <p style="font-size:var(--fs-7);color:var(--sonic-silver);line-height:1.8;">
            <?= nl2br(htmlspecialchars($listing['description'])) ?>
          </p>
        </div>

        <!-- Date posted -->
        <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-bottom:24px;">
          Posted <?= date('d M Y', strtotime($listing['created_at'])) ?>
        </p>

        <!-- Action buttons -->
        <div id="buy" style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">

          <?php if (!$currentUser): ?>
            <!-- Not logged in -->
            <a href="/tuelo-main/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
               style="display:flex;align-items:center;justify-content:center;gap:8px;
                      background:var(--tuelo-green-dark);color:#fff;padding:14px;
                      border-radius:var(--border-radius-sm);font-weight:600;font-size:var(--fs-7);
                      transition:var(--transition-timing);"
               onmouseover="this.style.background='var(--tuelo-green)'"
               onmouseout="this.style.background='var(--tuelo-green-dark)'">
              <ion-icon name="log-in-outline" style="font-size:20px;"></ion-icon>
              Login to Buy or Message
            </a>

          <?php elseif ($currentUser['id'] === $listing['seller_id']): ?>
            <!-- Own listing -->
            <div style="background:var(--tuelo-green-light);border:1px solid hsl(152,51%,70%);
                        border-radius:var(--border-radius-sm);padding:14px;text-align:center;">
              <p style="font-size:var(--fs-7);color:var(--tuelo-green-dark);font-weight:500;">
                This is your listing
              </p>
            </div>

          <?php else: ?>
            <!-- Logged in, not own listing -->
            <?php if (hasPermission('purchase_item')): ?>
            <a href="/tuelo-main/checkout.php?listing=<?= $listing['id'] ?>"
               style="display:flex;align-items:center;justify-content:center;gap:8px;
                      background:var(--tuelo-green-dark);color:#fff;padding:14px;
                      border-radius:var(--border-radius-sm);font-weight:600;font-size:var(--fs-7);
                      transition:var(--transition-timing);"
               onmouseover="this.style.background='var(--tuelo-green)'"
               onmouseout="this.style.background='var(--tuelo-green-dark)'">
              <ion-icon name="bag-handle-outline" style="font-size:20px;"></ion-icon>
              Buy Now — R <?= number_format($listing['price'], 2) ?>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('send_message')): ?>
            <a href="/tuelo-main/messages.php?listing=<?= $listing['id'] ?>&seller=<?= $listing['seller_id'] ?>"
               style="display:flex;align-items:center;justify-content:center;gap:8px;
                      border:1.5px solid var(--tuelo-green-dark);color:var(--tuelo-green-dark);
                      padding:14px;border-radius:var(--border-radius-sm);
                      font-weight:600;font-size:var(--fs-7);transition:var(--transition-timing);"
               onmouseover="this.style.background='var(--tuelo-green-light)'"
               onmouseout="this.style.background='transparent'">
              <ion-icon name="chatbubble-outline" style="font-size:20px;"></ion-icon>
              Message Seller
            </a>
            <?php endif; ?>

          <?php endif; ?>

        </div>

        <!--  Seller card  -->
        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);padding:20px;">
          <h3 style="font-size:var(--fs-8);font-weight:600;text-transform:uppercase;
                     letter-spacing:0.5px;color:var(--sonic-silver);margin-bottom:15px;">
            Seller
          </h3>
          <div style="display:flex;align-items:center;gap:14px;">
            <img src="<?= $listing['seller_avatar']
                ? htmlspecialchars($listing['seller_avatar'])
                : '/tuelo-main/assets/img/default-avatar.png' ?>"
                 style="width:52px;height:52px;border-radius:50%;object-fit:cover;
                        border:2px solid var(--cultured);" />
            <div>
              <p style="font-weight:600;font-size:var(--fs-6);color:var(--onyx);">
                <?= htmlspecialchars($listing['seller_name'] . ' ' . $listing['seller_surname']) ?>
              </p>
              <!-- Star rating -->
              <div style="display:flex;align-items:center;gap:4px;margin:4px 0;">
                <?php
                $rating = round($listing['seller_rating'] * 2) / 2;
                for ($i = 1; $i <= 5; $i++):
                    if ($rating >= $i):
                ?>
                  <ion-icon name="star" style="font-size:13px;color:var(--sandy-brown);"></ion-icon>
                <?php elseif ($rating >= $i - 0.5): ?>
                  <ion-icon name="star-half" style="font-size:13px;color:var(--sandy-brown);"></ion-icon>
                <?php else: ?>
                  <ion-icon name="star-outline" style="font-size:13px;color:var(--sandy-brown);"></ion-icon>
                <?php endif; endfor; ?>
                <span style="font-size:var(--fs-9);color:var(--sonic-silver);margin-left:4px;">
                  (<?= (int)$reviewCount['total'] ?> review<?= $reviewCount['total'] != 1 ? 's' : '' ?>)
                </span>
              </div>
              <p style="font-size:var(--fs-9);color:var(--sonic-silver);">
                Member since <?= date('M Y', strtotime($listing['seller_joined'])) ?>
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!--  RELATED LISTINGS  -->
<?php if (!empty($related)): ?>
<div style="padding:0 0 50px;">
  <div class="container">
    <h2 class="title">More in <?= htmlspecialchars($listing['category_name']) ?></h2>
    <div class="product-grid">
      <?php foreach ($related as $item): ?>
      <div class="showcase">
        <div class="showcase-banner">
          <img src="<?= $item['cover_img']
              ? htmlspecialchars($item['cover_img'])
              : '/tuelo-main/assets/img/no-image.png' ?>"
               alt="<?= htmlspecialchars($item['title']) ?>"
               class="product-img default" />
          <p class="showcase-badge <?= $item['condition_type'] ?>">
            <?= ucfirst($item['condition_type']) ?>
          </p>
          <div class="showcase-actions">
            <a href="/tuelo-main/product.php?id=<?= $item['id'] ?>" class="btn-action" title="View">
              <ion-icon name="eye-outline"></ion-icon>
            </a>
          </div>
        </div>
        <div class="showcase-content">
          <span class="showcase-category"><?= htmlspecialchars($item['category_name']) ?></span>
          <a href="/tuelo-main/product.php?id=<?= $item['id'] ?>">
            <h3 class="showcase-title"><?= htmlspecialchars($item['title']) ?></h3>
          </a>
          <div class="price-box">
            <p class="price">R <?= number_format($item['price'], 2) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>