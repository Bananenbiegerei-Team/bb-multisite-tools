// @ts-check

function bbSetStatus(id, status) {
	switch (status) {
		case 'idle':
			jQuery(`#${id} .b_error`).hide();
			jQuery(`#${id} .b_error`).hide();
			jQuery(`#${id} .b_progress`).hide();
			jQuery(`#${id} .b_done`).hide();
			jQuery(`#${id} .b_idle`).show();
			break;
		case 'progress':
			jQuery(`#${id} .b_error`).hide();
			jQuery(`#${id} .b_progress`).show();
			jQuery(`#${id} .b_done`).hide();
			jQuery(`#${id} .b_idle`).hide();
			break;
		case 'success':
			jQuery(`#${id} .b_error`).hide();
			jQuery(`#${id} .b_progress`).hide();
			jQuery(`#${id} .b_done`).show();
			jQuery(`#${id} .b_idle`).hide();
			break;
		case 'error':
			jQuery(`#${id} .b_error`).show();
			jQuery(`#${id} .b_progress`).hide();
			jQuery(`#${id} .b_done`).hide();
			jQuery(`#${id} .b_idle`).hide();
			break;
	}
}

function bbDeleteUsers() {
	var count = 200;
	jQuery('#user_cleanup_button').attr('disabled', 'disabled');
	console.log(`deleting ${count} users...`);
	bbSetStatus('user_cleanup', 'progress');
	jQuery.ajax({
		url: admin_url,
		data: {
			action: 'bb_multisite_tools_cleanup_data_mv_users',
			delete: count,
		},
		success: function (data) {
			var c = 0;
			JSON.parse(data).forEach((u) => {
				if (u.result) {
					c++;
				}
			});
			userCount = userCount - c;
			if (userCount < 0) {
				userCount = 0;
			}
			jQuery('#count_users').text(userCount);
			bbSetStatus('user_cleanup', 'success');
			if (userCount > 0) {
				bbDeleteUsers();
			}
		},
		error: function (errorThrown) {
			console.log(errorThrown);
			bbSetStatus('user_cleanup', 'error');
		},
	});
}

function bbTruncateTables() {
	jQuery('#tables_cleanup_button').attr('disabled', 'disabled');
	tables.forEach((table) => {
		console.log(`truncating '${table}' tables...`);
		bbSetStatus(`table_cleanup_${table}`, 'progress');
		jQuery.ajax({
			url: admin_url,
			data: {
				action: 'bb_multisite_tools_cleanup_data_tables',
				truncate: table,
			},
			success: function (data) {
				console.log(table, JSON.parse(data));
				bbSetStatus(`table_cleanup_${table}`, 'success');
				jQuery(`#count_cleanup_${table}`).text('0');
			},
			error: function (errorThrown) {
				console.log(errorThrown);
				bbSetStatus(`table_cleanup_${table}`, 'error');
			},
		});
	});
}

jQuery(document).ready(function () {
	// Users Overview
	jQuery('#users_pages .legend .role_bullet').on('click', function () {
		var roles = jQuery(this)
			.attr('class')
			.split(' ')
			.filter(function (e) {
				return e !== 'role_bullet_active';
			})
			.filter(function (e) {
				return e !== 'role_bullet';
			})
			.pop()
			.split('-X-')
			.filter((entry) => entry.trim() != '');
		if (roles.length === 0) {
			console.log('nope');
			return;
		} else if (roles[0] === 'reset') {
			jQuery('#users_pages tr[data-roles]').show();
			jQuery('#users_pages .legend .role_bullet:not(.button)').addClass('role_bullet_active');
			jQuery('#users_pages table.bb-users').addClass('striped').removeClass('filtered');
		} else {
			jQuery('#users_pages .legend .role_bullet').removeClass('role_bullet_active');
			jQuery(this).addClass('role_bullet_active');
			jQuery('#users_pages tr[data-roles]').hide();
			jQuery('#users_pages table.bb-users').removeClass('striped').addClass('filtered');
			roles.forEach((r) => {
				jQuery(`#users_pages tr[data-roles*="${r}"]`).show();
			});
		}
		var count = jQuery('.bb-users tr:visible').length;
		jQuery('.count_users').text(count);
	});

	jQuery('.bb-roles tr').on('click', function () {
		console.log(jQuery(this).find('ul'));
		jQuery(this).find('ul').toggle();
	});
});

jQuery(document).ready(function () {
	window.location.hash
		.slice(1)
		.split('_')
		.forEach(function (i) {
			jQuery(`#wpforms-overview-table input[value="${i}"]`).attr('checked', 'checked');
		});
});
