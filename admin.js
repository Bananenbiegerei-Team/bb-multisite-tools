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

// Database table sorting
jQuery(document).ready(function () {
	const table = jQuery('#bb_db_usage');
	if (table.length === 0) return;

	const tbody = table.find('tbody');
	const headers = table.find('th.sortable');
	let currentSortColumn = null;
	let currentSortDirection = 'desc';

	// Set initial sort on "Rows" column (descending - showing biggest first)
	const rowsHeader = headers.filter(function() {
		return jQuery(this).text().includes('Rows');
	});
	if (rowsHeader.length > 0) {
		currentSortColumn = rowsHeader.index();
		rowsHeader.addClass('sort-desc');
		currentSortDirection = 'desc';
	}

	headers.on('click', function () {
		const th = jQuery(this);
		const columnIndex = th.index();
		const sortType = th.data('sort-type');

		// Determine sort direction
		if (currentSortColumn === columnIndex) {
			// Toggle direction
			currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
		} else {
			// New column, default to descending for numbers, ascending for strings
			currentSortDirection = sortType === 'number' ? 'desc' : 'asc';
		}

		currentSortColumn = columnIndex;

		// Update header classes
		headers.removeClass('sort-asc sort-desc');
		th.addClass('sort-' + currentSortDirection);

		// Sort rows
		const rows = tbody.find('tr').toArray();
		rows.sort(function (a, b) {
			const cellA = jQuery(a).find('td').eq(columnIndex);
			const cellB = jQuery(b).find('td').eq(columnIndex);

			let valA = cellA.data('value');
			let valB = cellB.data('value');

			// Handle empty values
			if (valA === undefined || valA === null || valA === '') valA = sortType === 'number' ? 0 : '';
			if (valB === undefined || valB === null || valB === '') valB = sortType === 'number' ? 0 : '';

			let comparison = 0;
			if (sortType === 'number') {
				valA = parseFloat(valA) || 0;
				valB = parseFloat(valB) || 0;
				comparison = valA - valB;
			} else {
				valA = String(valA).toLowerCase();
				valB = String(valB).toLowerCase();
				comparison = valA.localeCompare(valB);
			}

			return currentSortDirection === 'asc' ? comparison : -comparison;
		});

		// Re-append rows in sorted order
		tbody.empty().append(rows);
	});
});
