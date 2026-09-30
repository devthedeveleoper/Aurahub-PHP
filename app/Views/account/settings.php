<?php page_header('Account settings · Aurahub'); ?>
<div class="card narrow">
  <h1>Account settings</h1>
  <p class="muted">Signed in as <?= e($me['username']) ?></p>
  <?php if ($success): ?><p class="muted">Changes saved successfully.</p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

  <form method="post" action="/aurahub/public/account" enctype="multipart/form-data" style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 2rem;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="avatar">
    <h3>Profile Picture</h3>
    <?php if (!empty($me['avatar_url'])): ?>
      <div style="margin-bottom: 1rem;">
        <img src="<?= e($me['avatar_url']) ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
      </div>
    <?php endif; ?>
    <label>Upload new picture <span class="muted">(Max <?= MAX_IMAGE_MB ?> MB)</span>
      <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" required>
    </label>
    <button class="btn" type="submit">Upload picture</button>
  </form>

  <form method="post" action="/aurahub/public/account" style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 2rem;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="privacy">
    <h3>Privacy Settings</h3>
    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 400; cursor: pointer;">
      <input type="checkbox" name="keep_history" value="1" <?= !empty($me['keep_history']) ? 'checked' : '' ?> style="width: auto; margin: 0;">
      Keep a history of videos I watch
    </label>
    <p class="muted" style="margin-top: -0.5rem; margin-bottom: 1rem;">If disabled, newly watched videos won't appear in your history.</p>
    <button class="btn" type="submit">Save privacy settings</button>
  </form>

  <form method="post" action="/aurahub/public/account">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="password">
    <h3>Change Password</h3>
    <label>Current password <input type="password" name="current_password" autocomplete="current-password" required></label>
    <label>New password <input type="password" name="new_password" minlength="8" autocomplete="new-password" required></label>
    <label>Confirm new password <input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
    <button class="btn" type="submit">Change password</button>
  </form>

  <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border);">
    <h3>Data Export</h3>
    <p class="muted" style="margin-bottom: 1rem;">Download a copy of your channel data (videos metadata, profile) in JSON format.</p>
    <a href="/aurahub/public/account/export" class="pill" target="_blank">Export My Data</a>
  </div>
</div>
<?php page_footer(); ?>
