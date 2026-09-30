<?php page_header('Edit video · Aurahub'); ?>
<form class="card" method="post" enctype="multipart/form-data" action="/aurahub/public/edit_video?id=<?= $id ?>">
  <h1>Edit video</h1>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <label>Title <input name="title" required maxlength="150" value="<?= e($title) ?>"></label>
  <label>Category
    <select name="category_id">
      <option value="">None</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= (int)($video['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Visibility
    <select name="visibility">
      <option value="public" <?= ($video['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>Public (Anyone can watch)</option>
      <option value="unlisted" <?= ($video['visibility'] ?? 'public') === 'unlisted' ? 'selected' : '' ?>>Unlisted (Anyone with the link)</option>
      <option value="private" <?= ($video['visibility'] ?? 'public') === 'private' ? 'selected' : '' ?>>Private (Only you)</option>
      <option value="subscribers" <?= ($video['visibility'] ?? 'public') === 'subscribers' ? 'selected' : '' ?>>Subscribers Only</option>
    </select>
  </label>
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
