<?php page_header('Log in · Aurahub'); ?>
<form class="card narrow" method="post" action="/aurahub/public/login">
  <h1>Welcome back</h1>
  <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <label>Email or username <input name="identity" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <button class="btn" type="submit">Log in</button>
  <p class="muted">New here? <a href="/aurahub/public/register">Create an account</a></p>
</form>
<?php page_footer(); ?>
