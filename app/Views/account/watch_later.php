<?php page_header('Watch Later · Aurahub'); ?>
<h1>Watch Later</h1>
<?php if (!$videos): ?>
  <div class="empty">
    <p>Your saved videos will show up here.</p>
    <a class="btn" href="/aurahub/public/">Browse videos</a>
  </div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($videos as $v): ?>
    <div class="vcard">
      <a href="/aurahub/public/watch?id=<?= (int)$v['id'] ?>">
        <div class="thumb">
          <?php if ($v['thumbnail_url']): ?>
            <img src="<?= e($v['thumbnail_url']) ?>" alt="" loading="lazy">
          <?php else: ?>
            <span class="ph"><?= e(mb_strtoupper(mb_substr($v['title'], 0, 1))) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= e($v['title']) ?></h3>
      </a>
      <p class="muted"><a href="/aurahub/public/channel?u=<?= rawurlencode($v['username']) ?>"><?= e($v['username']) ?></a> · <?= number_format($v['views']) ?> views · saved <?= e(time_ago($v['saved_at'])) ?></p>
      <form method="post" action="/aurahub/public/watch_later">
        <?= csrf_field() ?>
        <input type="hidden" name="video_id" value="<?= (int)$v['id'] ?>">
        <button class="link" type="submit">Remove</button>
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
