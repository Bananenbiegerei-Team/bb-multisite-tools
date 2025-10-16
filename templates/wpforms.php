<div class="wrap">
	<h1>WPForms</h1>
	<p>List of posts that contain embedded WPForms as well as WPForms that are not embedded in any post.</p>
	<p>	This also checks if the cache is disabled for the page slug. Check out the <i>Rejected URL Strings</i> in the <a target="_blank" href="/wp-admin/options-general.php?page=wpsupercache&tab=settings">WP Super Cache settings</a> to be sure.</p>

	<table class="wp-list-table striped widefat">
		<tr>
			<th>ID</th><th>Title</th><th>Type & Status</th><th>Slug</th><th>Forms</th><th style="text-align: center">Cache Disabled</th>
		</tr>
		<?php foreach ($posts_with_forms_per_site as $blog_id => $posts): ?>
			<?php if (count($unused_forms[$blog_id]) > 0 || count($posts) > 0): ?>
				<?php $site = $sites[$blog_id]->domain . $sites[$blog_id]->path; ?>
					<tr style="background-color: lightgrey;font-weight: bold;color: black;">
						<td colspan="7"><?= $site ?></td>
					</tr>
					<?php foreach ($posts as $post): ?>
						<tr>
							<td><a href="<?= $post->edit_url ?>"><?= $post->id ?></a></td>
							<td><?= $post->post_title ?></td>
							<td><?= $post->post_type ?> <?= $post->post_status ?></td>
							<td><tt><?= $post->slug ?></tt></td>
							<td>
							<table>
							<?php foreach($post->forms_data as $form) : ?>
							<tr>
							<td><?= $form['ID'] ?></td>
							<td><?= $form['title'] . ($form['save_resume_enabled'] ? '<br><b style="color: red">save &amp; resume enabled</b>': '') ?></td>
							<td><?= $form['links'] ?></td>
							</tr>
							<?php endforeach; ?>
							</table>
							
							</td>
							<td style="text-align: center"><?= $this->is_cache_disabled_for_slug($post->slug) ? '🟢' : '🔴' ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if (count($unused_forms[$blog_id] ?? []) > 0): ?>
						<tr style="background-color: white;color: black;">
							<td colspan="7">
								<?php $h = join('_', $unused_forms[$blog_id] ?? []); ?>
								<?php $list = join(', ', $unused_forms[$blog_id] ?? []); ?>
								<b><a href="//<?= $site ?>wp-admin/admin.php?page=wpforms-overview#<?= $h ?>" target="_blank">Unused Forms:</a></b> <?= $list ?>
							</td>
						</tr>
				<?php endif; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</table>
</div>
