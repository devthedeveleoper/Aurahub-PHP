<?php page_header('Transparency Report · Aurahub'); ?>
<div class="card">
  <h1>Transparency Report</h1>
  <p class="muted">Live Moderation Statistics</p>

  <p>Aurahub is committed to a transparent, legal-only moderation policy. We do not use automated content filters to ban users or remove videos based on engagement metrics or community guidelines. Content is only removed if it violates the law.</p>
  
  <p>Below is a live, real-time count of all videos that have been removed from the platform by our moderation team, categorized by the specific legal reason for removal.</p>

  <hr>

  <h2>Videos Removed by Category</h2>
  
  <?php if (empty($stats)): ?>
    <div class="empty">No videos have been removed yet.</div>
  <?php else: ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 1.5rem;">
      <thead>
        <tr style="border-bottom: 2px solid var(--border); text-align: left;">
          <th style="padding: 1rem 0;">Removal Reason</th>
          <th style="padding: 1rem 0; text-align: right;">Total Takedowns</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          $labels = [
            'csam' => 'Child Exploitation Material (CSAM)',
            'terrorism' => 'Terrorism Recruitment / Propaganda',
            'doxxing' => 'Doxxing (Private Information)',
            'violence' => 'Direct Threats of Real-World Violence',
            'illegal' => 'Other Illegal Content',
            'other' => 'Other Legal Order'
          ];
          $totalRemoved = 0;
        ?>
        <?php foreach ($stats as $stat): 
          $totalRemoved += $stat['count'];
        ?>
          <tr style="border-bottom: 1px solid var(--border);">
            <td style="padding: 1rem 0; font-weight: 500;">
              <?= e($labels[$stat['reason']] ?? $stat['reason']) ?>
            </td>
            <td style="padding: 1rem 0; text-align: right; font-variant-numeric: tabular-nums; font-size: 1.1rem;">
              <?= number_format($stat['count']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td style="padding: 1rem 0; font-weight: 700;">Total Videos Removed</td>
          <td style="padding: 1rem 0; text-align: right; font-weight: 700; font-size: 1.2rem;"><?= number_format($totalRemoved) ?></td>
        </tr>
      </tfoot>
    </table>
  <?php endif; ?>

  <div style="margin-top: 3rem; background: var(--bg); padding: 1.5rem; border-radius: var(--radius);">
    <h3>Moderation Process</h3>
    <ul style="margin-left: 1.5rem; margin-bottom: 0;">
      <li><strong>Human Review:</strong> Every report is reviewed by a human moderator.</li>
      <li><strong>Notification:</strong> When a video is removed, the creator is always notified of the exact reason.</li>
      <li><strong>No Shadowbanning:</strong> If a video is legal, it stays up and is fully accessible via search and chronological feeds.</li>
    </ul>
    <p style="margin-top: 1rem; margin-bottom: 0;"><a href="/aurahub/public/policy">Read our full Content Policy &rarr;</a></p>
  </div>
</div>
<?php page_footer(); ?>
