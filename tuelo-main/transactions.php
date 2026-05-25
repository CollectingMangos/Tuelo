<?php
$pageTitle = 'Transactions';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('/tuelo-main/login.php');

$userId = $currentUser['id'];
$view   = $_GET['view'] ?? 'buying'; // buying or selling

//  Transactions as buyer 
$buying = queryDB(
    'SELECT t.*, l.title AS listing_title,
            u.name AS seller_name, u.surname AS seller_surname,
            u.profile_image AS seller_avatar,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN users u ON t.seller_id = u.id
     WHERE t.buyer_id = ?
     ORDER BY t.created_at DESC',
    [$userId]
);

//  Transactions as seller 
$selling = queryDB(
    'SELECT t.*, l.title AS listing_title,
            u.name AS buyer_name, u.surname AS buyer_surname,
            u.profile_image AS buyer_avatar,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN users u ON t.buyer_id = u.id
     WHERE t.seller_id = ?
     ORDER BY t.created_at DESC',
    [$userId]
);

//  Status colour helper 
function statusStyle(string $status): string {
    return match($status) {
        'completed' => 'background:var(--tuelo-green-light);color:var(--tuelo-green-dark);',
        'pending'   => 'background:hsl(45,100%,92%);color:hsl(35,100%,35%);',
        'paid'      => 'background:hsl(210,100%,92%);color:hsl(210,100%,35%);',
        'refunded'  => 'background:var(--cultured);color:var(--sonic-silver);',
        default     => 'background:var(--cultured);color:var(--sonic-silver);',
    };
}
?>

<!-- Page header -->
<div style="background:var(--tuelo-green-light);padding:25px 0;
            border-bottom:1px solid hsl(152,51%,80%);">
  <div class="container">
    <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--tuelo-green-dark);margin-bottom:4px;">
      Transactions
    </h1>
    <p style="font-size:var(--fs-8);color:var(--sonic-silver);">
      Your buying and selling history on Tuelo
    </p>
  </div>
</div>

<div style="padding:30px 0 60px;">
  <div class="container">

    <!-- Tab switcher -->
    <div style="display:flex;gap:0;border:1px solid var(--cultured);
                border-radius:var(--border-radius-sm);overflow:hidden;
                width:fit-content;margin-bottom:30px;">
      <a href="/tuelo-main/transactions.php?view=buying"
         style="display:flex;align-items:center;gap:7px;padding:10px 20px;
                font-size:var(--fs-7);font-weight:600;
                background:<?= $view === 'buying' ? 'var(--tuelo-green-dark)' : 'transparent' ?>;
                color:<?= $view === 'buying' ? '#fff' : 'var(--sonic-silver)' ?>;
                transition:var(--transition-timing);">
        <ion-icon name="bag-handle-outline" style="font-size:16px;"></ion-icon>
        Buying (<?= count($buying) ?>)
      </a>
      <?php if (hasPermission('create_listing')): ?>
      <a href="/tuelo-main/transactions.php?view=selling"
         style="display:flex;align-items:center;gap:7px;padding:10px 20px;
                font-size:var(--fs-7);font-weight:600;
                border-left:1px solid var(--cultured);
                background:<?= $view === 'selling' ? 'var(--tuelo-green-dark)' : 'transparent' ?>;
                color:<?= $view === 'selling' ? '#fff' : 'var(--sonic-silver)' ?>;
                transition:var(--transition-timing);">
        <ion-icon name="pricetag-outline" style="font-size:16px;"></ion-icon>
        Selling (<?= count($selling) ?>)
      </a>
      <?php endif; ?>
    </div>

    <?php
    $list         = $view === 'selling' ? $selling : $buying;
    $isSelling    = $view === 'selling';
    ?>

    <?php if (empty($list)): ?>
      <div style="text-align:center;padding:80px 0;color:var(--sonic-silver);">
        <ion-icon name="receipt-outline"
                  style="font-size:70px;color:var(--cultured);display:block;margin:0 auto 20px;">
        </ion-icon>
        <h3 style="font-size:var(--fs-3);font-weight:600;color:var(--onyx);margin-bottom:10px;">
          No <?= $isSelling ? 'sales' : 'purchases' ?> yet
        </h3>
        <p style="font-size:var(--fs-7);margin-bottom:25px;">
          <?= $isSelling
              ? 'When buyers purchase your listings, your sales will appear here.'
              : 'When you buy items, your purchases will appear here.' ?>
        </p>
        <a href="/tuelo-main/listings.php"
           style="display:inline-block;background:var(--tuelo-green-dark);color:#fff;
                  padding:10px 25px;border-radius:var(--border-radius-sm);font-weight:600;">
          Browse Listings
        </a>
      </div>

    <?php else: ?>

      <!-- Transactions table -->
      <div style="border:1px solid var(--cultured);border-radius:var(--border-radius-md);overflow:hidden;">

        <!-- Table header -->
        <div style="display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr 0.8fr;
                    padding:12px 20px;background:var(--cultured);
                    font-size:var(--fs-9);font-weight:600;color:var(--sonic-silver);
                    text-transform:uppercase;letter-spacing:0.5px;">
          <span>Item</span>
          <span><?= $isSelling ? 'Buyer' : 'Seller' ?></span>
          <span>Amount</span>
          <span>Date</span>
          <span>Status</span>
        </div>

        <?php foreach ($list as $tx): ?>
        <div style="display:grid;grid-template-columns:2fr 1.2fr 1fr 1fr 0.8fr;
                    padding:16px 20px;border-top:1px solid var(--cultured);
                    align-items:center;transition:var(--transition-timing);"
             onmouseover="this.style.background='hsl(0,0%,99%)'"
             onmouseout="this.style.background='transparent'">

          <!-- Item -->
          <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            <img src="<?= $tx['cover_img']
                ? htmlspecialchars($tx['cover_img'])
                : '/tuelo-main/assets/img/no-image.png' ?>"
                 style="width:50px;height:50px;object-fit:cover;
                        border-radius:var(--border-radius-sm);flex-shrink:0;" />
            <p style="font-size:var(--fs-8);font-weight:500;color:var(--onyx);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($tx['listing_title']) ?>
            </p>
          </div>

          <!-- Buyer/Seller -->
          <div style="display:flex;align-items:center;gap:8px;">
            <img src="<?= ($isSelling ? $tx['buyer_avatar'] : $tx['seller_avatar'])
                    ? htmlspecialchars($isSelling ? $tx['buyer_avatar'] : $tx['seller_avatar'])
                    : '/tuelo-main/assets/img/default-avatar.png' ?>"
                 style="width:28px;height:28px;border-radius:50%;object-fit:cover;" />
            <p style="font-size:var(--fs-8);color:var(--sonic-silver);">
              <?= $isSelling
                  ? htmlspecialchars($tx['buyer_name']  . ' ' . $tx['buyer_surname'])
                  : htmlspecialchars($tx['seller_name'] . ' ' . $tx['seller_surname']) ?>
            </p>
          </div>

          <!-- Amount -->
          <p style="font-size:var(--fs-7);font-weight:700;color:var(--tuelo-green-dark);">
            R <?= number_format($tx['amount'], 2) ?>
          </p>

          <!-- Date -->
          <p style="font-size:var(--fs-9);color:var(--sonic-silver);">
            <?= date('d M Y', strtotime($tx['created_at'])) ?>
          </p>

          <!-- Status badge -->
          <span style="font-size:var(--fs-10);padding:3px 10px;border-radius:20px;
                       font-weight:600;text-transform:capitalize;width:fit-content;
                       <?= statusStyle($tx['status']) ?>">
            <?= ucfirst($tx['status']) ?>
          </span>

        </div>
        <?php endforeach; ?>

      </div>

      <!-- Summary row -->
      <div style="display:flex;justify-content:flex-end;margin-top:16px;">
        <p style="font-size:var(--fs-8);color:var(--sonic-silver);">
          Total <?= $isSelling ? 'earned' : 'spent' ?>:
          <strong style="color:var(--tuelo-green-dark);font-size:var(--fs-6);">
            R <?= number_format(array_sum(array_column($list, 'amount')), 2) ?>
          </strong>
        </p>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>