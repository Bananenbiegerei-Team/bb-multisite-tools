<?php
/* Context:
 * - $cleanup_tables
 * - $user_count
 */
?>
<div class="wrap" id="cleanup">
	<h1>Cleanup of Production Data</h1>

	<h2>MV Users</h2>
	<?php if ($user_count > 0): ?>
		<p>This will delete all MV users of the network.</p>
		<p>
			<span id="user_cleanup">
				<span class="b_error">🔴</span>
				<span class="b_idle">⚪️</span>
				<span class="b_progress">🟠</span>
				<span class="b_done">🟢</span>
			</span>
			<span id="count_users"><?php echo $user_count; ?></span> users to delete
		</p>
		<a id="user_cleanup_button" class="button" href="#" onClick="bbDeleteUsers()">Clean Up Users</a>
	<?php else: ?>
		<p>No users to delete.</p>
	<?php endif; ?>

	<h2>Tables</h2>
	<?php if (count($cleanup_tables) > 0): ?>
		<p>This will remove production data from the WP database: comments, Cookie Consent visitor data, WiseChat users and logs, WPForms entries.</p>
		<table class="wp-list-table striped widefat">
			<?php foreach ($cleanup_tables as $slug => $count): ?>
				<tr>
					<td>
						<tt><?php echo $slug; ?></tt>
					</td>
					<td style="text-align: right">
						<span id="count_cleanup_<?php echo $slug; ?>"><?php echo $count; ?></span>
					</td>
					<td style="text-align: right">
						<span id="table_cleanup_<?php echo $slug; ?>">
							<span class="b_error">🔴</span>
							<span class="b_idle">⚪️</span>
							<span class="b_progress">🟠</span>
							<span class="b_done">🟢</span>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<br>
		<a id="tables_cleanup_button" class="button" href="#" onClick="bbTruncateTables()">Clean Up Tables</a>
		<?php else: ?>
			<p>No tables to clean up.</p>
	<?php endif; ?>
</div>
<script>
	var tables = <?php echo json_encode(array_keys($cleanup_tables)); ?>;
	var userCount = <?php echo $user_count; ?>;
	var admin_url = "<?php echo admin_url('admin-ajax.php'); ?>";
	tables.forEach((t) => {
		bbSetStatus(`table_cleanup_${t}`, 'idle');
	});
	bbSetStatus('user_cleanup', 'idle');
</script>
