<?php
/* Context:
 * - $block_usage_data
 */
?>
<div class="wrap">
  <h1>ACF Blocks Usage </h1>
  <p>Posts in red are custom post types present in the DB but not used by the site theme.</p>
  <p><a href="#" onclick="toggleAll()" class="button">Toggle all</a></p>
  <table class="wp-list-table striped widefat">
  <?php foreach ($block_usage_data as $site_id => $site_data): ?>
  <tr>
    <td class="bbacf_site"><?= $site_data->desc ?></td>
    <td class="bbacf_blocks">
    <table style="display:none">
      <?php foreach ($site_data->blocks as $block): ?>
      <tr>
      <td class="bbacf_block"><?= $block->name ?></td>
      <td class="bbacf_posts">
        <?php foreach ($block->posts as $post): ?>
        <?php if (in_array($post->post_type, $site_data->post_types)): ?>
        <span class="bbacf_post"><a target="_blank" href="<?= $post->edit_url ?>"><?= $post->post_type ?> (<?= $post->ID ?>)</a></span>
        <?php else: ?>
        <span class="bbacf_post missing"><?= $post->post_type ?> (<?= $post->ID ?>)</span>
        <?php endif; ?>
        <?php endforeach; ?>
      </td>
      </tr>
      <?php endforeach; ?>
    </table>
    </td>
  </tr>
  <?php endforeach; ?>
  </table>
</div>
<script>
function toggleAll() {
  jQuery('.bbacf_blocks > table').toggle();
}
jQuery('.bbacf_site').on('click', function() {
  jQuery(this).parent().find('.bbacf_blocks > table').toggle();
});
</script>