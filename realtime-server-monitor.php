<?php
/**
 * Plugin Name: Real-Time Server Monitor
 * Plugin URI: https://mannmade.us
 * Description: Real-time server monitoring with admin bar widget, detailed process viewer, and process management. Works across all hosting platforms.
 * Version: 1.0.0
 * Author: MannMade Solutions
 * Author URI: https://mannmade.us
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: realtime-server-monitor
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RTSM_VERSION', '1.0.0');
define('RTSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RTSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RTSM_PLUGIN_FILE', __FILE__);

// Include main class
require_once RTSM_PLUGIN_DIR . 'includes/class-server-monitor.php';
require_once RTSM_PLUGIN_DIR . 'includes/class-platform-detector.php';
require_once RTSM_PLUGIN_DIR . 'includes/class-stats-collector.php';

// Initialize plugin
function rtsm_init() {
    $monitor = new RTSM_Server_Monitor();
}
add_action('plugins_loaded', 'rtsm_init');

// Activation hook
register_activation_hook(__FILE__, 'rtsm_activate');
function rtsm_activate() {
    // Check minimum requirements
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Real-Time Server Monitor requires PHP 7.2 or higher.', 'realtime-server-monitor'));
    }
    
    // Set default options
    add_option('rtsm_refresh_interval', 10);
    add_option('rtsm_show_admin_bar', 1);
    add_option('rtsm_show_dashboard_widget', 1);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'rtsm_deactivate');
function rtsm_deactivate() {
    // Cleanup if needed
}

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'rtsm_add_settings_link');
function rtsm_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=realtime-server-monitor') . '">' . __('Settings', 'realtime-server-monitor') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
