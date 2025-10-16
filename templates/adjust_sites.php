<?php
/* Context:
 * - $sites
 */
?>
<?php $updates_needed = false; ?>
<div class="wrap">
	<h1>Adjust Network Sites Domains & Paths</h1>
	<table class="wp-list-table striped widefat">
		<?php foreach ($sites as $site): ?>
			<?php $updates_needed = $updates_needed || $site->update_needed; ?>
			<?php $arrow = $site->update_needed ? '&rarr;' : '&nbsp;'; ?>
			<?php $status = $site->update_needed ? '🟠' : '🟢'; ?>
			<tr>
				<td><?= $site->blog_id ?></td>
				<td><tt><?= $site->domain . $site->path ?></tt></td>
				<td><?= $arrow ?></td><td><tt><?= $site->update_needed ? $_SERVER['SERVER_NAME'] . $site->new_path : '' ?></tt></td>
				<td><?= $status ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php if ($updates_needed): ?>
		<br>
		<form name="adjust_sites" method="post" action="<?php echo $form_url; ?>">
			<input type="hidden" name="form" value="adjust_sites">
			<input type="hidden" name="update" value="1">
			<input class="button" type="submit" value="Update">
		</form>
	<?php endif; ?>
</div>
