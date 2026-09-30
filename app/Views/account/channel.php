<?php page_header($creator['username'] . ' · Aurahub'); ?>
<header class="channel-head" style="display: flex; align-items: flex-start; gap: 1rem;">
  <?php if (!empty($creator['avatar_url'])): ?>
    <img src="<?= e($creator['avatar_url']) ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-top: 1rem;">
  <?php else: ?>
    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--bg-alt); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin-top: 1rem;">
      <?= e(mb_strtoupper(mb_substr($creator['username'], 0, 1))) ?>
    </div>
  <?php endif; ?>
  
  <div style="flex: 1;">
    <p class="muted">CREATOR CHANNEL</p>
    <h1 style="margin-top: 0;"><?= e($creator['username']) ?></h1>
  <p class="muted"><?= number_format($total) ?> video<?= $total === 1 ? '' : 's' ?> · Member since <?= e(date('F Y', strtotime($creator['created_at']))) ?></p>
  
  <?php if (user() && !$isOwner): ?>
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 1rem;">
      <form method="post" action="/aurahub/public/channel" style="margin: 0;">
        <?= csrf_field() ?>
        <input type="hidden" name="u" value="<?= e($creator['username']) ?>">
        <input type="hidden" name="action" value="toggle_subscription">
        <button class="pill <?= $isSubscribed ? 'outline' : '' ?>" type="submit">
          <?= $isSubscribed ? 'Unsubscribe' : 'Subscribe' ?>
        </button>
      </form>
      <a href="/aurahub/public/messages/chat?u=<?= rawurlencode($creator['username']) ?>" class="pill outline">Message</a>
    </div>
  <?php elseif (!user()): ?>
    <div style="margin-top: 1rem;">
      <a class="pill outline" href="/aurahub/public/login">Sign in to subscribe</a>
    </div>
  <?php endif; ?>

  <?php if ($creator['bio'] !== '' || $isOwner): ?>
  <div class="creator-bio">
    <?php if ($creator['bio'] !== ''): ?><p><?= nl2br(e($creator['bio'])) ?></p><?php endif; ?>
    <?php if ($isOwner): ?>
    <details>
      <summary><?= $creator['bio'] !== '' ? 'Edit bio' : 'Add a creator bio' ?></summary>
      <form method="post" class="creator-bio-form" action="/aurahub/public/channel">
        <?= csrf_field() ?>
        <input type="hidden" name="u" value="<?= e($creator['username']) ?>">
        <input type="hidden" name="action" value="update_bio">
        <?php if ($bioError): ?><p class="error"><?= e($bioError) ?></p><?php endif; ?>
        <label>About this creator
          <textarea name="bio" rows="4" maxlength="1000"><?= e($_POST['bio'] ?? $creator['bio']) ?></textarea>
        </label>
        <button class="pill" type="submit">Save bio</button>
      </form>
    </details>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  </div>
</header>

<?php if ($publicPlaylists): ?>
<section class="creator-playlists">
  <h2 class="section">Public playlists</h2>
  <div class="playlist-list">
    <?php foreach ($publicPlaylists as $playlist): ?>
    <a class="playlist-row" href="/aurahub/public/playlist?id=<?= (int)$playlist['id'] ?>">
      <span><strong><?= e($playlist['name']) ?></strong><span class="muted"><?= (int)$playlist['video_count'] ?> videos</span></span>
      <span class="pill">Open</span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($pinnedVideo): ?>
<div class="section" style="display: flex; align-items: center; justify-content: space-between;">
  <span>Pinned Video</span>
  <?php if ($isOwner): ?>
    <form method="post" action="/aurahub/public/channel" style="margin: 0;">
      <?= csrf_field() ?>
      <input type="hidden" name="u" value="<?= e($creator['username']) ?>">
      <input type="hidden" name="action" value="pin_video">
      <input type="hidden" name="video_id" value="0">
      <button class="pill outline" type="submit" style="padding: 2px 8px; font-size: 11px;">Unpin</button>
    </form>
  <?php endif; ?>
</div>
<div class="card" style="margin-bottom: 2rem;">
  <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
    <a href="/aurahub/public/watch?id=<?= (int)$pinnedVideo['id'] ?>" style="flex: 1; min-width: 280px; max-width: 400px;">
      <div style="width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 4px; overflow: hidden;">
        <?php if ($pinnedVideo['thumbnail_url']): ?>
          <img src="<?= e($pinnedVideo['thumbnail_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
        <?php endif; ?>
      </div>
    </a>
    <div style="flex: 2; min-width: 280px;">
      <h2 style="margin-top: 0;"><a href="/aurahub/public/watch?id=<?= (int)$pinnedVideo['id'] ?>" style="color: inherit; text-decoration: none;"><?= e($pinnedVideo['title']) ?></a></h2>
      <p class="muted"><?= number_format($pinnedVideo['views']) ?> views · <?= e(time_ago($pinnedVideo['created_at'])) ?></p>
      <p style="color: var(--text-secondary); display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
        <?= nl2br(e($pinnedVideo['description'] ?: 'No description provided.')) ?>
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!$videos): ?>
  <div class="empty"><p>No published videos yet.</p></div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($videos as $video): ?>
    <div class="vcard">
      <a href="/aurahub/public/watch?id=<?= (int)$video['id'] ?>">
        <div class="thumb">
          <?php if ($video['thumbnail_url']): ?>
            <img src="<?= e($video['thumbnail_url']) ?>" alt="" loading="lazy">
          <?php else: ?>
            <span class="ph"><?= e(mb_strtoupper(mb_substr($video['title'], 0, 1))) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= e($video['title']) ?></h3>
      </a>
      <p class="muted"><?= number_format($video['views']) ?> views · <?= e(time_ago($video['created_at'])) ?></p>
      <?php if ($isOwner && (!$pinnedVideo || (int)$pinnedVideo['id'] !== (int)$video['id'])): ?>
        <form method="post" action="/aurahub/public/channel" style="margin-top: 0.5rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="u" value="<?= e($creator['username']) ?>">
          <input type="hidden" name="action" value="pin_video">
          <input type="hidden" name="video_id" value="<?= (int)$video['id'] ?>">
          <button class="pill outline" type="submit" style="padding: 2px 8px; font-size: 11px;">Pin to channel</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(['u' => $creator['username'], 'page' => $page - 1]) ?>">Newer</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="?<?= http_build_query(['u' => $creator['username'], 'page' => $page + 1]) ?>">Older</a><?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>
