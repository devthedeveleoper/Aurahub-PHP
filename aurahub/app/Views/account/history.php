<?php page_header('Recently watched · Aurahub'); ?>
<div class="history-heading">
  <h1>Recently watched</h1>
  <?php if ($total): ?>
  <form method="post" action="/aurahub/public/history" onsubmit="return confirm('Clear your watch history?')">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="clear">
    <button class="pill danger" type="submit">Clear history</button>
  </form>
  <?php endif; ?>
</div>
<?php if (!$videos): ?>
  <div class="empty"><p>Your watch history is empty.</p><a class="btn" href="/aurahub/public/">Browse videos</a></div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($videos as $video): ?>
    <div class="vcard">
      <a href="/aurahub/public/watch?id=<?= (int)$video['id'] ?>">
        <div class="thumb">
          <?php if ($video['thumbnail_url']): ?><img src="<?= e($video['thumbnail_url']) ?>" alt="" loading="lazy">
          <?php else: ?><span class="ph"><?= e(mb_strtoupper(mb_substr($video['title'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <h3><?= e($video['title']) ?></h3>
      </a>
      <p class="muted"><a href="/aurahub/public/channel?u=<?= rawurlencode($video['username']) ?>"><?= e($video['username']) ?></a> · <?= number_format($video['views']) ?> views · watched <?= e(time_ago($video['watched_at'])) ?></p>
      <form method="post" action="/aurahub/public/history">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="video_id" value="<?= (int)$video['id'] ?>">
        <button class="link" type="submit">Remove from history</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">Newer</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="?page=<?= $page + 1 ?>">Older</a><?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>
