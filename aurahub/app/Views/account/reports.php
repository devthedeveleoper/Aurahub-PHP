<?php page_header('My reports · Aurahub'); ?>
<h1>My reports</h1>
<?php if (!$reports): ?>
  <div class="empty"><p>You haven't reported any videos.</p><a class="btn" href="/aurahub/public/">Browse videos</a></div>
<?php else: ?>
  <div class="moderation-list">
  <?php foreach ($reports as $report): ?>
    <article class="moderation-item">
      <p class="muted"><span class="report-status <?= e($report['status']) ?>"><?= e(strtoupper($report['status'])) ?></span> · <?= e(ucfirst($report['reason'])) ?> · Reported <?= e(time_ago($report['created_at'])) ?></p>
      <h2><?php if ($report['video_id'] !== null): ?><a href="/aurahub/public/watch?id=<?= (int)$report['video_id'] ?>"><?= e($report['title']) ?></a><?php else: ?><?= e($report['title']) ?><?php endif; ?></h2>
      <?php if ($report['details'] !== ''): ?><p><?= nl2br(e($report['details'])) ?></p><?php endif; ?>
      <?php if ($report['moderator_note'] !== ''): ?><p class="moderator-note"><strong>Moderator update:</strong> <?= nl2br(e($report['moderator_note'])) ?></p><?php endif; ?>
      <?php if ($report['reviewed_at']): ?><p class="muted">Reviewed <?= e(time_ago($report['reviewed_at'])) ?></p><?php endif; ?>
    </article>
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
