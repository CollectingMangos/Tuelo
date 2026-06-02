<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/rbac.php';

requireAdmin();
$currentUser = getUser();

//  Handle actions 
$message = '';
$msgType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        updateDB('UPDATE listings SET status = "active", updated_at = NOW() WHERE id = ?', [$id]);
        logAction('listing_approved', 'listing', $id);
        $message = 'Listing approved and set to active.';
        $msgType = 'success';
    } elseif ($action === 'remove') {
        updateDB('UPDATE listings SET status = "removed", updated_at = NOW() WHERE id = ?', [$id]);
        logAction('listing_removed', 'listing', $id);
        $message = 'Listing has been removed.';
        $msgType = 'danger';
    } elseif ($action === 'restore') {
        updateDB('UPDATE listings SET status = "active", updated_at = NOW() WHERE id = ?', [$id]);
        logAction('listing_restored', 'listing', $id);
        $message = 'Listing restored to active.';
        $msgType = 'success';
    }

    header('Location: /tuelo-admin/listings.php?msg=' . urlencode($message) . '&type=' . $msgType);
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $msgType = $_GET['type'] ?? 'success';
}

//  Filters 
$filter   = $_GET['filter']   ?? 'all';
$search   = trim($_GET['q']   ?? '');
$catId    = (int)($_GET['cat'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;
$offset   = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($filter !== 'all') {
    $where[]  = 'l.status = ?';
    $params[] = $filter;
}
if ($search) {
    $where[]  = '(l.title LIKE ? OR u.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($catId) {
    $where[]  = 'l.category_id = ?';
    $params[] = $catId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = singleQueryDB(
    "SELECT COUNT(*) AS n FROM listings l
     JOIN users u ON l.seller_id = u.id
     $whereSQL", $params
)['n'];

$totalPages = (int)ceil($total / $perPage);

$listings = queryDB(
    "SELECT l.*, u.name AS seller_name, c.name AS category_name,
            (SELECT image_path FROM listing_images
             WHERE listing_id = l.id AND is_main_image = 1 LIMIT 1) AS cover_img
     FROM listings l
     JOIN users u ON l.seller_id = u.id
     JOIN categories c ON l.category_id = c.id
     $whereSQL
     ORDER BY l.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$categories = queryDB('SELECT * FROM categories ORDER BY name ASC');

$counts = [
    'all'     => singleQueryDB('SELECT COUNT(*) AS n FROM listings')['n'],
    'active'  => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "active"')['n'],
    'pending' => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "pending"')['n'],
    'sold'    => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "sold"')['n'],
    'removed' => singleQueryDB('SELECT COUNT(*) AS n FROM listings WHERE status = "removed"')['n'],
];

$pageTitle = 'Listings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1 class="admin-page-title">Listings</h1>
      <p class="admin-page-subtitle">Manage, approve and remove platform listings.</p>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $msgType ?>" style="margin-bottom:20px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Filter tabs -->
  <div style="display:flex;gap:0;border:1px solid var(--admin-border);
              border-radius:var(--admin-radius-sm);overflow:hidden;
              width:fit-content;margin-bottom:20px;">
    <?php foreach (['all' => 'All', 'active' => 'Active', 'pending' => 'Pending', 'sold' => 'Sold', 'removed' => 'Removed'] as $key => $label): ?>
    <a href="/tuelo-admin/listings.php?filter=<?= $key ?>"
       style="padding:8px 16px;font-size:var(--admin-fs-sm);font-weight:500;
              border-right:1px solid var(--admin-border);
              background:<?= $filter === $key ? 'var(--admin-green-dark)' : '#fff' ?>;
              color:<?= $filter === $key ? '#fff' : 'var(--admin-muted)' ?>;">
      <?= $label ?> (<?= $counts[$key] ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Search + category filter -->
  <div class="admin-filter-bar">
    <form method="GET" action="/tuelo-admin/listings.php" style="display:flex;gap:10px;flex:1;max-width:500px;">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>" />
      <input type="text" name="q" class="admin-input" placeholder="Search title or seller..."
             value="<?= htmlspecialchars($search) ?>" style="flex:1;" />
      <select name="cat" class="admin-input" style="width:180px;cursor:pointer;">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="admin-btn admin-btn-primary">Search</button>
      <?php if ($search || $catId): ?>
      <a href="/tuelo-admin/listings.php?filter=<?= $filter ?>" class="admin-btn admin-btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
    <p style="font-size:var(--admin-fs-sm);color:var(--admin-muted);margin-left:auto;">
      <?= number_format($total) ?> listing<?= $total != 1 ? 's' : '' ?>
    </p>
  </div>

  <!-- Table -->
  <div class="admin-card">
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Listing</th>
            <th>Seller</th>
            <th>Category</th>
            <th>Price</th>
            <th>Condition</th>
            <th>Status</th>
            <th>Posted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($listings)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--admin-muted);">
              No listings found matching your filters.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($listings as $l): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= $l['cover_img'] ? htmlspecialchars($l['cover_img']) : '/tuelo-main/assets/img/no-image.png' ?>"
                     style="width:42px;height:42px;object-fit:cover;border-radius:var(--admin-radius-sm);flex-shrink:0;" />
                <span style="font-weight:500;color:var(--admin-text);max-width:200px;
                             display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($l['title']) ?>
                </span>
              </div>
            </td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($l['seller_name']) ?></td>
            <td style="color:var(--admin-muted);"><?= htmlspecialchars($l['category_name']) ?></td>
            <td style="font-weight:700;color:var(--admin-green);">R <?= number_format($l['price'], 2) ?></td>
            <td>
              <span class="admin-badge <?= $l['condition_type'] === 'new' ? 'admin-badge-green' : ($l['condition_type'] === 'refurbished' ? 'admin-badge-blue' : 'admin-badge-gray') ?>">
                <?= ucfirst($l['condition_type']) ?>
              </span>
            </td>
            <td>
              <span class="admin-badge <?= match($l['status']) {
                  'active'  => 'admin-badge-green',
                  'pending' => 'admin-badge-yellow',
                  'sold'    => 'admin-badge-blue',
                  'removed' => 'admin-badge-red',
                  default   => 'admin-badge-gray'
              } ?>">
                <?= ucfirst($l['status']) ?>
              </span>
            </td>
            <td style="color:var(--admin-muted);white-space:nowrap;">
              <?= date('d M Y', strtotime($l['created_at'])) ?>
            </td>
            <td>
              <div style="display:flex;gap:5px;">
                <a href="/tuelo-main/product.php?id=<?= $l['id'] ?>" target="_blank"
                   class="admin-btn-icon" title="View on site">
                  <ion-icon name="eye-outline"></ion-icon>
                </a>
                <?php if ($l['status'] === 'pending'): ?>
                <a href="/tuelo-admin/listings.php?action=approve&id=<?= $l['id'] ?>"
                   class="admin-btn-icon" title="Approve"
                   style="background:#D1FAE5;border-color:#6EE7B7;"
                   onclick="return confirm('Approve this listing?')">
                  <ion-icon name="checkmark-outline" style="color:#059669;"></ion-icon>
                </a>
                <?php endif; ?>
                <?php if ($l['status'] !== 'removed'): ?>
                <a href="/tuelo-admin/listings.php?action=remove&id=<?= $l['id'] ?>"
                   class="admin-btn-icon admin-btn-icon-danger" title="Remove"
                   onclick="return confirm('Remove this listing?')">
                  <ion-icon name="trash-outline"></ion-icon>
                </a>
                <?php else: ?>
                <a href="/tuelo-admin/listings.php?action=restore&id=<?= $l['id'] ?>"
                   class="admin-btn-icon" title="Restore"
                   onclick="return confirm('Restore this listing?')">
                  <ion-icon name="refresh-outline"></ion-icon>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
      <?php if ($page > 1): ?>
      <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $page-1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
      <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"
         class="admin-btn <?= $i === $page ? 'admin-btn-primary' : 'admin-btn-secondary' ?>"
         style="padding:6px 12px;min-width:36px;justify-content:center;"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $page+1 ?>"
         class="admin-btn admin-btn-secondary" style="padding:6px 12px;">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>