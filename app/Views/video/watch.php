<?php page_header($v['title'] . ' · Aurahub'); ?>
<div class="watch">
  <section>
    <?php if ($v['status'] === 'ready' && $v['stream_id']): ?>
    <div class="player">
      <iframe src="<?= e(player_url($v['stream_id'])) ?>" allowfullscreen allow="autoplay; fullscreen; picture-in-picture"
              scrolling="no" frameborder="0" title="<?= e($v['title']) ?>"></iframe>
    </div>
    <?php else: ?>
        <div class="player pending" id="pending" data-id="<?= (int)$id ?>"
          data-status-url="<?= e(WRAPPER_BASE_URL . WRAPPER_PATH_REMOTE_STATUS) ?>"
          data-remote-id="<?= e((string)($v['remote_id'] ?? '')) ?>"
          data-status-csrf="<?= $me && (int)$me['id'] === (int)$v['user_id'] ? e(csrf_token()) : '' ?>">
      <p id="pmsg"><?= $v['status'] === 'failed' ? 'This video could not be imported. ' . e($v['status_msg'] ?? '') : 'Importing video from the link. This page updates on its own.' ?></p>
    </div>
    <?php if ($v['status'] === 'processing'): ?>
    <script>
    let tries = 0;
    (function poll(){
      if (++tries > 80) { document.getElementById('pmsg').textContent = 'Still importing. Refresh later to check.'; return; }
      const pending = document.getElementById('pending');
      if (pending.dataset.statusCsrf) {
        const endpoint = new URL(pending.dataset.statusUrl);
        endpoint.searchParams.set('id', pending.dataset.remoteId);
        fetch(endpoint, {headers: {Accept: 'application/json'}, cache: 'no-store'})
          .then(r => { if (!r.ok) throw new Error('Status check failed'); return r.json(); })
          .then(api => {
            const payload = api.result || api;
            const item = payload.status ? payload : (payload[pending.dataset.remoteId] || Object.values(payload)[0] || {});
            const apiState = String(item.status || '').toLowerCase();
            const terminal = ['finished', 'completed', 'complete', 'done', 'ready', 'success', 'error', 'failed', 'cancelled', 'canceled'].includes(apiState);
            if (!terminal) { setTimeout(poll, 15000); return; }
            const sync = new FormData();
            sync.append('csrf', pending.dataset.statusCsrf);
            sync.append('action', 'sync_remote_status');
            sync.append('id', pending.dataset.id);
            sync.append('api_response', JSON.stringify(api));
            return fetch('/aurahub/public/status', {method: 'POST', body: sync, cache: 'no-store'})
              .then(r => r.json()).then(result => {
                if (result.state === 'ready') return location.reload();
                if (result.state === 'failed') { document.getElementById('pmsg').textContent = 'This video could not be imported. ' + (result.message || ''); return; }
                setTimeout(poll, 15000);
              });
          })
          .catch(() => setTimeout(poll, 20000));
        return;
      }
      fetch('/aurahub/public/status?id=' + document.getElementById('pending').dataset.id, {cache:'no-store'})
        .then(r => r.json()).then(j => {
          if (j.state === 'ready') return location.reload();
          if (j.state === 'failed') { document.getElementById('pmsg').textContent = 'This video could not be imported. ' + (j.message || ''); return; }
          setTimeout(poll, 15000);
        }).catch(() => setTimeout(poll, 20000));
    })();
    </script>
    <?php endif; endif; ?>
    <h1><?= e($v['title']) ?></h1>
    <div class="row">
      <p class="muted">
        <a href="/aurahub/public/channel?u=<?= rawurlencode($v['username']) ?>"><?= e($v['username']) ?></a> 
        · <?= number_format($v['views']) ?> views 
        · <?= e(time_ago($v['created_at'])) ?>
        <?php if (!empty($v['category_name'])): ?>
          · Category: <a href="/aurahub/public/?category=<?= (int)$v['category_id'] ?>"><?= e($v['category_name']) ?></a>
        <?php endif; ?>
      </p>
      <div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="like">
          <button class="pill <?= $liked ? 'on' : '' ?>" <?= $me ? '' : 'disabled title="Log in to like"' ?>><?= $liked ? 'Liked' : 'Like' ?> · <?= $likes ?></button>
        </form>
        <?php if ($v['status'] === 'ready'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="watch_later">
          <button class="pill <?= $saved ? 'on' : '' ?>" <?= $me ? '' : 'disabled title="Log in to save videos"' ?>><?= $saved ? 'Saved' : 'Watch later' ?></button>
        </form>
        <?php if ($me && $myPlaylists): ?>
        <form method="post" class="playlist-add"><?= csrf_field() ?><input type="hidden" name="action" value="add_to_playlist">
          <select name="playlist_id" aria-label="Add to playlist" required>
            <option value="" selected disabled>Add to playlist</option>
            <?php foreach ($myPlaylists as $playlist): ?><option value="<?= (int)$playlist['id'] ?>"><?= e($playlist['name']) ?></option><?php endforeach; ?>
          </select>
          <button class="pill" type="submit">Add</button>
        </form>
        <?php elseif ($me): ?>
        <a class="pill" href="/aurahub/public/playlists">Create a playlist</a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($me && (int)$me['id'] === (int)$v['user_id']): ?>
        <a class="pill" href="/aurahub/public/edit_video?id=<?= (int)$id ?>">Edit details</a>
        <form method="post" onsubmit="return confirm('Delete this video permanently?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_video">
          <button class="pill danger">Delete</button>
        </form>
        <?php endif; ?>
        <?php if ($me && (int)$me['id'] !== (int)$v['user_id'] && $v['status'] === 'ready'): ?>
        <a class="pill danger" href="/aurahub/public/report?id=<?= (int)$id ?>">Report video</a>
        <?php endif; ?>
      </div>
    </div>
    <?php if (($_GET['reported'] ?? '') === '1'): ?><p class="muted">Thanks. Your report was submitted for review.</p><?php endif; ?>
    <?php if (trim((string)$v['description']) !== ''): ?><p class="desc"><?= nl2br(e($v['description'])) ?></p><?php endif; ?>

    <div id="comments">
      <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <h2 class="section"><?= $totalComments ?> comment<?= $totalComments === 1 ? '' : 's' ?></h2>
        <?php if ($totalComments > 0): ?>
        <form method="get" action="/aurahub/public/watch">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <select name="sort" onchange="this.form.submit()" aria-label="Sort comments" style="border:none; background:transparent; font:inherit; color:inherit; cursor:pointer;">
            <option value="newest" <?= $sortComments === 'newest' ? 'selected' : '' ?>>Newest first</option>
            <option value="popular" <?= $sortComments === 'popular' ? 'selected' : '' ?>>Top comments</option>
          </select>
        </form>
        <?php endif; ?>
      </div>
      <?php if (($_GET['comment_error'] ?? '') === 'invalid'): ?><p class="error">Comment cannot be empty and must be 1,000 characters or fewer.</p><?php endif; ?>
      <?php if ($me): ?>
      <form method="post" class="cform"><?= csrf_field() ?><input type="hidden" name="action" value="comment">
        <textarea name="body" rows="2" maxlength="1000" placeholder="Add a comment" required></textarea>
        <button class="btn" type="submit">Post</button>
      </form>
      <?php else: ?><p class="muted"><a href="/aurahub/public/login">Log in</a> to comment.</p><?php endif; ?>

      
      <?php
      // Helper function to render a single comment block
      $renderComment = function($c, $isReply = false) use ($me, $v, $commentReplies) {
      ?>
        <div class="comment" style="display: flex; gap: 1rem; margin-bottom: <?= $isReply ? '1rem' : '1.5rem' ?>; <?= $isReply ? 'margin-left: 3rem;' : '' ?>">
          <div style="flex-shrink: 0;">
            <a href="/aurahub/public/channel?u=<?= rawurlencode($c['username']) ?>">
              <?php if (!empty($c['avatar_url'])): ?>
                <img src="<?= e($c['avatar_url']) ?>" alt="" style="width: <?= $isReply ? '30px' : '40px' ?>; height: <?= $isReply ? '30px' : '40px' ?>; border-radius: 50%; object-fit: cover;">
              <?php else: ?>
                <div style="width: <?= $isReply ? '30px' : '40px' ?>; height: <?= $isReply ? '30px' : '40px' ?>; border-radius: 50%; background: var(--bg-alt); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: <?= $isReply ? '0.9rem' : '1.2rem' ?>;">
                  <?= e(mb_strtoupper(mb_substr($c['username'], 0, 1))) ?>
                </div>
              <?php endif; ?>
            </a>
          </div>
          <div style="flex-grow: 1;">
            <p style="margin-top: 0; margin-bottom: 0.5rem;"><strong><a href="/aurahub/public/channel?u=<?= rawurlencode($c['username']) ?>"><?= e($c['username']) ?></a></strong> <span class="muted"><?= e(time_ago($c['created_at'])) ?></span></p>
            <p style="margin-top: 0;"><?= nl2br(e($c['body'])) ?></p>
            
            <div style="display: flex; gap: 1rem; align-items: baseline;">
              <form method="post" class="comment-like-form" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="comment_like">
                <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                <?php if ($me): ?>
                <button class="link <?= $c['liked_by_me'] ? 'comment-liked' : '' ?>" type="submit"><?= $c['liked_by_me'] ? 'Liked' : 'Like' ?> · <?= (int)$c['like_count'] ?></button>
                <?php else: ?><span class="muted">Likes <?= (int)$c['like_count'] ?></span><?php endif; ?>
              </form>
              
              <?php if ($me && !$isReply): ?>
              <details class="comment-reply">
                <summary class="link">Reply</summary>
                <form method="post" class="cform" style="margin-top: 0.5rem;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="comment">
                  <input type="hidden" name="parent_id" value="<?= (int)$c['id'] ?>">
                  <textarea name="body" rows="2" maxlength="1000" placeholder="Write a reply..." required></textarea>
                  <button class="pill" type="submit">Post reply</button>
                </form>
              </details>
              <?php endif; ?>
            </div>

            <?php if ($me && (int)$me['id'] === (int)$c['user_id']): ?>
            <details class="comment-edit" style="margin-top: 0.5rem;">
              <summary>Edit comment</summary>
              <form method="post" class="cform">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit_comment">
                <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                <textarea name="body" rows="2" maxlength="1000" required><?= e($c['body']) ?></textarea>
                <button class="pill" type="submit">Save</button>
              </form>
            </details>
            <?php endif; ?>
            
            <?php if ($me && ((int)$me['id'] === (int)$c['user_id'] || (int)$me['id'] === (int)$v['user_id'])): ?>
            <form method="post" style="margin-top: 0.5rem;"><?= csrf_field() ?><input type="hidden" name="action" value="delete_comment"><input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
              <button class="link">Delete</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      <?php
      };
      
      foreach ($commentParents as $c): 
        $renderComment($c, false);
        if (!empty($commentReplies[$c['id']])) {
          foreach ($commentReplies[$c['id']] as $r) {
            $renderComment($r, true);
          }
        }
      endforeach; 
      ?>

    </div>
  </section>

  <aside>
    <h2 class="section">Up next</h2>
    <?php foreach ($related as $r): ?>
      <a class="rel" href="/aurahub/public/watch?id=<?= (int)$r['id'] ?>">
        <div class="thumb"><?php if ($r['thumbnail_url']): ?><img src="<?= e($r['thumbnail_url']) ?>" alt="" loading="lazy"><?php else: ?><span class="ph"><?= e(mb_strtoupper(mb_substr($r['title'], 0, 1))) ?></span><?php endif; ?></div>
        <div><h3><?= e($r['title']) ?></h3><p class="muted"><?= e($r['username']) ?> · <?= number_format($r['views']) ?> views</p></div>
      </a>
    <?php endforeach; ?>
  </aside>
</div>
<?php page_footer();
