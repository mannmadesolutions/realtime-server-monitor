<?php
/**
 * Main Server Monitor Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTSM_Server_Monitor {
    
    private $detector;
    private $collector;
    
    public function __construct() {
        $this->detector = RTSM_Platform_Detector::get_instance();
        $this->collector = new RTSM_Stats_Collector();
        
        // Register hooks
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        add_action('wp_head', [$this, 'add_admin_bar_assets']);
        add_action('admin_head', [$this, 'add_admin_bar_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_rtsm_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_rtsm_get_processes', [$this, 'ajax_get_processes']);
        add_action('wp_ajax_rtsm_kill_process', [$this, 'ajax_kill_process']);
        add_action('wp_ajax_rtsm_get_platform_info', [$this, 'ajax_get_platform_info']);
    }
    
    public function register_settings() {
        register_setting('rtsm_settings', 'rtsm_refresh_interval');
        register_setting('rtsm_settings', 'rtsm_show_admin_bar');
        register_setting('rtsm_settings', 'rtsm_show_dashboard_widget');
    }
    
    public function add_settings_page() {
        add_options_page(
            __('Server Monitor Settings', 'realtime-server-monitor'),
            __('Server Monitor', 'realtime-server-monitor'),
            'manage_options',
            'realtime-server-monitor',
            [$this, 'render_settings_page']
        );
    }
    
    public function render_settings_page() {
        require_once RTSM_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    public function add_dashboard_widget() {
        if (!get_option('rtsm_show_dashboard_widget', 1)) {
            return;
        }
        
        wp_add_dashboard_widget(
            'rtsm_dashboard_widget',
            '📊 ' . __('Real-Time Server Monitor', 'realtime-server-monitor'),
            [$this, 'render_dashboard_widget']
        );
    }
    
    public function render_dashboard_widget() {
        require_once RTSM_PLUGIN_DIR . 'templates/dashboard-widget.php';
    }
    
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options') || !get_option('rtsm_show_admin_bar', 1)) {
            return;
        }
        
        $load = $this->collector->get_load_average();
        $load1 = $load['1min'];
        
        $icon = '📊';
        $color = '#00a32a';
        if ($load1 >= 6.0) {
            $icon = '🔴';
            $color = '#d63638';
        } elseif ($load1 >= 4.0) {
            $icon = '🟡';
            $color = '#dba617';
        }
        
        $wp_admin_bar->add_node([
            'id' => 'rtsm-server-monitor',
            'title' => sprintf(
                '<span style="display: flex; align-items: center; gap: 5px;"><span>%s</span><span style="color: %s; font-weight: 600;">%.2f</span></span>',
                $icon,
                $color,
                $load1
            ),
            'href' => '#',
            'meta' => [
                'class' => 'rtsm-admin-bar',
                'title' => __('Server Load Monitor - Click for details', 'realtime-server-monitor')
            ]
        ]);
    }
    
    public function add_admin_bar_assets() {
        if (!is_admin_bar_showing() || !current_user_can('manage_options') || !get_option('rtsm_show_admin_bar', 1)) {
            return;
        }
        
        require_once RTSM_PLUGIN_DIR . 'templates/admin-bar-popup.php';
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('rtsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $load = $this->collector->get_load_average();
        $cpu = $this->collector->get_cpu_usage();
        $memory = $this->collector->get_memory_info();
        $connections = $this->collector->get_connections();
        $processes = $this->collector->get_processes('all', 5);
        
        wp_send_json_success([
            'load' => $load,
            'cpu_usage' => $cpu,
            'memory' => $memory,
            'connections' => $connections,
            'processes' => $processes,
            'timestamp' => current_time('H:i:s')
        ]);
    }
    
    public function ajax_get_processes() {
        check_ajax_referer('rtsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $processes = $this->collector->get_processes($filter, 20);
        
        wp_send_json_success(['processes' => $processes]);
    }
    
    public function ajax_kill_process() {
        check_ajax_referer('rtsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $pid = intval($_POST['pid']);
        $force = isset($_POST['force']) && $_POST['force'] === 'true';
        
        $result = $this->collector->kill_process($pid, $force);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_get_platform_info() {
        check_ajax_referer('rtsm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $info = $this->detector->get_platform_info();
        wp_send_json_success($info);
    }
}
