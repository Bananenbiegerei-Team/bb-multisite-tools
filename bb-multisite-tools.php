<?php
/*
Plugin Name: BB Multisite Tools for Wikimedia DE
Description: Useful tools: user management, DB usage, plugins and themes usage
Version: 3.8
Author: Eric Leclercq
Requires at least: 5.3
Requires PHP: 5.5
BB Update Checker: enabled
*/

require_once 'polyfills.php';

class BBMultisiteTools
{
  // List of suffixes of tables to truncate
  public $tables_to_truncate = [
  'comments',
  'commentmeta',
  'cli_visitor_details',
  'wise_chat_users',
  'wise_chat_sent_notifications',
  'wise_chat_reactions_log',
  'wise_chat_reactions',
  'wise_chat_pending_chats',
  'wise_chat_messages',
  'wise_chat_kicks',
  'wise_chat_channels',
  'wise_chat_channel_users',
  'wise_chat_bans',
  'wise_chat_actions',
  'wpforms_entries',
  'wpforms_entry_fields',
  'wpforms_entry_meta',
  'wpforms_tasks_meta',
  'mvh',
  ];

  // Roles that can never be deleted
  public $protected_roles = ['administrator', 'editor', 'author', 'contributor', 'um_active-member', 'um_passive-member'];

  //---------------------------------------------------------------------------
  // Setup admin pages and hooks...
  public function __construct()
  {

    if (!is_admin() || wp_doing_ajax()) {
      return false;
    }

    // Setup menu pages
    $this->setup_menu_pages();

    // Enqueue JS and CSS
    add_action(
      'admin_enqueue_scripts',
      function () {
        wp_enqueue_style('bb-multisite-tools', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('bb-multisite-tools', plugin_dir_url(__FILE__) . 'admin.js', [], '', false);
        //$string = 'var hiddenRoles = ' . json_encode($this->hidden_roles) . ';';
        //wp_add_inline_script('bb-multisite-tools', $string);
      },
      999,
    );

    // Register REST API end-points
    if (wp_get_environment_type() !== 'production') {
      add_action('wp_ajax_bb_multisite_tools_cleanup_data_mv_users', [$this, 'cleanup_data_mv_users']);
      add_action('wp_ajax_bb_multisite_tools_cleanup_data_tables', [$this, 'cleanup_data_tables']);
    }

    // Enrich users list with roles per site
    add_action('wpmu_users_columns', function ($column_headers) {
      unset($column_headers['blogs']);
      unset($column_headers['registered']);
      $column_headers['site_roles'] = 'Sites & Roles';
      return $column_headers;
    });
    add_action('manage_users_custom_column', [$this, 'manage_users_custom_column'], 10, 3);

    // Handle submitted forms and display notices
    add_action('admin_init', [$this, 'action_handler']);
  }

  //---------------------------------------------------------------------------
  // PAGES
  // Main page with redirect checker
  public function main_page()
  {
    include 'templates/main.php';
  }

  // DB usage page
  public function db_page()
  {
    global $wpdb;
    $tab = $_GET['tab'] ?? 'stats';
    // Table sizes
    $db_stats = $this->get_db_stats();
    // Prepare data for template
    $sites = $this->get_sites();
    foreach ($db_stats as $table) {
      preg_match('/' . $wpdb->prefix . '(\\d+)_/', $table->table_name, $m, 0, 0);
      $table->blog_id = intval($m[1] ?? 1);
      $table->site = empty($sites[$table->blog_id]) ? '&nbsp;' : $sites[$table->blog_id]->domain . $sites[$table->blog_id]->path;
      $table->rows = number_format($table->table_rows, 0, '.', ' ');
      $table->size = number_format($table->total_size, 2, '.', ' ');
    }
    // Orphaned tables
    $orphans = $this->select_orphaned_tables();
    include 'templates/db.php';
  }

  // Cleanup production data
  public function cleanup_data_page()
  {
    // Data on users to delete
    $users = $this->select_mv_users();
    $user_count = count($users);
    // Data on tables to truncate (only those with at least 1 row...)
    $cleanup_tables = [];
    foreach ($this->tables_to_truncate as $s) {
      $data = $this->select_tables_for_cleanup($s);
      $count = 0;
      foreach ($data as $d) {
        $count += $d['rows'];
      }
      $cleanup_tables[$s] = $count;
    }
    $cleanup_tables = array_filter($cleanup_tables, function ($a) {
      return $a > 0;
    });

    include 'templates/cleanup_data.php';
  }

  // ACF Blocks
  public function acfblocks_page()
  {
    $sites = $this->get_sites();
    $custom_post_types = $this->get_custom_post_types();
    $block_data = $this->get_posts_with_acf_blocks();
    $block_usage_data = [];

    foreach ($block_data as $site_id => $site_blocks) {
      if (count($site_blocks) == 0) {
        continue;
      }

      $site_data = new stdClass();
      $site_data->desc = $sites[$site_id]->domain . $sites[$site_id]->path;
      $site_data->blocks = [];

      $stylesheet = $this->get_site_theme($site_id);
      if (empty($custom_post_types[$stylesheet])) {
        $custom_post_types[$stylesheet] = [];
      }
      $types = $custom_post_types[$stylesheet];
      $types[] = 'wp_block';
      $types[] = 'page';
      $types[] = 'post';
      $types[] = 'tribe_events';
      $site_data->post_types = $types;

      foreach ($site_blocks as $block_name => $posts_with_block) {
        $edit_url = "//{$sites[$site_id]->domain}{$sites[$site_id]->path}wp-admin/post.php?action=edit&post=";
        $block = new stdClass();
        $block->name = explode('/', $block_name)[1];
        $block->posts = [];
        foreach ($posts_with_block as $post) {
          $p = new stdClass();
          $p->ID = $post['ID'];
          $p->post_type = $post['post_type'];
          $p->edit_url = "{$edit_url}{$post['ID']}";
          $block->posts[] = $p;
        }
        $site_data->blocks[] = $block;
      }
      $block_usage_data[$site_id] = $site_data;
    }
    include 'templates/acfblocks.php';
  }

  // Sub-site domains and paths
  public function adjust_sites_page()
  {
    $sites = $this->adjust_sites();
    $form_url = network_admin_url() . 'admin.php?page=bb_multisite_tools_adjust_sites&update=1';
    include 'templates/adjust_sites.php';
  }

  // Themes & plugins page
  public function themes_page()
  {
    global $wpdb;

    $blogs_id = [];
    $table = $wpdb->prefix . 'blogs';
    $result = $wpdb->get_results("SELECT `blog_id` FROM {$table}");
    foreach ($result as $r) {
      $blogs_id[] = $r->blog_id;
    }
    $allowed_themes = [];
    $table = $wpdb->prefix . 'sitemeta';
    $q = unserialize($wpdb->get_var("SELECT `meta_value` FROM {$table} WHERE `meta_key` = 'allowedthemes'"));
    foreach ($q as $p => $t) {
      $allowed_themes[] = $p;
    }
    $themes = [];
    $themes_dir = dirname(get_template_directory());
    foreach (scandir($themes_dir) as $i) {
      if ('.' == $i || '..' == $i || !is_dir($themes_dir . '/' . $i) || !file_exists($themes_dir . '/' . $i . '/style.css')) {
        continue;
      }
      $themes[basename($i)] = [];
    }
    foreach ($blogs_id as $id) {
      if (1 == $id) {
        $table = $wpdb->prefix . 'options';
      } else {
        $table = $wpdb->prefix . $id . '_options';
      }
      $siteurl = $wpdb->get_var("SELECT `option_value` FROM {$table} WHERE `option_name` = 'siteurl'");
      $stylesheet = $wpdb->get_var("SELECT `option_value` FROM {$table} WHERE `option_name` = 'stylesheet'");
      $themes[$stylesheet][] = $siteurl;
    }
    ksort($themes);

    include 'templates/themes.php';
  }

  // Users & roles page
  public function users_roles_page()
  {
    $tab = $_GET['tab'] ?? 'wmde';
    $list_users = [];
    $all_users = [];

    if (in_array($tab, ['wmde', 'none', 'mv'])) {
      $all_users = $this->get_users();
      foreach ($all_users as $user) {
        switch ($tab) {

          case 'wmde':
            $is_wmde_or_contractor = true;
            foreach($user->roles as $role) {
              if (str_starts_with($role, "um_")) {
                $is_wmde_or_contractor = false;
              }
            }
            if (!$user->roles) {
              $is_wmde_or_contractor = false;
            }
            $is_none = true;
            foreach($user->roles as $role) {
              if ($role !== 'none') {
                $is_none = false;
              }
            }
            if ($is_wmde_or_contractor && !$is_none) {
              $this->user_pretty_roles($user);
              $list_users[] = $user;
            }
            break;

          case 'none':
            $is_none = false;
            foreach($user->roles as $role) {
              if ($role === 'none') {
                $is_none = true;
              }
            }
            if (count($user->roles) === 0) {
              $is_none = true;
            }
            if ($is_none) {
              $this->user_pretty_roles($user);
              $list_users[] = $user;
            }
            break;



          case 'mv':
            $is_mv = false;
            foreach($user->roles as $role) {
              if (str_starts_with($role, "um_")) {
                $is_mv = true;
              }
            }
            if ($is_mv) {
              $this->user_pretty_roles($user);
              $list_users[] = $user;
            }
            break;
        }
      }
    } elseif ($tab == 'roles') {
      $sites = [];
      foreach (get_sites() as $site) {
        switch_to_blog($site->blog_id);
        $roles = wp_roles()->roles;
        ksort($roles);
        $site->roles = [];
        foreach ($roles as $slug => $role) {
          $r = new StdClass();
          $r->slug = $slug;
          $r->name = $role['name'];
          $caps = array_keys($role['capabilities']);
          $r->caps = $caps;
          $site->roles[] = $r;
        }
        $sites[] = $site;
      }
      switch_to_blog(1);
    }
    include 'templates/users_roles.php';
  }

  // WPForms page
  public function wpforms_page()
  {
    $posts_with_forms_per_site = [];
    $sites = $this->get_sites();

    foreach ($sites as $site) {
      $posts_with_forms_per_site[$site->blog_id] = [];
      $site_forms = $this->get_site_forms($site->blog_id);
      $used_forms = [];
      $posts_with_forms = $this->get_posts_with_forms($site->blog_id);
      switch_to_blog($site->blog_id);

      foreach ($posts_with_forms as $post) {
        preg_match_all('/wp:wpforms.*?"formId":"([0-9]+)"/', $post->post_content, $matches);
        $post->forms = $matches[1] ?? [];
        preg_match_all('/\[wpforms id="([0-9]+)"\]/', $post->post_content, $matches);
        $post->forms = array_merge($post->forms, $matches[1] ?? []);
        $post->forms_data = [];
        foreach ($post->forms as $form_id) {
          if ($form_title = get_the_title($form_id)) {
            $rcpt = join(', ', $this->get_form_notification_email($form_id));
            $data = [
            'ID' => $form_id,
            'title' => $form_title,
            'links' => "<a href='//{$site->domain}{$site->path}wp-admin/admin.php?page=wpforms-builder&view=fields&form_id={$form_id}' target='_blank' title='Edit Form'>🛠️</a> <a href='//{$site->domain}{$site->path}wp-admin/admin.php?page=wpforms-entries&view=list&form_id={$form_id}' target='_blank' title='View Entries'>📊</a>",
            'rcpt' => $rcpt,
            'save_resume_enabled' => $this->has_save_resume_enabled($form_id)
            ];
            $used_forms[] = $form_id;
            $post->forms_data[] = $data;
          } else {
            $data = [
            'ID' => $form_id,
            'title' =>  "Form not found",
            'links' => null,
            'rcpt' => null,
            'save_resume_enabled' => false
            ];
            $post->forms_data[] = $data;
          }
        }
        $post->slug = get_post_field('post_name', $post->id);
        $post->post_content = '';
        $post->edit_url = "//{$site->domain}{$site->path}wp-admin/post.php?action=edit&post={$post->id}";
        $posts_with_forms_per_site[$site->blog_id][] = $post;
      }
      $unused_forms[$site->blog_id] = array_diff($site_forms, $used_forms);

      restore_current_blog();
    }
    include 'templates/wpforms.php';
  }

  // Refresh all permalinks
  public function permalinks_page()
  {
    global $wp_rewrite;
    $url = network_admin_url() . 'admin.php?page=bb_multisite_tools_permalinks&update=1';
    echo '<div class="wrap"><h1>Fix Permalinks</h1>';
    if ($_GET['update'] ?? 0) {
      echo '<p>Refreshing rewrite rules for all sites:</p>';
      echo '<ul>';
      foreach (get_sites() as $site) {
        switch_to_blog($site->blog_id);
        $wp_rewrite->init();
        delete_option('rewrite_rules');
        echo "<li>{$site->domain}{$site->path}</li>";
        restore_current_blog();
      }
      echo '</ul>';
    } else {
      echo '<p>This will refresh the rewrite rules of all network sites and (hopefully) fix all permalinks issues. Don\'t do this unless you\'re sure you need to as it may have an impact on the site performance.</p>';
      echo "<a class='button' href='{$url}'>Fix Permalinks</a>";
    }
    echo '</div>';
  }

  //---------------------------------------------------------------------------
  // HELPERS: Menu pages
  public function setup_menu_pages()
  {
    add_action('network_admin_menu', function () {
      add_menu_page('BB Multisite Tools', 'BB Multisite Tools', 'install_plugins', 'bb_multisite_tools', '', 'dashicons-palmtree');
      add_submenu_page('bb_multisite_tools', 'Info', 'Info', 'install_plugins', 'bb_multisite_tools', [$this, 'main_page']);
      add_submenu_page('bb_multisite_tools', 'Themes', 'Themes', 'install_plugins', 'bb_multisite_tools_themes', [$this, 'themes_page']);
      add_submenu_page('bb_multisite_tools', 'ACF Blocks Usage', 'ACF Blocks Usage', 'install_plugins', 'bb_multisite_tools_acfblocks', [$this, 'acfblocks_page']);
      add_submenu_page('bb_multisite_tools', 'Users & Roles', 'Users & Roles', 'install_plugins', 'bb_multisite_tools_users_roles', [$this, 'users_roles_page']);
      add_submenu_page('bb_multisite_tools', 'Database', 'Database', 'install_plugins', 'bb_multisite_tools_db', [$this, 'db_page']);
      add_submenu_page('bb_multisite_tools', 'WPForms', 'WPForms', 'install_plugins', 'bb_multisite_tools_wpf', [$this, 'wpforms_page']);
      add_submenu_page('bb_multisite_tools', 'Fix Permalinks', 'Fix Permalinks', 'install_plugins', 'bb_multisite_tools_permalinks', [$this, 'permalinks_page']);

      // Only for non-production servers
      if (wp_get_environment_type() !== 'production') {
        add_submenu_page('bb_multisite_tools', 'Adjust Network Sites', 'Adjust Network Sites', 'install_plugins', 'bb_multisite_tools_adjust_sites', [$this, 'adjust_sites_page']);
        add_submenu_page('bb_multisite_tools', 'Cleanup Prod Data', 'Cleanup Prod Data', 'install_plugins', 'bb_multisite_tools_cleanup_data', [$this, 'cleanup_data_page']);
      }
    });

    // Status item in menu bar
    if (wp_get_environment_type() !== 'production') {
      add_action(
        'admin_bar_menu',
        function ($admin_bar) {
          $args = [
          'id' => 'bb_multisite_tools_status',
          'title' => '<span>⚠️'. ucwords(wp_get_environment_type()) . ' Server</span>',
          'href' => null,
          'meta' => [
          'title' => __('BB Multisite Tools'),
          ],
          ];
          $admin_bar->add_menu($args);
        },
        900,
      );
    }
  }

  // HELPERS: General
  // Get list of sites
  public function get_sites()
  {
    global $wpdb;
    if (!is_multisite()) {
      return [1 => (object) ['blog_id' => 1, 'site_id' => 1, 'path' => '/', 'domain' => $_SERVER['SERVER_NAME']]];
    }
    $sites = [];
    foreach ($wpdb->get_results("SELECT * FROM {$wpdb->prefix}blogs;") as $site) {
      $sites[$site->blog_id] = $site;
    }
    return $sites;
  }

  // HELPERS: Adjust sites
  // List sites with domain and path and optionally adjust them
  public function adjust_sites($update = false)
  {
    global $wpdb;
    $sites = [];
    foreach (get_sites() as $site) {
      if ($site->domain != $_SERVER['SERVER_NAME']) {
        $site->update_needed = true;
        $path = '/';
        // FIXME: hardcoded :(
        $path = '/' . str_replace('.', '', str_replace('wikimedia.de', '', $site->domain)) . '/';
        $site->new_path = $path;
        if ($update === true) {
          $updates = [
          'blog_id' => $site->blog_id,
          'domain' => $_SERVER['SERVER_NAME'],
          'path' => $path,
          'proto' => 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://',
          ];
          $wpdb->get_results("UPDATE {$wpdb->prefix}blogs SET domain='{$updates['domain']}', path='{$updates['path']}' WHERE blog_id='{$updates['blog_id']}';");
          $wpdb->get_results("UPDATE {$wpdb->prefix}{$site->blog_id}_options SET option_value='{$updates['proto']}{$updates['domain']}{$updates['path']}' WHERE option_name='siteurl';");
          $wpdb->get_results("UPDATE {$wpdb->prefix}{$site->blog_id}_options SET option_value='{$updates['proto']}{$updates['domain']}{$updates['path']}' WHERE option_name='home';");
        }
      } else {
        $site->update_needed = false;
      }
      $sites[] = $site;
    }
    return $sites;
  }

  // HELPERS: DB
  // Get stats on database
  public function get_db_stats()
  {
    global $wpdb;
    $size_query = "SELECT table_schema AS database_name, TABLE_NAME as table_name, table_rows as table_rows, round(1.0*data_length/1024/1024, 2) AS data_size, round(index_length/1024/1024, 2) AS index_size, round((data_length + index_length)/1024/1024, 2) AS total_size FROM information_schema.tables WHERE table_schema NOT IN('information_schema', 'mysql', 'sys', 'performance_schema') ORDER BY table_rows DESC;";
    $db_stats = $wpdb->get_results($size_query);
    return $db_stats;
  }

  // Find orphaned tables (with the ID of an inexistent site)
  public function select_orphaned_tables()
  {
    if (!is_multisite()) {
      return [];
    }
    global $wpdb;
    $orphans = [];
    $sites = $this->get_sites();
    $db_query = "SELECT TABLE_NAME as table_name FROM information_schema.tables WHERE table_schema NOT IN('information_schema', 'mysql', 'sys', 'performance_schema');";
    $res = $wpdb->get_results($db_query);
    foreach ($res as $r) {
      preg_match('/' . $wpdb->prefix . '(\\d+)_/', $r->table_name, $m, 0, 0);
      $blog_id = intval($m[1] ?? 1);
      if (empty($sites[$blog_id])) {
        $orphans[] = $r->table_name;
      }
      sort($orphans);
    }
    return $orphans;
  }

  // Delete tables
  public function delete_tables($tables)
  {
    global $wpdb;
    foreach ($tables as $t) {
      $wpdb->get_results("DROP TABLE `{$t}`;");
    }
  }

  // HELPERS: Cleanup Data
  // Select MV users
  public function select_mv_users($limit = false)
  {
    $all_users = $this->get_users();
    $limit = intval($limit);
    $users = [];
    foreach ($all_users as $user) {
      if ($this->user_has_only_roles($user, ['um_passive-member', 'um_active-member'])) {
        $users[$user->ID] = $user->user_login;
        if (count($users) == $limit) {
          return $users;
        }
      }
    }
    return $users;
  }

  // Select all tables with a suffix
  public function select_tables_for_cleanup($table)
  {
    global $wpdb, $tables_to_truncate;
    if (!in_array($table, $this->tables_to_truncate)) {
      return false;
    }
    $tdata = [];
    foreach (get_sites() as $site) {
      if (1 == $site->id) {
        $table_name = $wpdb->prefix . $table;
      } else {
        $table_name = $wpdb->prefix . $site->id . '_' . $table;
      }
      $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
      if ($count > 0) {
        $tdata[] = [
          'site_id' => $site->id,
          'table_name' => $table_name,
          'rows' => $count,
        ];
      }
    }
    return $tdata;
  }

  // HELPERS: WPForms
  // Get all posts with an embedded form for a site
  public function get_posts_with_forms($site_id)
  {
    global $wpdb;
    if (1 == $site_id) {
      $table = $wpdb->prefix . 'posts';
    } else {
      $table = $wpdb->prefix . $site_id . '_posts';
    }
    return $wpdb->get_results("SELECT id, post_title, post_status, post_type,post_content  FROM {$table}  WHERE post_content LIKE '%wpforms%' AND post_type NOT IN ('revision', 'wpforms')");
  }

  // Get all forms for a site
  public function get_site_forms($site_id)
  {
    global $wpdb;
    if (1 == $site_id) {
      $table = $wpdb->prefix . 'posts';
    } else {
      $table = $wpdb->prefix . $site_id . '_posts';
    }
    $results = $wpdb->get_results("SELECT id FROM {$table}  WHERE post_type='wpforms' AND post_status='publish'");
    $forms = [];
    foreach ($results as $r) {
      $forms[] = $r->id;
    }
    return $forms;
  }

  // Get forms notification email
  public function get_form_notification_email($form_id)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'posts';
    $result = $wpdb->get_var("SELECT post_content FROM {$table}  WHERE ID={$form_id}");
    $data = json_decode($result);
    $rcpt = [];
    foreach($data->settings->notifications as $notif) {
      if (strpos($notif->email, '@') !== false) {
        $rcpt = array_merge($rcpt, explode(',', $notif->email));
      }
    }
    return $rcpt;
  }

  public function has_save_resume_enabled($form_id)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'posts';
    $result = $wpdb->get_var("SELECT post_content FROM {$table}  WHERE ID={$form_id}");
    $data = json_decode($result);
    return $data->settings->save_resume_enable === "1";
  }

  // Check is cache is disabled for a slug
  public function is_cache_disabled_for_slug($slug)
  {
    global $cache_rejected_uri;
    foreach ($cache_rejected_uri as $url) {
      $pattern = '/' . preg_quote($url, '/') . '/';
      if (preg_match($pattern, $slug) || preg_match($pattern, "/{$slug}/")) {
        return true;
      }
    }
    return false;
  }

  // HELPERS: ACF Blocks Usage
  // Get all posts with ACF Blocks
  public function get_posts_with_acf_blocks()
  {
    global $wpdb;
    $blocks = [];
    $sites = $this->get_sites();
    foreach ($sites as $site) {
      $blocks[$site->blog_id] = [];
      if ($site->blog_id == 1) {
        $table = "{$wpdb->prefix}posts";
      } else {
        $table = "{$wpdb->prefix}{$site->blog_id}_posts";
      }
      $data[$site->site_id] = [];
      $res = $wpdb->get_results("SELECT ID,post_title,post_content,post_type FROM {$table} WHERE post_content LIKE '%-- wp:acf/%' AND post_status = 'publish';");
      foreach ($res as $p) {
        preg_match_all('#-- (wp:acf/.*?) {#', $p->post_content, $matches);
        $matches = array_unique($matches[1]);
        foreach ($matches as $block) {
          if (empty($blocks[$site->blog_id][$block])) {
            $blocks[$site->blog_id][$block] = [];
          }
          $blocks[$site->blog_id][$block][] = ['post_type' => $p->post_type, 'ID' => $p->ID];
        }
      }
      ksort($blocks[$site->blog_id]);
    }
    return $blocks;
  }

  // Get site theme
  public function get_site_theme($site_id)
  {
    global $wpdb;
    if (1 == $site_id) {
      $table = $wpdb->prefix . 'options';
    } else {
      $table = $wpdb->prefix . $site_id . '_options';
    }
    $stylesheet = $wpdb->get_var("SELECT `option_value` FROM {$table} WHERE `option_name` = 'stylesheet'");
    return $stylesheet;
  }

  // Get list of custom post types
  public function get_custom_post_types()
  {
    $themes_dir = dirname(get_stylesheet_directory());
    $cpt = [];
    foreach (glob($themes_dir . '/*/functions/custom-posts.php') as $filename) {
      $theme = basename(dirname(dirname($filename)));
      preg_match_all("#register_post_type\('(.*?)'#", file_get_contents($filename), $matches);
      $cpt[$theme] = $matches[1];
    }
    return $cpt;
  }

  // HELPERS: Site users
  // Get roles of user on all sites
  public function get_user_site_roles($user_id, $sites = false)
  {
    global $wpdb;
    if ($sites === false) {
      $sites = $this->get_sites();
    }
    $roles = [];
    $list = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}usermeta WHERE user_id={$user_id} and meta_key LIKE '{$wpdb->prefix}%capabilities';");
    foreach ($list as $c) {
      $meta = unserialize($c->meta_value);
      if (count($meta) == 0) {
        $caps = 'none';
      } else {
        // FIXME: user might have multiple roles on site!!!
        $caps = array_keys($meta)[0];
      }
      preg_match_all('/' . $wpdb->prefix . '(\d+_)?capabilities/', $c->meta_key, $matches);
      // FIXME: Undefined array key 0
      $site_id = intval($matches[1][0]);
      if ($site_id == 0) {
        $site_id = 1;
      }
      if (in_array($site_id, array_keys($sites))) {
        $roles[$site_id] = $caps;
      }
    }
    return $roles;
  }

  // Get a pretty list of roles for a user
  public function user_pretty_roles($user)
  {
    $user->pretty_roles = '';
    if (is_super_admin($user->ID)) {
      $user->pretty_roles = "<a href=\"/wp-admin/network/user-edit.php?user_id={$user->ID}\"><span class=\"role_bullet superadmin\">superadmin</span></a>";
    }

    foreach ($user->roles as $site_id => $role) {
      $site = get_site($site_id);
      if (empty($site)) {
        unset($user->roles[$site_id]);
        continue;
      }

      $site_disp_name = $site->path == '/' ? $site->domain : $site->path;
      $site_disp_name = trim($site_disp_name, '/');
      //$user->pretty_roles .= "<a href=\"//{$site->domain}{$site->path}wp-admin/user-edit.php?user_id={$user->ID}\" target=\"_blank\"><span class=\"role_bullet {$role}\">{$site_disp_name}:{$role}</span></a>";
      $user->pretty_roles .= "<a href=\"//{$site->domain}{$site->path}wp-admin/users.php?s={$user->user_login}\" target=\"_blank\"><span class=\"role_bullet {$role}\">{$site_disp_name}:{$role}</span></a>";
    }
  }

  // Get roles options for form
  public function dropdown_all_roles($include_protected = true)
  {
    echo "<option value=\"-none-\">select role</option>";
    foreach ($this->get_all_roles() as $role) {
      if ($include_protected || !in_array($role, $this->protected_roles)) {
        echo "<option value=\"$role\">$role</option>";
      }
    }
  }

  // Set role for user on all sites
  public function gobal_set_role($user_login, $role, $revoke_sa = true, $add_to_all = false)
  {
    global $wpdb;
    if (!get_user_by('login', $user_login)) {
      return false;
    }
    $id = null;
    $user_roles = $this->get_user_site_roles(get_user_by('login', $user_login)->ID);
    $sites = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}blogs;");
    foreach ($sites as $site) {
      if ($add_to_all == false && empty($user_roles[$site->blog_id])) {
        continue;
      }
      switch_to_blog($site->blog_id);
      $user = get_user_by('login', $user_login);
      $id = $user->ID; // FIXME: need this only once...
      $user->set_role($role);
    }
    switch_to_blog(1);
    if ($revoke_sa) {
      revoke_super_admin($id);
    }
  }

  // HELPERS: Users list
  public function get_users()
  {
    global $wpdb;
    $sites = $this->get_sites();
    $query = "SELECT * FROM `{$wpdb->users}` ORDER BY user_login;";
    $users = [];
    foreach ($wpdb->get_results($query) as $u) {
      $u->roles = $this->get_user_site_roles($u->ID, $sites);
      $users[] = $u;
    }
    return $users;
  }

  // Check if a user *only* has specific roles
  public function user_has_only_roles($user, $match_roles)
  {
    $sites = $this->get_sites();
    $user_roles = [];
    foreach ($user->roles as $sid => $role) {
      if (!empty($sites[$sid])) {
        $user_roles[$sid] = $role;
      }
    }
    $user_roles = array_unique(array_values($user_roles));
    asort($user_roles);
    asort($match_roles);
    return empty(array_diff($user_roles, $match_roles));
  }

  // HELPERS: Users list column
  public function manage_users_custom_column($value, $column_name, $user_id)
  {
    if ('site_roles' == $column_name) {
      $roledata = $this->get_user_site_roles($user_id);
      $value = "<table class=\"site_roles_list\">";
      foreach ($roledata as $site_id => $role) {
        $site = get_site($site_id);
        $side_disp_name = $site->path == '/' ? $site->domain : $site->path;
        $a = "<a href=\"//{$site->domain}{$site->path}wp-admin/user-edit.php?user_id={$user_id}\">{$side_disp_name}</a>";
        $value .= "<td>{$a}</td><td>{$role}</td></tr>";
      }
      $value .= '</table>';
    }
    return $value;
  }

  // HELPERS: Roles
  // Get all available roles
  public function get_all_roles()
  {
    $all_roles = [];
    foreach (get_sites() as $site) {
      switch_to_blog($site->blog_id);
      $roles = wp_roles()->roles;
      foreach ($roles as $slug => $role) {
        $all_roles[] = $slug;
      }
    }
    return array_unique($all_roles);
  }

  // HELPERS: Show an admin notice
  public function admin_notice($type, $message)
  {
    if (!in_array($type, ['error', 'warning', 'success', 'info'])) {
      return;
    }
    $message = htmlspecialchars($message); // FIXME handle non-network sites...
    add_action('network_admin_notices', function () use ($type, $message) {
      echo "<div class=\"notice notice-{$type}\"><p>{$message}</div>";
    });
  }

  //---------------------------------------------------------------------------
  // HOOKS
  // AJAX End Point: Delete a batch of users
  public function cleanup_data_mv_users()
  {
    global $wpdb;
    if (isset($_REQUEST) && $_REQUEST['delete']) {
      if (!function_exists('wpmu_delete_user')) {
        require_once ABSPATH . '/wp-admin/includes/ms.php';
      }
      $count = intval($_REQUEST['delete']);
      $users = $this->select_mv_users($count);
      $log = [];
      foreach ($users as $user_id => $user_login) {
        if (is_multisite()) {
          $res = wpmu_delete_user($user_id);
        } else {
          $res = wp_delete_user($user_id, wp_get_current_user()->ID);
        }
        $log[] = ['user_id' => $user_id, 'user_login' => $user_login, 'result' => $res];
      }
      echo json_encode($log);
    }
    wp_die();
  }
  // AJAX End Point: Truncate selected tables
  public function cleanup_data_tables()
  {
    global $wpdb;
    $log = [];
    if (isset($_REQUEST) && $_REQUEST['truncate']) {
      $table = $_REQUEST['truncate'];
      foreach ($this->select_tables_for_cleanup($table) as $d) {
        $wpdb->get_var("TRUNCATE TABLE `{$d['table_name']}`");
        $c = true;
        if ($wpdb->last_error !== '') {
          $c = $wpdb->last_error;
        }
        $log[$d['table_name']] = $c;
      }
    }
    echo json_encode($log);
    wp_die();
  }
  // Handle POST actions and show admin notice
  public function action_handler()
  {
    $form = $_POST['form'] ?? false;
    switch ($form) {
      case false:
        return;
        break;

      case 'set_role': // Set user role on (all) sites (and remove superadmin rights)
        $user_login = $_POST['user_login'] ?? false;
        $role = $_POST['role'] ?? false;
        $revoke_sa = ($_POST['revoke_sa'] ?? 'no') == 'no' ? false : true;
        $add_to_all = ($_POST['add_to_all'] ?? 'no') == 'no' ? false : true;
        if ($user_login != false && $role != false) {
          if ($role === '-none-') {
            get_user_by('login', $user_login)->remove_all_caps();
          } else {
            $r = $this->gobal_set_role($user_login, $role, $revoke_sa, $add_to_all);
            $this->admin_notice('success', "Set role {$role} to user {$user_login}.");
          }
        }
        break;

      case 'delete_role': // Delete a role from all sites
        if (!isset($_POST['role']) || $_POST['role'] == '-none-') {
          return;
        }
        $role = $_POST['role'];
        foreach ($this->get_sites() as $site) {
          switch_to_blog($site->blog_id);
          remove_role($role);
        }
        switch_to_blog(1);
        $this->admin_notice('success', "Deleted role {$role} from all sites.");
        break;

      case 'delete_orphans':
        $orphans = $this->select_orphaned_tables();
        $count = count($orphans);
        $this->delete_tables($orphans);
        $this->admin_notice('success', "Deleted {$count} orphaned tables.");
        break;

      case 'adjust_sites':
        $this->adjust_sites(true);
        $this->admin_notice('success', 'Adjusted site paths and domains.');
        break;
    }
  }
}

new BBMultisiteTools();
