<?php
/* Context:
 * - $tab
 * - $db_stats
 * - $orphans
 */
?>
<div class="wrap">
  <h1>Database</h1>

  <?php if (is_multisite()): ?>
  <nav class="nav-tab-wrapper">
  <a href="?page=bb_multisite_tools_db&tab=stats" class="nav-tab <?php if ('stats' === $tab) { ?>nav-tab-active<?php } ?>">Stats</a>
  <a href="?page=bb_multisite_tools_db&tab=orphans" class="nav-tab <?php if ('orphans' === $tab) { ?>nav-tab-active<?php } ?>">Orphaned Tables</a>
  </nav>
  <?php endif; ?>

  <div class="tab-content">
  <?php if ($tab == 'stats'): ?>
  <table id="bb_db_usage" class="wp-list-table striped widefat sortable">
    <thead>
    <tr>
    <th class="sortable" data-sort-type="string">Table <span class="sort-indicator"></span></th>
    <th class="sortable" data-sort-type="string">Site <span class="sort-indicator"></span></th>
    <th class='number sortable' data-sort-type="number">Total Size (MB) <span class="sort-indicator"></span></th>
    <th class='number sortable' data-sort-type="number">Rows <span class="sort-indicator"></span></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($db_stats as $table): ?>
    <tr>
    <?php echo "<td data-value='{$table->table_name}'><tt>{$table->table_name}</tt></td><td data-value='{$table->site}'>{$table->site}</td><td class='number' data-value='{$table->total_size}'>{$table->size}</td><td class='number' data-value='{$table->table_rows}'>{$table->rows}</td>"; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php elseif ($tab == 'orphans'): ?>
  <p>Found <?php echo count($orphans); ?> tables not used by any site.</p>
  <?php if (count($orphans) > 0): ?>
  <ul>
    <li><tt><?php echo join('</tt></li><li><tt>', $orphans); ?></tt></li>
  </ul>
  <?php $form_url = network_admin_url() . 'admin.php?page=bb_multisite_tools_db&tab=orphans'; ?>
  <form name="delete_orphans" method="post" action="<?php echo $form_url; ?>">
    <input type="hidden" name="form" value="delete_orphans">
    <input class="button" type="submit" value="Delete tables">
  </form>
  <?php endif; ?>
  <?php endif; ?>
  </div>
</div>