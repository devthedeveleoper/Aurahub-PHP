<?php page_header($creator['username'] . ' · Aurahub'); ?>
<header class="channel-head">
  <p class="muted">CREATOR CHANNEL</p>
  <h1><?= e($creator['username']) ?></h1>
  <p class="muted"><?= number_format($total) ?> video<?= $total === 1 ? '' : 's' ?> · Member since <?= e(date('F Y', strtotime($creator['created_at']))) ?></p>
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
