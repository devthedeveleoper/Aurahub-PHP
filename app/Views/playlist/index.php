<?php page_header('Playlists · Aurahub'); ?>
<h1>Your playlists</h1>
<form class="card playlist-create" method="post" action="/aurahub/public/playlists">
  <h2 class="section">New playlist</h2>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="create">
  <label>Name <input name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>"></label>
  <label>Visibility
    <select name="visibility">
      <option value="private" <?= ($_POST['visibility'] ?? 'private') === 'private' ? 'selected' : '' ?>>Private</option>
      <option value="public" <?= ($_POST['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Public</option>
    </select>
  </label>
  <button class="btn" type="submit">Create playlist</button>
</form>

<?php if (!$playlists): ?>
  <div class="empty"><p>No playlists yet.</p></div>
<?php else: ?>
  <div class="playlist-list">
  <?php foreach ($playlists as $playlist): ?>
    <section class="playlist-row">
      <div>
        <h2><a href="/aurahub/public/playlist?id=<?= (int)$playlist['id'] ?>"><?= e($playlist['name']) ?></a></h2>
        <p class="muted"><?= e(ucfirst($playlist['visibility'])) ?> · <?= (int)$playlist['video_count'] ?> videos</p>
      </div>
      <form method="post" action="/aurahub/public/playlists" onsubmit="return confirm('Delete this playlist? Videos will not be deleted.')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="playlist_id" value="<?= (int)$playlist['id'] ?>">
        <button class="pill danger" type="submit">Delete</button>
      </form>
    </section>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php page_footer(); ?>
