<?php
page_header('Subscriptions · Aurahub');
?>
<h2 class="section">Subscriptions</h2>

<?php if (!$videos): ?>
  <div class="empty">
    <p>No recent videos from your subscriptions.</p>
    <a class="btn" href="/aurahub/public/">Discover creators</a>
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
      <p class="muted"><a href="/aurahub/public/channel?u=<?= rawurlencode($v['username']) ?>"><?= e($v['username']) ?></a> · <?= number_format($v['views']) ?> views · <?= e(time_ago($v['created_at'])) ?></p>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="pager">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(['page' => $page - 1]) ?>">Previous</a><?php endif; ?>
    <?php if ($hasMore): ?><a href="?<?= http_build_query(['page' => $page + 1]) ?>">Next</a><?php endif; ?>
  </div>
<?php endif; ?>
<?php page_footer(); ?>
