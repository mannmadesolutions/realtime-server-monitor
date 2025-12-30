<?php if (!defined('ABSPATH')) exit; ?>

<div id="rtsm-dashboard-widget-content">
    <div style="text-align: center; padding: 20px;">
        <span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
        <p><?php _e('Loading server statistics...', 'realtime-server-monitor'); ?></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function updateDashboardWidget() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rtsm_get_stats',
                nonce: '<?php echo wp_create_nonce('rtsm_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderDashboardWidget(response.data);
                }
            }
        });
    }
    
    function renderDashboardWidget(data) {
        const load1Class = data.load['1min'] >= 6.0 ? 'danger' : (data.load['1min'] >= 4.0 ? 'warning' : 'success');
        
        let processRows = '';
        if (data.processes && data.processes.length > 0) {
            data.processes.forEach(function(proc) {
                const cpuStyle = proc.cpu >= 50 ? 'color: #d63638; font-weight: 600;' : (proc.cpu >= 25 ? 'color: #dba617; font-weight: 600;' : '');
                processRows += `<tr>
                    <td>${proc.pid}</td>
                    <td>${proc.user}</td>
                    <td style="${cpuStyle}">${proc.cpu}%</td>
                    <td>${proc.mem}%</td>
                    <td>${proc.command}</td>
                </tr>`;
            });
        }
        
        const html = `
            <style>
                .rtsm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
                .rtsm-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #2271b1; }
                .rtsm-card.warning { border-left-color: #dba617; }
                .rtsm-card.danger { border-left-color: #d63638; }
                .rtsm-card h4 { margin: 0 0 8px 0; font-size: 13px; color: #666; text-transform: uppercase; font-weight: 600; }
                .rtsm-value { font-size: 24px; font-weight: 700; color: #1d2327; }
                .rtsm-value.small { font-size: 18px; }
                .rtsm-value.success { color: #00a32a; }
                .rtsm-value.warning { color: #dba617; }
                .rtsm-value.danger { color: #d63638; }
                .rtsm-subtitle { font-size: 11px; color: #999; margin-top: 4px; }
                .rtsm-table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .rtsm-table th { text-align: left; padding: 6px 8px; background: #f0f0f1; font-weight: 600; }
                .rtsm-table td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; }
            </style>
            <div class="rtsm-grid">
                <div class="rtsm-card ${load1Class}">
                    <h4><?php _e('Load Average (1m)', 'realtime-server-monitor'); ?></h4>
                    <div class="rtsm-value ${load1Class}">${data.load['1min']}</div>
                    <div class="rtsm-subtitle">5m: ${data.load['5min']} | 15m: ${data.load['15min']}</div>
                </div>
                <div class="rtsm-card">
                    <h4><?php _e('CPU Usage', 'realtime-server-monitor'); ?></h4>
                    <div class="rtsm-value small">${data.cpu_usage}%</div>
                </div>
                <div class="rtsm-card">
                    <h4><?php _e('Memory', 'realtime-server-monitor'); ?></h4>
                    <div class="rtsm-value small">${data.memory.used}MB / ${data.memory.total}MB</div>
                    <div class="rtsm-subtitle">${data.memory.percent}% used</div>
                </div>
                <div class="rtsm-card">
                    <h4><?php _e('Connections', 'realtime-server-monitor'); ?></h4>
                    <div class="rtsm-value small">${data.connections}</div>
                </div>
            </div>
            <h4><?php _e('Top CPU Consumers', 'realtime-server-monitor'); ?></h4>
            <table class="rtsm-table">
                <thead><tr><th>PID</th><th>User</th><th>CPU</th><th>Mem</th><th>Command</th></tr></thead>
                <tbody>${processRows || '<tr><td colspan="5" style="text-align:center; color:#999;">No active processes</td></tr>'}</tbody>
            </table>
            <p style="text-align: right; font-size: 11px; color: #999; margin-top: 10px;">Last updated: ${data.timestamp}</p>
        `;
        
        $('#rtsm-dashboard-widget-content').html(html);
    }
    
    updateDashboardWidget();
    setInterval(updateDashboardWidget, <?php echo get_option('rtsm_refresh_interval', 10) * 1000; ?>);
});
</script>
