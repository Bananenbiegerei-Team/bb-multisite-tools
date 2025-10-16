<?php
/**
 * PageSpeed Insights Template
 * Displays Google PageSpeed scores for all sites in the network
 */

// Get the score color class
function get_score_class($score) {
  if ($score >= 90) return 'good';
  if ($score >= 50) return 'average';
  return 'poor';
}

// Get the score color
function get_score_color($score) {
  if ($score >= 90) return '#0cce6b';
  if ($score >= 50) return '#ffa400';
  return '#ff4e42';
}
?>

<div class="wrap">
  <h1>PageSpeed Insights - Network Performance</h1>

  <style>
    .pagespeed-table {
      margin-top: 20px;
    }
    .pagespeed-score {
      font-size: 48px;
      font-weight: bold;
      display: inline-block;
      width: 80px;
      height: 80px;
      line-height: 80px;
      text-align: center;
      border-radius: 50%;
      border: 8px solid;
      margin: 10px;
    }
    .pagespeed-score.good {
      color: #0cce6b;
      border-color: #0cce6b;
    }
    .pagespeed-score.average {
      color: #ffa400;
      border-color: #ffa400;
    }
    .pagespeed-score.poor {
      color: #ff4e42;
      border-color: #ff4e42;
    }
    .pagespeed-metrics {
      display: inline-block;
      vertical-align: top;
      margin-left: 20px;
    }
    .pagespeed-metric {
      margin: 5px 0;
    }
    .pagespeed-metric strong {
      display: inline-block;
      width: 180px;
    }
    .pagespeed-opportunities {
      margin-top: 15px;
      padding: 10px;
      background: #f5f5f5;
      border-left: 4px solid #2271b1;
    }
    .pagespeed-opportunities h4 {
      margin-top: 0;
    }
    .pagespeed-opportunity {
      margin: 5px 0;
      padding: 5px 0;
      border-bottom: 1px solid #ddd;
    }
    .pagespeed-opportunity:last-child {
      border-bottom: none;
    }
    .test-button {
      margin-right: 10px;
    }
    .cached-notice {
      color: #666;
      font-style: italic;
      font-size: 12px;
    }
    .loading {
      display: none;
      margin-left: 10px;
    }
    .loading.active {
      display: inline-block;
    }
  </style>

  <?php if (isset($action) && $action === 'test' && isset($results)): ?>
    <!-- Single site test results -->
    <div class="notice notice-info">
      <p><strong>Testing site:</strong> <?php echo esc_html($site->domain . $site->path); ?></p>
    </div>

    <h2>Mobile</h2>
    <?php if (isset($results['mobile']['error']) && $results['mobile']['error']): ?>
      <div class="notice notice-error">
        <p><strong>Error:</strong> <?php echo esc_html($results['mobile']['message']); ?></p>
      </div>
    <?php else: ?>
      <div class="pagespeed-score <?php echo get_score_class($results['mobile']['score']); ?>">
        <?php echo $results['mobile']['score']; ?>
      </div>
      <div class="pagespeed-metrics">
        <div class="pagespeed-metric">
          <strong>First Contentful Paint:</strong> <?php echo esc_html($results['mobile']['metrics']['fcp']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Largest Contentful Paint:</strong> <?php echo esc_html($results['mobile']['metrics']['lcp']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Total Blocking Time:</strong> <?php echo esc_html($results['mobile']['metrics']['tbt']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Cumulative Layout Shift:</strong> <?php echo esc_html($results['mobile']['metrics']['cls']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Speed Index:</strong> <?php echo esc_html($results['mobile']['metrics']['si']); ?>
        </div>
      </div>

      <?php if (!empty($results['mobile']['opportunities'])): ?>
        <div class="pagespeed-opportunities">
          <h4>Top Opportunities for Improvement:</h4>
          <?php foreach ($results['mobile']['opportunities'] as $opportunity): ?>
            <div class="pagespeed-opportunity">
              <strong><?php echo esc_html($opportunity['title']); ?></strong>
              <?php if (!empty($opportunity['savings'])): ?>
                <span style="color: #666;"> - <?php echo esc_html($opportunity['savings']); ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <h2>Desktop</h2>
    <?php if (isset($results['desktop']['error']) && $results['desktop']['error']): ?>
      <div class="notice notice-error">
        <p><strong>Error:</strong> <?php echo esc_html($results['desktop']['message']); ?></p>
      </div>
    <?php else: ?>
      <div class="pagespeed-score <?php echo get_score_class($results['desktop']['score']); ?>">
        <?php echo $results['desktop']['score']; ?>
      </div>
      <div class="pagespeed-metrics">
        <div class="pagespeed-metric">
          <strong>First Contentful Paint:</strong> <?php echo esc_html($results['desktop']['metrics']['fcp']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Largest Contentful Paint:</strong> <?php echo esc_html($results['desktop']['metrics']['lcp']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Total Blocking Time:</strong> <?php echo esc_html($results['desktop']['metrics']['tbt']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Cumulative Layout Shift:</strong> <?php echo esc_html($results['desktop']['metrics']['cls']); ?>
        </div>
        <div class="pagespeed-metric">
          <strong>Speed Index:</strong> <?php echo esc_html($results['desktop']['metrics']['si']); ?>
        </div>
      </div>

      <?php if (!empty($results['desktop']['opportunities'])): ?>
        <div class="pagespeed-opportunities">
          <h4>Top Opportunities for Improvement:</h4>
          <?php foreach ($results['desktop']['opportunities'] as $opportunity): ?>
            <div class="pagespeed-opportunity">
              <strong><?php echo esc_html($opportunity['title']); ?></strong>
              <?php if (!empty($opportunity['savings'])): ?>
                <span style="color: #666;"> - <?php echo esc_html($opportunity['savings']); ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <p>
      <a href="<?php echo network_admin_url('admin.php?page=bb_multisite_tools_pagespeed'); ?>" class="button">
        &larr; Back to All Sites
      </a>
    </p>

  <?php else: ?>
    <!-- All sites overview -->
    <div class="notice notice-info inline">
      <p>
        <strong>About PageSpeed Insights:</strong> This tool uses Google's PageSpeed Insights API to measure the performance of your sites.
        Tests are cached for 24 hours to avoid rate limits. Each test takes about 30-60 seconds to complete.
      </p>
    </div>

    <table class="wp-list-table widefat fixed striped pagespeed-table">
      <thead>
        <tr>
          <th style="width: 40%;">Site</th>
          <th style="width: 15%; text-align: center;">Mobile Score</th>
          <th style="width: 15%; text-align: center;">Desktop Score</th>
          <th style="width: 15%; text-align: center;">Last Tested</th>
          <th style="width: 15%; text-align: center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pagespeed_data as $blog_id => $data): ?>
          <tr id="site-<?php echo $blog_id; ?>">
            <td>
              <strong>
                <a href="https://<?php echo esc_html($data['site']->domain . $data['site']->path); ?>" target="_blank">
                  <?php echo esc_html($data['site']->domain . $data['site']->path); ?>
                </a>
              </strong>
            </td>
            <td style="text-align: center;">
              <?php if ($data['results']): ?>
                <?php if (isset($data['results']['mobile']['error']) && $data['results']['mobile']['error']): ?>
                  <span style="color: #dc3232;">Error</span>
                <?php else: ?>
                  <span style="font-size: 24px; font-weight: bold; color: <?php echo get_score_color($data['results']['mobile']['score']); ?>">
                    <?php echo $data['results']['mobile']['score']; ?>
                  </span>
                <?php endif; ?>
              <?php else: ?>
                <span style="color: #999;">Not tested</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <?php if ($data['results']): ?>
                <?php if (isset($data['results']['desktop']['error']) && $data['results']['desktop']['error']): ?>
                  <span style="color: #dc3232;">Error</span>
                <?php else: ?>
                  <span style="font-size: 24px; font-weight: bold; color: <?php echo get_score_color($data['results']['desktop']['score']); ?>">
                    <?php echo $data['results']['desktop']['score']; ?>
                  </span>
                <?php endif; ?>
              <?php else: ?>
                <span style="color: #999;">Not tested</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <?php if ($data['cached_time']): ?>
                <span class="cached-notice">
                  <?php echo human_time_diff($data['cached_time'], current_time('timestamp')) . ' ago'; ?>
                </span>
              <?php else: ?>
                <span style="color: #999;">Never</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <button class="button button-secondary test-button" onclick="testSite(<?php echo $blog_id; ?>)">
                Test Now
              </button>
              <span class="spinner loading" id="spinner-<?php echo $blog_id; ?>"></span>
              <?php if ($data['results']): ?>
                <br><br>
                <a href="<?php echo network_admin_url('admin.php?page=bb_multisite_tools_pagespeed&action=test&site_id=' . $blog_id); ?>" class="button button-small">
                  View Details
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <script>
    function testSite(siteId) {
      // Show loading spinner
      const spinner = document.getElementById('spinner-' + siteId);
      spinner.classList.add('active');

      // Disable button
      const row = document.getElementById('site-' + siteId);
      const button = row.querySelector('.test-button');
      const originalText = button.textContent;
      button.disabled = true;
      button.textContent = 'Testing...';

      // Make AJAX request
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'bb_test_pagespeed',
          site_id: siteId,
          nonce: '<?php echo wp_create_nonce('bb_pagespeed_test'); ?>'
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show new results
          location.reload();
        } else {
          alert('Error: ' + (data.data || 'Unknown error'));
          spinner.classList.remove('active');
          button.disabled = false;
          button.textContent = originalText;
        }
      })
      .catch(error => {
        alert('Error testing site: ' + error);
        spinner.classList.remove('active');
        button.disabled = false;
        button.textContent = originalText;
      });
    }
    </script>

  <?php endif; ?>
</div>
