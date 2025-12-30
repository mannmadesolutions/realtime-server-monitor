<?php if (!defined('ABSPATH')) exit; ?>
<style>
#wpadminbar .rtsm-admin-bar > .ab-item { padding: 0 10px !important; }
#rtsm-popup { display: none; position: fixed; top: 32px; right: 20px; width: 450px; max-height: 80vh; overflow-y: auto; background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 999999; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
#rtsm-popup.active { display: block; }
#rtsm-close { position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 0; width: 30px; height: 30px; }
#rtsm-close:hover { color: #d63638; }
.rtsm-popup-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; }
.rtsm-popup-card { background: #f8f9fa; padding: 12px; border-radius: 6px; border-left: 3px solid #2271b1; }
.rtsm-popup-card.warning { border-left-color: #dba617; }
.rtsm-popup-card.danger { border-left-color: #d63638; }
.rtsm-popup-card h4 { margin: 0 0 5px 0; font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; }
.rtsm-popup-value { font-size: 20px; font-weight: 700; color: #1d2327; }
.rtsm-popup-value.success { color: #00a32a; }
.rtsm-popup-value.warning { color: #dba617; }
.rtsm-popup-value.danger { color: #d63638; }
.rtsm-popup-subtitle { font-size: 10px; color: #999; margin-top: 3px; }
.rtsm-filter { margin: 10px 0; display: flex; gap: 5px; flex-wrap: wrap; }
.rtsm-filter button { padding: 4px 10px; font-size: 11px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px; }
.rtsm-filter button:hover { background: #f0f0f1; }
.rtsm-filter button.active { background: #2271b1; color: white; border-color: #2271b1; }
.rtsm-table { width: 100%; border-collapse: collapse; font-size: 11px; }
.rtsm-table th { text-align: left; padding: 5px; background: #f0f0f1; font-weight: 600; position: sticky; top: 0; }
.rtsm-table td { padding: 5px; border-bottom: 1px solid #f0f0f1; }
.rtsm-table tr:hover { background: #f8f9fa; }
.rtsm-kill-btn { padding: 2px 6px; font-size: 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer; }
.rtsm-kill-btn:hover { background: #b32d2e; }
.rtsm-kill-btn:disabled { background: #ccc; cursor: not-allowed; }
@media screen and (max-width: 782px) { #rtsm-popup { top: 46px; right: 10px; width: 95%; max-width: 450px; } }
</style>

<div id="rtsm-popup">
    <button id="rtsm-close">&times;</button>
    <h3>📊 <?php _e('Server Monitor', 'realtime-server-monitor'); ?></h3>
    <div id="rtsm-popup-content"><p style="text-align: center; color: #666;"><?php _e('Loading...', 'realtime-server-monitor'); ?></p></div>
</div>

<script>
(function($) {
    let popupInterval, isOpen = false, currentFilter = 'all';
    const nonce = '<?php echo wp_create_nonce('rtsm_nonce'); ?>';
    
    function killProcess(pid, force) {
        if (!confirm('<?php _e('Kill process', 'realtime-server-monitor'); ?> ' + pid + '?' + (force ? ' (FORCE)' : ''))) return;
        $.post(ajaxurl, { action: 'rtsm_kill_process', nonce: nonce, pid: pid, force: force }, function(r) {
            if (r.success) { alert(r.data.message); loadProcesses(currentFilter); }
            else if (confirm(r.data + '\n\n<?php _e('Try force kill?', 'realtime-server-monitor'); ?>')) killProcess(pid, true);
        });
    }
    window.rtsmKillProcess = killProcess;
    
    function loadProcesses(filter) {
        currentFilter = filter;
        $('.rtsm-filter button').removeClass('active').filter('[data-filter="' + filter + '"]').addClass('active');
        $('#rtsm-process-table').html('<tr><td colspan="7" style="text-align:center;"><?php _e('Loading...', 'realtime-server-monitor'); ?></td></tr>');
        $.post(ajaxurl, { action: 'rtsm_get_processes', nonce: nonce, filter: filter }, function(r) {
            if (r.success) renderProcesses(r.data.processes);
        });
    }
    
    function renderProcesses(procs) {
        if (!procs || !procs.length) {
            $('#rtsm-process-table').html('<tr><td colspan="7" style="text-align:center; color:#999;"><?php _e('No processes', 'realtime-server-monitor'); ?></td></tr>');
            return;
        }
        let rows = '';
        procs.forEach(function(p) {
            const cpuStyle = p.cpu >= 50 ? 'color: #d63638; font-weight: 600;' : (p.cpu >= 25 ? 'color: #dba617; font-weight: 600;' : '');
            const killBtn = p.killable ? '<button class="rtsm-kill-btn" onclick="rtsmKillProcess(' + p.pid + ', false)">Kill</button>' : '<span style="color:#ccc;" title="System">🔒</span>';
            rows += '<tr><td>' + p.pid + '</td><td>' + p.user + '</td><td style="' + cpuStyle + '">' + p.cpu + '%</td><td>' + p.mem + '%</td><td>' + (p.elapsed || '-') + '</td><td title="' + p.command + '">' + p.command + '</td><td>' + killBtn + '</td></tr>';
        });
        $('#rtsm-process-table').html(rows);
    }
    
    function update() {
        $.post(ajaxurl, { action: 'rtsm_get_stats', nonce: nonce }, function(r) {
            if (r.success) {
                updateAdminBar(r.data);
                if (isOpen) updatePopup(r.data);
            }
        });
    }
    
    function updateAdminBar(d) {
        const l = d.load['1min'], icon = l >= 6 ? '🔴' : (l >= 4 ? '��' : '📊'), color = l >= 6 ? '#d63638' : (l >= 4 ? '#dba617' : '#00a32a');
        $('#wp-admin-bar-rtsm-server-monitor .ab-item').html('<span style="display: flex; align-items: center; gap: 5px;"><span>' + icon + '</span><span style="color: ' + color + '; font-weight: 600;">' + l + '</span></span>');
    }
    
    function updatePopup(d) {
        const lc = d.load['1min'] >= 6 ? 'danger' : (d.load['1min'] >= 4 ? 'warning' : 'success');
        
        // Update only the stats cards, preserve the process table
        const statsHtml = '<div class="rtsm-popup-grid"><div class="rtsm-popup-card ' + lc + '"><h4><?php _e('Load (1m)', 'realtime-server-monitor'); ?></h4><div class="rtsm-popup-value ' + lc + '">' + d.load['1min'] + '</div><div class="rtsm-popup-subtitle">5m: ' + d.load['5min'] + ' | 15m: ' + d.load['15min'] + '</div></div><div class="rtsm-popup-card"><h4><?php _e('CPU', 'realtime-server-monitor'); ?></h4><div class="rtsm-popup-value">' + d.cpu_usage + '%</div></div><div class="rtsm-popup-card"><h4><?php _e('Memory', 'realtime-server-monitor'); ?></h4><div class="rtsm-popup-value">' + d.memory.used + 'MB</div><div class="rtsm-popup-subtitle">' + d.memory.percent + '% of ' + d.memory.total + 'MB</div></div><div class="rtsm-popup-card"><h4><?php _e('Connections', 'realtime-server-monitor'); ?></h4><div class="rtsm-popup-value">' + d.connections + '</div></div></div>';
        
        // Check if process table exists (already initialized)
        if ($('#rtsm-process-table').length) {
            // Just update the stats cards
            if ($('.rtsm-popup-grid').length) {
                $('.rtsm-popup-grid').replaceWith(statsHtml);
            }
            // Refresh the current filter view
            loadProcesses(currentFilter);
        } else {
            // First time - build entire popup
            let prows = '';
            if (d.processes && d.processes.length) {
                d.processes.forEach(function(p) {
                    const cs = p.cpu >= 50 ? 'style="color: #d63638; font-weight: 600;"' : (p.cpu >= 25 ? 'style="color: #dba617; font-weight: 600;"' : '');
                    const kb = p.killable ? '<button class="rtsm-kill-btn" onclick="rtsmKillProcess(' + p.pid + ', false)">×</button>' : '<span style="color:#ccc;">🔒</span>';
                    prows += '<tr><td>' + p.pid + '</td><td>' + p.user + '</td><td ' + cs + '>' + p.cpu + '%</td><td>' + p.mem + '%</td><td>' + (p.elapsed || '-') + '</td><td>' + p.command + '</td><td>' + kb + '</td></tr>';
                });
            }
            $('#rtsm-popup-content').html(statsHtml + '<h4><?php _e('Process Monitor', 'realtime-server-monitor'); ?></h4><div class="rtsm-filter"><button data-filter="all" class="active">All</button><button data-filter="high-cpu">High CPU</button><button data-filter="php">PHP</button><button data-filter="python">Python</button><button data-filter="node">Node</button><button data-filter="mysql">MySQL</button></div><table class="rtsm-table"><thead><tr><th>PID</th><th>User</th><th>CPU</th><th>Mem</th><th>Time</th><th>Command</th><th>Kill</th></tr></thead><tbody id="rtsm-process-table">' + (prows || '<tr><td colspan="7" style="text-align:center; color:#999;"><?php _e('No processes', 'realtime-server-monitor'); ?></td></tr>') + '</tbody></table><p style="text-align: center; font-size: 10px; color: #999; margin-top: 10px;">Last: ' + d.timestamp + ' • <?php echo get_option('rtsm_refresh_interval', 10); ?>s</p>');
            $('.rtsm-filter button').on('click', function() { loadProcesses($(this).data('filter')); });
        }
    }
    
    $(document).ready(function() {
        $('#wp-admin-bar-rtsm-server-monitor').on('click', function(e) {
            e.preventDefault();
            isOpen = !isOpen;
            $('#rtsm-popup').toggleClass('active');
            if (isOpen) { update(); popupInterval = setInterval(update, <?php echo get_option('rtsm_refresh_interval', 10) * 1000; ?>); }
            else { clearInterval(popupInterval); popupInterval = null; }
        });
        $('#rtsm-close').on('click', function() { isOpen = false; $('#rtsm-popup').removeClass('active'); if (popupInterval) clearInterval(popupInterval); });
        $(document).on('click', function(e) {
            if (isOpen && !$(e.target).closest('#rtsm-popup').length && !$(e.target).closest('#wp-admin-bar-rtsm-server-monitor').length) {
                isOpen = false; $('#rtsm-popup').removeClass('active'); if (popupInterval) clearInterval(popupInterval);
            }
        });
        setInterval(function() { if (!isOpen) update(); }, <?php echo get_option('rtsm_refresh_interval', 10) * 1000; ?>);
        update();
    });
})(jQuery);
</script>
