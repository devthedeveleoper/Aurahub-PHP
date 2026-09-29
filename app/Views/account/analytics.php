<?php page_header('Creator analytics · Aurahub'); ?>
<h1>Creator analytics</h1>
<div class="analytics-summary" aria-label="Channel summary">
  <section><p class="muted">PUBLISHED VIDEOS</p><strong><?= number_format((int)$stats['video_count']) ?></strong></section>
  <section><p class="muted">TOTAL VIEWS</p><strong><?= number_format((int)$stats['total_views']) ?></strong></section>
  <section><p class="muted">AVERAGE VIEWS</p><strong><?= number_format((int)round((float)$stats['average_views'])) ?></strong></section>
</div>

<h2 class="section">Your videos</h2>
<?php if (!$videos): ?>
  <div class="empty"><p>No published videos to measure yet.</p><a class="btn" href="/aurahub/public/upload">Upload a video</a></div>
<?php else: ?>
  <div class="analytics-list">
  <?php foreach ($videos as $video): ?>
    <article class="analytics-row">
      <a class="analytics-video" href="/aurahub/public/watch?id=<?= (int)$video['id'] ?>">
        <div class="thumb">
          <?php if ($video['thumbnail_url']): ?><img src="<?= e($video['thumbnail_url']) ?>" alt="" loading="lazy">
          <?php else: ?><span class="ph"><?= e(mb_strtoupper(mb_substr($video['title'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <h3><?= e($video['title']) ?></h3>
      </a>
      <div class="analytics-metric"><strong><?= number_format((int)$video['views']) ?></strong><span class="muted">views</span></div>
      <p class="muted">Published <?= e(time_ago($video['created_at'])) ?></p>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_footer(); ?>
