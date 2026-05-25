<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('/tuelo-main/login.php');

if (!hasPermission('purchase_item')) {
    header('Location: /tuelo-main/listings.php');
    exit;
}

$listingId = (int)($_GET['listing'] ?? 0);

if (!$listingId) {
    header('Location: /tuelo-main/listings.php');
    exit;
}

// Fetch listing
$listing = singleQueryDB(
    'SELECT l.*, u.id AS seller_id, u.name AS seller_name,
            u.surname AS seller_surname, u.profile_image AS seller_avatar,
            c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     WHERE l.id = ? AND l.status = "active"',
    [$listingId]
);

if (!$listing) {
    header('Location: /tuelo-main/404.php');
    exit;
}

// Can't buy own listing
if ($listing['seller_id'] === $currentUser['id']) {
    header('Location: /tuelo-main/product.php?id=' . $listingId);
    exit;
}

$tueloFee    = round($listing['price'] * 0.05, 2); // 5% platform fee
$totalAmount = $listing['price'] + $tueloFee;
$error       = '';
$success     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {
    // Create transaction
    $transactionId = insertIntoDB(
        'INSERT INTO transactions
            (listing_id, buyer_id, seller_id, amount, tuelo_fee, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, "pending", NOW(), NOW())',
        [
            $listingId,
            $currentUser['id'],
            $listing['seller_id'],
            $listing['price'],
            $tueloFee,
        ]
    );

    if ($transactionId) {
        // Mark listing as sold
        updateDB(
            'UPDATE listings SET status = "sold", updated_at = NOW() WHERE id = ?',
            [$listingId]
        );
        $success = true;
    } else {
        $error = 'Something went wrong. Please try again.';
    }
}
?>

<div style="padding:40px 0 60px;">
  <div class="container">
    <div style="max-width:600px;margin:auto;">

      <?php if ($success): ?>
        <!--  Success state  -->
        <div style="text-align:center;padding:50px 30px;border:1px solid var(--cultured);
                    border-radius:var(--border-radius-md);">
          <ion-icon name="checkmark-circle"
                    style="font-size:70px;color:var(--tuelo-green);display:block;margin:0 auto 16px;">
          </ion-icon>
          <h2 style="font-size:var(--fs-2);font-weight:700;color:var(--eerie-black);margin-bottom:10px;">
            Order placed!
          </h2>
          <p style="font-size:var(--fs-7);color:var(--sonic-silver);margin-bottom:8px;">
            Your purchase of <strong><?= htmlspecialchars($listing['title']) ?></strong>
            has been confirmed.
          </p>
          <p style="font-size:var(--fs-7);color:var(--sonic-silver);margin-bottom:30px;">
            Message the seller to arrange delivery or collection.
          </p>
          <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
            <a href="/tuelo-main/messages.php?seller=<?= $listing['seller_id'] ?>"
               style="display:inline-flex;align-items:center;gap:6px;
                      background:var(--tuelo-green-dark);color:#fff;
                      padding:12px 24px;border-radius:var(--border-radius-sm);
                      font-weight:600;font-size:var(--fs-7);">
              <ion-icon name="chatbubble-outline"></ion-icon>
              Message Seller
            </a>
            <a href="/tuelo-main/profile.php?tab=purchases"
               style="display:inline-flex;align-items:center;gap:6px;
                      border:1.5px solid var(--cultured);color:var(--onyx);
                      padding:12px 24px;border-radius:var(--border-radius-sm);
                      font-weight:600;font-size:var(--fs-7);">
              <ion-icon name="receipt-outline"></ion-icon>
              View Purchases
            </a>
          </div>
        </div>

      <?php else: ?>
        <!--  Checkout form  -->
        <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--eerie-black);margin-bottom:6px;">
          Confirm Purchase
        </h1>
        <p style="font-size:var(--fs-7);color:var(--sonic-silver);margin-bottom:30px;">
          Review the details below before confirming your order.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Listing summary card -->
        <div style="display:flex;gap:16px;border:1px solid var(--cultured);
                    border-radius:var(--border-radius-md);overflow:hidden;margin-bottom:20px;">
          <img src="<?= $listing['cover_img']
              ? htmlspecialchars($listing['cover_img'])
              : '/tuelo-main/assets/img/no-image.png' ?>"
               style="width:120px;height:120px;object-fit:cover;flex-shrink:0;" />
          <div style="padding:16px;flex:1;">
            <p style="font-size:var(--fs-9);color:var(--tuelo-green);font-weight:600;
                      text-transform:uppercase;margin-bottom:4px;">
              <?= htmlspecialchars($listing['category_name']) ?>
            </p>
            <p style="font-size:var(--fs-6);font-weight:600;color:var(--onyx);margin-bottom:6px;">
              <?= htmlspecialchars($listing['title']) ?>
            </p>
            <div style="display:flex;align-items:center;gap:10px;">
              <img src="<?= $listing['seller_avatar']
                  ? htmlspecialchars($listing['seller_avatar'])
                  : '/tuelo-main/assets/img/default-avatar.png' ?>"
                   style="width:24px;height:24px;border-radius:50%;object-fit:cover;" />
              <p style="font-size:var(--fs-9);color:var(--sonic-silver);">
                <?= htmlspecialchars($listing['seller_name'] . ' ' . $listing['seller_surname']) ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Order summary -->
        <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);
                    padding:20px;margin-bottom:20px;">
          <h3 style="font-size:var(--fs-7);font-weight:600;color:var(--onyx);margin-bottom:16px;">
            Order Summary
          </h3>
          <div style="display:flex;justify-content:space-between;
                      font-size:var(--fs-7);color:var(--sonic-silver);padding:8px 0;
                      border-bottom:1px solid var(--cultured);">
            <span>Item price</span>
            <span>R <?= number_format($listing['price'], 2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;
                      font-size:var(--fs-7);color:var(--sonic-silver);padding:8px 0;
                      border-bottom:1px solid var(--cultured);">
            <span>
              Tuelo platform fee (5%)
              <span style="font-size:var(--fs-9);display:block;margin-top:2px;">
                Covers buyer protection and secure payments
              </span>
            </span>
            <span>R <?= number_format($tueloFee, 2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;
                      font-size:var(--fs-5);font-weight:700;
                      color:var(--eerie-black);padding:12px 0;">
            <span>Total</span>
            <span style="color:var(--tuelo-green-dark);">R <?= number_format($totalAmount, 2) ?></span>
          </div>
        </div>

        <!-- Buyer protection notice -->
        <div style="background:var(--tuelo-green-light);border:1px solid hsl(152,51%,70%);
                    border-radius:var(--border-radius-sm);padding:14px;
                    display:flex;gap:12px;align-items:flex-start;margin-bottom:24px;">
          <ion-icon name="shield-checkmark-outline"
                    style="font-size:22px;color:var(--tuelo-green-dark);flex-shrink:0;margin-top:1px;">
          </ion-icon>
          <div>
            <p style="font-size:var(--fs-8);font-weight:600;color:var(--tuelo-green-dark);margin-bottom:3px;">
              Tuelo Buyer Protection
            </p>
            <p style="font-size:var(--fs-9);color:var(--tuelo-green-dark);line-height:1.6;">
              Your order is covered by Tuelo's buyer protection policy.
              If your item doesn't arrive or isn't as described, we'll help resolve it.
            </p>
          </div>
        </div>

        <!-- Confirm button -->
        <form method="POST" action="/tuelo-main/checkout.php?listing=<?= $listingId ?>">
          <input type="hidden" name="confirm_purchase" value="1" />
          <button type="submit" class="btn-submit"
                  style="font-size:var(--fs-6);">
            Confirm Purchase — R <?= number_format($totalAmount, 2) ?>
          </button>
        </form>

        <a href="/tuelo-main/product.php?id=<?= $listingId ?>"
           style="display:block;text-align:center;margin-top:14px;
                  font-size:var(--fs-8);color:var(--sonic-silver);">
          ← Go back to listing
        </a>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>