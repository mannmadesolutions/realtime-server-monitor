<?php
/**
 * Stats Collector
 * 
 * Collects server statistics using multiple methods based on platform capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTSM_Stats_Collector {
    
    private $detector;
    
    public function __construct() {
        $this->detector = RTSM_Platform_Detector::get_instance();
    }
    
    /**
     * Get load average using the best available method
     */
    public function get_load_average() {
        // Method 1: sys_getloadavg (most reliable)
        if ($this->detector->has_capability('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                return [
                    '1min' => round($load[0], 2),
                    '5min' => round($load[1], 2),
                    '15min' => round($load[2], 2)
                ];
            }
        }
        
        // Method 2: Read /proc/loadavg (Linux)
        if ($this->detector->has_capability('proc_files')) {
            $loadavg = @file_get_contents('/proc/loadavg');
            if ($loadavg) {
                $load = explode(' ', $loadavg);
                if (count($load) >= 3) {
                    return [
                        '1min' => round(floatval($load[0]), 2),
                        '5min' => round(floatval($load[1]), 2),
                        '15min' => round(floatval($load[2]), 2)
                    ];
                }
            }
        }
        
        // Method 3: shell_exec uptime
        if ($this->detector->has_capability('shell_exec')) {
            $uptime = @shell_exec('uptime');
            if ($uptime && preg_match('/load average: ([0-9.]+),?\s+([0-9.]+),?\s+([0-9.]+)/', $uptime, $matches)) {
                return [
                    '1min' => round(floatval($matches[1]), 2),
                    '5min' => round(floatval($matches[2]), 2),
                    '15min' => round(floatval($matches[3]), 2)
                ];
            }
        }
        
        // Fallback: Return zeros if no method available
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }
    
    /**
     * Get CPU usage percentage
     */
    public function get_cpu_usage() {
        // Method 1: Read /proc/stat (Linux)
        if ($this->detector->has_capability('proc_files') && is_readable('/proc/stat')) {
            static $prev_stats = null;
            
            $stats = @file_get_contents('/proc/stat');
            if ($stats && preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/m', $stats, $matches)) {
                $current = [
                    'user' => $matches[1],
                    'nice' => $matches[2],
                    'system' => $matches[3],
                    'idle' => $matches[4]
                ];
                
                if ($prev_stats) {
                    $diff_idle = $current['idle'] - $prev_stats['idle'];
                    $diff_total = ($current['user'] + $current['nice'] + $current['system'] + $current['idle']) -
                                ($prev_stats['user'] + $prev_stats['nice'] + $prev_stats['system'] + $prev_stats['idle']);
                    
                    if ($diff_total > 0) {
                        $cpu_usage = 100 * (1 - $diff_idle / $diff_total);
                        $prev_stats = $current;
                        return round($cpu_usage, 1);
                    }
                }
                
                $prev_stats = $current;
            }
        }
        
        // Method 2: shell_exec top
        if ($this->detector->has_capability('shell_exec')) {
            $cpu = @shell_exec("top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{print $1}'");
            if ($cpu !== null && $cpu !== false && $cpu !== '') {
                return round(100 - floatval($cpu), 1);
            }
        }
        
        // Method 3: Windows
        if ($this->detector->has_capability('windows') && $this->detector->has_capability('exec')) {
            @exec('wmic cpu get loadpercentage', $output);
            if (isset($output[1])) {
                return round(floatval($output[1]), 1);
            }
        }
        
        // Fallback: Use load average as approximation
        $load = $this->get_load_average();
        return round($load['1min'] * 25, 1); // Rough approximation
    }
    
    /**
     * Get memory information
     */
    public function get_memory_info() {
        // Method 1: Read /proc/meminfo (Linux)
        if ($this->detector->has_capability('proc_files') && is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
                
                if (isset($total[1])) {
                    $total_mb = round($total[1] / 1024, 0);
                    $available_mb = isset($available[1]) ? round($available[1] / 1024, 0) : 0;
                    $used_mb = $total_mb - $available_mb;
                    
                    return [
                        'total' => $total_mb,
                        'used' => $used_mb,
                        'percent' => round(($used_mb / $total_mb) * 100, 1)
                    ];
                }
            }
        }
        
        // Method 2: shell_exec free
        if ($this->detector->has_capability('shell_exec')) {
            $mem = @shell_exec("free -m | awk 'NR==2{print $2,$3}'");
            if ($mem) {
                $parts = explode(' ', trim($mem));
                if (count($parts) >= 2) {
                    $total = intval($parts[0]);
                    $used = intval($parts[1]);
                    return [
                        'total' => $total,
                        'used' => $used,
                        'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0
                    ];
                }
            }
        }
        
        // Method 3: PHP memory_get_usage (less accurate but universal)
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit) {
            $memory_limit_bytes = $this->parse_memory_limit($memory_limit);
            $current_usage = memory_get_usage(true);
            
            return [
                'total' => round($memory_limit_bytes / 1024 / 1024, 0),
                'used' => round($current_usage / 1024 / 1024, 0),
                'percent' => round(($current_usage / $memory_limit_bytes) * 100, 1)
            ];
        }
        
        return ['total' => 0, 'used' => 0, 'percent' => 0];
    }
    
    /**
     * Get process list
     */
    public function get_processes($filter = 'all', $limit = 20) {
        $processes = [];
        
        // Method 1: shell_exec ps (Unix/Linux)
        if ($this->detector->has_capability('shell_exec') && !$this->detector->has_capability('windows')) {
            $grep = '';
            switch ($filter) {
                case 'php':
                    $grep = " | grep -E 'php-fpm|php'";
                    break;
                case 'python':
                    $grep = " | grep python";
                    break;
                case 'node':
                    $grep = " | grep node";
                    break;
                case 'mysql':
                    $grep = " | grep -E 'mysql|mariadb'";
                    break;
            }
            
            $output = @shell_exec("ps aux --sort=-%cpu | head -" . ($limit + 1) . " | tail -n +2" . $grep);
            
            if ($output) {
                $lines = array_filter(explode("\n", trim($output)));
                foreach ($lines as $line) {
                    $parts = preg_split('/\s+/', $line, 11);
                    if (count($parts) >= 11) {
                        $cpu = floatval($parts[2]);
                        $mem = floatval($parts[3]);
                        
                        if ($filter === 'high-cpu' && $cpu < 10.0) {
                            continue;
                        }
                        
                        if ($filter === 'all' && $cpu < 0.5 && $mem < 0.5) {
                            continue;
                        }
                        
                        $pid = $parts[1];
                        $etime = @shell_exec("ps -p $pid -o etime --no-headers 2>/dev/null");
                        
                        $processes[] = [
                            'pid' => $pid,
                            'user' => substr($parts[0], 0, 12),
                            'cpu' => $cpu,
                            'mem' => $mem,
                            'vsz' => $parts[4],
                            'rss' => $parts[5],
                            'elapsed' => trim($etime ?: $parts[9]),
                            'command' => substr($parts[10], 0, 60),
                            'killable' => $this->is_killable_process($parts[0], $parts[10])
                        ];
                    }
                }
            }
        }
        
        // Method 2: Windows tasklist
        if ($this->detector->has_capability('windows') && $this->detector->has_capability('exec')) {
            @exec('tasklist /FO CSV /NH', $output);
            foreach ($output as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 5) {
                    $processes[] = [
                        'pid' => $parts[1],
                        'user' => 'N/A',
                        'cpu' => 0,
                        'mem' => round(intval(str_replace(',', '', $parts[4])) / 1024, 1),
                        'elapsed' => 'N/A',
                        'command' => substr($parts[0], 0, 60),
                        'killable' => false
                    ];
                }
            }
        }
        
        return array_slice($processes, 0, $limit);
    }
    
    /**
     * Kill a process
     */
    public function kill_process($pid, $force = false) {
        if (!$this->detector->has_capability('shell_exec')) {
            return ['success' => false, 'message' => 'Process killing not available on this platform'];
        }
        
        $pid = intval($pid);
        if ($pid <= 0) {
            return ['success' => false, 'message' => 'Invalid PID'];
        }
        
        // Verify process exists
        $process_info = @shell_exec("ps -p $pid -o user,comm --no-headers 2>/dev/null");
        if (!$process_info) {
            return ['success' => false, 'message' => 'Process not found'];
        }
        
        $info_parts = preg_split('/\s+/', trim($process_info), 2);
        if (!$this->is_killable_process($info_parts[0], $info_parts[1])) {
            return ['success' => false, 'message' => 'Cannot kill system or critical processes'];
        }
        
        // Kill the process
        $signal = $force ? '-9' : '-15';
        @shell_exec("kill $signal $pid 2>&1");
        
        usleep(500000);
        $still_running = @shell_exec("ps -p $pid --no-headers 2>/dev/null");
        
        if ($still_running) {
            return ['success' => false, 'message' => 'Process still running'];
        }
        
        return ['success' => true, 'message' => "Process $pid killed successfully"];
    }
    
    /**
     * Check if process is safe to kill
     */
    private function is_killable_process($user, $command) {
        $system_users = ['root', 'mysql', 'redis', 'www-data', 'SYSTEM', 'postgres'];
        if (in_array($user, $system_users)) {
            return false;
        }
        
        $critical_keywords = ['systemd', 'sshd', 'init', 'kernel', 'System'];
        foreach ($critical_keywords as $keyword) {
            if (stripos($command, $keyword) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get active connections
     */
    public function get_connections() {
        if ($this->detector->has_capability('shell_exec')) {
            $count = @shell_exec("netstat -an 2>/dev/null | grep :80 | grep ESTABLISHED | wc -l");
            if ($count !== null && $count !== false) {
                return intval(trim($count));
            }
        }
        
        return 0;
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parse_memory_limit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = intval($limit);
        
        switch($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
}
