<div class="wrap">
  <h1>BB Multisite Tools - System Information</h1>

  <?php
  // Get WordPress and PHP environment information
  global $wpdb;

  $php_version = phpversion();
  $wp_version = get_bloginfo('version');
  $is_multisite = is_multisite();
  $site_count = $is_multisite ? get_blog_count() : 1;
  $active_theme = wp_get_theme();
  $server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
  $mysql_version = $wpdb->db_version();
  $max_execution_time = ini_get('max_execution_time');
  $memory_limit = ini_get('memory_limit');
  $upload_max_filesize = ini_get('upload_max_filesize');
  $post_max_size = ini_get('post_max_size');
  $max_input_vars = ini_get('max_input_vars');
  $wp_memory_limit = WP_MEMORY_LIMIT;
  $wp_max_memory_limit = WP_MAX_MEMORY_LIMIT;
  $wp_debug = WP_DEBUG ? 'Enabled' : 'Disabled';
  $wp_debug_log = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled';
  $environment_type = wp_get_environment_type();
  ?>

  <div class="notice notice-info inline">
    <p><strong>Environment Type:</strong> <?php echo ucwords($environment_type); ?></p>
  </div>

  <h2>WordPress Information</h2>
  <table class="widefat striped">
    <tbody>
      <tr>
        <td style="width: 30%;"><strong>WordPress Version</strong></td>
        <td><?php echo esc_html($wp_version); ?></td>
      </tr>
      <tr>
        <td><strong>Multisite</strong></td>
        <td><?php echo $is_multisite ? 'Yes (' . $site_count . ' sites)' : 'No'; ?></td>
      </tr>
      <tr>
        <td><strong>Active Theme</strong></td>
        <td><?php echo esc_html($active_theme->get('Name')) . ' v' . esc_html($active_theme->get('Version')); ?></td>
      </tr>
      <tr>
        <td><strong>Home URL</strong></td>
        <td><?php echo esc_html(get_home_url()); ?></td>
      </tr>
      <tr>
        <td><strong>Site URL</strong></td>
        <td><?php echo esc_html(get_site_url()); ?></td>
      </tr>
      <tr>
        <td><strong>WP Memory Limit</strong></td>
        <td><?php echo esc_html($wp_memory_limit); ?></td>
      </tr>
      <tr>
        <td><strong>WP Max Memory Limit</strong></td>
        <td><?php echo esc_html($wp_max_memory_limit); ?></td>
      </tr>
      <tr>
        <td><strong>WP Debug</strong></td>
        <td><?php echo esc_html($wp_debug); ?></td>
      </tr>
      <tr>
        <td><strong>WP Debug Log</strong></td>
        <td><?php echo esc_html($wp_debug_log); ?></td>
      </tr>
    </tbody>
  </table>

  <h2>Server Information</h2>
  <table class="widefat striped">
    <tbody>
      <tr>
        <td style="width: 30%;"><strong>Server Software</strong></td>
        <td><?php echo esc_html($server_software); ?></td>
      </tr>
      <tr>
        <td><strong>PHP Version</strong></td>
        <td><?php echo esc_html($php_version); ?></td>
      </tr>
      <tr>
        <td><strong>MySQL Version</strong></td>
        <td><?php echo esc_html($mysql_version); ?></td>
      </tr>
      <tr>
        <td><strong>PHP Memory Limit</strong></td>
        <td><?php echo esc_html($memory_limit); ?></td>
      </tr>
      <tr>
        <td><strong>Max Execution Time</strong></td>
        <td><?php echo esc_html($max_execution_time); ?> seconds</td>
      </tr>
      <tr>
        <td><strong>Max Upload File Size</strong></td>
        <td><?php echo esc_html($upload_max_filesize); ?></td>
      </tr>
      <tr>
        <td><strong>Max Post Size</strong></td>
        <td><?php echo esc_html($post_max_size); ?></td>
      </tr>
      <tr>
        <td><strong>Max Input Vars</strong></td>
        <td><?php echo esc_html($max_input_vars); ?></td>
      </tr>
    </tbody>
  </table>

  <h2>PHP Extensions</h2>
  <table class="widefat striped">
    <tbody>
      <?php
      $important_extensions = [
        'curl' => 'cURL',
        'gd' => 'GD',
        'imagick' => 'ImageMagick',
        'json' => 'JSON',
        'mbstring' => 'Multibyte String',
        'mysqli' => 'MySQLi',
        'openssl' => 'OpenSSL',
        'xml' => 'XML',
        'zip' => 'ZIP',
        'exif' => 'EXIF',
        'fileinfo' => 'FileInfo',
        'hash' => 'Hash',
        'iconv' => 'Iconv',
        'sodium' => 'Sodium',
      ];

      foreach ($important_extensions as $ext => $name) {
        $loaded = extension_loaded($ext);
        echo '<tr>';
        echo '<td style="width: 30%;"><strong>' . esc_html($name) . '</strong></td>';
        echo '<td>';
        if ($loaded) {
          echo '<span style="color: green;">✓ Enabled</span>';
        } else {
          echo '<span style="color: red;">✗ Not Enabled</span>';
        }
        echo '</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>

  <h2>WordPress Constants</h2>
  <table class="widefat striped">
    <tbody>
      <?php
      $constants = [
        'ABSPATH',
        'WP_CONTENT_DIR',
        'WP_PLUGIN_DIR',
        'WPMU_PLUGIN_DIR',
        'WP_CONTENT_URL',
        'WP_PLUGIN_URL',
        'UPLOADS',
      ];

      foreach ($constants as $constant) {
        if (defined($constant)) {
          echo '<tr>';
          echo '<td style="width: 30%;"><strong>' . esc_html($constant) . '</strong></td>';
          echo '<td><code>' . esc_html(constant($constant)) . '</code></td>';
          echo '</tr>';
        }
      }
      ?>
    </tbody>
  </table>

  <h2>Plugin Update Checker Status</h2>
  <table class="widefat striped">
    <tbody>
      <tr>
        <td style="width: 30%;"><strong>Library Loaded</strong></td>
        <td>
          <?php
          $puc_loaded = class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory');
          if ($puc_loaded) {
            echo '<span style="color: green;">✓ Yes</span>';
          } else {
            echo '<span style="color: red;">✗ No - Run install-update-checker.sh</span>';
          }
          ?>
        </td>
      </tr>
      <tr>
        <td><strong>GitHub Repository</strong></td>
        <td><code>https://github.com/Bananenbiegerei-Team/bb-multisite-tools</code></td>
      </tr>
      <tr>
        <td><strong>Current Plugin Version</strong></td>
        <td><?php
        $plugin_data = get_plugin_data(__DIR__ . '/../bb-multisite-tools.php');
        echo esc_html($plugin_data['Version']);
        ?></td>
      </tr>
    </tbody>
  </table>

  <h2>Full PHP Info</h2>
  <p>
    <button type="button" class="button button-secondary" id="toggle-phpinfo">Show PHP Info</button>
  </p>
  <div id="phpinfo-container" style="display: none; margin-top: 20px; border: 1px solid #ccd0d4; padding: 20px; background: white;">
    <?php
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();

    // Remove the HTML/body tags and styles to better integrate with WordPress admin
    $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
    echo $phpinfo;
    ?>
  </div>

  <script>
  document.getElementById('toggle-phpinfo').addEventListener('click', function() {
    var container = document.getElementById('phpinfo-container');
    if (container.style.display === 'none') {
      container.style.display = 'block';
      this.textContent = 'Hide PHP Info';
    } else {
      container.style.display = 'none';
      this.textContent = 'Show PHP Info';
    }
  });
  </script>

</div>
