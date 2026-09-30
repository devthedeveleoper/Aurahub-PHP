<?php
page_header($q ? e($q) . ' · Aurahub' : 'Aurahub');
?>
<?php if ($q): ?><h2 class="section">Results for “<?= e($q) ?>”</h2><?php endif; ?>
<form class="browse-controls" method="get" action="/aurahub/public/">
  <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
  <label>Creator <input type="text" name="creator" value="<?= e($creatorFilter) ?>" maxlength="30" placeholder="Exact username"></label>
  <label>Category
    <select name="category">
      <option value="0">All</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Sort by
    <select name="sort">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most viewed</option>
      <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
    </select>
  </label>
  <button class="pill" type="submit">Apply</button>
</form>

<?php if (!$videos): ?>
  <div class="empty">
    <p><?= $q ? 'No videos match that search.' : 'No videos yet.' ?></p>
    <?php if (!$q): ?><a class="btn" href="/aurahub/public/upload">Upload the first video</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="grid">
  <?php foreach ($videos as $v): ?>
    <div class="vcard">
      <a href="/aurahub/public/watch?id=<?= (int)$v['id'] ?>">
      <div class="thumb">
        <?php if ($v['thumbnail_url']): ?>
          <img src="<?= e($v['thumbnail_url']) ?>" alt="" loading="lazy">
        <?php else: ?>
          <span class="ph"><?= e(mb_strtoupper(mb_substr($v['title'], 0, 1))) ?></span>
        <?php endif; ?>
      </div>
      <h3><?= e($v['title']) ?></h3>
      </a>
      <p class="muted"><a href="/aurahub/public/channel?u=<?= rawurlencode($v['username']) ?>"><?= e($v['username']) ?></a> · <?= number_format($v['views']) ?> views · <?= e(time_ago($v['created_at'])) ?></p>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="pager">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(['q' => $q, 'creator' => $creatorFilter, 'category' => $category, 'sort' => $sort, 'page' => $page - 1]) ?>">Previous</a><?php endif; ?>
    <?php if ($hasMore): ?><a href="?<?= http_build_query(['q' => $q, 'creator' => $creatorFilter, 'category' => $category, 'sort' => $sort, 'page' => $page + 1]) ?>">Next</a><?php endif; ?>
  </div>
<?php endif; ?>
<?php page_footer();
