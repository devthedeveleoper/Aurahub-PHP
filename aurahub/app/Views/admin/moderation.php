<?php page_header('Moderation · Aurahub'); ?>
<h1>Moderation queue</h1>
<nav class="moderation-filters" aria-label="Report status">
  <?php foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'all' => 'All'] as $value => $label): ?>
    <a class="pill <?= $filter === $value ? 'on' : '' ?>" href="?status=<?= e($value) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php if (!$reports): ?>
  <div class="empty"><p>No reports in this view.</p></div>
<?php else: ?>
  <div class="moderation-list">
  <?php foreach ($reports as $report): ?>
    <article class="moderation-item">
      <div class="moderation-summary">
        <div>
          <p class="muted"><?= e(strtoupper($report['status'])) ?> · <?= e(ucfirst($report['reason'])) ?> · <?= e(time_ago($report['created_at'])) ?></p>
          <h2><?php if ($report['video_id'] !== null): ?><a href="/aurahub/public/watch?id=<?= (int)$report['video_id'] ?>"><?= e($report['title']) ?></a><?php else: ?><?= e($report['title']) ?><?php endif; ?></h2>
          <p class="muted">By <?= e($report['owner_name'] ?? 'deleted creator') ?> · Reported by <?= e($report['reporter_name']) ?></p>
        </div>
      </div>
      <?php if ($report['details'] !== ''): ?><p><?= nl2br(e($report['details'])) ?></p><?php endif; ?>
      <?php if ($report['moderator_note'] !== ''): ?><p class="moderator-note"><strong>Decision note:</strong> <?= nl2br(e($report['moderator_note'])) ?></p><?php endif; ?>
      <?php if ($report['status'] === 'pending'): ?>
      <div class="actions">
        <?php if ($report['video_id'] !== null): ?><a class="pill" href="/aurahub/public/watch?id=<?= (int)$report['video_id'] ?>">View video</a><?php endif; ?>
        <form method="post" action="/aurahub/public/moderation"><?= csrf_field() ?><input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>"><input type="hidden" name="action" value="resolved"><input name="moderator_note" maxlength="1000" placeholder="Decision note (optional)"><button class="pill" type="submit">Resolve</button></form>
        <form method="post" action="/aurahub/public/moderation"><?= csrf_field() ?><input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>"><input type="hidden" name="action" value="dismissed"><input name="moderator_note" maxlength="1000" placeholder="Decision note (optional)"><button class="pill" type="submit">Dismiss</button></form>
        <?php if ($report['video_id'] !== null): ?><form method="post" action="/aurahub/public/moderation" onsubmit="return confirm('Permanently remove this video?')"><?= csrf_field() ?><input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>"><input type="hidden" name="action" value="remove_video"><input name="moderator_note" maxlength="1000" placeholder="Removal note (optional)"><button class="pill danger" type="submit">Remove video</button></form><?php endif; ?>
      </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_footer(); ?>
