<?php page_header('Edit video · Aurahub'); ?>
<form class="card" method="post" enctype="multipart/form-data" action="/aurahub/public/edit_video?id=<?= $id ?>">
  <h1>Edit video</h1>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <label>Title <input name="title" required maxlength="150" value="<?= e($title) ?>"></label>
  <label>Description <textarea name="description" rows="5" maxlength="10000"><?= e($description) ?></textarea></label>
  <?php if ($video['thumbnail_url']): ?>
  <div class="thumb" style="max-width: 320px; margin-bottom: 1rem"><img src="<?= e($video['thumbnail_url']) ?>" alt="Current thumbnail"></div>
  <?php endif; ?>
  <label>Replace thumbnail <span class="muted">(optional, max <?= MAX_IMAGE_MB ?> MB)</span>
    <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp,image/gif">
  </label>
  <div class="actions">
    <button class="btn" type="submit">Save changes</button>
    <a class="pill" href="/aurahub/public/watch?id=<?= (int)$id ?>">Cancel</a>
  </div>
</form>
<?php page_footer(); ?>
