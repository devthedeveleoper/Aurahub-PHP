<?php page_header('Create Community · Aurahub'); ?>
<form class="card narrow" method="post" action="/aurahub/public/community_create">
  <h1>Create a Community</h1>
  <p class="muted">Start a space for discussion and sharing videos.</p>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?= csrf_field() ?>
  
  <label>Community Name
    <input name="name" required maxlength="100" placeholder="e.g. Speedrunning, Synthwave, Tech Talk">
  </label>
  
  <label>Description <span class="muted">(optional)</span>
    <textarea name="description" rows="4" maxlength="1000" placeholder="What is this community about?"></textarea>
  </label>

  <label>Visibility
    <select name="visibility">
      <option value="public" selected>Public (Anyone can find and join)</option>
      <option value="private">Private (Only invited members can join)</option>
    </select>
  </label>
  
  <div class="actions" style="margin-top: 1rem;">
    <button class="btn" type="submit">Create</button>
    <a class="pill" href="/aurahub/public/communities">Cancel</a>
  </div>
</form>
<?php page_footer(); ?>
