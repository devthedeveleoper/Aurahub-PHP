<?php page_header('Sign up · Aurahub'); ?>
<form class="card narrow" method="post" action="/aurahub/public/register">
  <h1>Create your account</h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <label>Username <input name="username" value="<?= e($f['username']) ?>" required maxlength="30"></label>
  <label>Email <span class="muted">(Optional)</span> <input type="email" name="email" value="<?= e($f['email']) ?>"></label>
  <label>Password <input type="password" name="password" required minlength="8"></label>
  <button class="btn" type="submit">Sign up</button>
  <p class="muted">Already have an account? <a href="/aurahub/public/login">Log in</a></p>
</form>
<?php page_footer(); ?>
