<?php page_header('User controls · Aurahub'); ?>
<h1>User controls</h1>
<p class="muted">Suspended accounts cannot sign in or continue an existing session. Administrator accounts are not managed here.</p>
<?php if (!$users): ?>
  <div class="empty"><p>No regular accounts found.</p></div>
<?php else: ?>
  <div class="moderation-list">
  <?php foreach ($users as $account): ?>
    <article class="moderation-item user-control-row">
      <div>
        <p class="muted"><?= e(strtoupper($account['account_status'])) ?> · <?= (int)$account['video_count'] ?> videos · joined <?= e(time_ago($account['created_at'])) ?></p>
        <h2><?= e($account['username']) ?></h2>
        <p class="muted"><?= e($account['email']) ?></p>
      </div>
      <form method="post" action="/aurahub/public/admin_users" onsubmit="return confirm('<?= $account['account_status'] === 'active' ? 'Suspend' : 'Reactivate' ?> this account?')">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int)$account['id'] ?>">
        <input type="hidden" name="action" value="<?= $account['account_status'] === 'active' ? 'suspend' : 'reactivate' ?>">
        <button class="pill <?= $account['account_status'] === 'active' ? 'danger' : '' ?>" type="submit">
          <?= $account['account_status'] === 'active' ? 'Suspend account' : 'Reactivate account' ?>
        </button>
      </form>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_footer(); ?>
