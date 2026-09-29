<?php page_header('Report video · Aurahub'); ?>
<form class="card narrow" method="post" action="/aurahub/public/report?id=<?= $id ?>">
  <h1>Report video</h1>
  <p class="muted"><?= e($video['title']) ?></p>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <?php if ($existingReport): ?>
    <p>This video has already been reported from your account. Thank you for helping keep Aurahub safe.</p>
    <a class="pill" href="/aurahub/public/my_reports">View my reports</a>
    <a class="btn" href="/aurahub/public/watch?id=<?= (int)$id ?>">Back to video</a>
  <?php else: ?>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <label>Reason
      <select name="reason" required>
        <option value="" selected disabled>Choose a reason</option>
        <option value="spam">Spam</option>
        <option value="harassment">Harassment</option>
        <option value="copyright">Copyright concern</option>
        <option value="misleading">Misleading content</option>
        <option value="other">Other</option>
      </select>
    </label>
    <label>Details <span class="muted">(optional)</span>
      <textarea name="details" rows="4" maxlength="1000"></textarea>
    </label>
    <div class="actions">
      <button class="btn" type="submit">Submit report</button>
      <a class="pill" href="/aurahub/public/watch?id=<?= (int)$id ?>">Cancel</a>
    </div>
  <?php endif; ?>
</form>
<?php page_footer(); ?>
