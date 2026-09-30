<?php page_header('Communities · Aurahub'); ?>
<div class="row" style="margin-bottom: 1.5rem; align-items: center;">
  <h1 style="margin: 0;">Communities</h1>
  <?php if ($me): ?>
    <a href="/aurahub/public/community_create" class="btn">Create Community</a>
  <?php endif; ?>
</div>

<?php if ($me && !empty($myCommunities)): ?>
  <div class="section">My Communities</div>
  <div class="grid" style="margin-bottom: 2rem;">
    <?php foreach ($myCommunities as $c): ?>
      <a href="/aurahub/public/community?id=<?= $c['id'] ?>" class="card" style="margin: 0; display: block; text-decoration: none; padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0;"><?= e($c['name']) ?></h3>
        <p class="muted" style="font-size: 13px; margin-bottom: 1rem;"><?= e((int)$c['member_count']) ?> members &middot; <?= $c['visibility'] === 'private' ? 'Private' : 'Public' ?></p>
        <p style="margin: 0; color: var(--text-secondary); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
          <?= e($c['description'] ?: 'No description provided.') ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="section">Discover Communities</div>
<?php if (empty($publicCommunities)): ?>
  <div class="empty">
    <p>No public communities found.</p>
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($publicCommunities as $c): ?>
      <a href="/aurahub/public/community?id=<?= $c['id'] ?>" class="card" style="margin: 0; display: block; text-decoration: none; padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0;"><?= e($c['name']) ?></h3>
        <p class="muted" style="font-size: 13px; margin-bottom: 1rem;"><?= e((int)$c['member_count']) ?> members</p>
        <p style="margin: 0; color: var(--text-secondary); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
          <?= e($c['description'] ?: 'No description provided.') ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php page_footer(); ?>
