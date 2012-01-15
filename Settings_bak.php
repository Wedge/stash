<?php
/**
 * Wedge
 *
 * Contains a backup of the master settings.
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 */

########## Maintenance ##########
# Note: If $maintenance is set to 2, the forum will be unusable!  Change it to 0 to fix it.
$maintenance = 0;								# Set to 1 to enable Maintenance Mode, 2 to make the forum untouchable. (you'll have to make it 0 again manually!)
$mtitle = 'Maintenance Mode';					# Title for the Maintenance Mode message.
$mmessage = 'We are currently working on website maintenance. Please bear with us, we\'ll restore access as soon as we can!';	# Description of why the forum is in maintenance mode.

########## Forum Info ##########
$mbname = 'My Community';						# The name of your forum.
$language = 'english';							# The default language file set for the forum.
$boardurl = 'http://127.0.0.1/wedge';			# URL to your forum's folder. (without the trailing /!)
$webmaster_email = 'noreply@myserver.com';		# Email address to send emails from. (like noreply@yourdomain.com.)
$cookiename = 'WedgeCookie01';					# Name of the cookie to set for authentication.

########## Database Info ##########
$db_server = 'localhost';
$db_name = 'wedge';
$db_user = 'root';
$db_passwd = '';
$ssi_db_user = '';
$ssi_db_passwd = '';
$db_prefix = 'wedge_';
$db_persist = 0;
$db_error_send = 1;
$db_show_debug = false;

########## Directories/Files ##########
# Note: These directories do not have to be changed unless you move things.
$boarddir = dirname(__FILE__);					# The absolute path to the forum's folder. (not just '.'!)
$sourcedir = dirname(__FILE__) . '/Sources';	# Path to the Sources directory.
$cachedir = dirname(__FILE__) . '/cache';		# Path to the cache directory.
$pluginsdir = dirname(__FILE__) . '/Plugins';	# Path to the plugins directory.
$pluginsurl = $boardurl . '/Plugins';			# URL to the Plugins area root.

########## Error-Catching ##########
# Note: You shouldn't touch these settings.
$db_last_error = 0;

?>