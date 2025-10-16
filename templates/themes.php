<?php
/* Context:
 * - $themes
 */
?>
<div class="wrap">
  <h1>Themes</h1>
  <table class="wp-list-table plugins striped widefat" id="bb_themes_usage">
  <thead>
    <tr>
    <th scope="col" class="manage-column column-name column-primary"><span>Theme</span></th>
    <th scope="col" class="manage-column column-description">Sites</th>
    </tr>
  </thead>
  <tbody id="the-list">
    <?php foreach ($themes as $stylesheet => $sites): ?>
    <tr>
    <td class="theme-title column-primary">
      <strong><?php echo $stylesheet; ?></strong>
    </td>
    <td class="column-description desc">
      <?php if (count($sites) > 0): ?>
      <ul>
      <?php foreach ($sites as $site) { ?>
      <li><a href="<?php echo $site; ?>/wp-admin/"><?php echo $site; ?></a></li>
      <?php } ?>
      </ul>
      <?php else: ?>
      <span class="bullet">unused</span>
      <?php endif; ?>
    </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  </table>
</div>