=== IOSEC HTTP Anti Flood/DoS Security Gateway Module ===
Contributors: iosec
Donate link: http://www.iosec.org/donate.html
Tags: anti flood, iosec, security, gateway, module, plugin, admin, http flood, gokhan muharremoglu, anti dos, hack, firewall, protection, spam, captcha, brute force, flood guard, ddos, stable
Requires at least: 2.0.2
Tested up to: 3.4.2
Stable tag: 1.8.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This module provides security enhancements against (HTTP) Flood & Brute Force Attacks for Wordpress. 
Massive scanning tools (like vulnerability scanners), HTTP Flood tools can be blocked or detected by this module.
This module can be integrated with htaccess, any firewall, iptables or etc. via "banlist" file.

To see a quick test page follow this link: http://www.iosec.org/test.php for proof of concept.

Watch the proof of concept video: http://youtu.be/LzLY_SKLq9w

Note: Change the default configuration values before activating the plugin.


== Installation ==


1. Upload "iosec.php" and "iosec_content" folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

These files must be in world writeable mode (e.g. chmod 777) (locally):

/wp-content/iosec_admin/

- ips
- banlist
- banlisttemp

To begin test with default values, try to refresh main page 3 or more times in a second (connetion interval= 0.5, Connection count= 1)

== Screenshots ==

1. Suspended Process screen when an attack is detected.

== CONFIGURATION DESCRIPTIONS ==

BENEFITS

- You can block proxies (via header information)
- You can detect flooding IP addresses.
- You can slow down or restrict access for automated tools (HTTP DoS tools & Flood tools, Brute force tools, Vulnerability scanners, etc.)
- You can save your server resources (database, cpu, ram, etc.) under an attack.
- You can restrict access permanently or temporarily for listed IP addresses in "banlist" file.
- You can notify yourself via email alerts when attacks begin.

CONS

- You have to tweak configuration file and even script's itself to avoid false positives.
- You have to restrict access for world writeable files and apply least privilige permissions to file properties.


Functions of files:

/wp-content/iosec_admin/

- banlist (Detected IP addresses listed here. You can use this file with iptables, htaccess with bash scripts.)
- banlisttemp  (Just a system file. IP and Time correlations listed in it.)
- ips  (Just a system file. Every request is listed in it.) 
- whitelist (Excluded IP List seperated by new lines.)
- excluded (Excluded File List seperated by new lines. E.g. for http://YOUR_SITE/wordpress/index.php file add this line to excluded file: /wordpress/index.php)

You should configure plugin by editing iosec.php file.

1. Connection Interval: This is second based interval for accepting another connection.
If you choose value 1 (1 second), another request in 1 second will be suspended by module. You can enter values like 0.1, 0.001, etc.

2. Max. Connection Count: This is the interval based maximum connection limit count for accepting another connection.
If you choose value 10 and your connection interval is 1 second. This means only 10 connections permitted in 1 second.

3. Suspended Process Timeout: When a connection interval rule finds a connection is not prohibited, this timeout value will be activated.
For example, if connection interval is 1 and this value is 30 then, second connection in 1 second will be suspended for 30 seconds.

4. Page Redirection: You redirect your detected users to another page after timeout page disappears.

5. Send Me Mail: Module can send you a mail when an IP address detected.

6. Block Proxies: You can identify and block proxies via http header.

7. Show Debug Info: Time and IP information will be displayed on suspension page when this option is activated.

8. Use Incremental Blocking: This option will increase time of suspension if attack is still happening.
For example,  if C.I. is 1 and a second connection happens in 1 second this will be suspended for 30 seconds (above ex.). 
If  one connection in 10 seconds happens, this will increase suspension time when this option is activated.

9. Implicit Deny Timeout: If you want to block every request as default for a timeout period (seconds), set this value to greater than "0". This is an emergency option for DDoS attacks etc.

10. Cached Requests:  Monitoring data window size for last requests (for "ips" file size) (default is "150"). 

11. Implicit Deny for banlist Timeout: If you want to block every recorded IP that is listed in the banlist as default and let the human users to view page for a timeout period (seconds), set this value to greater than "0" (default is "0").

CHANGES v.1.8.1 - v.1.8.2
-------------------------------------------------
- Improved Implicit Deny for Banned IP Addresses (Deny without detection)
- Minor Performance Tweaks

CHANGES v.1.5 - v.1.8
-------------------------------------------------
- Added Implicit Deny for the Banned IP Addresses
- Added Request Cache Size Option
- Added Excluded Files Support
- Added/Improved Implicit Deny Mode (with detection)

CHANGES v.1.3 - v.1.4
-------------------------------------------------
- Added Connection Limit Support


CHANGES v.1.2
-------------------------------------------------
- Added Whitelist Support


CHANGES v.1.1
-------------------------------------------------
- Added Reverse Proxy Support
- Added reCAPTCHA Support 
- Now Blocks Brute Force More Efficiently
- Minor Security Fixes


Gökhan Muharremoğlu
Information Security Specialist

You can reach me @
Twitter: https://twitter.com/iosec_org
gokhanmuh@users.sourceforge.net
gokhan.muharremoglu@iosec.org
https://sourceforge.net/projects/iosec/
http://www.iosec.org
http://www.linkedin.com/in/gokhanmuharremoglu