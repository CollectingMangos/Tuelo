<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('/tuelo-main/login.php');

$currentUser = getUser();

if (!hasPermission('create_listing')) {
    header('Location: /tuelo-main/index.php');
    exit;
}

$error  = '';
$fields = [
    'title'          => '',
    'description'    => '',
    'price'          => '',
    'category_id'    => '',
    'condition_type' => 'used',
    'location'       => '',
];

$categories = queryDB('SELECT * FROM categories ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['title']          = trim($_POST['title']          ?? '');
    $fields['description']    = trim($_POST['description']    ?? '');
    $fields['price']          = trim($_POST['price']          ?? '');
    $fields['category_id']    = (int)($_POST['category_id']   ?? 0);
    $fields['condition_type'] = trim($_POST['condition_type'] ?? 'used');
    $fields['location']       = trim($_POST['location']       ?? '');

    if (empty($fields['title'])) {
        $error = 'Please enter a title for your listing.';
    } elseif (empty($fields['description'])) {
        $error = 'Please enter a description.';
    } elseif (!is_numeric($fields['price']) || $fields['price'] <= 0) {
        $error = 'Please enter a valid price.';
    } elseif (!$fields['category_id']) {
        $error = 'Please select a category.';
    } elseif (!in_array($fields['condition_type'], ['new', 'used', 'refurbished'])) {
        $error = 'Please select a valid condition.';
    } elseif (empty($fields['location'])) {
        $error = 'Please enter a location.';
    } elseif (empty($_FILES['images']['name'][0])) {
        $error = 'Please upload at least one photo of your item.';
    } else {
        $listingId = insertIntoDB(
            'INSERT INTO listings (seller_id, category_id, title, description, price,
                                   condition_type, location, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "active", NOW(), NOW())',
            [
                $currentUser['id'],
                $fields['category_id'],
                $fields['title'],
                $fields['description'],
                (float)$fields['price'],
                $fields['condition_type'],
                $fields['location'],
            ]
        );

        if ($listingId) {
            $uploadDir = __DIR__ . '/uploads/listings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024;
            $isFirst = true;

            foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (!in_array($_FILES['images']['type'][$i], $allowed))  continue;
                if ($_FILES['images']['size'][$i] > $maxSize)            continue;

                $ext      = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = 'listing-' . $listingId . '-' . ($i + 1) . '-' . time() . '.' . $ext;

                if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                    insertIntoDB(
                        'INSERT INTO listing_images (listing_id, image_path, is_main_image)
                         VALUES (?, ?, ?)',
                        [
                            $listingId,
                            '/tuelo-main/uploads/listings/' . $filename,
                            $isFirst ? 1 : 0,
                        ]
                    );
                    $isFirst = false;
                }
            }

            header('Location: /tuelo-main/product.php?id=' . $listingId . '&posted=1');
            exit;

        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Sell an Item';
require_once __DIR__ . '/includes/header.php';
?>

<div style="padding:40px 0 60px;">
  <div class="container">
    <div style="max-width:680px;margin:auto;">

      <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--eerie-black);margin-bottom:6px;">
        Post a Listing
      </h1>
      <p style="font-size:var(--fs-7);color:var(--sonic-silver);margin-bottom:30px;">
        Fill in the details below for your listing that will go live.
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/tuelo-main/sell.php"
            enctype="multipart/form-data" id="sellForm">

        <div class="form-group">
          <label class="form-label">
            Listing title <span style="color:red">*</span>
          </label>
          <input type="text" name="title" class="form-input"
                 placeholder="e.g. Nike Air Max 90 Sneakers - Size 10"
                 value="<?= htmlspecialchars($fields['title']) ?>"
                 maxlength="200" required />
          <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-top:4px;">
            Be specific as good titles get more views!
          </p>
        </div>

        <div style="display:flex;gap:15px;">
          <div class="form-group" style="flex:1;">
            <label class="form-label">
              Category <span style="color:red">*</span>
            </label>
            <select name="category_id" class="form-input" required style="cursor:pointer;">
              <option value="">Select a category</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"
                <?= $fields['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="flex:1;">
            <label class="form-label">
              Condition <span style="color:red">*</span>
            </label>
            <select name="condition_type" class="form-input" style="cursor:pointer;">
              <option value="used"        <?= $fields['condition_type'] === 'used'        ? 'selected' : '' ?>>Used</option>
              <option value="new"         <?= $fields['condition_type'] === 'new'         ? 'selected' : '' ?>>New</option>
              <option value="refurbished" <?= $fields['condition_type'] === 'refurbished' ? 'selected' : '' ?>>Refurbished</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Description <span style="color:red">*</span>
          </label>
          <textarea name="description" class="form-input"
                    placeholder="Describe your item — condition, size, brand, what's included..."
                    rows="5" required
                    style="resize:vertical;"><?= htmlspecialchars($fields['description']) ?></textarea>
        </div>

        <div style="display:flex;gap:15px;">
          <div class="form-group" style="flex:1;">
            <label class="form-label">
              Price (R) <span style="color:red">*</span>
            </label>
            <input type="number" name="price" class="form-input"
                   placeholder="0.00" step="0.01" min="1"
                   value="<?= htmlspecialchars($fields['price']) ?>" required />
          </div>

          <div class="form-group" style="flex:1;">
            <label class="form-label">
              Location <span style="color:red">*</span>
            </label>
            <input type="text" name="location" class="form-input"
                   placeholder="e.g. Sandton, Gauteng"
                   value="<?= htmlspecialchars($fields['location']) ?>" required />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Photos <span style="color:red">*</span>
          </label>

          <div id="dropZone"
               style="border:2px dashed var(--cultured);border-radius:var(--border-radius-md);
                      padding:35px;text-align:center;cursor:pointer;
                      transition:border-color 0.2s ease,background 0.2s ease;"
               onclick="document.getElementById('imageInput').click()"
               ondragover="event.preventDefault();this.style.borderColor='var(--tuelo-green)';this.style.background='var(--tuelo-green-light)'"
               ondragleave="this.style.borderColor='var(--cultured)';this.style.background='transparent'"
               ondrop="handleDrop(event)">
            <ion-icon name="cloud-upload-outline"
                      style="font-size:40px;color:var(--tuelo-green);display:block;margin:0 auto 10px;">
            </ion-icon>
            <p style="font-size:var(--fs-7);color:var(--onyx);font-weight:500;">
              Click to upload or drag and drop
            </p>
            <p style="font-size:var(--fs-9);color:var(--sonic-silver);margin-top:4px;">
              JPG, PNG or WEBP — up to 5MB each — max 5 photos
            </p>
          </div>

          <input type="file" id="imageInput" name="images[]"
                 accept="image/jpeg,image/png,image/webp"
                 multiple style="display:none;"
                 onchange="previewImages(this.files)" />

          <div id="imagePreviews"
               style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;"></div>
        </div>

        <div style="display:flex;gap:12px;margin-top:10px;">
          <button type="submit" class="btn-submit" style="flex:1;">
            Post Listing
          </button>
          <a href="/tuelo-main/listings.php"
             style="display:flex;align-items:center;justify-content:center;
                    border:1.5px solid var(--cultured);color:var(--sonic-silver);
                    padding:12px 20px;border-radius:var(--border-radius-sm);
                    font-size:var(--fs-7);font-weight:600;transition:var(--transition-timing);"
             onmouseover="this.style.borderColor='var(--onyx)';this.style.color='var(--onyx)'"
             onmouseout="this.style.borderColor='var(--cultured)';this.style.color='var(--sonic-silver)'">
            Cancel
          </a>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
function previewImages(files) {
    const container = document.getElementById('imagePreviews');
    container.innerHTML = '';
    const maxFiles = Math.min(files.length, 5);
    for (let i = 0; i < maxFiles; i++) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;width:90px;height:90px;';
            wrap.innerHTML = `
                <img src="${e.target.result}"
                     style="width:90px;height:90px;object-fit:cover;
                            border-radius:var(--border-radius-sm);
                            border:1px solid var(--cultured);" />
                ${i === 0 ? '<span style="position:absolute;bottom:4px;left:4px;background:var(--tuelo-green-dark);color:#fff;font-size:10px;padding:1px 6px;border-radius:3px;">Cover</span>' : ''}
            `;
            container.appendChild(wrap);
        };
        reader.readAsDataURL(files[i]);
    }
}

function handleDrop(event) {
    event.preventDefault();
    const dropZone = document.getElementById('dropZone');
    dropZone.style.borderColor = 'var(--cultured)';
    dropZone.style.background  = 'transparent';
    const input = document.getElementById('imageInput');
    input.files = event.dataTransfer.files;
    previewImages(event.dataTransfer.files);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
