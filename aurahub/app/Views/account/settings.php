<?php page_header('Account settings · Aurahub'); ?>
<form class="card narrow" method="post" action="/aurahub/public/account">
  <h1>Account settings</h1>
  <p class="muted">Signed in as <?= e($me['username']) ?></p>
  <?php if ($success): ?><p class="muted">Password updated.</p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <label>Current password <input type="password" name="current_password" autocomplete="current-password" required></label>
  <label>New password <input type="password" name="new_password" minlength="8" autocomplete="new-password" required></label>
  <label>Confirm new password <input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
  <button class="btn" type="submit">Change password</button>
</form>
<?php page_footer(); ?>
