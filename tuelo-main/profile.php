<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('/tuelo-main/login.php');

$tab    = $_GET['tab'] ?? 'listings';
$userId = $currentUser['id'];

$myListings = queryDB(
    'SELECT l.*, c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN categories c ON l.category_id = c.id
     WHERE l.seller_id = ?
     ORDER BY l.created_at DESC',
    [$userId]
);

$myPurchases = queryDB(
    'SELECT t.*, l.title AS listing_title, l.price,
            u.name AS seller_name, u.surname AS seller_surname,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN users u ON t.seller_id = u.id
     WHERE t.buyer_id = ?
     ORDER BY t.created_at DESC',
    [$userId]
);

$myReviews = queryDB(
    'SELECT r.*, u.name AS reviewer_name, u.surname AS reviewer_surname,
            u.profile_image AS reviewer_avatar, l.title AS listing_title
     FROM reviews r
     JOIN users u ON r.reviewer_id = u.id
     JOIN listings l ON r.listing_id = l.id
     WHERE r.reviewee_id = ?
     ORDER BY r.created_at DESC',
    [$userId]
);

$updateSuccess = '';
$updateError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name']    ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $phone   = trim($_POST['phone_number'] ?? '');

    if (empty($name) || empty($surname)) {
        $updateError = 'Name and surname are required.';
    } else {
        updateDB(
            'UPDATE users SET name = ?, surname = ?, phone_number = ?, updated_at = NOW() WHERE id = ?',
            [$name, $surname, $phone ?: null, $userId]
        );
        unset($_SESSION['user_cache']);
        $updateSuccess = 'Profile updated successfully.';
        $currentUser = getUser();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file     = $_FILES['profile_image'];
    $allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize  = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $updateError = 'Upload failed. Please try again.';
    } elseif (!in_array($file['type'], $allowed)) {
        $updateError = 'Only JPG, PNG and WEBP images are allowed.';
    } elseif ($file['size'] > $maxSize) {
        $updateError = 'Image must be under 5MB.';
    } else {
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $oldAvatar = $currentUser['profile_image'] ?? '';
        if ($oldAvatar && str_contains($oldAvatar, '/uploads/avatars/')) {
            $oldPath = __DIR__ . str_replace('/tuelo-main', '', $oldAvatar);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar-' . $userId . '-' . time() . '.' . $ext;
        $savePath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $savePath)) {
            $dbPath = '/tuelo-main/uploads/avatars/' . $filename;
            updateDB(
                'UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?',
                [$dbPath, $userId]
            );
            unset($_SESSION['user_cache']);
            $currentUser    = getUser();
            $updateSuccess  = 'Profile picture updated successfully.';
        } else {
            $updateError = 'Could not save the image. Please try again.';
        }
    }
}
?>

<div style="padding:40px 0 60px;">
  <div class="container">
    <div class="profile-layout">

      <div class="profile-sidebar">

        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);
                    padding:25px;text-align:center;margin-bottom:20px;">

          <img src="<?= $currentUser['profile_image']
              ? htmlspecialchars($currentUser['profile_image'])
              : '/tuelo-main/assets/img/default-avatar.png' ?>"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                      border:3px solid var(--tuelo-green-light);margin:0 auto 12px;" />

          <h2 style="font-size:var(--fs-5);font-weight:700;color:var(--eerie-black);margin-bottom:2px;">
            <?= htmlspecialchars($currentUser['name'] . ' ' . $currentUser['surname']) ?>
          </h2>
          <p style="font-size:var(--fs-9);color:var(--tuelo-green);font-weight:500;
                    text-transform:capitalize;margin-bottom:8px;">
            <?= htmlspecialchars($currentUser['role_name']) ?>
          </p>

          <div style="display:flex;justify-content:center;align-items:center;gap:3px;margin-bottom:6px;">
            <?php
            $rating = round($currentUser['rating_average'] * 2) / 2;
            for ($i = 1; $i <= 5; $i++):
                if ($rating >= $i):
            ?>
              <ion-icon name="star" style="font-size:14px;color:var(--sandy-brown);"></ion-icon>
            <?php elseif ($rating >= $i - 0.5): ?>
              <ion-icon name="star-half" style="font-size:14px;color:var(--sandy-brown);"></ion-icon>
            <?php else: ?>
              <ion-icon name="star-outline" style="font-size:14px;color:var(--sandy-brown);"></ion-icon>
            <?php endif; endfor; ?>
            <span style="font-size:var(--fs-9);color:var(--sonic-silver);margin-left:3px;">
              (<?= count($myReviews) ?>)
            </span>
          </div>

          <p style="font-size:var(--fs-9);color:var(--sonic-silver);">
            Member since <?= date('M Y', strtotime($currentUser['created_at'])) ?>
          </p>
        </div>

        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);overflow:hidden;">
          <?php
          $tabs = [
              'listings'  => ['icon' => 'grid-outline',        'label' => 'My Listings',  'count' => count($myListings)],
              'purchases' => ['icon' => 'bag-handle-outline',   'label' => 'My Purchases', 'count' => count($myPurchases)],
              'reviews'   => ['icon' => 'star-outline',         'label' => 'My Reviews',   'count' => count($myReviews)],
              'settings'  => ['icon' => 'settings-outline',     'label' => 'Settings',     'count' => null],
          ];
          foreach ($tabs as $key => $t):
              $active = $tab === $key;
          ?>
          <a href="/tuelo-main/profile.php?tab=<?= $key ?>"
             style="display:flex;align-items:center;justify-content:space-between;
                    padding:13px 16px;border-bottom:1px solid var(--cultured);
                    background:<?= $active ? 'var(--tuelo-green-light)' : 'transparent' ?>;
                    color:<?= $active ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;
                    font-size:var(--fs-7);font-weight:<?= $active ? '600' : '400' ?>;
                    transition:var(--transition-timing);">
            <span style="display:flex;align-items:center;gap:8px;">
              <ion-icon name="<?= $t['icon'] ?>" style="font-size:18px;"></ion-icon>
              <?= $t['label'] ?>
            </span>
            <?php if ($t['count'] !== null): ?>
            <span style="background:<?= $active ? 'var(--tuelo-green-dark)' : 'var(--cultured)' ?>;
                         color:<?= $active ? '#fff' : 'var(--sonic-silver)' ?>;
                         font-size:var(--fs-10);padding:2px 8px;border-radius:20px;">
              <?= $t['count'] ?>
            </span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if (hasPermission('create_listing')): ?>
        <a href="/tuelo-main/sell.php"
           style="display:flex;align-items:center;justify-content:center;gap:8px;
                  background:var(--tuelo-gold);color:#fff;padding:12px;
                  border-radius:var(--border-radius-sm);font-weight:600;
                  font-size:var(--fs-7);margin-top:15px;transition:var(--transition-timing);"
           onmouseover="this.style.background='var(--tuelo-gold-dark)'"
           onmouseout="this.style.background='var(--tuelo-gold)'">
          <ion-icon name="add-circle-outline" style="font-size:20px;"></ion-icon>
          Post a Listing
        </a>
        <?php endif; ?>

      </div>

      <div class="profile-content">

        <?php if ($updateSuccess): ?>
          <div class="alert alert-success" style="margin-bottom:20px;"><?= $updateSuccess ?></div>
        <?php endif; ?>
        <?php if ($updateError): ?>
          <div class="alert alert-danger" style="margin-bottom:20px;"><?= $updateError ?></div>
        <?php endif; ?>

        <?php if ($tab === 'listings'): ?>
        <h2 style="font-size:var(--fs-3);font-weight:700;color:var(--eerie-black);margin-bottom:20px;">
          My Listings
        </h2>

        <?php if (empty($myListings)): ?>
          <div style="text-align:center;padding:60px 0;color:var(--sonic-silver);">
            <ion-icon name="cube-outline" style="font-size:60px;color:var(--cultured);display:block;margin:0 auto 15px;"></ion-icon>
            <p style="font-size:var(--fs-6);margin-bottom:15px;">You haven't posted any listings yet.</p>
            <?php if (hasPermission('create_listing')): ?>
            <a href="/tuelo-main/sell.php"
               style="display:inline-block;background:var(--tuelo-green-dark);color:#fff;
                      padding:10px 25px;border-radius:var(--border-radius-sm);font-weight:600;">
              Post your first listing
            </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:15px;">
            <?php foreach ($myListings as $item): ?>
            <div class="profile-listing-row"
                 onmouseover="this.style.boxShadow='0 3px 12px hsla(0,0%,0%,0.08)'"
                 onmouseout="this.style.boxShadow='none'">

              <img src="<?= $item['cover_img']
                  ? htmlspecialchars($item['cover_img'])
                  : '/tuelo-main/assets/img/no-image.png' ?>"
                   style="width:110px;height:110px;object-fit:cover;flex-shrink:0;" />

              <div style="padding:14px;flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                  <div style="min-width:0;">
                    <a href="/tuelo-main/product.php?id=<?= $item['id'] ?>"
                       style="font-size:var(--fs-6);font-weight:600;color:var(--onyx);
                              display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      <?= htmlspecialchars($item['title']) ?>
                    </a>
                    <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin:3px 0;">
                      <?= htmlspecialchars($item['category_name']) ?> &middot;
                      <?= ucfirst($item['condition_type']) ?>
                    </p>
                    <p style="font-size:var(--fs-6);font-weight:700;color:var(--tuelo-green-dark);">
                      R <?= number_format($item['price'], 2) ?>
                    </p>
                  </div>
                  <span style="font-size:var(--fs-9);padding:3px 10px;border-radius:20px;flex-shrink:0;
                               font-weight:600;white-space:nowrap;
                               background:<?= $item['status'] === 'active' ? 'var(--tuelo-green-light)' : 'var(--cultured)' ?>;
                               color:<?= $item['status'] === 'active' ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;">
                    <?= ucfirst($item['status']) ?>
                  </span>
                </div>
                <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-top:6px;">
                  Posted <?= date('d M Y', strtotime($item['created_at'])) ?>
                </p>
              </div>

            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php elseif ($tab === 'purchases'): ?>
        <h2 style="font-size:var(--fs-3);font-weight:700;color:var(--eerie-black);margin-bottom:20px;">
          My Purchases
        </h2>

        <?php if (empty($myPurchases)): ?>
          <div style="text-align:center;padding:60px 0;color:var(--sonic-silver);">
            <ion-icon name="bag-handle-outline" style="font-size:60px;color:var(--cultured);display:block;margin:0 auto 15px;"></ion-icon>
            <p style="font-size:var(--fs-6);margin-bottom:15px;">You haven't made any purchases yet.</p>
            <a href="/tuelo-main/listings.php"
               style="display:inline-block;background:var(--tuelo-green-dark);color:#fff;
                      padding:10px 25px;border-radius:var(--border-radius-sm);font-weight:600;">
              Browse listings
            </a>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:15px;">
            <?php foreach ($myPurchases as $purchase): ?>
            <div style="display:flex;gap:15px;border:1px solid var(--cultured);
                        border-radius:var(--border-radius-md);overflow:hidden;">
              <img src="<?= $purchase['cover_img']
                  ? htmlspecialchars($purchase['cover_img'])
                  : '/tuelo-main/assets/img/no-image.png' ?>"
                   style="width:110px;height:110px;object-fit:cover;flex-shrink:0;" />
              <div style="padding:14px;flex:1;">
                <p style="font-size:var(--fs-6);font-weight:600;color:var(--onyx);margin-bottom:4px;">
                  <?= htmlspecialchars($purchase['listing_title']) ?>
                </p>
                <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-bottom:4px;">
                  Seller: <?= htmlspecialchars($purchase['seller_name'] . ' ' . $purchase['seller_surname']) ?>
                </p>
                <p style="font-size:var(--fs-6);font-weight:700;color:var(--tuelo-green-dark);margin-bottom:6px;">
                  R <?= number_format($purchase['amount'], 2) ?>
                </p>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                  <span style="font-size:var(--fs-9);padding:3px 10px;border-radius:20px;font-weight:600;
                               background:<?= $purchase['status'] === 'completed' ? 'var(--tuelo-green-light)' : 'var(--cultured)' ?>;
                               color:<?= $purchase['status'] === 'completed' ? 'var(--tuelo-green-dark)' : 'var(--sonic-silver)' ?>;">
                    <?= ucfirst($purchase['status']) ?>
                  </span>
                  <p style="font-size:var(--fs-9);color:var(--sonic-silver);">
                    <?= date('d M Y', strtotime($purchase['created_at'])) ?>
                  </p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php elseif ($tab === 'reviews'): ?>
        <h2 style="font-size:var(--fs-3);font-weight:700;color:var(--eerie-black);margin-bottom:20px;">
          Reviews
        </h2>

        <?php if (empty($myReviews)): ?>
          <div style="text-align:center;padding:60px 0;color:var(--sonic-silver);">
            <ion-icon name="star-outline" style="font-size:60px;color:var(--cultured);display:block;margin:0 auto 15px;"></ion-icon>
            <p style="font-size:var(--fs-6);">No reviews yet.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:15px;">
            <?php foreach ($myReviews as $review): ?>
            <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);padding:18px;">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <img src="<?= $review['reviewer_avatar']
                    ? htmlspecialchars($review['reviewer_avatar'])
                    : '/tuelo-main/assets/img/default-avatar.png' ?>"
                     style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />
                <div>
                  <p style="font-weight:600;font-size:var(--fs-7);color:var(--onyx);">
                    <?= htmlspecialchars($review['reviewer_name'] . ' ' . $review['reviewer_surname']) ?>
                  </p>
                  <div style="display:flex;gap:2px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <ion-icon name="<?= $i <= $review['rating'] ? 'star' : 'star-outline' ?>"
                              style="font-size:12px;color:var(--sandy-brown);"></ion-icon>
                    <?php endfor; ?>
                  </div>
                </div>
                <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-left:auto;">
                  <?= date('d M Y', strtotime($review['created_at'])) ?>
                </p>
              </div>
              <p style="font-size:var(--fs-8);color:var(--sonic-silver);margin-bottom:6px;">
                Re: <?= htmlspecialchars($review['listing_title']) ?>
              </p>
              <?php if ($review['review_message']): ?>
              <p style="font-size:var(--fs-7);color:var(--onyx);line-height:1.7;">
                <?= htmlspecialchars($review['review_message']) ?>
              </p>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php elseif ($tab === 'settings'): ?>
        <h2 style="font-size:var(--fs-3);font-weight:700;color:var(--eerie-black);margin-bottom:20px;">
        Settings
        </h2>

        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);
                    padding:25px;max-width:480px;margin-bottom:20px;">
        <h3 style="font-size:var(--fs-7);font-weight:600;color:var(--onyx);margin-bottom:16px;">
            Profile Picture
        </h3>
        <div style="display:flex;align-items:center;gap:20px;">
            <img id="avatarPreview"
                src="<?= $currentUser['profile_image']
                    ? htmlspecialchars($currentUser['profile_image'])
                    : '/tuelo-main/assets/img/default-avatar.png' ?>"
                style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                        border:3px solid var(--tuelo-green-light);flex-shrink:0;" />
            <div style="flex:1;">
            <form method="POST" action="/tuelo-main/profile.php?tab=settings"
                    enctype="multipart/form-data">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;
                            background:var(--tuelo-green-dark);color:#fff;
                            padding:9px 18px;border-radius:var(--border-radius-sm);
                            font-size:var(--fs-8);font-weight:600;transition:var(--transition-timing);"
                    onmouseover="this.style.background='var(--tuelo-green)'"
                    onmouseout="this.style.background='var(--tuelo-green-dark)'">
                <ion-icon name="camera-outline" style="font-size:16px;"></ion-icon>
                Choose Photo
                <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp"
                        style="display:none;" onchange="previewAvatar(this);this.form.submit();" />
                </label>
                <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-top:8px;">
                JPG, PNG or WEBP. Max 5MB.
                </p>
            </form>
            </div>
        </div>
        </div>

        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);
                    padding:30px;max-width:480px;">
        <h3 style="font-size:var(--fs-7);font-weight:600;color:var(--onyx);margin-bottom:16px;">
            Personal Details
        </h3>
        <form method="POST" action="/tuelo-main/profile.php?tab=settings">
            <input type="hidden" name="update_profile" value="1" />

            <div class="form-group">
            <label class="form-label">First name</label>
            <input type="text" name="name" class="form-input"
                    value="<?= htmlspecialchars($currentUser['name']) ?>" required />
            </div>

            <div class="form-group">
            <label class="form-label">Last name</label>
            <input type="text" name="surname" class="form-input"
                    value="<?= htmlspecialchars($currentUser['surname']) ?>" required />
            </div>

            <div class="form-group">
            <label class="form-label">Phone number</label>
            <input type="tel" name="phone_number" class="form-input"
                    value="<?= htmlspecialchars($currentUser['phone_number'] ?? '') ?>"
                    placeholder="071 234 5678" />
            </div>

            <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" class="form-input"
                    value="<?= htmlspecialchars($currentUser['email']) ?>"
                    disabled style="background:var(--cultured);cursor:not-allowed;" />
            <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-top:4px;">
                Email cannot be changed.
            </p>
            </div>

            <div class="form-group">
            <label class="form-label">Account type</label>
            <input type="text" class="form-input"
                    value="<?= ucfirst(htmlspecialchars($currentUser['role_name'])) ?>"
                    disabled style="background:var(--cultured);cursor:not-allowed;" />
            </div>

            <button type="submit" class="btn-submit">Save Changes</button>
        </form>
        </div>

        <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                    const headerAvatar = document.querySelector('.tuelo-avatar');
                    if (headerAvatar) headerAvatar.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        </script>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>