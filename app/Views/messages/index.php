<?php page_header('Messages · Aurahub'); ?>
<div class="row" style="margin-bottom: 1.5rem; align-items: center;">
  <h1 style="margin: 0;">Messages</h1>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
  <?php if (empty($conversations)): ?>
    <div class="empty" style="padding: 3rem 1rem;">
      <p>You have no messages yet.</p>
      <p class="muted">Go to a creator's channel to start a conversation.</p>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column;">
      <?php foreach ($conversations as $conv): ?>
        <a href="/aurahub/public/messages/chat?u=<?= rawurlencode($conv['other_username']) ?>" 
           style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; text-decoration: none; border-bottom: 1px solid var(--border); background: <?= $conv['unread_count'] > 0 ? 'var(--surface-raised)' : 'transparent' ?>; color: var(--text);">
          
          <?php if (!empty($conv['avatar_url'])): ?>
            <img src="<?= e($conv['avatar_url']) ?>" alt="" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            <span style="width: 48px; height: 48px; border-radius: 50%; background: var(--bg-alt); display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.2rem;">
              <?= e(mb_strtoupper(mb_substr($conv['other_username'], 0, 1))) ?>
            </span>
          <?php endif; ?>
          
          <div style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.25rem;">
              <h3 style="margin: 0; font-size: 15px; <?= $conv['unread_count'] > 0 ? 'font-weight: 700;' : 'font-weight: 600;' ?>"><?= e($conv['other_username']) ?></h3>
              <span class="muted" style="font-size: 12px; white-space: nowrap;"><?= time_ago($conv['last_message_date']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <p style="margin: 0; font-size: 14px; color: <?= $conv['unread_count'] > 0 ? 'var(--text)' : 'var(--text-secondary)' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php if ($conv['sender_id'] === $me['id']): ?><span class="muted">You: </span><?php endif; ?>
                <?= e($conv['last_message'] ?: 'Sent a video') ?>
              </p>
              <?php if ($conv['unread_count'] > 0): ?>
                <span style="background: var(--primary); color: white; border-radius: 12px; padding: 2px 8px; font-size: 11px; font-weight: 600; margin-left: 0.5rem;">
                  <?= $conv['unread_count'] ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php page_footer(); ?>
