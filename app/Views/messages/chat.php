<?php page_header('Chat with ' . $otherUser['username'] . ' · Aurahub'); ?>

<div class="card" style="padding: 0; display: flex; flex-direction: column; height: 75vh; max-height: 800px; margin-bottom: 2rem;">
  
  <!-- Header -->
  <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 1rem; background: var(--surface-raised);">
    <a href="/aurahub/public/messages" style="color: var(--text-secondary); text-decoration: none; font-size: 1.2rem;">&larr;</a>
    <a href="/aurahub/public/channel?u=<?= rawurlencode($otherUser['username']) ?>" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--text);">
      <?php if (!empty($otherUser['avatar_url'])): ?>
        <img src="<?= e($otherUser['avatar_url']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
      <?php else: ?>
        <span style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-alt); display: inline-flex; align-items: center; justify-content: center; font-weight: 600;">
          <?= e(mb_strtoupper(mb_substr($otherUser['username'], 0, 1))) ?>
        </span>
      <?php endif; ?>
      <h3 style="margin: 0; font-size: 16px;"><?= e($otherUser['username']) ?></h3>
    </a>
  </div>

  <!-- Messages Area -->
  <div style="flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; background: var(--bg);">
    <?php if (empty($messages)): ?>
      <div class="empty" style="margin: auto; border: none; background: transparent;">
        <p>No messages yet.</p>
        <p class="muted">Send a message to start the conversation.</p>
      </div>
    <?php else: ?>
      <?php foreach ($messages as $msg): 
        $isMe = $msg['sender_id'] === $me['id'];
      ?>
        <div style="display: flex; flex-direction: column; align-items: <?= $isMe ? 'flex-end' : 'flex-start' ?>; max-width: 80%; align-self: <?= $isMe ? 'flex-end' : 'flex-start' ?>;">
          
          <div style="background: <?= $isMe ? 'var(--primary)' : 'var(--surface-raised)' ?>; color: <?= $isMe ? 'white' : 'var(--text)' ?>; padding: 0.75rem 1rem; border-radius: 1rem; <?= $isMe ? 'border-bottom-right-radius: 0.25rem;' : 'border-bottom-left-radius: 0.25rem;' ?>">
            
            <?php if ($msg['video_id']): ?>
              <a href="/aurahub/public/watch?id=<?= $msg['video_id'] ?>" style="display: block; width: 240px; margin-bottom: <?= $msg['body'] ? '0.75rem' : '0' ?>; text-decoration: none; color: inherit;">
                <div style="width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 4px; overflow: hidden; margin-bottom: 0.5rem;">
                  <?php if ($msg['video_thumbnail']): ?>
                    <img src="<?= e($msg['video_thumbnail']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php endif; ?>
                </div>
                <div style="font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($msg['video_title']) ?></div>
              </a>
            <?php endif; ?>
            
            <?php if ($msg['body']): ?>
              <div style="font-size: 15px; line-height: 1.4; white-space: pre-wrap; word-break: break-word;"><?= e($msg['body']) ?></div>
            <?php endif; ?>
          </div>
          
          <div style="font-size: 11px; color: var(--text-secondary); margin-top: 0.25rem; margin-<?= $isMe ? 'right' : 'left' ?>: 0.5rem;">
            <?= date('M j, g:i a', strtotime($msg['created_at'])) ?>
            <?php if ($isMe && $msg['read_at']): ?>&middot; Read<?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Input Area -->
  <form method="post" action="/aurahub/public/messages/chat?u=<?= rawurlencode($otherUser['username']) ?>" style="padding: 1rem; border-top: 1px solid var(--border); background: var(--surface-raised); margin: 0; display: flex; gap: 0.5rem; align-items: flex-end;">
    <?= csrf_field() ?>
    <textarea name="body" rows="1" placeholder="Write a message..." style="flex: 1; resize: none; margin: 0; max-height: 100px; padding: 0.75rem; border-radius: 20px; font-family: inherit;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
    <button type="submit" class="btn" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" aria-label="Send">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
    </button>
  </form>

</div>

<script>
// Auto-scroll to bottom of messages
const chatArea = document.querySelector('.card > div:nth-child(2)');
chatArea.scrollTop = chatArea.scrollHeight;
</script>

<?php page_footer(); ?>
