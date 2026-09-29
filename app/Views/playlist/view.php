<?php page_header($playlist['name'] . ' · Aurahub'); ?>
<header class="channel-head">
  <p class="muted"><?= e(strtoupper($playlist['visibility'])) ?> PLAYLIST</p>
  <h1><?= e($playlist['name']) ?></h1>
  <p class="muted">By <a href="/aurahub/public/channel?u=<?= rawurlencode($playlist['username']) ?>"><?= e($playlist['username']) ?></a> · <?= $total ?> video<?= $total === 1 ? '' : 's' ?></p>
</header>

<?php if ($isOwner): ?>
<form class="playlist-settings" method="post" action="/aurahub/public/playlist?id=<?= $id ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <input type="hidden" name="action" value="update">
  <label>Playlist name <input name="name" maxlength="100" required value="<?= e($playlist['name']) ?>"></label>
  <label>Visibility
    <select name="visibility">
      <option value="private" <?= $playlist['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
      <option value="public" <?= $playlist['visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
    </select>
  </label>
  <button class="pill" type="submit">Save</button>
</form>
<?php endif; ?>

<?php if (!$items): ?>
  <div class="empty"><p>This playlist is empty.</p><?php if ($isOwner): ?><a class="btn" href="/aurahub/public/">Browse videos</a><?php endif; ?></div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($items as $item): ?>
    <?php $orderIndex = array_search((int)$item['id'], $orderedReadyIds, true); ?>
    <div class="vcard">
      <a href="/aurahub/public/watch?id=<?= (int)$item['id'] ?>">
        <div class="thumb">
          <?php if ($item['thumbnail_url']): ?><img src="<?= e($item['thumbnail_url']) ?>" alt="" loading="lazy">
          <?php else: ?><span class="ph"><?= e(mb_strtoupper(mb_substr($item['title'], 0, 1))) ?></span><?php endif; ?>
        </div>
        <h3><?= e($item['title']) ?></h3>
      </a>
      <p class="muted"><a href="/aurahub/public/channel?u=<?= rawurlencode($item['username']) ?>"><?= e($item['username']) ?></a> · <?= number_format($item['views']) ?> views</p>
      <?php if ($isOwner): ?>
      <div class="playlist-item-actions">
      <form method="post" action="/aurahub/public/playlist?id=<?= $id ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="action" value="move_video">
        <input type="hidden" name="video_id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="direction" value="up">
        <button class="link" type="submit" <?= $orderIndex === 0 ? 'disabled' : '' ?>>Move up</button>
      </form>
      <form method="post" action="/aurahub/public/playlist?id=<?= $id ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="action" value="move_video">
        <input type="hidden" name="video_id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="direction" value="down">
        <button class="link" type="submit" <?= $orderIndex === count($orderedReadyIds) - 1 ? 'disabled' : '' ?>>Move down</button>
      </form>
      <form method="post" action="/aurahub/public/playlist?id=<?= $id ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="action" value="remove_video">
        <input type="hidden" name="video_id" value="<?= (int)$item['id'] ?>">
        <button class="link" type="submit">Remove from playlist</button>
      </form>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(['id' => $id, 'page' => $page - 1]) ?>">Newer</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="?<?= http_build_query(['id' => $id, 'page' => $page + 1]) ?>">Older</a><?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>
