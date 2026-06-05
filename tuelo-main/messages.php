<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin('/tuelo-main/login.php');

if (!hasPermission('send_message')) {
    header('Location: /tuelo-main/index.php');
    exit;
}

$currentUser = getUser();
$userId      = $currentUser['id'];
$listingId   = (int)($_GET['listing'] ?? 0);
$sellerId    = (int)($_GET['seller']  ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $body       = trim($_POST['body'] ?? '');
    $msgListing = (int)($_POST['listing_id'] ?? 0);

    if ($receiverId && $body && $receiverId !== $userId) {
        insertIntoDB(
            'INSERT INTO messages (sender_id, receiver_id, listing_id, body, is_read, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())',
            [$userId, $receiverId, $msgListing ?: null, $body]
        );
        $redirectUrl = '/tuelo-main/messages.php?with=' . $receiverId;
        if ($msgListing) $redirectUrl .= '&listing=' . $msgListing;
        header('Location: ' . $redirectUrl);
        exit;
    }
}

$withUserId = (int)($_GET['with'] ?? 0);
if (!$withUserId && $sellerId) {
    $withUserId = $sellerId;
}

$threads = queryDB(
    'SELECT
         partner_id,
         MAX(created_at) AS last_message_at,
         SUM(CASE WHEN receiver_id = ? AND is_read = 0 THEN 1 ELSE 0 END) AS unread_count
     FROM (
         SELECT
             CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
             sender_id,
             receiver_id,
             created_at,
             is_read
         FROM messages
         WHERE sender_id = ? OR receiver_id = ?
     ) AS convos
     GROUP BY partner_id
     ORDER BY last_message_at DESC',
    [$userId, $userId, $userId, $userId]
);

foreach ($threads as &$thread) {
    $partner = singleQueryDB(
        'SELECT id, name, surname, profile_image FROM users WHERE id = ?',
        [$thread['partner_id']]
    );
    $thread['partner_name']   = $partner ? $partner['name'] . ' ' . $partner['surname'] : 'Unknown';
    $thread['partner_avatar'] = $partner['profile_image'] ?? null;

    $lastMsg = singleQueryDB(
        'SELECT body FROM messages
         WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1',
        [$userId, $thread['partner_id'], $thread['partner_id'], $userId]
    );
    $thread['last_body'] = $lastMsg['body'] ?? null;
}
unset($thread);

if ($withUserId && !array_filter($threads, fn($t) => $t['partner_id'] == $withUserId)) {
    $partner = singleQueryDB(
        'SELECT id, name, surname, profile_image FROM users WHERE id = ?',
        [$withUserId]
    );
    if ($partner) {
        array_unshift($threads, [
            'partner_id'      => $withUserId,
            'last_message_at' => null,
            'unread_count'    => 0,
            'last_body'       => null,
            'partner_name'    => $partner['name'] . ' ' . $partner['surname'],
            'partner_avatar'  => $partner['profile_image'] ?? null,
        ]);
    }
}

$activeMessages = [];
$activePartner  = null;
$activeListing  = null;

if ($withUserId) {
    updateDB(
        'UPDATE messages SET is_read = 1, read_at = NOW()
         WHERE sender_id = ? AND receiver_id = ? AND is_read = 0',
        [$withUserId, $userId]
    );

    $activeMessages = queryDB(
        'SELECT * FROM messages
         WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
         ORDER BY created_at ASC',
        [$userId, $withUserId, $withUserId, $userId]
    );

    $activePartner = singleQueryDB(
        'SELECT id, name, surname, profile_image, rating_average FROM users WHERE id = ?',
        [$withUserId]
    );

    if ($listingId) {
        $activeListing = singleQueryDB(
            'SELECT id, title, price, status FROM listings WHERE id = ?',
            [$listingId]
        );
    }
}

$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background:var(--tuelo-green-light);padding:20px 0;
            border-bottom:1px solid hsl(152,51%,80%);">
  <div class="container">
    <h1 style="font-size:var(--fs-2);font-weight:700;color:var(--tuelo-green-dark);">
      Messages
    </h1>
  </div>
</div>

<div style="padding:25px 0 60px;">
  <div class="container">
    <div class="messages-shell<?= ($withUserId && $activePartner) ? ' chat-open' : '' ?>" id="messagesShell">

      <div class="messages-threads">

        <div style="padding:16px;border-bottom:1px solid var(--cultured);">
          <p style="font-size:var(--fs-8);font-weight:600;color:var(--onyx);
                    text-transform:uppercase;letter-spacing:0.5px;">
            Conversations
          </p>
        </div>

        <?php if (empty($threads)): ?>
          <div style="padding:40px 20px;text-align:center;color:var(--sonic-silver);">
            <ion-icon name="chatbubbles-outline"
                      style="font-size:40px;color:var(--cultured);display:block;margin:0 auto 10px;">
            </ion-icon>
            <p style="font-size:var(--fs-8);">No conversations yet</p>
          </div>
        <?php else: ?>
          <?php foreach ($threads as $thread):
              $isActive = $thread['partner_id'] == $withUserId;
          ?>
          <a href="/tuelo-main/messages.php?with=<?= $thread['partner_id'] ?><?= $listingId ? '&listing=' . $listingId : '' ?>"
             style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                    border-bottom:1px solid var(--cultured);
                    background:<?= $isActive ? 'var(--tuelo-green-light)' : 'transparent' ?>;
                    transition:background 0.15s ease;">

            <div style="position:relative;flex-shrink:0;">
              <img src="<?= $thread['partner_avatar']
                  ? htmlspecialchars($thread['partner_avatar'])
                  : '/tuelo-main/assets/img/default-avatar.png' ?>"
                   style="width:42px;height:42px;border-radius:50%;object-fit:cover;" />
              <?php if ($thread['unread_count'] > 0): ?>
              <span style="position:absolute;top:-3px;right:-3px;
                           background:var(--tuelo-gold);color:#fff;
                           font-size:10px;font-weight:700;
                           width:18px;height:18px;border-radius:50%;
                           display:flex;align-items:center;justify-content:center;">
                <?= $thread['unread_count'] ?>
              </span>
              <?php endif; ?>
            </div>

            <div style="flex:1;min-width:0;">
              <p style="font-size:var(--fs-8);
                        font-weight:<?= $thread['unread_count'] > 0 ? '700' : '500' ?>;
                        color:var(--onyx);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($thread['partner_name']) ?>
              </p>
              <p style="font-size:var(--fs-9);color:var(--sonic-silver);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                <?= $thread['last_body']
                    ? htmlspecialchars(substr($thread['last_body'], 0, 40)) . '...'
                    : 'No messages yet' ?>
              </p>
            </div>

            <?php if ($thread['last_message_at']): ?>
            <p style="font-size:var(--fs-10);color:var(--sonic-silver);flex-shrink:0;">
              <?= date('d M', strtotime($thread['last_message_at'])) ?>
            </p>
            <?php endif; ?>

          </a>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <div class="messages-chat">

        <button class="messages-back-btn" onclick="document.getElementById('messagesShell').classList.remove('chat-open')">
          <ion-icon name="arrow-back-outline" style="font-size:18px;"></ion-icon>
          Back to conversations
        </button>

        <?php if (!$withUserId || !$activePartner): ?>
          <div style="flex:1;display:flex;flex-direction:column;
                      align-items:center;justify-content:center;
                      color:var(--sonic-silver);padding:40px;">
            <ion-icon name="chatbubble-ellipses-outline"
                      style="font-size:60px;color:var(--cultured);margin-bottom:16px;">
            </ion-icon>
            <p style="font-size:var(--fs-6);font-weight:500;color:var(--onyx);margin-bottom:8px;">
              Select a conversation
            </p>
            <p style="font-size:var(--fs-8);">
              Choose a conversation from the left, or message a seller from a listing page.
            </p>
          </div>

        <?php else: ?>

          <div style="padding:14px 20px;border-bottom:1px solid var(--cultured);
                      display:flex;align-items:center;gap:12px;">
            <img src="<?= $activePartner['profile_image']
                ? htmlspecialchars($activePartner['profile_image'])
                : '/tuelo-main/assets/img/default-avatar.png' ?>"
                 style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />
            <p style="font-weight:600;font-size:var(--fs-7);color:var(--onyx);">
              <?= htmlspecialchars($activePartner['name'] . ' ' . $activePartner['surname']) ?>
            </p>

            <?php if ($activeListing): ?>
            <div style="margin-left:auto;background:var(--tuelo-green-light);
                        border:1px solid hsl(152,51%,70%);border-radius:var(--border-radius-sm);
                        padding:6px 12px;display:flex;align-items:center;gap:8px;">
              <p style="font-size:var(--fs-9);color:var(--tuelo-green-dark);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">
                <?= htmlspecialchars($activeListing['title']) ?>
              </p>
              <span style="font-weight:700;font-size:var(--fs-9);color:var(--tuelo-green-dark);">
                R <?= number_format($activeListing['price'], 2) ?>
              </span>
              <?php if ($activeListing['status'] === 'active' && hasPermission('purchase_item')): ?>
              <a href="/tuelo-main/checkout.php?listing=<?= $activeListing['id'] ?>"
                 style="background:var(--tuelo-green-dark);color:#fff;
                        font-size:var(--fs-9);font-weight:600;
                        padding:4px 10px;border-radius:3px;white-space:nowrap;">
                Buy Now
              </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <div id="messagesArea"
               style="flex:1;overflow-y:auto;padding:20px;
                      display:flex;flex-direction:column;gap:12px;">
            <?php if (empty($activeMessages)): ?>
              <div style="text-align:center;color:var(--sonic-silver);margin:auto;">
                <p style="font-size:var(--fs-8);">Start the conversation — say hello!</p>
              </div>
            <?php else: ?>
              <?php foreach ($activeMessages as $msg):
                  $isOwn = $msg['sender_id'] === $userId;
              ?>
              <div style="display:flex;flex-direction:column;
                          align-items:<?= $isOwn ? 'flex-end' : 'flex-start' ?>;">
                <div style="max-width:70%;padding:10px 14px;
                            background:<?= $isOwn ? 'var(--tuelo-green-dark)' : 'var(--cultured)' ?>;
                            color:<?= $isOwn ? '#fff' : 'var(--onyx)' ?>;
                            border-radius:<?= $isOwn ? '14px 14px 4px 14px' : '14px 14px 14px 4px' ?>;
                            font-size:var(--fs-8);line-height:1.6;">
                  <?= nl2br(htmlspecialchars($msg['body'])) ?>
                </div>
                <p style="font-size:var(--fs-10);color:var(--sonic-silver);margin-top:4px;">
                  <?= date('d M, H:i', strtotime($msg['created_at'])) ?>
                  <?php if ($isOwn && $msg['is_read']): ?>
                    &middot; <span style="color:var(--tuelo-green);">Read</span>
                  <?php endif; ?>
                </p>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div style="padding:14px 20px;border-top:1px solid var(--cultured);">
            <form method="POST"
                  action="/tuelo-main/messages.php?with=<?= $withUserId ?><?= $listingId ? '&listing=' . $listingId : '' ?>"
                  style="display:flex;gap:10px;align-items:flex-end;">
              <input type="hidden" name="send_message" value="1" />
              <input type="hidden" name="receiver_id"  value="<?= $withUserId ?>" />
              <input type="hidden" name="listing_id"   value="<?= $listingId ?>" />
              <textarea name="body" rows="2"
                        placeholder="Type a message..."
                        required
                        style="flex:1;padding:10px 14px;font-family:inherit;
                               font-size:var(--fs-8);border:1.5px solid var(--cultured);
                               border-radius:var(--border-radius-md);resize:none;
                               transition:border-color 0.2s ease;"
                        onfocus="this.style.borderColor='var(--tuelo-green)'"
                        onblur="this.style.borderColor='var(--cultured)'"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}">
              </textarea>
              <button type="submit"
                      style="background:var(--tuelo-green-dark);color:#fff;
                             padding:10px 18px;border-radius:var(--border-radius-md);
                             font-size:20px;transition:var(--transition-timing);flex-shrink:0;"
                      onmouseover="this.style.background='var(--tuelo-green)'"
                      onmouseout="this.style.background='var(--tuelo-green-dark)'">
                <ion-icon name="send-outline"></ion-icon>
              </button>
            </form>
            <p style="font-size:var(--fs-10);color:var(--sonic-silver);margin-top:5px;">
              Press Enter to send &middot; Shift+Enter for new line
            </p>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
const area = document.getElementById('messagesArea');
if (area) area.scrollTop = area.scrollHeight;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>