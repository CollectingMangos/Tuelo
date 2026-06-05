<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('/tuelo-admin/login.php');

$currentUser = getUser();

if (!in_array($currentUser['role_name'], ['admin', 'superuser'])) {
    header('Location: /tuelo-main/index.php');
    exit;
}

//  Platform stats 
$stats = [
    'total_users'        => singleQueryDB('SELECT COUNT(*) AS n FROM users WHERE is_verified != -1')['n'],
    'buyers'             => singleQueryDB('SELECT COUNT(*) AS n FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = "buyer" AND u.is_verified = 1')['n'],
    'sellers'            => singleQueryDB('SELECT COUNT(*) AS n FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = "seller" AND u.is_verified = 1')['n'],
    'active_listings'    => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "active"')['n'],
    'pending_listings'   => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "pending"')['n'],
    'sold_listings'      => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "sold"')['n'],
    'total_transactions' => singleQueryDB('SELECT COUNT(*) AS n FROM transactions')['n'],
    'pending_tx'         => singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE status = "pending"')['n'],
    'completed_tx'       => singleQueryDB('SELECT COUNT(*) AS n FROM transactions WHERE status = "completed"')['n'],
    'total_revenue'      => singleQueryDB('SELECT COALESCE(SUM(tuelo_fee),0) AS n FROM transactions WHERE status != "refunded"')['n'],
    'total_messages'     => singleQueryDB('SELECT COUNT(*) AS n FROM messages')['n'],
    'unread_messages'    => singleQueryDB('SELECT COUNT(*) AS n FROM messages WHERE is_read = 0')['n'],
    'total_reviews'      => singleQueryDB('SELECT COUNT(*) AS n FROM reviews')['n'],
    'avg_rating'         => singleQueryDB('SELECT ROUND(AVG(rating),1) AS n FROM reviews')['n'] ?? 0,
];

//  Recent activity (last 5 of each) 
$recentUsers = queryDB(
    'SELECT u.*, r.name AS role_name FROM users u
     JOIN roles r ON u.role_id = r.id
     ORDER BY u.created_at DESC LIMIT 5'
);

$recentListings = queryDB(
    'SELECT l.*, u.name AS seller_name, c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     ORDER BY l.created_at DESC LIMIT 5'
);

$recentTransactions = queryDB(
    'SELECT t.*, l.title AS listing_title,
            b.name AS buyer_name, s.name AS seller_name
     FROM transactions t
     JOIN listings l ON t.listing_id = l.id
     JOIN users b ON t.buyer_id = b.id
     JOIN users s ON t.seller_id = s.id
     ORDER BY t.created_at DESC LIMIT 5'
);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">

  <!--  PAGE HEADER  -->
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Dashboard</h1>
      <p class="admin-page-subtitle">
        Welcome back, <?= htmlspecialchars($currentUser['name']) ?>.
        Here's a full overview of Tuelo.
      </p>
    </div>
    <p style="font-size:var(--admin-fs-sm);color:var(--admin-muted);">
      <?= date('l, d F Y') ?>
    </p>
  </div>

  <!--  SECTION CARDS  -->
  <!-- These are the clickable admin section cards at the top -->
  <div class="dash-section-grid">

    <!-- Users -->
    <a href="/tuelo-admin/users.php"
       style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--admin-radius);
              padding:20px;display:flex;flex-direction:column;gap:12px;
              transition:box-shadow 0.15s ease,border-color 0.15s ease;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.borderColor='#6366F1'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--admin-border)'">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="width:44px;height:44px;background:#EEF2FF;border-radius:var(--admin-radius-sm);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
          <ion-icon name="people-outline" style="color:#6366F1;"></ion-icon>
        </div>
        <ion-icon name="arrow-forward-outline" style="color:#6366F1;font-size:18px;"></ion-icon>
      </div>
      <div>
        <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);"><?= number_format($stats['total_users']) ?></p>
        <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);margin-bottom:4px;">User Management</p>
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
          <?= $stats['buyers'] ?> buyers · <?= $stats['sellers'] ?> sellers
        </p>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <span class="admin-badge admin-badge-blue">View</span>
        <span class="admin-badge admin-badge-green">Create</span>
        <span class="admin-badge admin-badge-yellow">Edit Roles</span>
      </div>
    </a>

    <!-- Listings -->
    <a href="/tuelo-admin/listings.php"
       style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--admin-radius);
              padding:20px;display:flex;flex-direction:column;gap:12px;
              transition:box-shadow 0.15s ease,border-color 0.15s ease;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.borderColor='var(--admin-green)'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--admin-border)'">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="width:44px;height:44px;background:var(--admin-green-light);border-radius:var(--admin-radius-sm);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
          <ion-icon name="grid-outline" style="color:var(--admin-green-dark);"></ion-icon>
        </div>
        <ion-icon name="arrow-forward-outline" style="color:var(--admin-green);font-size:18px;"></ion-icon>
      </div>
      <div>
        <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);"><?= number_format($stats['active_listings']) ?></p>
        <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);margin-bottom:4px;">Listing Management</p>
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
          <?= $stats['pending_listings'] ?> pending · <?= $stats['sold_listings'] ?> sold
        </p>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <span class="admin-badge admin-badge-green">Approve</span>
        <span class="admin-badge admin-badge-red">Remove</span>
        <span class="admin-badge admin-badge-gray">Filter</span>
      </div>
    </a>

    <!-- Reports -->
    <a href="/tuelo-admin/reports.php"
       style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--admin-radius);
              padding:20px;display:flex;flex-direction:column;gap:12px;
              transition:box-shadow 0.15s ease,border-color 0.15s ease;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.borderColor='#0284C7'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--admin-border)'">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="width:44px;height:44px;background:#E0F2FE;border-radius:var(--admin-radius-sm);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
          <ion-icon name="bar-chart-outline" style="color:#0284C7;"></ion-icon>
        </div>
        <ion-icon name="arrow-forward-outline" style="color:#0284C7;font-size:18px;"></ion-icon>
      </div>
      <div>
        <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);">R <?= number_format($stats['total_revenue'], 2) ?></p>
        <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);margin-bottom:4px;">Reports & Analytics</p>
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
          <?= $stats['total_transactions'] ?> transactions total
        </p>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <span class="admin-badge admin-badge-blue">Revenue</span>
        <span class="admin-badge admin-badge-purple">Transactions</span>
      </div>
    </a>

    <!-- Roles (superuser only) or Logs (admin) -->
    <?php if ($currentUser['role_name'] === 'superuser'): ?>
    <a href="/tuelo-admin/roles.php"
       style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--admin-radius);
              padding:20px;display:flex;flex-direction:column;gap:12px;
              transition:box-shadow 0.15s ease,border-color 0.15s ease;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.borderColor='#9333EA'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--admin-border)'">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="width:44px;height:44px;background:#F3E8FF;border-radius:var(--admin-radius-sm);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
          <ion-icon name="shield-outline" style="color:#9333EA;"></ion-icon>
        </div>
        <ion-icon name="arrow-forward-outline" style="color:#9333EA;font-size:18px;"></ion-icon>
      </div>
      <div>
        <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);">RBAC</p>
        <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);margin-bottom:4px;">Roles & Permissions</p>
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
          Assign and manage user roles
        </p>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <span class="admin-badge admin-badge-purple">Superuser only</span>
      </div>
    </a>
    <?php else: ?>
    <a href="/tuelo-admin/logs.php"
       style="background:#fff;border:1px solid var(--admin-border);border-radius:var(--admin-radius);
              padding:20px;display:flex;flex-direction:column;gap:12px;
              transition:box-shadow 0.15s ease,border-color 0.15s ease;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.borderColor='#D97706'"
       onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--admin-border)'">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="width:44px;height:44px;background:#FEF3C7;border-radius:var(--admin-radius-sm);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
          <ion-icon name="document-text-outline" style="color:#D97706;"></ion-icon>
        </div>
        <ion-icon name="arrow-forward-outline" style="color:#D97706;font-size:18px;"></ion-icon>
      </div>
      <div>
        <p style="font-size:1.5rem;font-weight:700;color:var(--admin-text);">Logs</p>
        <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);margin-bottom:4px;">Audit Logs</p>
        <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
          Track all admin actions
        </p>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <span class="admin-badge admin-badge-yellow">View logs</span>
      </div>
    </a>
    <?php endif; ?>

  </div>

  <!--  PLATFORM STATS ROW  -->
  <div class="dash-stats-grid">

    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#D1FAE5;">
        <ion-icon name="cash-outline" style="color:#059669;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value">R <?= number_format($stats['total_revenue'], 2) ?></p>
        <p class="admin-stat-label">Platform Revenue</p>
      </div>
    </div>

    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#FEF3C7;">
        <ion-icon name="time-outline" style="color:#D97706;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= $stats['pending_tx'] ?></p>
        <p class="admin-stat-label">Pending Transactions</p>
      </div>
    </div>

    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#F3E8FF;">
        <ion-icon name="chatbubbles-outline" style="color:#9333EA;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= number_format($stats['unread_messages']) ?></p>
        <p class="admin-stat-label">Unread Messages</p>
      </div>
    </div>

    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background:#FEF9C3;">
        <ion-icon name="star-outline" style="color:#CA8A04;"></ion-icon>
      </div>
      <div>
        <p class="admin-stat-value"><?= $stats['avg_rating'] ?> / 5</p>
        <p class="admin-stat-label">Avg Seller Rating</p>
      </div>
    </div>

  </div>

  <!--  THREE COLUMN ACTIVITY  -->
  <div class="dash-activity-grid">

    <!-- Recent Users -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">New Users</h3>
        <a href="/tuelo-admin/users.php" class="admin-link">View all →</a>
      </div>
      <div style="padding:0;">
        <?php foreach ($recentUsers as $u): ?>
        <div style="display:flex;align-items:center;gap:10px;
                    padding:11px 16px;border-bottom:1px solid var(--admin-border);">
          <img src="<?= $u['profile_image']
              ? htmlspecialchars($u['profile_image'])
              : '/tuelo-main/assets/img/default-avatar.png' ?>"
               style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;" />
          <div style="flex:1;min-width:0;">
            <p style="font-size:var(--admin-fs-sm);font-weight:600;color:var(--admin-text);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($u['name'] . ' ' . $u['surname']) ?>
            </p>
            <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
              <?= date('d M Y', strtotime($u['created_at'])) ?>
            </p>
          </div>
          <span class="admin-badge <?= match($u['role_name']) {
              'superuser' => 'admin-badge-purple',
              'admin'     => 'admin-badge-blue',
              'seller'    => 'admin-badge-green',
              default     => 'admin-badge-gray'
          } ?>">
            <?= ucfirst($u['role_name']) ?>
          </span>
        </div>
        <?php endforeach; ?>
        <div style="padding:12px 16px;">
          <a href="/tuelo-admin/user-create.php" class="admin-btn admin-btn-primary" style="width:100%;justify-content:center;">
            <ion-icon name="person-add-outline"></ion-icon>
            Add New User
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Listings -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">Recent Listings</h3>
        <a href="/tuelo-admin/listings.php" class="admin-link">View all →</a>
      </div>
      <div style="padding:0;">
        <?php foreach ($recentListings as $l): ?>
        <div style="display:flex;align-items:center;gap:10px;
                    padding:11px 16px;border-bottom:1px solid var(--admin-border);">
          <img src="<?= $l['cover_img']
              ? htmlspecialchars($l['cover_img'])
              : '/tuelo-main/assets/img/no-image.png' ?>"
               style="width:38px;height:38px;object-fit:cover;
                      border-radius:var(--admin-radius-sm);flex-shrink:0;" />
          <div style="flex:1;min-width:0;">
            <p style="font-size:var(--admin-fs-sm);font-weight:500;color:var(--admin-text);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($l['title']) ?>
            </p>
            <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
              R <?= number_format($l['price'], 2) ?> · <?= htmlspecialchars($l['seller_name']) ?>
            </p>
          </div>
          <span class="admin-badge <?= match($l['status']) {
              'active'  => 'admin-badge-green',
              'pending' => 'admin-badge-yellow',
              'sold'    => 'admin-badge-blue',
              default   => 'admin-badge-gray'
          } ?>">
            <?= ucfirst($l['status']) ?>
          </span>
        </div>
        <?php endforeach; ?>
        <div style="padding:12px 16px;">
          <a href="/tuelo-admin/listings.php?filter=pending" class="admin-btn admin-btn-secondary" style="width:100%;justify-content:center;">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
            Review Pending (<?= $stats['pending_listings'] ?>)
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">Recent Transactions</h3>
        <a href="/tuelo-admin/reports.php" class="admin-link">View all →</a>
      </div>
      <div style="padding:0;">
        <?php if (empty($recentTransactions)): ?>
          <div style="padding:30px;text-align:center;color:var(--admin-muted);">
            <ion-icon name="receipt-outline" style="font-size:36px;color:var(--admin-border);display:block;margin:0 auto 8px;"></ion-icon>
            <p style="font-size:var(--admin-fs-sm);">No transactions yet</p>
          </div>
        <?php else: ?>
          <?php foreach ($recentTransactions as $tx): ?>
          <div style="display:flex;align-items:center;gap:10px;
                      padding:11px 16px;border-bottom:1px solid var(--admin-border);">
            <div style="width:38px;height:38px;background:var(--admin-green-light);
                        border-radius:var(--admin-radius-sm);display:flex;align-items:center;
                        justify-content:center;font-size:18px;flex-shrink:0;">
              <ion-icon name="receipt-outline" style="color:var(--admin-green-dark);"></ion-icon>
            </div>
            <div style="flex:1;min-width:0;">
              <p style="font-size:var(--admin-fs-sm);font-weight:500;color:var(--admin-text);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($tx['listing_title']) ?>
              </p>
              <p style="font-size:var(--admin-fs-xs);color:var(--admin-muted);">
                <?= htmlspecialchars($tx['buyer_name']) ?> → <?= htmlspecialchars($tx['seller_name']) ?>
              </p>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <p style="font-size:var(--admin-fs-sm);font-weight:700;color:var(--admin-green);">
                R <?= number_format($tx['amount'], 2) ?>
              </p>
              <span class="admin-badge <?= match($tx['status']) {
                  'completed' => 'admin-badge-green',
                  'pending'   => 'admin-badge-yellow',
                  'refunded'  => 'admin-badge-gray',
                  default     => 'admin-badge-blue'
              } ?>">
                <?= ucfirst($tx['status']) ?>
              </span>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div style="padding:12px 16px;">
          <a href="/tuelo-admin/reports.php" class="admin-btn admin-btn-secondary" style="width:100%;justify-content:center;">
            <ion-icon name="bar-chart-outline"></ion-icon>
            Full Reports
          </a>
        </div>
      </div>
    </div>

  </div>

  <!--  QUICK ACTIONS  -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Quick Actions</h3>
    </div>
    <div class="admin-card-body">
      <div style="display:flex;gap:12px;flex-wrap:wrap;">

        <a href="/tuelo-admin/user-create.php" class="admin-btn admin-btn-primary">
          <ion-icon name="person-add-outline"></ion-icon>
          Create User
        </a>

        <a href="/tuelo-admin/users.php" class="admin-btn admin-btn-secondary">
          <ion-icon name="people-outline"></ion-icon>
          Manage Users
        </a>

        <a href="/tuelo-admin/listings.php?filter=pending" class="admin-btn admin-btn-secondary">
          <ion-icon name="time-outline"></ion-icon>
          Pending Listings
        </a>

        <a href="/tuelo-admin/listings.php" class="admin-btn admin-btn-secondary">
          <ion-icon name="grid-outline"></ion-icon>
          All Listings
        </a>

        <a href="/tuelo-admin/reports.php" class="admin-btn admin-btn-secondary">
          <ion-icon name="bar-chart-outline"></ion-icon>
          View Reports
        </a>

        <?php if ($currentUser['role_name'] === 'superuser'): ?>
        <a href="/tuelo-admin/roles.php" class="admin-btn admin-btn-secondary">
          <ion-icon name="shield-outline"></ion-icon>
          Manage Roles
        </a>

        <a href="/tuelo-admin/logs.php" class="admin-btn admin-btn-secondary">
          <ion-icon name="document-text-outline"></ion-icon>
          Audit Logs
        </a>
        <?php endif; ?>

        <a href="/tuelo-main/index.php" target="_blank" class="admin-btn admin-btn-secondary">
          <ion-icon name="storefront-outline"></ion-icon>
          View Main Site
        </a>

      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>