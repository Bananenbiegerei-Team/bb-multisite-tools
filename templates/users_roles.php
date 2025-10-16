  <div class="wrap" id="users_pages">
  <h1>Users Overview</h1>
  <nav class="nav-tab-wrapper">
    <a href="?page=bb_multisite_tools_users_roles&tab=wmde" class="nav-tab <?php if ('wmde' === $tab) { ?>nav-tab-active<?php } ?>">Admins & Content Editors</a>
    <a href="?page=bb_multisite_tools_users_roles&tab=none" class="nav-tab <?php if ('none' === $tab) { ?>nav-tab-active<?php } ?>">No Roles</a>
    <a href="?page=bb_multisite_tools_users_roles&tab=mv" class="nav-tab <?php if ('mv' === $tab) { ?>nav-tab-active<?php } ?>">MV Users</a>
    <!-- -->
    <a href="?page=bb_multisite_tools_users_roles&tab=set_roles" class="nav-tab <?php if ('set_roles' === $tab) { ?>nav-tab-active<?php } ?>">Set Roles</a>
    <a href="?page=bb_multisite_tools_users_roles&tab=roles" class="nav-tab <?php if ('roles' === $tab) { ?>nav-tab-active<?php } ?>"> Roles</a>
    <a href="?page=bb_multisite_tools_users_roles&tab=delete_role" class="nav-tab <?php if ('delete_role' === $tab) { ?>nav-tab-active<?php } ?>"> Delete Role</a>
  </nav>
  <div class="tab-content">
    <?php if (in_array($tab, ['wmde', 'none'])): ?>
    <p>Clicking on a user login will take you to the network user admin page. Clicking on a site role will take you to the user admin page on the relevant site.</p>
    <p><span class="count_users"><?= count($list_users) ?>
    </span> users found out of
    <?= count($all_users) ?> network users.</p>
    <?php endif; ?>

    <?php if (in_array($tab, ['wmde'])): ?>
    <p class="legend"><b>Filter roles:</b>
    <span class="role_bullet role_bullet_active superadmin">superadmin</span>
    <span class="role_bullet role_bullet_active administrator">administrator</span>
    <span class="role_bullet role_bullet_active editor">editor</span>
    <span class="role_bullet role_bullet_active author">author</span>
    <span class="role_bullet role_bullet_active contributor">contributor</span>
    <span class="role_bullet role_bullet_active subscriber">subscriber</span>
    <span class="role_bullet role_bullet_active um_passive-member-X-um_active-member">um_passive-member / um_active-member</span>
    <span class="role_bullet role_bullet_active unknown">unknown</span>
    <span class="role_bullet button reset">Reset</span>
    </p>
    <?php elseif (in_array($tab, ['mv'])): ?>
    <p class="legend"><b>Filter roles:</b>
    <span class="role_bullet role_bullet_active um_passive-member">um_passive-member</span>
    <span class="role_bullet role_bullet_active um_active-member">um_active-member</span>
    <span class="role_bullet button reset">Reset</span>
    </p>
    <?php endif; ?>

    <?php if (in_array($tab, ['wmde', 'none', 'mv'])): ?>
    <table class="wp-list-table widefat fixed striped table-view-list users-network bb-users">
    <?php foreach ($list_users as $user): ?>
    <?php $roles = join(' ', array_unique($user->roles)); ?>
    <?php $roles .= is_super_admin($user->ID) ? ' superadmin' : ''; ?>
    <tr data-roles="<?= $roles ?>">
      <td><a target="_new" href="<?= network_admin_url() ?>users.php?orderby=login&order=asc&s=<?= $user->ID ?>"><?= $user->user_login ?></a></td>
      <td><?= $user->display_name ?></td>
      <td><?= $user->user_email ?></td>
      <td class="roles_bullets"><?php echo $user->pretty_roles; ?></td>
      <?php if (in_array($tab, ['none'])): ?>
      <td><?= get_user_meta($user->ID, 'member_id', true) ? 'MV user' : '' ?></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>

    <?php elseif ('set_roles' == $tab): ?>
    <h2>Set User Role on all Sites</h2>
    <p>This will set a role to a user on the sites they have been added to. It can also add them to all sites or revoke their superadmin privileges.</p>
    <?php $form_url = network_admin_url() . 'admin.php?page=bb_multisite_tools_users_roles&tab=set_roles'; ?>
    <form name="set_role" method="post" action="<?= $form_url ?>">
    <input type="hidden" name="form" value="set_role">
    <input type="text" id="user_login" name="user_login" placeholder="User Login" />
    <select name="role">
      <?php $this->dropdown_all_roles(); ?>
    </select>
    <label for="revoke_sa">Revoke Superadmin<input type="checkbox" id="revoke_sa" name="revoke_sa" checked="checked" value="yes"></label>
    <label for="add_to_all">Add to all sites<input type="checkbox" id="add_to_all" name="add_to_all" value="yes"></label>
    <input class="button" type="submit" value="Set">
    </form>
    <?php elseif ('roles' == $tab): ?>
    <?php foreach ($sites as $site): ?>
    <h2><?= $site->domain . $site->path ?></h2>
    <table class="wp-list-table widefat fixed striped table-view-list bb-roles">
    <?php foreach ($site->roles as $role): ?>
    <tr>
      <td><?= $role->slug ?></td>
      <td><?= $role->name ?></td>
      <td>
      <ul style="display:none">
        <li><?= join('</li><li>', $role->caps) ?></li>
      </ul>
      </td>
    </tr>
    <?php endforeach; ?>
    </table>
    <?php endforeach; ?>


    <?php elseif ('delete_role' == $tab): ?>
    <p>Select a role to be deleted from all sites.</p>
    <?php $form_url = network_admin_url() . 'admin.php?page=bb_multisite_tools_users_roles&tab=delete_role'; ?>
    <form name="delete_role" method="post" action="<?= $form_url ?>">
    <input type="hidden" name="form" value="delete_role">
    <label>Delete role <select name="role"><?php $this->dropdown_all_roles(false); ?>
      < select></label>
    <input class="button" type="submit" value="Delete">
    </form>
    <?php endif; ?>
  </div>
  </div>