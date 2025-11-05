<?php
/*
Plugin Name: GSC Trending Plus
Description: Display trending and most visited posts from Google Search Console.
Version: 1.0
Author: Rohit Saini
License: GPL2
*/

if (!defined('ABSPATH')) exit;

// --- Default option keys
function gsc_default_options() {
    return array(
        'client_path' => WP_CONTENT_DIR . '/google-api-php-client/vendor/autoload.php',
        'key_path'    => WP_CONTENT_DIR . '/uploads/gsc-key.json',
        'site_url'    => home_url('/') , // default to current site
        'cache'       => 3600,
        'days'        => 14,
        'limit'       => 5,
        'metric'      => 'clicks'
    );
}

// Add "Settings" link under plugin name in Plugins list
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="options-general.php?page=gsc-trending">' . __('Settings', 'gsc-trending') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});


// --- Register settings page
add_action('admin_menu', function(){
    add_options_page('GSC Trending', 'GSC Trending', 'manage_options', 'gsc-trending', 'gsc_settings_page');
});
add_action('admin_init', function(){
    register_setting('gsc_trending_options', 'gsc_trending_options', 'gsc_options_sanitize');
});

function gsc_options_sanitize($input) {
    $defaults = gsc_default_options();
    $out = array();
    $out['client_path'] = sanitize_text_field($input['client_path'] ?: $defaults['client_path']);
    $out['key_path']    = sanitize_text_field($input['key_path'] ?: $defaults['key_path']);
    $out['site_url']    = esc_url_raw($input['site_url'] ?: $defaults['site_url']);
    $out['cache']       = intval($input['cache'] ?: $defaults['cache']);
    $out['days']        = intval($input['days'] ?: $defaults['days']);
    $out['limit']       = intval($input['limit'] ?: $defaults['limit']);
    $out['metric']      = in_array($input['metric'], array('clicks','impressions')) ? $input['metric'] : $defaults['metric'];
    return $out;
}

function gsc_settings_page(){
    if (!current_user_can('manage_options')) return;
    $opts = get_option('gsc_trending_options', gsc_default_options());
    ?>
    <div class="wrap">
    <h1>GSC Trending Settings</h1>
    <form method="post" action="options.php">
    <?php settings_fields('gsc_trending_options'); ?>
    <table class="form-table">
    <tr><th>Google API Client autoload path</th>
        <td><input style="width:60%" type="text" name="gsc_trending_options[client_path]" value="<?php echo esc_attr($opts['client_path']); ?>" />
        <p class="description">Path to vendor/autoload.php from google-api-php-client (e.g. <?php echo esc_html(WP_CONTENT_DIR . '/google-api-php-client/vendor/autoload.php'); ?>)</p></td></tr>
    <tr><th>Service account key JSON path</th>
        <td><input style="width:60%" type="text" name="gsc_trending_options[key_path]" value="<?php echo esc_attr($opts['key_path']); ?>" />
        <p class="description">Full filesystem path to the downloaded JSON key (recommended: <?php echo esc_html(WP_CONTENT_DIR . '/uploads/gsc-key.json'); ?>)</p></td></tr>
    <tr><th>Search Console site URL</th>
        <td><input style="width:60%" type="text" name="gsc_trending_options[site_url]" value="<?php echo esc_attr($opts['site_url']); ?>" />
        <p class="description">Must match the property in Search Console, include trailing slash (e.g. <?php echo esc_html($opts['site_url']); ?>)</p></td></tr>
    <tr><th>Cache (seconds)</th>
        <td><input type="number" name="gsc_trending_options[cache]" value="<?php echo esc_attr($opts['cache']); ?>" /></td></tr>
    <tr><th>Default days</th>
        <td><input type="number" name="gsc_trending_options[days]" value="<?php echo esc_attr($opts['days']); ?>" /></td></tr>
    <tr><th>Default limit</th>
        <td><input type="number" name="gsc_trending_options[limit]" value="<?php echo esc_attr($opts['limit']); ?>" /></td></tr>
    <tr><th>Default metric</th>
        <td>
            <select name="gsc_trending_options[metric]">
                <option value="clicks" <?php selected($opts['metric'],'clicks'); ?>>Clicks</option>
                <option value="impressions" <?php selected($opts['metric'],'impressions'); ?>>Impressions</option>
            </select>
        </td></tr>
    </table>
    <?php submit_button(); ?>
    </form>
	   
	<h2>How to Use GSC Trending Plus</h2>
<ol style="line-height:1.7; font-size:15px;">
  <li><strong>Enable the Search Console API</strong> — Visit 
    <a href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank">Search Console API</a> 
    and click <em>Enable</em>.
  </li>
  <li><strong>Create a Service Account</strong> in 
    <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank">Google Cloud Console</a>.
  </li>
  <li><strong>Grant Access</strong> — Add your service account email to 
    <a href="https://search.google.com/search-console" target="_blank">Search Console → Settings → Users & permissions</a> 
    with <em>Full</em> access.
  </li>
  <li><strong>Install PHP Google API Client</strong> — install manually via Composer:
    <code>composer require google/apiclient:^2.15</code>
  </li>
  <li><strong>Upload Your Service Account Key</strong> — Upload the JSON key to 
    <code>wp-content/uploads/gsc-key.json</code> and set its path above.
  </li>
  <li><strong>Use the Shortcode</strong> — Add <code>[gsc_trending_posts limit="6" days="14" metric="clicks"]</code> to any post or page.
  </li>
</ol>

    </div>
    <?php
}

// --- Admin warning if not configured
add_action('admin_notices', function(){
    if (!current_user_can('manage_options')) return;
    $opts = get_option('gsc_trending_options', gsc_default_options());
    if (!file_exists($opts['client_path'])) {
        echo '<div class="notice notice-warning"><p><strong>GSC Trending:</strong> Google API client not found at <code>'.esc_html($opts['client_path']).'</code>. Install google-api-php-client.</p></div>';
    }
    if (!file_exists($opts['key_path'])) {
        echo '<div class="notice notice-warning"><p><strong>GSC Trending:</strong> Service account JSON key not found at <code>'.esc_html($opts['key_path']).'</code>. Upload key and set the path in settings.</p></div>';
    }
});

// --- Shortcode implementation
add_shortcode('gsc_trending_posts', 'gsc_trending_posts_shortcode');

function gsc_trending_posts_shortcode($atts) {
    $opts = get_option('gsc_trending_options', gsc_default_options());
    $atts = shortcode_atts(array(
        'limit'  => $opts['limit'],
        'days'   => $opts['days'],
        'metric' => $opts['metric'],
        'cache'  => $opts['cache'],
    ), $atts, 'gsc_trending_posts');

    $cache_key = 'gsc_trending_' . md5(json_encode($atts));
    $output = get_transient($cache_key);
    if ($output !== false) return $output;

    // verify client & key
    if (!file_exists($opts['client_path']) || !file_exists($opts['key_path'])) {
        return '<div class="gsc-error">GSC configuration missing. Please go to Settings → GSC Trending to set paths.</div>';
    }

    // load client
    try {
        require_once $opts['client_path'];
    } catch (Exception $e) {
        return '<div class="gsc-error">Failed to load Google Client: '.esc_html($e->getMessage()).'</div>';
    }

    // create client and service
    try {
        $client = new Google\Client();
        $client->setAuthConfig($opts['key_path']);
        $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
        $service = new Google\Service\SearchConsole($client);
    } catch (Exception $e) {
        return '<div class="gsc-error">Google Client init error: '.esc_html($e->getMessage()).'</div>';
    }

    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-".intval($atts['days'])." days"));

    $request = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $request->setStartDate($startDate);
    $request->setEndDate($endDate);
    $request->setDimensions(['page']);
    $request->setRowLimit(250); // top rows
    $request->setDataState('final');

    try {
        $response = $service->searchanalytics->query(rtrim($opts['site_url'],'/').'/', $request);
    } catch (Exception $e) {
        return '<div class="gsc-error">Search Console API error: '.esc_html($e->getMessage()).'</div>';
    }

    $rows = $response->getRows();
    if (empty($rows)) {
        return '<div class="gsc-error">No data available for that period.</div>';
    }

    // convert rows to associative array with metrics
    $metric = $atts['metric'];
    $list = array();
    foreach ($rows as $row) {
        $keys = $row->getKeys();
        $page = is_array($keys) ? $keys[0] : $keys;
        $list[] = array(
            'page' => $page,
            'clicks' => $row->getClicks(),
            'impressions' => $row->getImpressions(),
            'ctr' => $row->getCtr(),
            'position' => $row->getPosition()
        );
    }

    usort($list, function($a,$b) use ($metric) {
        return ($b[$metric] <=> $a[$metric]);
    });

    $list = array_slice($list, 0, intval($atts['limit']));

    // build HTML
    ob_start();
    echo '<div class="gsc-trending"><ul>';
    foreach ($list as $entry) {
        $url = $entry['page'];
        $clicks = round($entry['clicks']);
        $impr = round($entry['impressions']);
        $ctr = round($entry['ctr'] * 100, 1);
        $pos = round($entry['position'], 1);

        $post_id = url_to_postid($url);
        $title = $post_id ? get_the_title($post_id) : esc_html($url);
        $permalink = $post_id ? get_permalink($post_id) : esc_url($url);

        echo '<li class="gsc-item">';
        echo '<a href="'.esc_url($permalink).'">'.esc_html($title).'</a>';
        echo '<div class="gsc-meta">
			  <span class="gsc-clicks" title="Number of times users clicked your link in Google Search">Clicks: '.intval($clicks).'</span> | 
			  <span class="gsc-impr" title="How many times your link was shown in search results">Impr: '.intval($impr).'</span> | 
			  <span class="gsc-ctr" title="CTR (Click-Through Rate): % of searchers who clicked your link">CTR: '.$ctr.'%</span> | 
			  <span class="gsc-pos" title="Pos (Average Position): Your average ranking in Google results">Pos: '.$pos.'</span>
			</div>';
        echo '</li>';
    }
    echo '</ul></div>';
    $output = ob_get_clean();

    set_transient($cache_key, $output, intval($atts['cache']));
    return $output;
}

// Load plugin CSS only when shortcode is used
function gsc_trending_enqueue_assets() {
    global $post;
    if (isset($post->post_content) && has_shortcode($post->post_content, 'gsc_trending_posts')) {
        wp_enqueue_style('gsc-trending-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0');
    }
}
add_action('wp_enqueue_scripts', 'gsc_trending_enqueue_assets');