<?php page_header(e($community['name']) . ' · Aurahub'); ?>

<div class="card" style="margin-top: 0; margin-bottom: 2rem;">
  <div class="row" style="align-items: center; justify-content: space-between;">
    <div>
      <h1 style="margin: 0;"><?= e($community['name']) ?></h1>
      <p class="muted" style="margin-top: 0.25rem;">
        Created by <a href="/aurahub/public/channel?u=<?= rawurlencode($community['creator_name']) ?>"><?= e($community['creator_name']) ?></a> 
        &middot; <?= time_ago($community['created_at']) ?> 
        &middot; <?= $community['visibility'] === 'private' ? 'Private Group' : 'Public Group' ?>
      </p>
    </div>
    <div>
      <?php if ($me): ?>
        <?php if ($role): ?>
          <form method="post" action="/aurahub/public/community?id=<?= $community['id'] ?>" style="display: inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="leave">
            <button class="pill" type="submit">Leave</button>
          </form>
        <?php else: ?>
          <form method="post" action="/aurahub/public/community?id=<?= $community['id'] ?>" style="display: inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="join">
            <button class="btn" type="submit">Join Community</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($community['description']): ?>
    <p style="margin-top: 1rem; color: var(--text-secondary);"><?= nl2br(e($community['description'])) ?></p>
  <?php endif; ?>
</div>

<div style="max-width: 640px; margin: 0 auto;">
  <?php if ($role): ?>
    <form class="card" style="margin-top: 0; margin-bottom: 2rem; padding: 1.5rem;" method="post" action="/aurahub/public/community?id=<?= $community['id'] ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="post">
      <textarea name="body" rows="3" placeholder="Post something to the community..." style="width: 100%; border: none; background: transparent; resize: none; margin-bottom: 1rem; font-size: 15px;" required></textarea>
      <!-- In a full implementation, you'd have a video picker here to attach a video_id -->
      <div style="display: flex; justify-content: flex-end;">
        <button class="btn" type="submit">Post</button>
      </div>
    </form>
  <?php elseif (!$me): ?>
    <div class="card" style="margin-top: 0; margin-bottom: 2rem; text-align: center; padding: 2rem;">
      <p>Log in to join the discussion.</p>
      <a href="/aurahub/public/login" class="btn">Log in</a>
    </div>
  <?php endif; ?>

  <div class="section">Recent Posts</div>
  
  <?php if (empty($posts)): ?>
    <div class="empty">No posts yet. Be the first to say hello!</div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
      <?php foreach ($posts as $post): ?>
        <div class="card" style="margin: 0; padding: 1.5rem;">
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <?php if (!empty($post['avatar_url'])): ?>
              <img src="<?= e($post['avatar_url']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
              <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-alt); display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">
                <?= e(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?>
              </span>
            <?php endif; ?>
            <div>
              <a href="/aurahub/public/channel?u=<?= rawurlencode($post['username']) ?>" style="font-weight: 600; color: var(--text);"><?= e($post['username']) ?></a>
              <div class="muted" style="font-size: 12px;"><?= time_ago($post['created_at']) ?></div>
            </div>
          </div>
          
          <div style="color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap; font-size: 15px; margin-bottom: <?= $post['video_id'] ? '1rem' : '0' ?>;"><?= e($post['body']) ?></div>
          
          <?php if ($post['video_id']): ?>
            <a href="/aurahub/public/watch?id=<?= $post['video_id'] ?>" style="display: flex; gap: 1rem; text-decoration: none; background: var(--surface-raised); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.75rem;">
              <div style="width: 120px; aspect-ratio: 16/9; background: #000; border-radius: 4px; overflow: hidden; flex-shrink: 0;">
                <?php if ($post['video_thumbnail']): ?>
                  <img src="<?= e($post['video_thumbnail']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
              </div>
              <div>
                <h4 style="margin: 0 0 0.25rem 0; color: var(--text); font-size: 14px;"><?= e($post['video_title']) ?></h4>
                <span class="muted" style="font-size: 12px;">Watch video</span>
              </div>
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php page_footer(); ?>
