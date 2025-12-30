=== Real-Time Server Monitor ===
Contributors: mannmade
Tags: server monitor, performance, admin, dashboard, server load
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Real-time server monitoring with admin bar widget, detailed process viewer, and process management. Works across all hosting platforms.

== Description ==

Real-Time Server Monitor provides comprehensive server monitoring directly in your WordPress admin area. Monitor server load, CPU usage, memory, active processes, and more - all in real-time.

**Features:**

* **Admin Bar Monitor** - Always-visible server load indicator
* **Detailed Popup** - Click for comprehensive server statistics
* **Dashboard Widget** - Full monitoring on your WordPress dashboard
* **Process Management** - View and manage running processes
* **Process Filtering** - Filter by PHP, Python, Node, MySQL, or high CPU usage
* **Safe Process Killing** - Terminate problematic processes with safety checks
* **Platform Detection** - Automatically adapts to your hosting environment
* **Multi-Method Collection** - Uses multiple methods to gather stats based on available system functions
* **Universal Compatibility** - Works on shared hosting, VPS, dedicated servers, and all major hosting platforms

**Supported Platforms:**

* WP Engine
* Kinsta
* SiteGround
* Flywheel
* GoDaddy
* Pantheon
* RunCloud
* cPanel
* Plesk
* Generic Linux/Unix
* Windows (partial support)

**Monitoring Capabilities:**

* Server load averages (1, 5, 15 minutes)
* CPU usage percentage
* Memory usage (total, used, percentage)
* Active HTTP connections
* Running processes with details
* Process CPU and memory consumption
* Process runtime/elapsed time

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/realtime-server-monitor/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The server monitor will appear in your admin bar
4. Visit Settings → Server Monitor to configure options

== Frequently Asked Questions ==

= Does this work on shared hosting? =

Yes! The plugin detects available system functions and adapts accordingly. Some features may be limited on highly restricted shared hosting environments.

= Can I kill any process? =

No. The plugin has safety measures to prevent killing system-critical processes. Only user-owned, non-critical processes can be terminated.

= What if my host doesn't allow shell_exec? =

The plugin uses multiple fallback methods including reading /proc files, sys_getloadavg(), and other PHP functions to gather statistics.

= Will this slow down my site? =

No. Monitoring runs only in the admin area via AJAX and has minimal impact. You can adjust refresh intervals in settings.

= What permissions are required? =

Only WordPress administrators with 'manage_options' capability can view the monitor and kill processes.

== Screenshots ==

1. Admin bar monitor showing current load
2. Detailed popup with server statistics
3. Process manager with filtering
4. Dashboard widget
5. Settings page with platform information

== Changelog ==

= 1.0.0 =
* Initial release
* Admin bar monitor
* Dashboard widget
* Process management
* Multi-platform support
* Process filtering
* Platform detection

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Additional Info ==

For support, feature requests, or bug reports, visit [https://mannmade.us](https://mannmade.us)
