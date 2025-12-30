<?php
if (!defined('ABSPATH')) exit;

$platform_info = RTSM_Platform_Detector::get_instance()->get_platform_info();
$capabilities = $platform_info['capabilities'];
?>

<div class="wrap">
    <h1><?php _e('Real-Time Server Monitor Settings', 'realtime-server-monitor'); ?></h1>
    
    <div class="rtsm-settings-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        <div>
            <form method="post" action="options.php">
                <?php settings_fields('rtsm_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Show Admin Bar Monitor', 'realtime-server-monitor'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rtsm_show_admin_bar" value="1" <?php checked(get_option('rtsm_show_admin_bar', 1), 1); ?> />
                                <?php _e('Display server monitor in admin bar', 'realtime-server-monitor'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Show Dashboard Widget', 'realtime-server-monitor'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rtsm_show_dashboard_widget" value="1" <?php checked(get_option('rtsm_show_dashboard_widget', 1), 1); ?> />
                                <?php _e('Display server monitor on dashboard', 'realtime-server-monitor'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Refresh Interval', 'realtime-server-monitor'); ?></th>
                        <td>
                            <input type="number" name="rtsm_refresh_interval" value="<?php echo esc_attr(get_option('rtsm_refresh_interval', 10)); ?>" min="5" max="60" />
                            <p class="description"><?php _e('Seconds between automatic refreshes (5-60)', 'realtime-server-monitor'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <div class="rtsm-platform-info">
            <div class="card">
                <h2><?php _e('Platform Information', 'realtime-server-monitor'); ?></h2>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Hosting Platform', 'realtime-server-monitor'); ?></strong></td>
                        <td><?php echo esc_html($platform_info['platform_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('PHP Version', 'realtime-server-monitor'); ?></strong></td>
                        <td><?php echo esc_html($platform_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Operating System', 'realtime-server-monitor'); ?></strong></td>
                        <td><?php echo esc_html($platform_info['os']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Server Software', 'realtime-server-monitor'); ?></strong></td>
                        <td><?php echo esc_html($platform_info['server_software']); ?></td>
                    </tr>
                </table>
                
                <h3 style="margin-top: 20px;"><?php _e('Capabilities', 'realtime-server-monitor'); ?></h3>
                <table class="widefat">
                    <?php foreach ($capabilities as $cap => $enabled): ?>
                    <tr>
                        <td><?php echo esc_html($cap); ?></td>
                        <td>
                            <span style="color: <?php echo $enabled ? '#00a32a' : '#d63638'; ?>;">
                                <?php echo $enabled ? '✓' : '✗'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <p style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                    <strong><?php _e('Note:', 'realtime-server-monitor'); ?></strong>
                    <?php _e('The plugin adapts to available system functions. Some features may be limited based on hosting restrictions.', 'realtime-server-monitor'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
