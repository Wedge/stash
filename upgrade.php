<?php
/**
 * Handles upgrading existing databases from older versions.
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

// Version information...
define('WEDGE_VERSION', '0.1');
define('WEDGE_LANG_VERSION', '0.1');

$GLOBALS['required_php_version'] = '5.2.4';
$GLOBALS['required_mysql_version'] = '5.0.3';

// General options for the script.
$timeLimitThreshold = 3;
$upgrade_path = dirname(__FILE__);
$upgradeurl = $_SERVER['PHP_SELF'];
// Where the Wedge images etc. are kept
$wedgesite = 'http://wedge.org/files';
// Disable the need for admins to login?
$disable_security = 0;
// How long, in seconds, must admin be inactive to allow someone else to run?
$upcontext['inactive_timeout'] = 10;

// All the steps in detail.
// Number,Name,Function,Progress Weight.
$upcontext['steps'] = array(
	0 => array(1, 'Login', 'WelcomeLogin', 2),
	1 => array(2, 'Upgrade Options', 'UpgradeOptions', 2),
	2 => array(3, 'Backup', 'BackupDatabase', 10),
	3 => array(4, 'Database Changes', 'DatabaseChanges', 70),
	4 => array(5, 'Delete Upgrade', 'DeleteUpgrade', 1),
);
// Just to remember which one has files in it.
$upcontext['database_step'] = 3;
@set_time_limit(600);
// Clean the upgrade path if this is from the client.
if (!empty($_SERVER['argv']) && php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']))
	for ($i = 1; $i < $_SERVER['argc']; $i++)
		if (preg_match('~^--path=(.+)$~', $_SERVER['argv'][$i], $match) != 0)
			$upgrade_path = substr($match[1], -1) == '/' ? substr($match[1], 0, -1) : $match[1];

// Are we from the client?
if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']))
{
	$command_line = true;
	$disable_security = 1;
}
else
	$command_line = false;

// Load this now just because we can.
require_once($upgrade_path . '/Settings.php');

// Are we logged in?
if (isset($upgradeData))
{
	$upcontext['user'] = unserialize(base64_decode($upgradeData));

	// Check for sensible values.
	if (empty($upcontext['user']['started']) || $upcontext['user']['started'] < time() - 86400)
		$upcontext['user']['started'] = time();
	if (empty($upcontext['user']['updated']) || $upcontext['user']['updated'] < time() - 86400)
		$upcontext['user']['updated'] = 0;

	$upcontext['started'] = $upcontext['user']['started'];
	$upcontext['updated'] = $upcontext['user']['updated'];
}

// Nothing sensible?
if (empty($upcontext['updated']))
{
	$upcontext['started'] = time();
	$upcontext['updated'] = 0;
	$upcontext['user'] = array(
		'id' => 0,
		'name' => 'Guest',
		'pass' => 0,
		'started' => $upcontext['started'],
		'updated' => $upcontext['updated'],
	);
}

// Load up some essential data...
loadEssentialData();

// Are we going to be mimic'ing SSI at this point?
if (isset($_GET['ssi']))
{
	require_once($sourcedir . '/Subs.php');
	require_once($sourcedir . '/Errors.php');
	require_once($sourcedir . '/Load.php');
	require_once($sourcedir . '/Security.php');
	require_once($sourcedir . '/Subs-Package.php');

	loadUserSettings();
	loadPermissions();
}

// All the non-SSI stuff.
if (!function_exists('ip2range'))
	require_once($sourcedir . '/Subs.php');

if (!function_exists('un_htmlspecialchars'))
{
	function un_htmlspecialchars($string)
	{
		return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES)) + array('&#039;' => '\'', '&nbsp;' => ' '));
	}
}

if (!function_exists('text2words'))
{
	function text2words($text)
	{
		global $smcFunc;

		// Step 1: Remove entities/things we don't consider words:
		$words = preg_replace('~(?:[\x0B\0\xA0\t\r\s\n(){}\\[\\]<>!@$%^*.,:+=`\~\?/\\\\]+|&(?:amp|lt|gt|quot);)+~', ' ', $text);

		// Step 2: Entities we left to letters, where applicable, lowercase.
		$words = preg_replace('~([^&\d]|^)[#;]~', '$1 ', un_htmlspecialchars(strtolower($words)));

		// Step 3: Ready to split apart and index!
		$words = explode(' ', $words);
		$returned_words = array();
		foreach ($words as $word)
		{
			$word = trim($word, '-_\'');

			if ($word != '')
				$returned_words[] = substr($word, 0, 20);
		}

		return array_unique($returned_words);
	}
}

if (!function_exists('clean_cache'))
{
	// Empty out the cache folder.
	function clean_cache($type = '')
	{
		global $cachedir;

		// No directory = no game.
		if (!is_dir($cachedir))
			return;

		$dh = scandir($cachedir);
		foreach ($dh as $file)
			if ($file != '.' && $file != '..' && $file != 'index.php' && $file != '.htaccess' && (!$type || substr($file, 0, strlen($type)) == $type))
				@unlink($cachedir . '/' . $file);
	}
}

// MD5 Encryption.
if (!function_exists('md5_hmac'))
{
	function md5_hmac($data, $key)
	{
		if (strlen($key) > 64)
			$key = pack('H*', md5($key));
		$key = str_pad($key, 64, chr(0x00));

		$k_ipad = $key ^ str_repeat(chr(0x36), 64);
		$k_opad = $key ^ str_repeat(chr(0x5c), 64);

		return md5($k_opad . pack('H*', md5($k_ipad . $data)));
	}
}

// http://www.faqs.org/rfcs/rfc959.html
if (!class_exists('ftp_connection'))
{
	class ftp_connection
	{
		var $connection = 'no_connection', $error = false, $last_message, $pasv = array();

		// Create a new FTP connection...
		function ftp_connection($ftp_server, $ftp_port = 21, $ftp_user = 'anonymous', $ftp_pass = 'ftpclient@wedge.org')
		{
			if ($ftp_server !== null)
				$this->connect($ftp_server, $ftp_port, $ftp_user, $ftp_pass);
		}

		function connect($ftp_server, $ftp_port = 21, $ftp_user = 'anonymous', $ftp_pass = 'ftpclient@wedge.org')
		{
			if (substr($ftp_server, 0, 6) == 'ftp://')
				$ftp_server = substr($ftp_server, 6);
			elseif (substr($ftp_server, 0, 7) == 'ftps://')
				$ftp_server = 'ssl://' . substr($ftp_server, 7);
			if (substr($ftp_server, 0, 7) == 'http://')
				$ftp_server = substr($ftp_server, 7);
			$ftp_server = strtr($ftp_server, array('/' => '', ':' => '', '@' => ''));

			// Connect to the FTP server.
			$this->connection = @fsockopen($ftp_server, $ftp_port, $err, $err, 5);
			if (!$this->connection)
			{
				$this->error = 'bad_server';
				return;
			}

			// Get the welcome message...
			if (!$this->check_response(220))
			{
				$this->error = 'bad_response';
				return;
			}

			// Send the username, it should ask for a password.
			fwrite($this->connection, 'USER ' . $ftp_user . "\r\n");
			if (!$this->check_response(331))
			{
				$this->error = 'bad_username';
				return;
			}

			// Now send the password... and hope it goes okay.
			fwrite($this->connection, 'PASS ' . $ftp_pass . "\r\n");
			if (!$this->check_response(230))
			{
				$this->error = 'bad_password';
				return;
			}
		}

		function chdir($ftp_path)
		{
			if (!is_resource($this->connection))
				return false;

			// No slash on the end, please...
			if (substr($ftp_path, -1) == '/' && $ftp_path !== '/')
				$ftp_path = substr($ftp_path, 0, -1);

			fwrite($this->connection, 'CWD ' . $ftp_path . "\r\n");
			if (!$this->check_response(250))
			{
				$this->error = 'bad_path';
				return false;
			}

			return true;
		}

		function chmod($ftp_file, $chmod)
		{
			if (!is_resource($this->connection))
				return false;

			// Convert the chmod value from octal (0777) to text ("777").
			fwrite($this->connection, 'SITE CHMOD ' . decoct($chmod) . ' ' . $ftp_file . "\r\n");
			if (!$this->check_response(200))
			{
				$this->error = 'bad_file';
				return false;
			}

			return true;
		}

		function unlink($ftp_file)
		{
			// We are actually connected, right?
			if (!is_resource($this->connection))
				return false;

			// Delete file X.
			fwrite($this->connection, 'DELE ' . $ftp_file . "\r\n");
			if (!$this->check_response(250))
			{
				fwrite($this->connection, 'RMD ' . $ftp_file . "\r\n");

				// Still no love?
				if (!$this->check_response(250))
				{
					$this->error = 'bad_file';
					return false;
				}
			}

			return true;
		}

		function check_response($desired)
		{
			// Wait for a response that isn't continued with -, but don't wait too long.
			$time = time();
			do
				$this->last_message = fgets($this->connection, 1024);
			while (substr($this->last_message, 3, 1) != ' ' && time() - $time < 5);

			// Was the desired response returned?
			return is_array($desired) ? in_array(substr($this->last_message, 0, 3), $desired) : substr($this->last_message, 0, 3) == $desired;
		}

		function passive()
		{
			// We can't create a passive data connection without a primary one first being there.
			if (!is_resource($this->connection))
				return false;

			// Request a passive connection - this means, we'll talk to you, you don't talk to us.
			@fwrite($this->connection, 'PASV' . "\r\n");
			$time = time();
			do
				$response = fgets($this->connection, 1024);
			while (substr($response, 3, 1) != ' ' && time() - $time < 5);

			// If it's not 227, we weren't given an IP and port, which means it failed.
			if (substr($response, 0, 4) != '227 ')
			{
				$this->error = 'bad_response';
				return false;
			}

			// Snatch the IP and port information, or die horribly trying...
			if (preg_match('~\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))\)~', $response, $match) == 0)
			{
				$this->error = 'bad_response';
				return false;
			}

			// This is pretty simple - store it for later use ;).
			$this->pasv = array('ip' => $match[1] . '.' . $match[2] . '.' . $match[3] . '.' . $match[4], 'port' => $match[5] * 256 + $match[6]);

			return true;
		}

		function create_file($ftp_file)
		{
			// First, we have to be connected... very important.
			if (!is_resource($this->connection))
				return false;

			// I'd like one passive mode, please!
			if (!$this->passive())
				return false;

			// Seems logical enough, so far...
			fwrite($this->connection, 'STOR ' . $ftp_file . "\r\n");

			// Okay, now we connect to the data port. If it doesn't work out, it's probably "file already exists", etc.
			$fp = @fsockopen($this->pasv['ip'], $this->pasv['port'], $err, $err, 5);
			if (!$fp || !$this->check_response(150))
			{
				$this->error = 'bad_file';
				@fclose($fp);
				return false;
			}

			// This may look strange, but we're just closing it to indicate a zero-byte upload.
			fclose($fp);
			if (!$this->check_response(226))
			{
				$this->error = 'bad_response';
				return false;
			}

			return true;
		}

		function list_dir($ftp_path = '', $search = false)
		{
			// Are we even connected...?
			if (!is_resource($this->connection))
				return false;

			// Passive... non-agressive...
			if (!$this->passive())
				return false;

			// Get the listing!
			fwrite($this->connection, 'LIST -1' . ($search ? 'R' : '') . ($ftp_path == '' ? '' : ' ' . $ftp_path) . "\r\n");

			// Connect, assuming we've got a connection.
			$fp = @fsockopen($this->pasv['ip'], $this->pasv['port'], $err, $err, 5);
			if (!$fp || !$this->check_response(array(150, 125)))
			{
				$this->error = 'bad_response';
				@fclose($fp);
				return false;
			}

			// Read in the file listing.
			$data = '';
			while (!feof($fp))
				$data .= fread($fp, 4096);
			fclose($fp);

			// Everything go okay?
			if (!$this->check_response(226))
			{
				$this->error = 'bad_response';
				return false;
			}

			return $data;
		}

		function locate($file, $listing = null)
		{
			if ($listing === null)
				$listing = $this->list_dir('', true);
			$listing = explode("\n", $listing);

			@fwrite($this->connection, 'PWD' . "\r\n");
			$time = time();
			do
				$response = fgets($this->connection, 1024);
			while (substr($response, 3, 1) != ' ' && time() - $time < 5);

			// Check for 257!
			if (preg_match('~^257 "(.+?)" ~', $response, $match) != 0)
				$current_dir = strtr($match[1], array('""' => '"'));
			else
				$current_dir = '';

			for ($i = 0, $n = count($listing); $i < $n; $i++)
			{
				if (trim($listing[$i]) == '' && isset($listing[$i + 1]))
				{
					$current_dir = substr(trim($listing[++$i]), 0, -1);
					$i++;
				}

				// Okay, this file's name is:
				$listing[$i] = $current_dir . '/' . trim(strlen($listing[$i]) > 30 ? strrchr($listing[$i], ' ') : $listing[$i]);

				if (substr($file, 0, 1) == '*' && substr($listing[$i], -(strlen($file) - 1)) == substr($file, 1))
					return $listing[$i];
				if (substr($file, -1) == '*' && substr($listing[$i], 0, strlen($file) - 1) == substr($file, 0, -1))
					return $listing[$i];
				if (basename($listing[$i]) == $file || $listing[$i] == $file)
					return $listing[$i];
			}

			return false;
		}

		function create_dir($ftp_dir)
		{
			// We must be connected to the server to do something.
			if (!is_resource($this->connection))
				return false;

			// Make this new beautiful directory!
			fwrite($this->connection, 'MKD ' . $ftp_dir . "\r\n");
			if (!$this->check_response(257))
			{
				$this->error = 'bad_file';
				return false;
			}

			return true;
		}

		function detect_path($filesystem_path, $lookup_file = null)
		{
			$username = '';

			if (isset($_SERVER['DOCUMENT_ROOT']))
			{
				if (preg_match('~^/home[2]?/([^/]+?)/public_html~', $_SERVER['DOCUMENT_ROOT'], $match))
				{
					$username = $match[1];

					$path = strtr($_SERVER['DOCUMENT_ROOT'], array('/home/' . $match[1] . '/' => '', '/home2/' . $match[1] . '/' => ''));

					if (substr($path, -1) == '/')
						$path = substr($path, 0, -1);

					if (strlen(dirname($_SERVER['PHP_SELF'])) > 1)
						$path .= dirname($_SERVER['PHP_SELF']);
				}
				elseif (substr($filesystem_path, 0, 9) == '/var/www/')
					$path = substr($filesystem_path, 8);
				else
					$path = strtr(strtr($filesystem_path, array('\\' => '/')), array($_SERVER['DOCUMENT_ROOT'] => ''));
			}
			else
				$path = '';

			if (is_resource($this->connection) && $this->list_dir($path) == '')
			{
				$data = $this->list_dir('', true);

				if ($lookup_file === null)
					$lookup_file = $_SERVER['PHP_SELF'];

				$found_path = dirname($this->locate('*' . basename(dirname($lookup_file)) . '/' . basename($lookup_file), $data));
				if ($found_path == false)
					$found_path = dirname($this->locate(basename($lookup_file)));
				if ($found_path != false)
					$path = $found_path;
			}
			elseif (is_resource($this->connection))
				$found_path = true;

			return array($username, $path, isset($found_path));
		}

		function close()
		{
			// Goodbye!
			fwrite($this->connection, 'QUIT' . "\r\n");
			fclose($this->connection);

			return true;
		}
	}
}

// Have we got tracking data - if so use it (It will be clean!)
if (isset($_GET['data']))
{
	$upcontext['upgrade_status'] = unserialize(base64_decode($_GET['data']));
	$upcontext['current_step'] = $upcontext['upgrade_status']['curstep'];
	$upcontext['language'] = $upcontext['upgrade_status']['lang'];
	$upcontext['rid'] = $upcontext['upgrade_status']['rid'];
	$is_debug = $upcontext['upgrade_status']['debug'];
	$support_js = $upcontext['upgrade_status']['js'];

	// Load the language.
	if (file_exists($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php'))
		require_once($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php');
}
// Set the defaults.
else
{
	$upcontext['current_step'] = 0;
	$upcontext['rid'] = mt_rand(0, 5000);
	$upcontext['upgrade_status'] = array(
		'curstep' => 0,
		'lang' => isset($_GET['lang']) ? $_GET['lang'] : basename($language, '.lng'),
		'rid' => $upcontext['rid'],
		'pass' => 0,
		'debug' => 0,
		'js' => 0,
	);
	$upcontext['language'] = $upcontext['upgrade_status']['lang'];
}

// If this isn't the first stage see whether they are logging in and resuming.
if ($upcontext['current_step'] != 0 || !empty($upcontext['user']['step']))
	checkLogin();

$request = $smcFunc['db_query']('', '
	SELECT variable, value
	FROM {db_prefix}themes
	WHERE id_theme = {int:id_theme}
		AND variable IN ({literal:theme_url}, {literal:theme_dir}, {literal:images_url})',
	array(
		'id_theme' => 1,
		'db_error_skip' => true,
	)
);
while ($row = $smcFunc['db_fetch_assoc']($request))
	$settings[$row['variable']] = $row['value'];
$smcFunc['db_free_result']($request);

if (!isset($settings['theme_url']))
{
	$settings['theme_dir'] = $boarddir . '/Themes/default';
	$settings['theme_url'] = 'Themes/default';
	$settings['images_url'] = 'Themes/default/images';
}
if (!isset($theme['default_theme_url']))
	$theme['default_theme_url'] = $settings['theme_url'];
if (!isset($theme['default_theme_dir']))
	$theme['default_theme_dir'] = $settings['theme_dir'];

// Default title...
$upcontext['page_title'] = 'Updating your Wedge Install!';

$upcontext['right_to_left'] = isset($txt['lang_rtl']) ? $txt['lang_rtl'] : false;

if ($command_line)
	cmdStep0();

// Don't error if we're using xml.
if (isset($_GET['xml']))
	$upcontext['return_error'] = true;

// Loop through all the steps doing each one as required.
$upcontext['overall_percent'] = 0;
foreach ($upcontext['steps'] as $num => $step)
{
	if ($num >= $upcontext['current_step'])
	{
		// The current weight of this step in terms of overall progress.
		$upcontext['step_weight'] = $step[3];
		// Make sure we reset the skip button.
		$upcontext['skip'] = false;

		// We cannot proceed if we're not logged in.
		if ($num != 0 && !$disable_security && $upcontext['user']['pass'] != $upcontext['upgrade_status']['pass'])
		{
			$upcontext['steps'][0][2]();
			break;
		}

		// Call the step and if it returns false that means pause!
		if (function_exists($step[2]) && $step[2]() === false)
			break;
		elseif (function_exists($step[2]))
			$upcontext['current_step']++;
	}
	$upcontext['overall_percent'] += $step[3];
}

upgradeExit();

// Exit the upgrade script.
function upgradeExit($fallThrough = false)
{
	global $upcontext, $upgradeurl, $boarddir, $command_line;

	// Save where we are...
	if (!empty($upcontext['current_step']) && !empty($upcontext['user']['id']))
	{
		$upcontext['user']['step'] = $upcontext['current_step'];
		$upcontext['user']['substep'] = $_GET['substep'];
		$upcontext['user']['updated'] = time();
		$upgradeData = base64_encode(serialize($upcontext['user']));
		copy($boarddir . '/Settings.php', $boarddir . '/Settings_bak.php');
		changeSettings(array('upgradeData' => '"' . $upgradeData . '"'));
	}

	// Handle the progress of the step, if any.
	if (!empty($upcontext['step_progress']) && isset($upcontext['steps'][$upcontext['current_step']]))
	{
		$upcontext['step_progress'] = round($upcontext['step_progress'], 1);
		$upcontext['overall_percent'] += $upcontext['step_progress'] * ($upcontext['steps'][$upcontext['current_step']][3] / 100);
	}
	$upcontext['overall_percent'] = (int) $upcontext['overall_percent'];

	// We usually dump our templates out.
	if (!$fallThrough)
	{
		// This should not happen my dear... HELP ME DEVELOPERS!!
		if (!empty($command_line))
		{
			debug_print_backtrace();

			echo "\n" . 'Error: Unexpected call to use the ' . (isset($upcontext['block']) ? $upcontext['block'] : '') . ' template. Please copy and paste all the text above and visit the Wedge support forum to tell the Developers that they\'ve made a boo boo; they\'ll get you up and running again.';
			flush();
			exit;
		}

		if (!isset($_GET['xml']))
			template_upgrade_above();
		else
		{
			header('Content-Type: text/xml; charset=UTF-8');

			// Sadly we need to retain the $_GET data thanks to the old upgrade scripts.
			$upcontext['get_data'] = array();
			foreach ($_GET as $k => $v)
				if (substr($k, 0, 3) != 'amp' && !in_array($k, array('xml', 'substep', 'lang', 'data', 'step', 'filecount')))
					$upcontext['get_data'][$k] = $v;

			template_xml_above();
		}

		// Call the template.
		if (isset($upcontext['block']))
		{
			$upcontext['upgrade_status']['curstep'] = $upcontext['current_step'];
			$upcontext['form_url'] = $upgradeurl . '?step=' . $upcontext['current_step'] . '&amp;substep=' . $_GET['substep'] . '&amp;data=' . base64_encode(serialize($upcontext['upgrade_status']));

			// Custom stuff to pass back?
			if (!empty($upcontext['query_string']))
				$upcontext['form_url'] .= $upcontext['query_string'];

			call_user_func('template_' . $upcontext['block']);
		}

		// Was there an error?
		if (!empty($upcontext['forced_error_message']))
			echo $upcontext['forced_error_message'];

		// Show the footer.
		if (!isset($_GET['xml']))
			template_upgrade_below();
		else
			template_xml_below();
	}

	// Bang - gone!
	exit;
}

// Used to direct the user to another location.
function redirectLocation($location, $addForm = true)
{
	global $upgradeurl, $upcontext, $command_line;

	// Command line users can't be redirected.
	if ($command_line)
		upgradeExit(true);

	// Are we providing the core info?
	if ($addForm)
	{
		$upcontext['upgrade_status']['curstep'] = $upcontext['current_step'];
		$location = $upgradeurl . '?step=' . $upcontext['current_step'] . '&substep=' . $_GET['substep'] . '&data=' . base64_encode(serialize($upcontext['upgrade_status'])) . $location;
	}

	while (@ob_end_clean());
	header('Location: ' . strtr($location, array('&amp;' => '&')));

	// Exit - saving status as we go.
	upgradeExit(true);
}

// Load all essential data and connect to the DB as this is pre SSI.php
function loadEssentialData()
{
	global $db_server, $db_user, $db_passwd, $db_name, $db_connection, $db_prefix;
	global $settings, $sourcedir, $upcontext;

	// Do the non-SSI stuff...
	@set_magic_quotes_runtime(0);
	error_reporting(E_ALL);
	define('WEDGE', 1);

	// Start the session.
	if (ini_get('session.save_handler') == 'user')
		ini_set('session.save_handler', 'files');
	@session_start();

	if (empty($smcFunc))
		$smcFunc = array();

	// Initialize everything...
	initialize_inputs();

	// Connect the database.
	if (!$db_connection)
	{
		require_once($sourcedir . '/Class-DB.php');
		wesql::getInstance();

		if (!$db_connection)
			$db_connection = wesql::connect($db_server, $db_name, $db_user, $db_passwd, $db_prefix, array('persist' => $db_persist));
	}

	// Oh dear god!!
	if ($db_connection === null)
		exit('Unable to connect to database - please check username and password are correct in Settings.php');

	$smcFunc['db_query']('', '
		SET NAMES utf8',
		array(
			'db_error_skip' => true,
		)
	);

	// Load the settings data...
	$request = $smcFunc['db_query']('', '
		SELECT variable, value
		FROM {db_prefix}settings',
		array(
			'db_error_skip' => true,
		)
	);
	$settings = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$settings[$row['variable']] = $row['value'];
	$smcFunc['db_free_result']($request);

	// If they don't have the file, they're going to get a warning anyway so we won't need to clean request vars.
	if (file_exists($sourcedir . '/QueryString.php'))
	{
		require_once($sourcedir . '/QueryString.php');
		cleanRequest();
	}

	if (!isset($_GET['substep']))
		$_GET['substep'] = 0;
}

function initialize_inputs()
{
	global $sourcedir, $start_time, $upcontext;

	$start_time = time();

	umask(0);

	// Fun. Low PHP version...
	if (!isset($_GET))
	{
		$GLOBALS['_GET']['step'] = 0;
		return;
	}

	ob_start();

	// Better to upgrade cleanly and fall apart than to screw everything up if things take too long.
	ignore_user_abort(true);

	// This is really quite simple; if ?delete is on the URL, delete the upgrader...
	if (isset($_GET['delete']))
	{
		@unlink(__FILE__);

		@unlink(dirname(__FILE__) . '/webinstall.php');

		$dh = scandir(dirname(__FILE__));
		foreach ($dh as $file)
			if (preg_match('~upgrade_[\w-]+\.sql~i', $file, $matches))
				@unlink(dirname(__FILE__) . '/' . $file);

		// Now just output a blank GIF... (Same code as in the verification code generator.)
		header('Content-Type: image/gif');
		exit("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B");
	}

	// Are we calling the backup css file?
	if (isset($_GET['infile_css']))
	{
		header('Content-Type: text/css');
		template_css();
		exit;
	}

	// Anybody home?
	if (!isset($_GET['xml']))
	{
		$upcontext['remote_files_available'] = false;
		$test = @fsockopen('wedge.org', 80, $errno, $errstr, 1);
		if ($test)
			$upcontext['remote_files_available'] = true;
		@fclose($test);
	}

	// Something is causing this to happen, and it's annoying. Stop it.
	$temp = 'upgrade_php?step';
	while (strlen($temp) > 4)
	{
		if (isset($_GET[$temp]))
			unset($_GET[$temp]);
		$temp = substr($temp, 1);
	}

	// Force a step, defaulting to 0.
	$_GET['step'] = (int) @$_GET['step'];
	$_GET['substep'] = (int) @$_GET['substep'];
}

// Step 0 - Let's welcome them in and ask them to login!
function WelcomeLogin()
{
	global $boarddir, $sourcedir, $db_prefix, $language, $settings, $cachedir, $upgradeurl;
	global $upcontext, $disable_security, $txt;

	$upcontext['block'] = 'welcome_message';

	// Check for some key files - one template, one language, and a new and an old source file.
	$check = @file_exists($boarddir . '/Themes/default/index.template.php')
		&& @file_exists($sourcedir . '/QueryString.php')
		&& @file_exists($sourcedir . '/Subs-Database.php')
		&& @file_exists(dirname(__FILE__) . '/upgrade.sql');

	if (!$check)
		// Don't tell them what files exactly because it's a spot check - just like teachers don't tell which problems they are spot checking, that's dumb.
		return throw_error('The upgrader was unable to find some crucial files.<br><br>Please make sure you uploaded all of the files included in the package, including the Themes, Sources, and other directories.');

	// Do they meet the install requirements?
	if (!php_version_check())
		return throw_error('Warning! You do not appear to have a version of PHP installed on your webserver that meets Wedge\'s minimum installations requirements.<br><br>Please ask your host to upgrade.');

	if (!db_version_check())
		return throw_error('Your MySQL version does not meet the minimum requirements of Wedge.<br><br>Please ask your host to upgrade.');

	// Do they have ALTER privileges?
	if ($smcFunc['db_query']('', 'ALTER TABLE {db_prefix}boards ORDER BY id_board', array()) === false)
		return throw_error('The MySQL user you have set in Settings.php does not have proper privileges.<br><br>Please ask your host to give this user the ALTER, CREATE, and DROP privileges.');

	// Do a quick version spot check.
	$temp = substr(@implode('', @file($boarddir . '/index.php')), 0, 4096);
	preg_match("~'WEDGE_VERSION', '([^']+)'~i", $data, $match);
	if (empty($match[1]) || $match[1] != WEDGE_VERSION)
		return throw_error('The upgrader found some old or outdated files.<br><br>Please make certain you uploaded the new versions of all the files included in the package.');

	// What absolutely needs to be writable?
	$writable_files = array(
		$boarddir . '/Settings.php',
		$boarddir . '/Settings_bak.php',
	);

	// Check the cache directory.
	$cachedir_temp = empty($cachedir) ? $boarddir . '/cache' : $cachedir;
	if (!file_exists($cachedir_temp))
		@mkdir($cachedir_temp);
	if (!file_exists($cachedir_temp))
		return throw_error('The cache directory could not be found.<br><br>Please make sure you have a directory called &quot;cache&quot; in your forum directory before continuing.');

	if (!isset($_GET['skiplang']))
	{
		$temp = substr(@implode('', @file($boarddir . '/Themes/default/languages/index.' . $upcontext['language'] . '.php')), 0, 4096);
		preg_match('~(?://|/\*)\s*Version:\s+(.+?);\s*index(?:[\s]{2}|\*/)~i', $temp, $match);

		if (empty($match[1]) || $match[1] != WEDGE_LANG_VERSION)
			return throw_error('The upgrader found some old or outdated language files, for the forum default language, ' . $upcontext['language'] . '.<br><br>Please make certain you uploaded the new versions of all the files included in the package, even the theme and language files for the default theme.<br>&nbsp;&nbsp;&nbsp;[<a href="' . $upgradeurl . '?skiplang">SKIP</a>] [<a href="' . $upgradeurl . '?lang=english">Try English</a>]');
	}

	// This needs to exist!
	if (!file_exists($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php'))
		return throw_error('The upgrader could not find the &quot;Install&quot; language file for the forum default language, ' . $upcontext['language'] . '.<br><br>Please make certain you uploaded all the files included in the package, even the theme and language files for the default theme.<br>&nbsp;&nbsp;&nbsp;[<a href="' . $upgradeurl . '?lang=english">Try English</a>]');
	else
		require_once($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php');

	if (!makeFilesWritable($writable_files))
		return false;

	// We're going to check that their board dir setting is right incase they've been moving stuff around.
	if (strtr($boarddir, array('/' => '', '\\' => '')) != strtr(dirname(__FILE__), array('/' => '', '\\' => '')))
		$upcontext['warning'] = '
			It looks as if your board directory settings <em>might</em> be incorrect. Your board directory is currently set to &quot;' . $boarddir . '&quot; but should probably be &quot;' . dirname(__FILE__) . '&quot;. Settings.php currently lists your paths as:<br>
			<ul>
				<li>Board Directory: ' . $boarddir . '</li>
				<li>Source Directory: ' . $boarddir . '</li>
				<li>Cache Directory: ' . $cachedir_temp . '</li>
			</ul>
			If these seem incorrect please open Settings.php in a text editor before proceeding with this upgrade. If they are incorrect due to you moving your forum to a new location please <a href="http://wedge.org/">download</a> and execute the Repair Settings tool from the Wedge website before continuing.';

	// Either we're logged in or we're going to present the login.
	if (checkLogin())
		return true;

	return false;
}

// Step 0.5: Does the login work?
function checkLogin()
{
	global $boarddir, $sourcedir, $db_prefix, $language, $settings, $cachedir, $upgradeurl;
	global $upcontext, $disable_security, $support_js, $txt;

	// Are we trying to login?
	if (isset($_POST['contbutt']) && (!empty($_POST['user']) || $disable_security))
	{
		// If we've disabled security pick a suitable name!
		if (empty($_POST['user']))
			$_POST['user'] = 'Administrator';

		// In ancient pre-fork versions, these column names were different.
		$oldDB = false;
		$request = $smcFunc['db_query']('', '
			SHOW COLUMNS
			FROM {db_prefix}members
			LIKE {string:member_name}',
			array(
				'member_name' => 'memberName',
				'db_error_skip' => true,
			)
		);
		if ($smcFunc['db_num_rows']($request) != 0)
			$oldDB = true;
		$smcFunc['db_free_result']($request);

		// Get what we believe to be their details.
		if (!$disable_security)
		{
			if ($oldDB)
				$request = $smcFunc['db_query']('', '
					SELECT id_member, memberName AS member_name, passwd, id_group,
					additionalGroups AS additional_groups, lngfile
					FROM {db_prefix}members
					WHERE memberName = {string:member_name}',
					array(
						'member_name' => $_POST['user'],
						'db_error_skip' => true,
					)
				);
			else
				$request = $smcFunc['db_query']('', '
					SELECT id_member, member_name, passwd, id_group, additional_groups, lngfile
					FROM {db_prefix}members
					WHERE member_name = {string:member_name}',
					array(
						'member_name' => $_POST['user'],
						'db_error_skip' => true,
					)
				);
			if ($smcFunc['db_num_rows']($request) != 0)
			{
				list ($id_member, $name, $password, $id_group, $addGroups, $user_language) = $smcFunc['db_fetch_row']($request);

				$groups = explode(',', $addGroups);
				$groups[] = $id_group;

				foreach ($groups as $k => $v)
					$groups[$k] = (int) $v;

				// Figure out the password using Wedge's encryption - if what they typed is right.
				if (isset($_REQUEST['hash_passwrd']) && strlen($_REQUEST['hash_passwrd']) == 40)
				{
					// Challenge passed.
					if ($_REQUEST['hash_passwrd'] == sha1($password . $upcontext['rid']))
						$sha_passwd = $password;
				}
				else
					$sha_passwd = sha1(strtolower($name) . un_htmlspecialchars($_REQUEST['passwrd']));
			}
			else
				$upcontext['username_incorrect'] = true;
			$smcFunc['db_free_result']($request);
		}
		$upcontext['username'] = $_POST['user'];

		// Track whether javascript works!
		if (!empty($_POST['js_works']))
		{
			$upcontext['upgrade_status']['js'] = 1;
			$support_js = 1;
		}
		else
			$support_js = 0;

		// Note down the version we are coming from.
		if (empty($upcontext['user']['version']))
			$upcontext['user']['version'] = $settings['weVersion'];

		// Didn't get anywhere?
		if ((empty($sha_passwd) || $password !== $sha_passwd) && empty($upcontext['username_incorrect']) && !$disable_security)
		{
			// MD5?
			$md5pass = md5_hmac($_REQUEST['passwrd'], strtolower($_POST['user']));
			if ($md5pass !== $password)
			{
				$upcontext['password_failed'] = true;
				// Disable the hashing this time.
				$upcontext['disable_login_hashing'] = true;
			}
		}

		if ((empty($upcontext['password_failed']) && !empty($name)) || $disable_security)
		{
			// Set the password.
			if (!$disable_security)
			{
				// Do we actually have permission?
				if (!in_array(1, $groups))
				{
					$request = $smcFunc['db_query']('', '
						SELECT permission
						FROM {db_prefix}permissions
						WHERE id_group IN ({array_int:groups})
							AND permission = {literal:admin_forum}',
						array(
							'groups' => $groups,
							'db_error_skip' => true,
						)
					);
					if ($smcFunc['db_num_rows']($request) == 0)
						return throw_error('You need to be an admin to perform an upgrade!');
					$smcFunc['db_free_result']($request);
				}

				$upcontext['user']['id'] = $id_member;
				$upcontext['user']['name'] = $name;
			}
			else
			{
				$upcontext['user']['id'] = 1;
				$upcontext['user']['name'] = 'Administrator';
			}
			$upcontext['user']['pass'] = mt_rand(0,60000);
			// This basically is used to match the GET variables to Settings.php.
			$upcontext['upgrade_status']['pass'] = $upcontext['user']['pass'];

			// Set the language to that of the user?
			if (isset($user_language) && $user_language != $upcontext['language'] && file_exists($boarddir . '/Themes/default/languages/index.' . basename($user_language, '.lng') . '.php'))
			{
				$user_language = basename($user_language, '.lng');
				$temp = substr(@implode('', @file($boarddir . '/Themes/default/languages/index.' . $user_language . '.php')), 0, 4096);
				preg_match('~(?://|/\*)\s*Version:\s+(.+?);\s*index(?:[\s]{2}|\*/)~i', $temp, $match);

				if (empty($match[1]) || $match[1] != WEDGE_LANG_VERSION)
					$upcontext['upgrade_options_warning'] = 'The language files for your selected language, ' . $user_language . ', have not been updated to the latest version. Upgrade will continue with the forum default, ' . $upcontext['language'] . '.';
				elseif (!file_exists($boarddir . '/Themes/default/languages/Install.' . basename($user_language, '.lng') . '.php'))
					$upcontext['upgrade_options_warning'] = 'The language files for your selected language, ' . $user_language . ', have not been uploaded/updated as the &quot;Install&quot; language file is missing. Upgrade will continue with the forum default, ' . $upcontext['language'] . '.';
				else
				{
					// Set this as the new language.
					$upcontext['language'] = $user_language;
					$upcontext['upgrade_status']['lang'] = $upcontext['language'];

					// Include the file.
					require_once($boarddir . '/Themes/default/languages/Install.' . $user_language . '.php');
				}
			}

			// If we're resuming set the step and substep to be correct.
			if (isset($_POST['cont']))
			{
				$upcontext['current_step'] = $upcontext['user']['step'];
				$_GET['substep'] = $upcontext['user']['substep'];
			}

			return true;
		}
	}

	return false;
}

// Step 1: Do the maintenance and backup.
function UpgradeOptions()
{
	global $db_prefix, $command_line, $settings, $is_debug;
	global $boarddir, $boardurl, $sourcedir, $maintenance, $mmessage, $cachedir, $upcontext;

	$upcontext['block'] = 'upgrade_options';
	$upcontext['page_title'] = 'Upgrade Options';

	// If we've not submitted then we're done.
	if (empty($_POST['upcont']))
		return false;

	// Emptying the error log?
	if (!empty($_POST['empty_error']))
		$smcFunc['db_query']('', '
			TRUNCATE {db_prefix}log_errors',
			array(
			)
		);

	$changes = array();

	// If we're overriding the language follow it through.
	if (isset($_GET['lang']) && file_exists($boarddir . '/Themes/default/languages/index.' . $_GET['lang'] . '.php'))
		$changes['language'] = '\'' . $_GET['lang'] . '\'';

	if (!empty($_POST['maint']))
	{
		$changes['maintenance'] = '2';
		// Remember what it was...
		$upcontext['user']['main'] = $maintenance;

		if (!empty($_POST['maintitle']))
		{
			$changes['mtitle'] = '\'' . addslashes($_POST['maintitle']) . '\'';
			$changes['mmessage'] = '\'' . addslashes($_POST['mainmessage']) . '\'';
		}
		else
		{
			$changes['mtitle'] = '\'Upgrading the forum...\'';
			$changes['mmessage'] = '\'Don\\\'t worry, we will be back shortly with an updated forum. It will only be a minute ;).\'';
		}
	}

	if ($command_line)
		echo ' * Updating Settings.php...';

	// Backup the current one first.
	copy($boarddir . '/Settings.php', $boarddir . '/Settings_bak.php');

	// Fix some old paths.
	if (substr($boarddir, 0, 1) == '.')
		$changes['boarddir'] = '\'' . fixRelativePath($boarddir) . '\'';

	if (substr($sourcedir, 0, 1) == '.')
		$changes['sourcedir'] = '\'' . fixRelativePath($sourcedir) . '\'';

	if (empty($cachedir) || substr($cachedir, 0, 1) == '.')
		$changes['cachedir'] = '\'' . fixRelativePath($boarddir) . '/cache\'';

	// !!! Maybe change the cookie name if going to 1.1, too?

	// Update Settings.php with the new settings.
	changeSettings($changes);

	if ($command_line)
		echo ' Successful.' . "\n";

	// Are we doing debug?
	if (isset($_POST['debug']))
	{
		$upcontext['upgrade_status']['debug'] = true;
		$is_debug = true;
	}

	// If we're not backing up then jump one.
	if (empty($_POST['backup']))
		$upcontext['current_step']++;

	// If we've got here then let's proceed to the next step!
	return true;
}

// Backup the database - why not...
function BackupDatabase()
{
	global $upcontext, $db_prefix, $command_line, $is_debug, $support_js, $file_steps;

	$upcontext['block'] = isset($_GET['xml']) ? 'backup_xml' : 'backup_database';
	$upcontext['page_title'] = 'Backup Database';

	// Done it already - js wise?
	if (!empty($_POST['backup_done']))
		return true;

	// Some useful stuff here.
	db_extend();

	// Get all the table names.
	$filter = str_replace('_', '\_', preg_match('~^`(.+?)`\.(.+?)$~', $db_prefix, $match) != 0 ? $match[2] : $db_prefix) . '%';
	$dbn = preg_match('~^`(.+?)`\.(.+?)$~', $db_prefix, $match) != 0 ? strtr($match[1], array('`' => '')) : false;
	$tables = $smcFunc['db_list_tables']($dbn, $filter);

	$table_names = array();
	foreach ($tables as $table)
		if (substr($table, 0, 7) !== 'backup_')
			$table_names[] = $table;

	$upcontext['table_count'] = count($table_names);
	$upcontext['cur_table_num'] = $_GET['substep'];
	$upcontext['cur_table_name'] = str_replace($db_prefix, '', isset($table_names[$_GET['substep']]) ? $table_names[$_GET['substep']] : $table_names[0]);
	$upcontext['step_progress'] = (int) (($upcontext['cur_table_num'] / $upcontext['table_count']) * 100);
	// For non-java auto submit...
	$file_steps = $upcontext['table_count'];

	// What ones have we already done?
	foreach ($table_names as $id => $table)
		if ($id < $_GET['substep'])
			$upcontext['previous_tables'][] = $table;

	if ($command_line)
		echo 'Backing Up Tables.';

	// If we don't support javascript we backup here.
	if (!$support_js || isset($_GET['xml']))
	{
		// Backup each table!
		for ($substep = $_GET['substep'], $n = count($table_names); $substep < $n; $substep++)
		{
			$upcontext['cur_table_name'] = str_replace($db_prefix, '', (isset($table_names[$substep + 1]) ? $table_names[$substep + 1] : $table_names[$substep]));
			$upcontext['cur_table_num'] = $substep + 1;

			$upcontext['step_progress'] = (int) (($upcontext['cur_table_num'] / $upcontext['table_count']) * 100);

			// Do we need to pause?
			nextSubstep($substep);

			backupTable($table_names[$substep]);

			// If this is XML to keep it nice for the user do one table at a time anyway!
			if (isset($_GET['xml']))
				return upgradeExit();
		}

		if ($is_debug && $command_line)
		{
			echo "\n" . ' Successful.\'' . "\n";
			flush();
		}
		$upcontext['step_progress'] = 100;

		$_GET['substep'] = 0;
		// Make sure we move on!
		return true;
	}

	// Either way next place to post will be database changes!
	$_GET['substep'] = 0;
	return false;
}

// Backup one table...
function backupTable($table)
{
	global $is_debug, $command_line, $db_prefix;

	if ($is_debug && $command_line)
	{
		echo "\n" . ' +++ Backing up \"' . str_replace($db_prefix, '', $table) . '"...';
		flush();
	}

	$smcFunc['db_backup_table']($table, 'backup_' . $table);

	if ($is_debug && $command_line)
		echo ' done.';
}

// Step 2: Everything.
function DatabaseChanges()
{
	global $db_prefix, $settings, $command_line;
	global $language, $boardurl, $sourcedir, $boarddir, $upcontext, $support_js;

	// Have we just completed this?
	if (!empty($_POST['database_done']))
		return true;

	$upcontext['block'] = isset($_GET['xml']) ? 'database_xml' : 'database_changes';
	$upcontext['page_title'] = 'Database Changes';

	// All possible files.
	// Name, <version, insert_on_complete
	$files = array(
		array('upgrade.sql', '0.1', WEDGE_VERSION),
	);

	// How many files are there in total?
	if (isset($_GET['filecount']))
		$upcontext['file_count'] = (int) $_GET['filecount'];
	else
	{
		$upcontext['file_count'] = 0;
		foreach ($files as $file)
			if ($settings['weVersion'] < $file[1])
				$upcontext['file_count']++;
	}

	// Do each file!
	$did_not_do = count($files) - $upcontext['file_count'];
	$upcontext['step_progress'] = 0;
	$upcontext['cur_file_num'] = 0;
	foreach ($files as $file)
	{
		if ($did_not_do)
			$did_not_do--;
		else
		{
			$upcontext['cur_file_num']++;
			$upcontext['cur_file_name'] = $file[0];
			// Do we actually need to do this still?
			if ($settings['weVersion'] < $file[1])
			{
				$nextFile = parse_sql(dirname(__FILE__) . '/' . $file[0]);
				if ($nextFile)
				{
					// Only update the version of this if complete.
					$smcFunc['db_insert']('replace',
						$db_prefix . 'settings',
						array('variable' => 'string', 'value' => 'string'),
						array('weVersion', $file[2]),
						array('variable')
					);

					$settings['weVersion'] = $file[2];
				}

				// If this is XML we only do this stuff once.
				if (isset($_GET['xml']))
				{
					// Flag to move on to the next.
					$upcontext['completed_step'] = true;
					// Did we complete the whole file?
					if ($nextFile)
						$upcontext['current_debug_item_num'] = -1;
					return upgradeExit();
				}
				elseif ($support_js)
					break;
			}
			// Set the progress bar to be right as if we had - even if we hadn't...
			$upcontext['step_progress'] = ($upcontext['cur_file_num'] / $upcontext['file_count']) * 100;
		}
	}

	$_GET['substep'] = 0;
	// So the template knows we're done.
	if (!$support_js)
	{
		$upcontext['changes_complete'] = true;

		// If this is the command line we can't do any more.
		if ($command_line)
			return DeleteUpgrade();

		return true;
	}
	return false;
}

// Delete the damn thing!
function DeleteUpgrade()
{
	global $command_line, $language, $upcontext, $boarddir, $sourcedir, $maintenance;

	// Now it's nice to have some of the basic Wedge source files.
	if (!isset($_GET['ssi']) && !$command_line)
		redirectLocation('&ssi=1');

	$upcontext['block'] = 'upgrade_complete';
	$upcontext['page_title'] = 'Upgrade Complete';

	$endl = $command_line ? "\n" : '<br>' . "\n";

	$changes = array(
		'language' => '\'' . (substr($language, -4) == '.lng' ? substr($language, 0, -4) : $language) . '\'',
		'db_error_send' => '1',
		'upgradeData' => '#remove#',
	);

	// Are we in maintenance mode?
	if (isset($upcontext['user']['main']))
	{
		if ($command_line)
			echo ' * ';
		$upcontext['removed_maintenance'] = true;
		$changes['maintenance'] = $upcontext['user']['main'];
	}
	// Otherwise if somehow we are in 2 let's go to 1.
	elseif (!empty($maintenance) && $maintenance == 2)
		$changes['maintenance'] = 1;

	// Wipe this out...
	$upcontext['user'] = array();

	// Make a backup of Settings.php first as otherwise earlier changes are lost.
	copy($boarddir . '/Settings.php', $boarddir . '/Settings_bak.php');
	changeSettings($changes);

	// Clean any old cache files away.
	clean_cache();

	// Can we delete the file?
	$upcontext['can_delete_script'] = is_writable(dirname(__FILE__)) || is_writable(__FILE__);

	// Now is the perfect time to fetch the Wedge files.
	if ($command_line)
		cli_scheduled_fetchRemoteFiles();
	else
	{
		require_once($sourcedir . '/ScheduledTasks.php');
		scheduled_fetchRemoteFiles(); // Now go get those files!
	}

	// Log what we've done.
	if (empty(we::$id))
		we::$id = !empty($upcontext['user']['id']) ? $upcontext['user']['id'] : 0;

	// Log the action manually, so CLI still works.
	$smcFunc['db_insert']('',
		'{db_prefix}log_actions',
		array(
			'log_time' => 'int', 'id_log' => 'int', 'id_member' => 'int', 'ip' => 'string-16', 'action' => 'string',
			'id_board' => 'int', 'id_topic' => 'int', 'id_msg' => 'int', 'extra' => 'string-65534',
		),
		array(
			time(), 3, we::$id, $command_line ? '127.0.0.1' : we::$user['ip'], 'upgrade',
			0, 0, 0, serialize(array('version' => WEDGE_VERSION, 'member' => we::$id)),
		),
		array('id_action')
	);
	we::$id = 0;

	if ($command_line)
	{
		echo $endl;
		echo 'Upgrade Complete!', $endl;
		echo 'Please delete this file as soon as possible for security reasons.', $endl;
		exit;
	}

	// Make sure it says we're done.
	$upcontext['overall_percent'] = 100;
	if (isset($upcontext['step_progress']))
		unset($upcontext['step_progress']);

	$_GET['substep'] = 0;
	return false;
}

// Just like the built-in one, but setup for CLI to not use themes.
function cli_scheduled_fetchRemoteFiles()
{
	global $sourcedir, $txt, $language, $theme, $settings;

	if (empty($txt['time_format']))
		$txt['time_format'] = '%B %e, %Y, %I:%M:%S %p';

	// What files do we want to get
	$request = $smcFunc['db_query']('', '
		SELECT id_file, filename, path, parameters
		FROM {db_prefix}admin_info_files',
		array(
		)
	);

	$js_files = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$js_files[$row['id_file']] = array(
			'filename' => $row['filename'],
			'path' => $row['path'],
			'parameters' => sprintf($row['parameters'], $language, urlencode($txt['time_format']), urlencode(WEDGE_VERSION)),
		);
	$smcFunc['db_free_result']($request);

	// We're gonna need Class-WebGet() to pull this off.
	require_once($sourcedir . '/Class-WebGet.php');

	foreach ($js_files as $id_file => $file)
	{
		// Create the url
		$server = empty($file['path']) || substr($file['path'], 0, 7) != 'http://' ? 'http://wedge.org' : '';
		$url = $server . (!empty($file['path']) ? $file['path'] : $file['path']) . $file['filename'] . (!empty($file['parameters']) ? '?' . $file['parameters'] : '');

		// Get the file
		$weget = new weget($url);
		$file_data = $weget->get();

		// If we got an error - give up - the site might be down.
		if ($file_data === false)
			return throw_error(sprintf('Could not retrieve the file %1$s.', $url));

		// Save the file to the database.
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}admin_info_files
			SET data = SUBSTRING({string:file_data}, 1, 65534)
			WHERE id_file = {int:id_file}',
			array(
				'id_file' => $id_file,
				'file_data' => $file_data,
			)
		);
	}
	return true;
}

function convertSettingsToTheme()
{
	global $db_prefix, $settings;

	$values = array(
		'show_latest_member' => @$GLOBALS['showlatestmember'],
		'show_bbc' => @$GLOBALS['showbbcbutt'],
		'show_user_images' => @$GLOBALS['showuserpic'],
		'show_blurb' => @$GLOBALS['showusertext'],
		'show_gender' => @$GLOBALS['showgenderimage'],
		'show_newsfader' => @$GLOBALS['shownewsfader'],
		'display_recent_bar' => @$GLOBALS['Show_RecentBar'],
		'show_member_bar' => @$GLOBALS['Show_MemberBar'],
		'show_board_desc' => @$GLOBALS['ShowBDescrip'],
		'newsfader_time' => @$GLOBALS['fadertime'],
		'use_image_buttons' => empty($GLOBALS['MenuType']) ? 1 : 0,
		'enable_news' => @$GLOBALS['enable_news'],
		'return_to_post' => @$settings['returnToPost'],
	);

	$themeData = array();
	foreach ($values as $variable => $value)
	{
		if (!isset($value) || $value === null)
			$value = 0;

		$themeData[] = array(0, 1, $variable, $value);
	}
	if (!empty($themeData))
	{
		$smcFunc['db_insert']('ignore',
			$db_prefix . 'themes',
			array('id_member' => 'int', 'id_theme' => 'int', 'variable' => 'string', 'value' => 'string'),
			$themeData,
			array('id_member', 'id_theme', 'variable')
		);
	}
}

function convertSettingstoOptions()
{
	global $db_prefix, $settings;

	// Format: new_setting -> old_setting_name.
	$values = array(
		'view_newest_first' => 'viewNewestFirst',
		'view_newest_pm_first' => 'viewNewestFirst',
	);

	foreach ($values as $variable => $value)
	{
		if (empty($settings[$value[0]]))
			continue;

		$smcFunc['db_query']('', '
			INSERT IGNORE INTO {db_prefix}themes
				(id_member, id_theme, variable, value)
			SELECT id_member, 1, {string:variable}, {string:value}
			FROM {db_prefix}members',
			array(
				'variable' => $variable,
				'value' => $settings[$value[0]],
				'db_error_skip' => true,
			)
		);

		$smcFunc['db_query']('', '
			INSERT IGNORE INTO {db_prefix}themes
				(id_member, id_theme, variable, value)
			VALUES (-1, 1, {string:variable}, {string:value})',
			array(
				'variable' => $variable,
				'value' => $settings[$value[0]],
				'db_error_skip' => true,
			)
		);
	}
}

function changeSettings($config_vars)
{
	global $boarddir;

	$settingsArray = file($boarddir . '/Settings_bak.php');

	if (count($settingsArray) == 1)
		$settingsArray = preg_split('~[\r\n]~', $settingsArray[0]);

	for ($i = 0, $n = count($settingsArray); $i < $n; $i++)
	{
		// Don't trim or bother with it if it's not a variable.
		if (substr($settingsArray[$i], 0, 1) == '$')
		{
			$settingsArray[$i] = trim($settingsArray[$i]) . "\n";

			foreach ($config_vars as $var => $val)
			{
				if (isset($settingsArray[$i]) && strncasecmp($settingsArray[$i], '$' . $var, 1 + strlen($var)) == 0)
				{
					if ($val == '#remove#')
						unset($settingsArray[$i]);
						else
						{
							$comment = strstr(substr($settingsArray[$i], strpos($settingsArray[$i], ';')), '#');
							$settingsArray[$i] = '$' . $var . ' = ' . $val . ';' . ($comment != '' ? "\t\t" . $comment : "\n");
						}

					unset($config_vars[$var]);
				}
			}
		}
		if (isset($settingsArray[$i]) && trim(substr($settingsArray[$i], 0, 2)) == '?' . '>')
			$end = $i;
	}

	// Assume end-of-file if the end wasn't found.
	if (empty($end) || $end < 10)
		$end = count($settingsArray);

	if (!empty($config_vars))
	{
		$settingsArray[$end++] = '';
		foreach ($config_vars as $var => $val)
		{
			if ($val != '#remove#')
				$settingsArray[$end++] = '$' . $var . ' = ' . $val . ';' . "\n";
		}
	}
	// This should be the last line and even last bytes of the file.
	$settingsArray[$end] = '?' . '>';

	// Blank out the file - done to fix a oddity with some servers.
	$fp = fopen($boarddir . '/Settings.php', 'w');
	fclose($fp);

	$fp = fopen($boarddir . '/Settings.php', 'r+');
	for ($i = 0; $i < $end; $i++)
	{
		if (isset($settingsArray[$i]))
			fwrite($fp, strtr($settingsArray[$i], "\r", ''));
	}
	fwrite($fp, rtrim($settingsArray[$i]));
	fclose($fp);
}

function php_version_check()
{
	return version_compare($GLOBALS['required_php_version'], PHP_VERSION, '<');
}

function db_version_check()
{
	return version_compare($GLOBALS['required_mysql_version'], preg_replace('~\-.+?$~', '', mysql_get_server_info()), '<');
}

function getMemberGroups()
{
	global $db_prefix;
	static $member_groups = array();

	if (!empty($member_groups))
		return $member_groups;

	$request = $smcFunc['db_query']('', '
		SELECT group_name, id_group
		FROM {db_prefix}membergroups
		WHERE id_group = {int:admin_group} OR id_group > {int:old_group}',
		array(
			'admin_group' => 1,
			'old_group' => 7,
			'db_error_skip' => true,
		)
	);
	if ($request === false)
	{
		$request = $smcFunc['db_query']('', '
			SELECT membergroup, id_group
			FROM {db_prefix}membergroups
			WHERE id_group = {int:admin_group} OR id_group > {int:old_group}',
			array(
				'admin_group' => 1,
				'old_group' => 7,
				'db_error_skip' => true,
			)
		);
	}
	while ($row = $smcFunc['db_fetch_row']($request))
		$member_groups[trim($row[0])] = $row[1];
	$smcFunc['db_free_result']($request);

	return $member_groups;
}

function fixRelativePath($path)
{
	global $install_path;

	// Fix the . at the start, clear any duplicate slashes, and fix any trailing slash...
	return addslashes(preg_replace(array('~^\.([/\\\]|$)~', '~[/]+~', '~[\\\]+~', '~[/\\\]$~'), array($install_path . '$1', '/', '\\', ''), $path));
}

function parse_sql($filename)
{
	global $db_prefix, $db_collation, $boarddir, $boardurl, $command_line, $file_steps, $step_progress, $custom_warning;
	global $upcontext, $support_js, $is_debug, $db_connection;

/*
	Failure allowed on:
		- INSERT INTO but not INSERT IGNORE INTO.
		- UPDATE IGNORE but not UPDATE.
		- ALTER TABLE and ALTER IGNORE TABLE.
		- DROP TABLE.
	Yes, I realize that this is a bit confusing... maybe it should be done differently?

	If a comment...
		- begins with --- it is to be output, with a break only in debug mode. (and say successful\n\n if there was one before.)
		- begins with ---# it is a debugging statement, no break - only shown at all in debug.
		- is only ---#, it is "done." and then a break - only shown in debug.
		- begins with ---{ it is a code block terminating at ---}.

	Every block of between "--- ..."s is a step. Every "---#" section represents a substep.

	Replaces the following variables:
		- {$boarddir}
		- {$boardurl}
		- {$db_prefix}
		- {$db_collation}
*/

	// May want to use extended functionality.
	db_extend();
	db_extend('packages');

	// Our custom error handler - does nothing but does stop public errors from XML!
	if (!function_exists('sql_error_handler'))
	{
		function sql_error_handler($errno, $errstr, $errfile, $errline)
		{
			global $support_js;

			if ($support_js)
				return true;
			else
				echo 'Error: ' . $errstr . ' File: ' . $errfile . ' Line: ' . $errline;
		}
	}

	// Make our own error handler.
	set_error_handler('sql_error_handler');

	// Let's find out what the members table uses and put it in a global var - to allow upgrade script to match collations!
	if (version_compare($GLOBALS['required_mysql_version'], mysql_get_server_info()) != 1)
	{
		$request = $smcFunc['db_query']('', '
			SHOW TABLE STATUS
			LIKE {string:table_name}',
			array(
				'table_name' => "{$db_prefix}members",
				'db_error_skip' => true,
			)
		);
		if ($smcFunc['db_num_rows']($request) === 0)
			exit('Unable to find members table!');
		$table_status = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!empty($table_status['Collation']))
		{
			$request = $smcFunc['db_query']('', '
				SHOW COLLATION
				LIKE {string:collation}',
				array(
					'collation' => $table_status['Collation'],
					'db_error_skip' => true,
				)
			);
			// Got something?
			if ($smcFunc['db_num_rows']($request) !== 0)
				$collation_info = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);

			// Excellent!
			if (!empty($collation_info['Collation']) && !empty($collation_info['Charset']))
				$db_collation = ' CHARACTER SET ' . $collation_info['Charset'] . ' COLLATE ' . $collation_info['Collation'];
		}
	}
	if (empty($db_collation))
		$db_collation = '';

	$endl = $command_line ? "\n" : '<br>' . "\n";

	$lines = file($filename);

	$current_type = 'sql';
	$current_data = '';
	$substep = 0;
	$last_step = '';

	// Make sure all newly created tables will have the proper characters set. We do this so that if we need to modify the syntax later, we can do it once instead of per table!
	$lines = str_replace(') ENGINE=MyISAM;', ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;', $lines);

	// Count the total number of steps within this file - for progress.
	$file_steps = substr_count(implode('', $lines), '---#');
	$upcontext['total_items'] = substr_count(implode('', $lines), '--- ');
	$upcontext['debug_items'] = $file_steps;
	$upcontext['current_item_num'] = 0;
	$upcontext['current_item_name'] = '';
	$upcontext['current_debug_item_num'] = 0;
	$upcontext['current_debug_item_name'] = '';
	// This array keeps a record of what we've done in case java is dead...
	$upcontext['actioned_items'] = array();

	$done_something = false;

	foreach ($lines as $line_number => $line)
	{
		$do_current = $substep >= $_GET['substep'];

		// Get rid of any comments in the beginning of the line...
		if (substr(trim($line), 0, 2) === '/*')
			$line = preg_replace('~/\*.+?\*/~', '', $line);

		// Always flush. Flush, flush, flush. Flush, flush, flush, flush! FLUSH!
		if ($is_debug && !$support_js && $command_line)
			flush();

		if (trim($line) === '')
			continue;

		if (trim(substr($line, 0, 3)) === '---')
		{
			$type = substr($line, 3, 1);

			// An error??
			if (trim($current_data) != '' && $type !== '}')
			{
				$upcontext['error_message'] = 'Error in upgrade script - line ' . $line_number . '!' . $endl;
				if ($command_line)
					echo $upcontext['error_message'];
			}

			if ($type == ' ')
			{
				if (!$support_js && $do_current && $_GET['substep'] != 0 && $command_line)
				{
					echo ' Successful.', $endl;
					flush();
				}

				$last_step = htmlspecialchars(rtrim(substr($line, 4)));
				$upcontext['current_item_num']++;
				$upcontext['current_item_name'] = $last_step;

				if ($do_current)
				{
					$upcontext['actioned_items'][] = $last_step;
					if ($command_line)
						echo ' * ';
				}
			}
			elseif ($type == '#')
			{
				$upcontext['step_progress'] += (100 / $upcontext['file_count']) / $file_steps;

				$upcontext['current_debug_item_num']++;
				if (trim($line) != '---#')
					$upcontext['current_debug_item_name'] = htmlspecialchars(rtrim(substr($line, 4)));

				// Have we already done something?
				if (isset($_GET['xml']) && $done_something)
				{
					restore_error_handler();
					return $upcontext['current_debug_item_num'] >= $upcontext['debug_items'] ? true : false;
				}

				if ($do_current)
				{
					if (trim($line) == '---#' && $command_line)
						echo ' done.', $endl;
					elseif ($command_line)
						echo ' +++ ', rtrim(substr($line, 4));
					elseif (trim($line) != '---#')
					{
						if ($is_debug)
							$upcontext['actioned_items'][] = htmlspecialchars(rtrim(substr($line, 4)));
					}
				}

				if ($substep < $_GET['substep'] && $substep + 1 >= $_GET['substep'])
				{
					if ($command_line)
						echo ' * ';
					else
						$upcontext['actioned_items'][] = $last_step;
				}

				// Small step - only if we're actually doing stuff.
				if ($do_current)
					nextSubstep(++$substep);
				else
					$substep++;
			}
			elseif ($type == '{')
				$current_type = 'code';
			elseif ($type == '}')
			{
				$current_type = 'sql';

				if (!$do_current)
				{
					$current_data = '';
					continue;
				}

				if (eval('global $db_prefix, $settings; ' . $current_data) === false)
				{
					$upcontext['error_message'] = 'Error in upgrade script ' . basename($filename) . ' on line ' . $line_number . '!' . $endl;
					if ($command_line)
						echo $upcontext['error_message'];
				}

				// Done with code!
				$current_data = '';
				$done_something = true;
			}

			continue;
		}

		$current_data .= $line;
		if (substr(rtrim($current_data), -1) === ';' && $current_type === 'sql')
		{
			if ((!$support_js || isset($_GET['xml'])))
			{
				if (!$do_current)
				{
					$current_data = '';
					continue;
				}

				$current_data = strtr(substr(rtrim($current_data), 0, -1), array('{$db_prefix}' => $db_prefix, '{$boarddir}' => $boarddir, '{$sboarddir}' => addslashes($boarddir), '{$boardurl}' => $boardurl, '{$db_collation}' => $db_collation));

				upgrade_query($current_data);

				$done_something = true;
			}
			$current_data = '';
		}
		// If this is xml based and we're just getting the item name then that's grand.
		elseif ($support_js && !isset($_GET['xml']) && $upcontext['current_debug_item_name'] != '' && $do_current)
		{
			restore_error_handler();
			return false;
		}

		// Clean up by cleaning any step info.
		$step_progress = array();
		$custom_warning = '';
	}

	// Put back the error handler.
	restore_error_handler();

	if ($command_line)
	{
		echo ' Successful.' . "\n";
		flush();
	}

	$_GET['substep'] = 0;
	return true;
}

function upgrade_query($string, $unbuffered = false)
{
	global $db_connection, $db_server, $db_user, $db_passwd, $command_line, $upcontext, $upgradeurl, $settings;
	global $db_name, $db_unbuffered;

	// Get the query result - working around some Wedge specific security - just this once!
	$settings['disableQueryCheck'] = true;
	$db_unbuffered = $unbuffered;
	$result = $smcFunc['db_query']('', $string, 'security_override');
	$db_unbuffered = false;

	// Failure?!
	if ($result !== false)
		return $result;

	$db_error_message = $smcFunc['db_error']($db_connection);

	// We do something more clever with MySQL.
	$mysql_errno = mysql_errno($db_connection);
	$error_query = in_array(substr(trim($string), 0, 11), array('INSERT INTO', 'UPDATE IGNO', 'ALTER TABLE', 'DROP TABLE ', 'ALTER IGNOR'));

	// Error numbers:
	//		1016: Can't open file '....MYI'
	//		1050: Table already exists.
	//		1054: Unknown column name.
	//		1060: Duplicate column name.
	//		1061: Duplicate key name.
	//		1062: Duplicate entry for unique key.
	//		1068: Multiple primary keys.
	//		1072: Key column '%s' doesn't exist in table.
	//		1091: Can't drop key, doesn't exist.
	//		1146: Table doesn't exist.
	//		2013: Lost connection to server during query.

	if ($mysql_errno == 1016)
	{
		if (preg_match('~\'([^\.\']+)~', $db_error_message, $match) != 0 && !empty($match[1]))
			mysql_query('
				REPAIR TABLE `' . $match[1] . '`');

		$result = mysql_query($string);
		if ($result !== false)
			return $result;
	}
	elseif ($mysql_errno == 2013)
	{
		$db_connection = mysql_connect($db_server, $db_user, $db_passwd);
		mysql_select_db($db_name, $db_connection);

		if ($db_connection)
		{
			$result = mysql_query($string);

			if ($result !== false)
				return $result;
		}
	}
	// Duplicate column name... should be okay. ;)
	elseif (in_array($mysql_errno, array(1060, 1061, 1068, 1091)))
		return false;
	// Duplicate insert... make sure it's the proper type of query. ;)
	elseif (in_array($mysql_errno, array(1054, 1062, 1146)) && $error_query)
		return false;
	// Creating an index on a non-existent column.
	elseif ($mysql_errno == 1072)
		return false;
	elseif ($mysql_errno == 1050 && substr(trim($string), 0, 12) == 'RENAME TABLE')
		return false;

	// If a table already exists don't go potty.
	else
	{
		if (in_array(substr(trim($string), 0, 8), array('CREATE T', 'CREATE S', 'DROP TABL', 'ALTER TA', 'CREATE I')))
		{
			if (strpos($db_error_message, 'exist') !== false)
				return true;
		}
		elseif (strpos(trim($string), 'INSERT ') !== false)
		{
			if (strpos($db_error_message, 'duplicate') !== false)
				return true;
		}
	}

	// Get the query string so we pass everything.
	$query_string = '';
	foreach ($_GET as $k => $v)
		$query_string .= ';' . $k . '=' . $v;
	if (strlen($query_string) != 0)
		$query_string = '?' . substr($query_string, 1);

	if ($command_line)
		exit("Unsuccessful! Database error message:\n" . $db_error_message);

	// Bit of a bodge - do we want the error?
	if (!empty($upcontext['return_error']))
	{
		$upcontext['error_message'] = $db_error_message;
		return false;
	}

	// Otherwise we have to display this somewhere appropriate if possible.
	$upcontext['forced_error_message'] = '
			<strong>Unsuccessful!</strong><br>

			<div style="margin: 2ex">
				This query:
				<blockquote><tt>' . nl2br(htmlspecialchars(trim($string)), false) . ';</tt></blockquote>

				Caused the error:
				<blockquote>' . nl2br(htmlspecialchars($db_error_message), false) . '</blockquote>
			</div>

			<form action="' . $upgradeurl . $query_string . '" method="post">
				<input type="submit" value="Try again" class="submit">
			</form>
		</div>';

	upgradeExit();
}

// This performs a table alter, but does it unbuffered so the script can time out professionally.
function protected_alter($change, $substep, $is_test = false)
{
	global $db_prefix;

	db_extend('packages');

	// Firstly, check whether the current index/column exists.
	$found = false;
	if ($change['type'] === 'column')
	{
		$columns = $smcFunc['db_list_columns']('{db_prefix}' . $change['table'], true);
		foreach ($columns as $column)
		{
			// Found it?
			if ($column['name'] === $change['name'])
			{
				$found |= 1;
				// Do some checks on the data if we have it set.
				if (isset($change['col_type']))
					$found &= $change['col_type'] === $column['type'];
				if (isset($change['null_allowed']))
					$found &= $column['null'] == $change['null_allowed'];
				if (isset($change['default']))
					$found &= $change['default'] === $column['default'];
			}
		}
	}
	elseif ($change['type'] === 'index')
	{
		$request = upgrade_query('
			SHOW INDEX
			FROM ' . $db_prefix . $change['table']);
		if ($request !== false)
		{
			$cur_index = array();

			while ($row = $smcFunc['db_fetch_assoc']($request))
				if ($row['Key_name'] === $change['name'])
					$cur_index[(int) $row['Seq_in_index']] = $row['Column_name'];

			ksort($cur_index, SORT_NUMERIC);
			$found = array_values($cur_index) === $change['target_columns'];

			$smcFunc['db_free_result']($request);
		}
	}

	// If we're trying to add and it's added, we're done.
	if ($found && in_array($change['method'], array('add', 'change')))
		return true;
	// Otherwise if we're removing and it wasn't found we're also done.
	elseif (!$found && in_array($change['method'], array('remove', 'change_remove')))
		return true;
	// Otherwise is it just a test?
	elseif ($is_test)
		return false;

	// Not found it yet? Bummer! How about we see if we're currently doing it?
	$running = false;
	$found = false;
	while (1 == 1)
	{
		$request = upgrade_query('
			SHOW FULL PROCESSLIST');
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (strpos($row['Info'], 'ALTER TABLE ' . $db_prefix . $change['table']) !== false && strpos($row['Info'], $change['text']) !== false)
				$found = true;
		}

		// Can't find it? Then we need to run it fools!
		if (!$found && !$running)
		{
			$smcFunc['db_free_result']($request);

			$success = upgrade_query('
				ALTER TABLE ' . $db_prefix . $change['table'] . '
				' . $change['text'], true) !== false;

			if (!$success)
				return false;

			// Return
			$running = true;
		}
		// What if we've not found it, but we'd ran it already? Must of completed.
		elseif (!$found)
		{
			$smcFunc['db_free_result']($request);
			return true;
		}

		// Pause execution for a sec or three.
		sleep(3);

		// Can never be too well protected.
		nextSubstep($substep);
	}

	// Protect it.
	nextSubstep($substep);
}

// Alter a text column definition preserving its character set.
function textfield_alter($change, $substep)
{
	global $db_prefix;

	// If we're here, we only need to concern ourselves with updating the column type; we don't need to worry about collation since everything's UTF-8!

	// Make sure there are no NULL's left.
	if (!$change['null_allowed'])
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}' . $change['table'] . '
			SET ' . $change['column'] . ' = {string:default}
			WHERE ' . $change['column'] . ' IS NULL',
			array(
				'default' => isset($change['default']) ? $change['default'] : '',
				'db_error_skip' => true,
			)
		);

	// Do the actual alteration.
	$smcFunc['db_query']('', '
		ALTER TABLE {db_prefix}' . $change['table'] . '
		CHANGE COLUMN ' . $change['column'] . ' ' . $change['column'] . ' ' . $change['type'] . ($change['null_allowed'] ? '' : ' NOT NULL') . (isset($change['default']) ? ' default {string:default}' : ''),
		array(
			'default' => isset($change['default']) ? $change['default'] : '',
			'db_error_skip' => true,
		)
	);

	nextSubstep($substep);
}

// Check if we need to alter this query.
function checkChange(&$change)
{
	// Not a column we need to check on?
	if (!in_array($change['name'], array('memberGroups', 'passwordSalt')))
		return;

	// Break it up you (six|seven).
	$temp = explode(' ', str_replace('NOT NULL', 'NOT_NULL', $change['text']));

	// Get the details about this change.
	$request = $smcFunc['db_query']('', '
		SHOW FIELDS
		FROM {db_prefix}{raw:table}
		WHERE Field = {string:old_name} OR Field = {string:new_name}',
		array(
			'table' => $change['table'],
			'old_name' => $temp[1],
			'new_name' => $temp[2],
	));
	if ($smcFunc['db_num_rows'] != 1)
		return;

	list (, $current_type) = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	// If this doesn't match, the column may of been altered for a reason.
	if (trim($current_type) != trim($temp[3]))
		$temp[3] = $current_type;

	// Piece this back together.
	$change['text'] = str_replace('NOT_NULL', 'NOT NULL', implode(' ', $temp));
}

// The next substep.
function nextSubstep($substep)
{
	global $start_time, $timeLimitThreshold, $command_line, $file_steps, $settings, $custom_warning;
	global $step_progress, $is_debug, $upcontext;

	if ($_GET['substep'] < $substep)
		$_GET['substep'] = $substep;

	if ($command_line)
	{
		if (time() - $start_time > 1 && empty($is_debug))
		{
			echo '.';
			$start_time = time();
		}
		return;
	}

	@set_time_limit(300);
	if (function_exists('apache_reset_timeout'))
		@apache_reset_timeout();

	if (time() - $start_time <= $timeLimitThreshold)
		return;

	// Do we have some custom step progress stuff?
	if (!empty($step_progress))
	{
		$upcontext['substep_progress'] = 0;
		$upcontext['substep_progress_name'] = $step_progress['name'];
		if ($step_progress['current'] > $step_progress['total'])
			$upcontext['substep_progress'] = 99.9;
		else
			$upcontext['substep_progress'] = ($step_progress['current'] / $step_progress['total']) * 100;

		// Make it nicely rounded.
		$upcontext['substep_progress'] = round($upcontext['substep_progress'], 1);
	}

	// If this is XML we just exit right away!
	if (isset($_GET['xml']))
		return upgradeExit();

	// We're going to pause after this!
	$upcontext['pause'] = true;

	$upcontext['query_string'] = '';
	foreach ($_GET as $k => $v)
	{
		if ($k != 'data' && $k != 'substep' && $k != 'step')
			$upcontext['query_string'] .= ';' . $k . '=' . $v;
	}

	// Custom warning?
	if (!empty($custom_warning))
		$upcontext['custom_warning'] = $custom_warning;

	upgradeExit();
}

function cmdStep0()
{
	global $boarddir, $sourcedir, $db_prefix, $language, $settings, $start_time, $cachedir, $upcontext;
	global $language, $is_debug, $txt;
	$start_time = time();

	ob_end_clean();
	ob_implicit_flush(true);
	@set_time_limit(600);

	if (!isset($_SERVER['argv']))
		$_SERVER['argv'] = array();
	$_GET['maint'] = 1;

	foreach ($_SERVER['argv'] as $i => $arg)
	{
		if (preg_match('~^--language=(.+)$~', $arg, $match) != 0)
			$_GET['lang'] = $match[1];
		elseif (preg_match('~^--path=(.+)$~', $arg) != 0)
			continue;
		elseif ($arg == '--no-maintenance')
			$_GET['maint'] = 0;
		elseif ($arg == '--debug')
			$is_debug = true;
		elseif ($arg == '--backup')
			$_POST['backup'] = 1;
		elseif ($arg == '--template' && (file_exists($boarddir . '/template.php') || file_exists($boarddir . '/template.html') && !file_exists($boarddir . '/Themes/converted')))
			$_GET['conv'] = 1;
		elseif ($i != 0)
		{
			echo 'Wedge Command-line Upgrader
Usage: /path/to/php -f ' . basename(__FILE__) . ' -- [OPTION]...

    --language=LANG         Reset the forum\'s language to LANG.
    --no-maintenance        Don\'t put the forum into maintenance mode.
    --debug                 Output debugging information.
    --backup                Create backups of tables with "backup_" prefix.';
			echo "\n";
			exit;
		}
	}

	if (!php_version_check())
		print_error('Error: PHP ' . PHP_VERSION . ' does not match the minimum requirements of Wedge.', true);
	if (!db_version_check())
		print_error('Error: Your MySQL version does not meet the minimum requirements (' . $GLOBALS['required_mysql_version'] . ') of Wedge. Please ask your host to upgrade.', true);

	if ($smcFunc['db_query']('', 'ALTER TABLE {db_prefix}boards ORDER BY id_board', array()) === false)
		print_error('Error: the MySQL account in Settings.php does not have sufficient privileges.', true);

	$check = @file_exists($boarddir . '/Themes/default/index.template.php')
		&& @file_exists($sourcedir . '/QueryString.php')
		&& @file_exists($sourcedir . '/ManageBoards.php');

	// Do a quick version spot check.
	$temp = substr(@implode('', @file($boarddir . '/index.php')), 0, 4096);
	preg_match('~\*\s*Software\s+Version:\s+Wedge\s+(.+?)[\s]{2}~i', $temp, $match);
	if (empty($match[1]) || $match[1] != WEDGE_VERSION)
		print_error('Error: Some files have not yet been updated properly.');

	// Make sure Settings.php is writable.
	if (!is_writable($boarddir . '/Settings.php'))
		@chmod($boarddir . '/Settings.php', 0777);
	if (!is_writable($boarddir . '/Settings.php'))
		print_error('Error: Unable to obtain write access to "Settings.php".', true);

	// Make sure Settings.php is writable.
	if (!is_writable($boarddir . '/Settings_bak.php'))
		@chmod($boarddir . '/Settings_bak.php', 0777);
	if (!is_writable($boarddir . '/Settings_bak.php'))
		print_error('Error: Unable to obtain write access to "Settings_bak.php".');

	// Make sure Themes is writable.
	if (!is_writable($boarddir . '/Themes'))
		@chmod($boarddir . '/Themes', 0777);

	// Make sure cache directory exists and is writable!
	$cachedir_temp = empty($cachedir) ? $boarddir . '/cache' : $cachedir;
	if (!file_exists($cachedir_temp))
		@mkdir($cachedir_temp);

	if (!is_writable($cachedir_temp))
		@chmod($cachedir_temp, 0777);

	if (!is_writable($cachedir_temp))
		print_error('Error: Unable to obtain write access to "cache".', true);

	$temp = substr(@implode('', @file($boarddir . '/Themes/default/languages/index.' . $upcontext['language'] . '.php')), 0, 4096);
	preg_match('~(?://|/\*)\s*Version:\s+(.+?);\s*index(?:[\s]{2}|\*/)~i', $temp, $match);

	if (empty($match[1]) || $match[1] != WEDGE_LANG_VERSION)
		print_error('Error: Language files out of date.', true);
	if (!file_exists($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php'))
		print_error('Error: Install language is missing for selected language.', true);

	// Otherwise include it!
	require_once($boarddir . '/Themes/default/languages/Install.' . $upcontext['language'] . '.php');

	// Make sure we skip the HTML for login.
	$_POST['upcont'] = true;
	$upcontext['current_step'] = 1;
}

function print_error($message, $fatal = false)
{
	static $fp = null;

	if ($fp === null)
		$fp = fopen('php://stderr', 'wb');

	fwrite($fp, $message . "\n");

	if ($fatal)
		exit;
}

function throw_error($message)
{
	global $upcontext;

	$upcontext['error_msg'] = $message;
	$upcontext['block'] = 'error_message';

	return false;
}

// Check files are writable - make them writable if necessary...
function makeFilesWritable(&$files)
{
	global $upcontext, $boarddir;

	if (empty($files))
		return true;

	$failure = false;
	// On linux, it's easy - just use is_writable!
	if (substr(__FILE__, 1, 2) != ':\\')
	{
		foreach ($files as $k => $file)
		{
			if (!is_writable($file))
			{
				@chmod($file, 0755);

				// Well, 755 hopefully worked... if not, try 777.
				if (!is_writable($file) && !@chmod($file, 0777))
					$failure = true;
				// Otherwise remove it as it's good!
				else
					unset($files[$k]);
			}
			else
				unset($files[$k]);
		}
	}
	// Windows is trickier. Let's try opening for r+...
	else
	{
		foreach ($files as $k => $file)
		{
			// Folders can't be opened for write... but the index.php in them can ;).
			if (is_dir($file))
				$file .= '/index.php';

			// Funny enough, chmod actually does do something on windows - it removes the read only attribute.
			@chmod($file, 0777);
			$fp = @fopen($file, 'r+');

			// Hmm, okay, try just for write in that case...
			if (!$fp)
				$fp = @fopen($file, 'w');

			if (!$fp)
				$failure = true;
			else
				unset($files[$k]);
			@fclose($fp);
		}
	}

	if (empty($files))
		return true;

	if (!isset($_SERVER))
		return !$failure;

	// What still needs to be done?
	$upcontext['chmod']['files'] = $files;

	// If it's windows it's a mess...
	if ($failure && substr(__FILE__, 1, 2) == ':\\')
	{
		$upcontext['chmod']['ftp_error'] = 'total_mess';

		return false;
	}
	// We're going to have to use... FTP!
	elseif ($failure)
	{
		// Load any session data we might have...
		if (!isset($_POST['ftp_username']) && isset($_SESSION['installer_temp_ftp']))
		{
			$upcontext['chmod']['server'] = $_SESSION['installer_temp_ftp']['server'];
			$upcontext['chmod']['port'] = $_SESSION['installer_temp_ftp']['port'];
			$upcontext['chmod']['username'] = $_SESSION['installer_temp_ftp']['username'];
			$upcontext['chmod']['password'] = $_SESSION['installer_temp_ftp']['password'];
			$upcontext['chmod']['path'] = $_SESSION['installer_temp_ftp']['path'];
		}
		// Or have we submitted?
		elseif (isset($_POST['ftp_username']))
		{
			$upcontext['chmod']['server'] = $_POST['ftp_server'];
			$upcontext['chmod']['port'] = $_POST['ftp_port'];
			$upcontext['chmod']['username'] = $_POST['ftp_username'];
			$upcontext['chmod']['password'] = $_POST['ftp_password'];
			$upcontext['chmod']['path'] = $_POST['ftp_path'];
		}

		if (isset($upcontext['chmod']['username']))
		{
			$ftp = new ftp_connection($upcontext['chmod']['server'], $upcontext['chmod']['port'], $upcontext['chmod']['username'], $upcontext['chmod']['password']);

			if ($ftp->error === false)
			{
				// Try it without /home/abc just in case they messed up.
				if (!$ftp->chdir($upcontext['chmod']['path']))
				{
					$upcontext['chmod']['ftp_error'] = $ftp->last_message;
					$ftp->chdir(preg_replace('~^/home[2]?/[^/]+?~', '', $upcontext['chmod']['path']));
				}
			}
		}

		if (!isset($ftp) || $ftp->error !== false)
		{
			if (!isset($ftp))
				$ftp = new ftp_connection(null);
			// Save the error so we can mess with listing...
			elseif ($ftp->error !== false && !isset($upcontext['chmod']['ftp_error']))
				$upcontext['chmod']['ftp_error'] = $ftp->last_message === null ? '' : $ftp->last_message;

			list ($username, $detect_path, $found_path) = $ftp->detect_path(dirname(__FILE__));

			if ($found_path || !isset($upcontext['chmod']['path']))
				$upcontext['chmod']['path'] = $detect_path;

			if (!isset($upcontext['chmod']['username']))
				$upcontext['chmod']['username'] = $username;

			return false;
		}
		else
		{
			// We want to do a relative path for FTP.
			if (!in_array($upcontext['chmod']['path'], array('', '/')))
			{
				$ftp_root = strtr($boarddir, array($upcontext['chmod']['path'] => ''));
				if (substr($ftp_root, -1) == '/' && ($upcontext['chmod']['path'] == '' || substr($upcontext['chmod']['path'], 0, 1) == '/'))
				$ftp_root = substr($ftp_root, 0, -1);
			}
			else
				$ftp_root = $boarddir;

			// Save the info for next time!
			$_SESSION['installer_temp_ftp'] = array(
				'server' => $upcontext['chmod']['server'],
				'port' => $upcontext['chmod']['port'],
				'username' => $upcontext['chmod']['username'],
				'password' => $upcontext['chmod']['password'],
				'path' => $upcontext['chmod']['path'],
				'root' => $ftp_root,
			);

			foreach ($files as $k => $file)
			{
				if (!is_writable($file))
					$ftp->chmod($file, 0755);
				if (!is_writable($file))
					$ftp->chmod($file, 0777);

				// Assuming that didn't work calculate the path without the boarddir.
				if (!is_writable($file))
				{
					if (strpos($file, $boarddir) === 0)
					{
						$ftp_file = strtr($file, array($_SESSION['installer_temp_ftp']['root'] => ''));
						$ftp->chmod($ftp_file, 0755);
						if (!is_writable($file))
							$ftp->chmod($ftp_file, 0777);
						// Sometimes an extra slash can help...
						$ftp_file = '/' . $ftp_file;
						if (!is_writable($file))
							$ftp->chmod($ftp_file, 0755);
						if (!is_writable($file))
							$ftp->chmod($ftp_file, 0777);
					}
				}

				if (is_writable($file))
					unset($files[$k]);
			}

			$ftp->close();
		}
	}

	// What remains?
	$upcontext['chmod']['files'] = $files;

	if (empty($files))
		return true;

	return false;
}

/******************************************************************************
******************* Templates are below this point ****************************
******************************************************************************/

// This is what is displayed if there's any chmod to be done. If not it returns nothing...
function template_chmod()
{
	global $upcontext, $upgradeurl, $theme;

	// Don't call me twice!
	if (!empty($upcontext['chmod_called']))
		return;

	$upcontext['chmod_called'] = true;

	// Nothing?
	if (empty($upcontext['chmod']['files']) && empty($upcontext['chmod']['ftp_error']))
		return;

	//!!! Temporary!
	$txt['error_ftp_no_connect'] = 'Unable to connect to FTP server with this combination of details.';
	$txt['ftp_login'] = 'Your FTP connection information';
	$txt['ftp_login_info'] = 'This web installer needs your FTP information in order to automate the installation for you. Please note that none of this information is saved in your installation, it is just used to setup Wedge.';
	$txt['ftp_server'] = 'Server';
	$txt['ftp_server_info'] = 'The address (often localhost) and port for your FTP server.';
	$txt['ftp_port'] = 'Port';
	$txt['ftp_username'] = 'Username';
	$txt['ftp_username_info'] = 'The username to login with. <em>This will not be saved anywhere.</em>';
	$txt['ftp_password'] = 'Password';
	$txt['ftp_password_info'] = 'The password to login with. <em>This will not be saved anywhere.</em>';
	$txt['ftp_path'] = 'Install Path';
	$txt['ftp_path_info'] = 'This is the <em>relative</em> path you use in your FTP client <a href="' . $_SERVER['PHP_SELF'] . '?ftphelp" onclick="window.open(this.href, \'\', \'width=450,height=250\');return false;" target="_blank">(more help)</a>.';
	$txt['ftp_path_found_info'] = 'The path in the box above was automatically detected.';
	$txt['ftp_path_help'] = 'Your FTP path is the path you see when you log in to your FTP client. It commonly starts with &quot;<tt>www</tt>&quot;, &quot;<tt>public_html</tt>&quot;, or &quot;<tt>httpdocs</tt>&quot; - but it should include the directory Wedge is in too, such as &quot;/public_html/forum&quot;. It is different from your URL and full path.<br><br>Files in this path may be overwritten, so make sure it\'s correct.';
	$txt['ftp_path_help_close'] = 'Close';
	$txt['ftp_connect'] = 'Connect';

	// Was it a problem with Windows?
	if (!empty($upcontext['chmod']['ftp_error']) && $upcontext['chmod']['ftp_error'] == 'total_mess')
	{
		echo '
		<div class="error_message">
			<div style="color: red">The following files need to be writable to continue the upgrade. Please ensure the Windows permissions are correctly set to allow this:</div>
			<ul style="margin: 2.5ex; font-family: monospace">
			<li>' . implode('</li>
			<li>', $upcontext['chmod']['files']). '</li>
		</ul>
		</div>';

		return false;
	}

	echo '
		<div class="panel">
			<h2>Your FTP connection information</h2>
			<h3>The upgrader can fix any issues with file permissions to make upgrading as simple as possible. Simply enter your connection information below or alternatively click <a href="#" onclick="warning_popup();">here</a> for a list of files which need to be changed.</h3>
			<script><!-- // --><![CDATA[
				function warning_popup()
				{
					popup = window.open(\'\',\'popup\',\'height=150,width=400,scrollbars=yes\');
					var content = popup.document;
					content.write(\'<!DOCTYPE html>\n\');
					content.write(\'<html', $upcontext['right_to_left'] ? ' dir="rtl"' : '', '>\n\t<head>\n\t\t<meta name="robots" content="noindex">\n\t\t\');
					content.write(\'<title>Warning</title>\n\t\t<link rel="stylesheet" href="', $theme['default_theme_url'], '/css/index.css">\n\t</head>\n\t<body id="popup">\n\t\t\');
					content.write(\'<div class="description wrc">\n\t\t\t<h4>The following files needs to be made writable to continue:</h4>\n\t\t\t\');
					content.write(\'<p>', implode('<br>\n\t\t\t', $upcontext['chmod']['files']), '</p>\n\t\t\t\');
					content.write(\'<a href="javascript:self.close();">close</a>\n\t\t</div>\n\t</body>\n</html>\');
					content.close();
				}
		// ]]></script>';

	if (!empty($upcontext['chmod']['ftp_error']))
		echo '
		<div class="error_message">
			<div style="color: red">
				The following error was encountered when trying to connect:<br>
				<br>
				<code>', $upcontext['chmod']['ftp_error'], '</code>
			</div>
		</div>
		<br>';

	if (empty($upcontext['chmod_in_form']))
		echo '
	<form action="', $upcontext['form_url'], '" method="post">';

	echo '
		<table width="520" cellspacing="0" cellpadding="0" border="0" align="center" style="margin-bottom: 1ex">
			<tr>
				<td width="26%" valign="top" class="textbox"><label for="ftp_server">', $txt['ftp_server'], ':</label></td>
				<td>
					<div style="float: right; margin-right: 1px"><label for="ftp_port" class="textbox"><strong>', $txt['ftp_port'], ':&nbsp;</strong></label> <input size="3" name="ftp_port" id="ftp_port" value="', isset($upcontext['chmod']['port']) ? $upcontext['chmod']['port'] : '21', '"></div>
					<input size="30" name="ftp_server" id="ftp_server" value="', isset($upcontext['chmod']['server']) ? $upcontext['chmod']['server'] : 'localhost', '" style="width: 70%">
					<div style="font-size: smaller; margin-bottom: 2ex">', $txt['ftp_server_info'], '</div>
				</td>
			</tr><tr>
				<td width="26%" valign="top" class="textbox"><label for="ftp_username">', $txt['ftp_username'], ':</label></td>
				<td>
					<input size="50" name="ftp_username" id="ftp_username" value="', isset($upcontext['chmod']['username']) ? $upcontext['chmod']['username'] : '', '" style="width: 99%">
					<div style="font-size: smaller; margin-bottom: 2ex">', $txt['ftp_username_info'], '</div>
				</td>
			</tr><tr>
				<td width="26%" valign="top" class="textbox"><label for="ftp_password">', $txt['ftp_password'], ':</label></td>
				<td>
					<input type="password" size="50" name="ftp_password" id="ftp_password" style="width: 99%">
					<div style="font-size: smaller; margin-bottom: 3ex">', $txt['ftp_password_info'], '</div>
				</td>
			</tr><tr>
				<td width="26%" valign="top" class="textbox"><label for="ftp_path">', $txt['ftp_path'], ':</label></td>
				<td style="padding-bottom: 1ex">
					<input size="50" name="ftp_path" id="ftp_path" value="', isset($upcontext['chmod']['path']) ? $upcontext['chmod']['path'] : '', '" style="width: 99%">
					<div style="font-size: smaller; margin-bottom: 2ex">', !empty($upcontext['chmod']['path']) ? $txt['ftp_path_found_info'] : $txt['ftp_path_info'], '</div>
				</td>
			</tr>
		</table>

		<div class="right" style="margin: 1ex"><input type="submit" value="', $txt['ftp_connect'], '" class="submit"></div>
	</div>';

	if (empty($upcontext['chmod_in_form']))
		echo '
	</form>';
}

function template_upgrade_above()
{
	global $settings, $txt, $wedgesite, $theme, $upcontext, $upgradeurl;

	echo '<!DOCTYPE html>
<html', $upcontext['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex">
		<title>', $txt['upgrade_upgrade_utility'], '</title>
		<link rel="stylesheet" href="', $theme['default_theme_url'], '/css/index.css">
		<link rel="stylesheet" href="', $theme['default_theme_url'], '/css/install.css">
		<script src="http://code.jquery.com/jquery-1.5.2.min.js"></script>
		<script src="Themes/default/scripts/script.js"></script>
		<script><!-- // --><![CDATA[
			var we_script = \'', $upgradeurl, '\';
			var startPercent = ', $upcontext['overall_percent'], ';

			// This function dynamically updates the step progress bar - and overall one as required.
			function updateStepProgress(current, max, overall_weight)
			{
				// What out the actual percent.
				var width = parseInt((current / max) * 100);
				if (document.getElementById(\'step_progress\'))
				{
					document.getElementById(\'step_progress\').style.width = width + "%";
					document.getElementById(\'step_text\').innerHTML = width + "%";
				}
				if (overall_weight && document.getElementById(\'overall_progress\'))
				{
					overall_width = parseInt(startPercent + width * (overall_weight / 100));
					document.getElementById(\'overall_progress\').style.width = overall_width + "%";
					document.getElementById(\'overall_text\').innerHTML = overall_width + "%";
				}
			}
		// ]]></script>
	</head>
	<body>
	<div id="header"><div class="frame">
		<div id="top_section">
			<h1 class="forumtitle">', $txt['upgrade_upgrade_utility'], '</h1>
			<img id="wedgelogo" src="Themes/default/images/wedgelogo.png" alt="Wedge" title="Wedge">
		</div>
		<div id="upper_section" class="flow_hidden">
			<div class="user"></div>
			<div class="news normaltext">
			</div>
		</div>
	</div></div>
	<div id="content"><div class="frame">
		<div id="main">
			<div id="main-steps">
				<h2>', $txt['upgrade_progress'], '</h2>
				<ul>';

	foreach ($upcontext['steps'] as $num => $step)
		echo '
						<li class="', $num < $upcontext['current_step'] ? 'stepdone' : ($num == $upcontext['current_step'] ? 'stepcurrent' : 'stepwaiting'), '">', $txt['upgrade_step'], ' ', $step[0], ': ', $step[1], '</li>';

	echo '
					</ul>
			</div>
			<div style="float: left; width: 40%">
				<div style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; width: 50%; margin: auto">
					<div id="overall_text" style="color: #000; position: absolute; margin-left: -5em">', $upcontext['overall_percent'], '%</div>
					<div id="overall_progress" style="width: ', $upcontext['overall_percent'], '%; height: 12pt; z-index: 1; background-color: lime">&nbsp;</div>
					<div class="progress">', $txt['upgrade_overall_progress'], '</div>
				</div>
				';

	if (isset($upcontext['step_progress']))
		echo '
				<div style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; width: 50%; margin: 5px auto">
					<div id="step_text" style="color: #000; position: absolute; margin-left: -5em">', $upcontext['step_progress'], '%</div>
					<div id="step_progress" style="width: ', $upcontext['step_progress'], '%; height: 12pt; z-index: 1; background-color: #ffd000">&nbsp;</div>
					<div class="progress">', $txt['upgrade_step_progress'], '</div>
				</div>
				';

	echo '
				<div id="substep_bar_div" class="smalltext" style="display: ', isset($upcontext['substep_progress']) ? '' : 'none', '">', isset($upcontext['substep_progress_name']) ? trim(strtr($upcontext['substep_progress_name'], array('.' => ''))) : '', ':</div>
				<div id="substep_bar_div2" style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; width: 50%; margin: 5px auto; display: ', isset($upcontext['substep_progress']) ? '' : 'none', '">
					<div id="substep_text" style="color: #000; position: absolute; margin-left: -5em">', isset($upcontext['substep_progress']) ? $upcontext['substep_progress'] : '', '%</div>
				<div id="substep_progress" style="width: ', isset($upcontext['substep_progress']) ? $upcontext['substep_progress'] : 0, '%; height: 12pt; z-index: 1; background-color: #eebaf4">&nbsp;</div>
								</div>';

	// How long have we been running this?
	$elapsed = time() - $upcontext['started'];
	$mins = (int) ($elapsed / 60);
	$seconds = $elapsed - $mins * 60;
	echo '
								<div class="smalltext" style="padding: 5px; text-align: center">', $txt['upgrade_time_elapsed'], ':
									<span id="mins_elapsed">', $mins, '</span> ', $txt['upgrade_time_mins'], ', <span id="secs_elapsed">', $seconds, '</span> ', $txt['upgrade_time_secs'], '.
								</div>';
	echo '
			</div>
			<div id="main_screen" class="clear">
				<h2>', $upcontext['page_title'], '</h2>
				<div class="panel">
					<div style="max-height: 360px; overflow: auto">';
}

function template_upgrade_below()
{
	global $upcontext, $txt;

	if (!empty($upcontext['pause']))
		echo '
								<em>', $txt['upgrade_incomplete'], '.</em><br>

								<h2 style="margin-top: 2ex">', $txt['upgrade_not_quite_done'], '</h2>
								<h3>
									', $txt['upgrade_paused_overload'], '
								</h3>';

	if (!empty($upcontext['custom_warning']))
		echo '
								<div style="margin: 2ex; padding: 2ex; border: 2px dashed #cc3344; color: black; background-color: #ffe4e9">
									<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
									<strong style="text-decoration: underline">', $txt['upgrade_note'], '</strong><br>
									<div style="padding-left: 6ex">', $upcontext['custom_warning'], '</div>
								</div>';

	echo '
								<div class="right" style="margin: 1ex">';

	if (!empty($upcontext['continue']))
		echo '
									<input type="submit" id="contbutt" name="contbutt" value="', $txt['upgrade_continue'], '"', $upcontext['continue'] == 2 ? ' disabled' : '', ' class="submit">';
	if (!empty($upcontext['skip']))
		echo '
									<input type="submit" id="skip" name="skip" value="', $txt['upgrade_skip'], '" onclick="dontSubmit = true; document.getElementById(\'contbutt\').disabled = \'disabled\'; return true;" class="cancel">';

	echo '
								</div>
							</form>
						</div>
				</div>
			</div>
		</div>
	</div></div>
	<div id="footer"><div class="frame" style="height: 40px">
		<div class="smalltext"><a href="http://wedge.org/" title="Free Forum Software" target="_blank" class="new_win">Wedge &copy; 2010, René-Gilles Deberdt</a></div>
	</div></div>
	</body>
</html>';

	// Are we on a pause?
	if (!empty($upcontext['pause']))
	{
		echo '
		<script><!-- // --><![CDATA[
			window.onload = doAutoSubmit;
			var countdown = 3;
			var dontSubmit = false;

			function doAutoSubmit()
			{
				if (countdown == 0 && !dontSubmit)
					document.upform.submit();
				else if (countdown == -1)
					return;

				document.getElementById(\'contbutt\').value = "', $txt['upgrade_continue'], ' (" + countdown + ")";
				countdown--;

				setTimeout(doAutoSubmit, 1000);
			}
		// ]]></script>';
	}
}

function template_xml_above()
{
	global $upcontext;

	echo '<', '?xml version="1.0" encoding="UTF-8"?', '>
	<we>';

	if (!empty($upcontext['get_data']))
		foreach ($upcontext['get_data'] as $k => $v)
			echo '
		<get key="', $k, '">', $v, '</get>';
}

function template_xml_below()
{
	global $upcontext;

	echo '
	</we>';
}

function template_error_message()
{
	global $upcontext;

	echo '
	<div class="error_message">
		<div style="color: red">
			', $upcontext['error_msg'], '
		</div>
		<br>
		<a href="', $_SERVER['PHP_SELF'], '">Click here to try again.</a>
	</div>';
}

function template_welcome_message()
{
	global $upcontext, $settings, $upgradeurl, $disable_security, $theme, $txt;

	echo '
		<script src="http://wedge.org/files/current-version.js?version=' . WEDGE_VERSION . '"></script>', empty($context['disable_login_hashing']) ? '
		<script src="' . $theme['default_theme_url'] . '/scripts/sha1.js"></script>' : '', '
			<h3>', sprintf($txt['upgrade_ready_proceed'], WEDGE_VERSION), '</h3>
	<form action="', $upcontext['form_url'], '" method="post" name="upform" id="upform" ', empty($upcontext['disable_login_hashing']) ? ' onsubmit="hashLoginPassword(this, \'' . $upcontext['rid'] . '\');"' : '', '>
		<div id="version_warning">
			<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
			<strong style="text-decoration: underline">', $txt['upgrade_warning'], '</strong><br>
			<div style="padding-left: 6ex">
				', sprintf($txt['upgrade_warning_out_of_date'], WEDGE_VERSION), '
			</div>
		</div>';

	$upcontext['chmod_in_form'] = true;
	template_chmod();

	// A warning message?
	if (!empty($upcontext['warning']))
		echo '
		<div style="margin: 2ex; padding: 2ex; border: 2px dashed #cc3344; color: black; background-color: #ffe4e9">
			<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
			<strong style="text-decoration: underline">', $txt['upgrade_warning'], '</strong><br>
			<div style="padding-left: 6ex">
				', $upcontext['warning'], '
			</div>
		</div>';

	// Paths are incorrect?
	echo '
		<div style="margin: 2ex; padding: 2ex; border: 2px dashed #804840; color: black; background-color: #fe5a44; ', (file_exists($theme['default_theme_dir'] . '/scripts/script.js') ? 'display: none;' : ''), '" id="js_script_missing_error">
			<div style="float: left; width: 2ex; font-size: 2em; color: black">!!</div>
			<strong style="text-decoration: underline">', $txt['upgrade_critical_error'], '</strong><br>
			<div style="padding-left: 6ex">
				', $txt['upgrade_error_script_js'], '
			</div>
		</div>';

	// Is there someone already doing this?
	if (!empty($upcontext['user']['id']) && (time() - $upcontext['started'] < 72600 || time() - $upcontext['updated'] < 3600))
	{
		$ago = time() - $upcontext['started'];
		if ($ago < 60)
			$ago = $ago . ' seconds';
		elseif ($ago < 3600)
			$ago = (int) ($ago / 60) . ' minutes';
		else
			$ago = (int) ($ago / 3600) . ' hours';

		$active = time() - $upcontext['updated'];
		if ($active < 60)
			$updated = $active . ' seconds';
		elseif ($active < 3600)
			$updated = (int) ($active / 60) . ' minutes';
		else
			$updated = (int) ($active / 3600) . ' hours';

		echo '
		<div style="margin: 2ex; padding: 2ex; border: 2px dashed #cc3344; color: black; background-color: #ffe4e9">
			<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
			<strong style="text-decoration: underline">', $txt['upgrade_warning'], '</strong><br>
			<div style="padding-left: 6ex">
				&quot;', $upcontext['user']['name'], '&quot; has been running the upgrade script for the last ', $ago, ' - and was last active ', $updated, ' ago.';

		if ($active < 600)
			echo '
				We recommend that you do not run this script unless you are sure that ', $upcontext['user']['name'], ' has completed their upgrade.';

		if ($active > $upcontext['inactive_timeout'])
			echo '
				<br><br>You can choose to either run the upgrade again from the beginning - or alternatively continue from the last step reached during the last upgrade.';
		else
			echo '
				<br><br>This upgrade script cannot be run until ', $upcontext['user']['name'], ' has been inactive for at least ', ($upcontext['inactive_timeout'] > 120 ? round($upcontext['inactive_timeout'] / 60, 1) . ' minutes!' : $upcontext['inactive_timeout'] . ' seconds!');

		echo '
			</div>
		</div>';
	}

	echo '
			<strong>Admin Login: ', $disable_security ? '(DISABLED)' : '', '</strong>
			<h3>For security purposes please login with your admin account to proceed with the upgrade.</h3>
			<table>
				<tr valign="top">
					<td><strong ', $disable_security ? 'style="color: gray"' : '', '>Username:</strong></td>
					<td>
						<input name="user" value="', !empty($upcontext['username']) ? $upcontext['username'] : '', '"', $disable_security ? ' disabled' : '', '>';

	if (!empty($upcontext['username_incorrect']))
		echo '
						<div class="smalltext" style="color: red">Username Incorrect</div>';

	echo '
					</td>
				</tr>
				<tr valign="top">
					<td><strong ', $disable_security ? 'style="color: gray"' : '', '>Password:</strong></td>
					<td>
						<input type="password" name="passwrd" value=""', $disable_security ? ' disabled' : '', '>
						<input type="hidden" name="hash_passwrd" value="">';

	if (!empty($upcontext['password_failed']))
		echo '
						<div class="smalltext" style="color: red">Password Incorrect</div>';

	echo '
					</td>
				</tr>';

	// Can they continue?
	if (!empty($upcontext['user']['id']) && time() - $upcontext['user']['updated'] >= $upcontext['inactive_timeout'] && $upcontext['user']['step'] > 1)
	{
		echo '
				<tr>
					<td colspan="2">
						<label for="cont"><input type="checkbox" id="cont" name="cont" checked>Continue from step reached during last execution of upgrade script.</label>
					</td>
				</tr>';
	}

	echo '
			</table><br>
			<span class="smalltext">
				<strong>Note:</strong> If necessary the above security check can be bypassed for users who may administrate a server but not have admin rights on the forum. In order to bypass the above check simply open &quot;upgrade.php&quot; in a text editor and replace &quot;$disable_security = 0;&quot; with &quot;$disable_security = 1;&quot; and refresh this page.
			</span>
			<input type="hidden" name="login_attempt" id="login_attempt" value="1">
			<input type="hidden" name="js_works" id="js_works" value="0">';

	// Say we want the continue button!
	$upcontext['continue'] = !empty($upcontext['user']['id']) && time() - $upcontext['user']['updated'] < $upcontext['inactive_timeout'] ? 2 : 1;

	// This defines whether javascript is going to work elsewhere :D
	echo '
		<script><!-- // --><![CDATA[
			if (document.getElementById(\'js_works\'))
				document.getElementById(\'js_works\').value = 1;

			// Latest version?
			function wedgeCurrentVersion()
			{
				var weVer, yourVer;

				if (!(\'weVersion\' in window))
					return;

				weVer = document.getElementById(\'wedgeVersion\');
				yourVer = document.getElementById(\'yourVersion\');
				weVer.innerHTML = window.weVersion;

				var currentVersion = yourVer.innerHTML;
				if (currentVersion < window.weVersion)
					document.getElementById(\'version_warning\').style.display = \'\';
			}
			$(window).load(wedgeCurrentVersion);

			// This checks that the script file even exists!
			if (typeof weSelectText == \'undefined\')
				document.getElementById(\'js_script_missing_error\').style.display = \'\';

		// ]]></script>';
}

function template_upgrade_options()
{
	global $upcontext, $settings, $upgradeurl, $disable_security, $theme, $boarddir, $db_prefix, $mmessage, $mtitle;

	echo '
			<h3>Before the upgrade gets underway please review the options below - and hit continue when you\'re ready to begin.</h3>
			<form action="', $upcontext['form_url'], '" method="post" name="upform" id="upform">';

	// Warning message?
	if (!empty($upcontext['upgrade_options_warning']))
		echo '
		<div style="margin: 1ex; padding: 1ex; border: 1px dashed #cc3344; color: black; background-color: #ffe4e9">
			<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
			<strong style="text-decoration: underline">Warning!</strong><br>
			<div style="padding-left: 4ex">
				', $upcontext['upgrade_options_warning'], '
			</div>
		</div>';

	echo '
				<table cellpadding="1" cellspacing="0">
					<tr valign="top">
						<td width="2%">
							<input type="checkbox" name="backup" id="backup" value="1">
						</td>
						<td width="100%">
							<label for="backup">Backup tables in your database with the prefix &quot;backup_', $db_prefix, '&quot;.</label> (recommended!)
						</td>
					</tr>
					<tr valign="top">
						<td width="2%">
							<input type="checkbox" name="maint" id="maint" value="1" checked>
						</td>
						<td width="100%">
							<label for="maint">Put the forum into maintenance mode during upgrade.</label> <span class="smalltext">(<a href="#" onclick="document.getElementById(\'mainmess\').style.display = document.getElementById(\'mainmess\').style.display == \'\' ? \'none\' : \'\'">Customize</a>)</span>
							<div id="mainmess" style="display: none">
								<strong class="smalltext">Maintenance Title: </strong><br>
								<input name="maintitle" size="30" value="', htmlspecialchars($mtitle), '"><br>
								<strong class="smalltext">Maintenance Message: </strong><br>
								<textarea name="mainmessage" rows="3" cols="50">', htmlspecialchars($mmessage), '</textarea>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<td width="2%">
							<input type="checkbox" name="debug" id="debug" value="1">
						</td>
						<td width="100%">
							<label for="debug">Output extra debugging information</label>
						</td>
					</tr>
					<tr valign="top">
						<td width="2%">
							<input type="checkbox" name="empty_error" id="empty_error" value="1">
						</td>
						<td width="100%">
							<label for="empty_error">Empty error log before upgrading</label>
						</td>
					</tr>
				</table>
				<input type="hidden" name="upcont" value="1">';

	// We need a normal continue button here!
	$upcontext['continue'] = 1;
}

// Template for the database backup tool/
function template_backup_database()
{
	global $upcontext, $settings, $upgradeurl, $disable_security, $theme, $support_js, $is_debug;

	echo '
			<h3>Please wait while a backup is created. For large forums this may take some time!</h3>';

	echo '
			<form action="', $upcontext['form_url'], '" name="upform" id="upform" method="post">
			<input type="hidden" name="backup_done" id="backup_done" value="0">
			<strong>Completed <span id="tab_done">', $upcontext['cur_table_num'], '</span> out of ', $upcontext['table_count'], ' tables.</strong>
			<span id="debuginfo"></span>';

	// Dont any tables so far?
	if (!empty($upcontext['previous_tables']))
		foreach ($upcontext['previous_tables'] as $table)
			echo '
			<br>Completed Table: &quot;', $table, '&quot;.';

	echo '
			<h3 id="current_tab_div">Current Table: &quot;<span id="current_table">', $upcontext['cur_table_name'], '</span>&quot;</h3>
			<br><span id="commess" style="font-weight: bold; display: ', $upcontext['cur_table_num'] == $upcontext['table_count'] ? 'inline' : 'none', '">Backup Complete! Click Continue to Proceed.</span>';

	// Continue please!
	$upcontext['continue'] = $support_js ? 2 : 1;

	// If javascript allows we want to do this using XML.
	if ($support_js)
	{
		echo '
		<script><!-- // --><![CDATA[
			var lastTable = ', $upcontext['cur_table_num'], ';
			function getNextTables()
			{
				$.get(\'', $upcontext['form_url'], '&xml&substep=\' + lastTable, onBackupUpdate);
			}

			// Got an update!
			function onBackupUpdate(oXMLDoc)
			{
				var sCurrentTableName = "";
				var iTableNum = 0;
				var sCompletedTableName = document.getElementById(\'current_table\').innerHTML;
				for (var i = 0; i < oXMLDoc.getElementsByTagName("table")[0].childNodes.length; i++)
					sCurrentTableName += oXMLDoc.getElementsByTagName("table")[0].childNodes[i].nodeValue;
				iTableNum = oXMLDoc.getElementsByTagName("table")[0].getAttribute("num");

				// Update the page.
				document.getElementById(\'tab_done\').innerHTML = iTableNum;
				document.getElementById(\'current_table\').innerHTML = sCurrentTableName;
				lastTable = iTableNum;
				updateStepProgress(iTableNum, ', $upcontext['table_count'], ', ', $upcontext['step_weight'] * ((100 - $upcontext['step_progress']) / 100), ');';

		// If debug flood the screen.
		if ($is_debug)
			echo '
				$(\'#debuginfo\').append(\'<br>Completed Table: &quot;\' + sCompletedTableName + \'&quot;.\');';

		echo '
				// Get the next update...
				if (iTableNum == ', $upcontext['table_count'], ')
				{
					document.getElementById(\'commess\').style.display = "";
					document.getElementById(\'current_tab_div\').style.display = "none";
					document.getElementById(\'contbutt\').disabled = 0;
					document.getElementById(\'backup_done\').value = 1;
				}
				else
					getNextTables();
			}
			getNextTables();
		// ]]></script>';
	}
}

function template_backup_xml()
{
	global $upcontext, $theme, $options, $txt;

	echo '
	<table num="', $upcontext['cur_table_num'], '">', $upcontext['cur_table_name'], '</table>';
}

// Here is the actual "make the changes" template!
function template_database_changes()
{
	global $upcontext, $settings, $upgradeurl, $disable_security, $theme, $support_js, $is_debug, $timeLimitThreshold;

	echo '
		<h3>Executing database changes</h3>
		<h4 style="font-style: italic">Please be patient - this may take some time on large forums. The time elapsed increments from the server to show progress is being made!</h4>';

	echo '
		<form action="', $upcontext['form_url'], '&amp;filecount=', $upcontext['file_count'], '" name="upform" id="upform" method="post">
		<input type="hidden" name="database_done" id="database_done" value="0">';

	// No javascript looks rubbish!
	if (!$support_js)
	{
		foreach ($upcontext['actioned_items'] as $num => $item)
		{
			if ($num != 0)
				echo ' Successful!';
			echo '<br>' . $item;
		}
		if (!empty($upcontext['changes_complete']))
			echo ' Successful!<br><br><span id="commess" style="font-weight: bold">Database Updates Complete! Click Continue to Proceed.</span><br>';
	}
	else
	{
		// Tell them how many files we have in total.
		if ($upcontext['file_count'] > 1)
			echo '
		<strong id="info1">Executing upgrade script <span id="file_done">', $upcontext['cur_file_num'], '</span> of ', $upcontext['file_count'], '.</strong>';

		echo '
		<h3 id="info2"><strong>Executing:</strong> &quot;<span id="cur_item_name">', $upcontext['current_item_name'], '</span>&quot; (<span id="item_num">', $upcontext['current_item_num'], '</span> of <span id="total_items"><span id="item_count">', $upcontext['total_items'], '</span>', $upcontext['file_count'] > 1 ? ' - of this script' : '', ')</span></h3>
		<br><span id="commess" style="font-weight: bold; display: ', !empty($upcontext['changes_complete']) || $upcontext['current_debug_item_num'] == $upcontext['debug_items'] ? 'inline' : 'none', '">Database Updates Complete! Click Continue to Proceed.</span>';

		if ($is_debug)
		{
			echo '
			<div id="debug_section" style="height: 200px; overflow: auto">
			<span id="debuginfo"></span>
			</div>';
		}
	}

	// Place for the XML error message.
	echo '
		<div id="error_block" style="margin: 2ex; padding: 2ex; border: 2px dashed #cc3344; color: black; background-color: #ffe4e9; display: ', empty($upcontext['error_message']) ? 'none' : '', '">
			<div style="float: left; width: 2ex; font-size: 2em; color: red">!!</div>
			<strong style="text-decoration: underline">Error!</strong><br>
			<div style="padding-left: 6ex" id="error_message">', isset($upcontext['error_message']) ? $upcontext['error_message'] : 'Unknown Error!', '</div>
		</div>';

	// We want to continue at some point!
	$upcontext['continue'] = $support_js ? 2 : 1;

	// If javascript allows we want to do this using XML.
	if ($support_js)
	{
		echo '
		<script><!-- // --><![CDATA[
			var lastItem = ', $upcontext['current_debug_item_num'], ';
			var sLastString = "', strtr($upcontext['current_debug_item_name'], array('"' => '&quot;')), '";
			var iLastSubStepProgress = -1;
			var curFile = ', $upcontext['cur_file_num'], ';
			var totalItems = 0;
			var prevFile = 0;
			var retryCount = 0;
			var testvar = 0;
			var timeOutID = 0;
			var getData = "";
			var debugItems = ', $upcontext['debug_items'], ';
			function getNextItem()
			{
				// We want to track this...
				if (timeOutID)
					clearTimeout(timeOutID);
				timeOutID = setTimeout(retTimeout, ', (10 * $timeLimitThreshold), '000);

				$.get(\'', $upcontext['form_url'], '&xml&filecount=', $upcontext['file_count'], '&substep=\' + lastItem + getData, onItemUpdate);
			}

			// Got an update!
			function onItemUpdate(oXMLDoc)
			{
				var sItemName = "";
				var sDebugName = "";
				var iItemNum = 0;
				var iSubStepProgress = -1;
				var iDebugNum = 0;
				var bIsComplete = 0;
				getData = "";

				// We\'ve got something - so reset the timeout!
				if (timeOutID)
					clearTimeout(timeOutID);

				// Assume no error at this time...
				document.getElementById("error_block").style.display = "none";

				// Are we getting some duff info?
				if (!oXMLDoc.getElementsByTagName("item")[0])
				{
					// Too many errors?
					if (retryCount > 15)
					{
						document.getElementById("error_block").style.display = "";
						document.getElementById("error_message").innerHTML = "Error retrieving information on step: " + (sDebugName == "" ? sLastString : sDebugName);';

	if ($is_debug)
		echo '
						$(\'#debuginfo\').append(\'<span style="color: red">failed<\' + \'/span>\');';

	echo '
					}
					else
					{
						retryCount++;
						getNextItem();
					}
					return false;
				}

				// Never allow loops.
				if (curFile == prevFile)
				{
					retryCount++;
					if (retryCount > 10)
					{
						document.getElementById("error_block").style.display = "";
						document.getElementById("error_message").innerHTML = "Upgrade script appears to be going into a loop - step: " + sDebugName;';

	if ($is_debug)
		echo '
						$(\'#debuginfo\').append(\'<span style="color: red">failed<\' + \'/span>\');';

	echo '
					}
				}
				retryCount = 0;

				for (var i = 0; i < oXMLDoc.getElementsByTagName("item")[0].childNodes.length; i++)
					sItemName += oXMLDoc.getElementsByTagName("item")[0].childNodes[i].nodeValue;
				for (var i = 0; i < oXMLDoc.getElementsByTagName("debug")[0].childNodes.length; i++)
					sDebugName += oXMLDoc.getElementsByTagName("debug")[0].childNodes[i].nodeValue;
				for (var i = 0; i < oXMLDoc.getElementsByTagName("get").length; i++)
				{
					getData += "&" + oXMLDoc.getElementsByTagName("get")[i].getAttribute("key") + "=";
					for (var j = 0; j < oXMLDoc.getElementsByTagName("get")[i].childNodes.length; j++)
					{
						getData += oXMLDoc.getElementsByTagName("get")[i].childNodes[j].nodeValue;
					}
				}

				iItemNum = oXMLDoc.getElementsByTagName("item")[0].getAttribute("num");
				iDebugNum = parseInt(oXMLDoc.getElementsByTagName("debug")[0].getAttribute("num"));
				bIsComplete = parseInt(oXMLDoc.getElementsByTagName("debug")[0].getAttribute("complete"));
				iSubStepProgress = parseFloat(oXMLDoc.getElementsByTagName("debug")[0].getAttribute("percent"));
				sLastString = sDebugName + " (Item: " + iDebugNum + ")";

				curFile = parseInt(oXMLDoc.getElementsByTagName("file")[0].getAttribute("num"));
				debugItems = parseInt(oXMLDoc.getElementsByTagName("file")[0].getAttribute("debug_items"));
				totalItems = parseInt(oXMLDoc.getElementsByTagName("file")[0].getAttribute("items"));

				// If we have an error we haven\'t completed!
				if (oXMLDoc.getElementsByTagName("error")[0] && bIsComplete)
					iDebugNum = lastItem;

				// Do we have the additional progress bar?
				if (iSubStepProgress != -1)
				{
					document.getElementById("substep_bar_div").style.display = "";
					document.getElementById("substep_bar_div2").style.display = "";
					document.getElementById("substep_progress").style.width = iSubStepProgress + "%";
					document.getElementById("substep_text").innerHTML = iSubStepProgress + "%";
					document.getElementById("substep_bar_div").innerHTML = sDebugName.replace(/\./g, "") + ":";
				}
				else
				{
					document.getElementById("substep_bar_div").style.display = "none";
					document.getElementById("substep_bar_div2").style.display = "none";
				}

				// Move onto the next item?
				if (bIsComplete)
					lastItem = iDebugNum;
				else
					lastItem = iDebugNum - 1;

				// Are we finished?
				if (bIsComplete && iDebugNum == -1 && curFile >= ', $upcontext['file_count'], ')
				{';

		if ($is_debug)
			echo '
					document.getElementById(\'debug_section\').style.display = "none"';

		echo '

					document.getElementById(\'commess\').style.display = "";
					document.getElementById(\'contbutt\').disabled = 0;
					document.getElementById(\'database_done\').value = 1;';

		if ($upcontext['file_count'] > 1)
			echo '
					document.getElementById(\'info1\').style.display = "none";';

		echo '
					document.getElementById(\'info2\').style.display = "none";
					updateStepProgress(100, 100, ', $upcontext['step_weight'] * ((100 - $upcontext['step_progress']) / 100), ');
					return true;
				}
				// Was it the last step in the file?
				else if (bIsComplete && iDebugNum == -1)
				{
					lastItem = 0;
					prevFile = curFile;';

		if ($is_debug)
			echo '
					$(\'#debuginfo\').append(\'done<br>Moving to next script file...done\');';

		echo '
					getNextItem();
					return true;
				}';

		// If debug scroll the screen.
		if ($is_debug)
			echo '
				if (iLastSubStepProgress == -1)
				{
					// Give it consistent dots.
					dots = sDebugName.match(/\./g);
					numDots = dots ? dots.length : 0;
					for (var i = numDots; i < 3; i++)
						sDebugName += ".";
					$(\'#debuginfo\').append(sDebugName);
				}
				iLastSubStepProgress = iSubStepProgress;

				if (bIsComplete)
					$(\'#debuginfo\').append(\'done<br>\');
				else
					$(\'#debuginfo\').append(\'...\');

				if (document.getElementById(\'debug_section\').scrollHeight)
					document.getElementById(\'debug_section\').scrollTop = document.getElementById(\'debug_section\').scrollHeight';

		echo '
				// Update the page.
				document.getElementById(\'item_num\').innerHTML = iItemNum;
				document.getElementById(\'cur_item_name\').innerHTML = sItemName;';

		if ($upcontext['file_count'] > 1)
		{
			echo '
				document.getElementById(\'file_done\').innerHTML = curFile;
				document.getElementById(\'item_count\').innerHTML = totalItems;';
		}

		echo '
				// Is there an error?
				if (oXMLDoc.getElementsByTagName("error")[0])
				{
					var sErrorMsg = "";
					for (var i = 0; i < oXMLDoc.getElementsByTagName("error")[0].childNodes.length; i++)
						sErrorMsg += oXMLDoc.getElementsByTagName("error")[0].childNodes[i].nodeValue;
					document.getElementById("error_block").style.display = "";
					document.getElementById("error_message").innerHTML = sErrorMsg;
					return false;
				}

				// Get the progress bar right.
				barTotal = debugItems * ', $upcontext['file_count'], ';
				barDone = debugItems * (curFile - 1) + lastItem;

				updateStepProgress(barDone, barTotal, ', $upcontext['step_weight'] * ((100 - $upcontext['step_progress']) / 100), ');

				// Finally - update the time here as it shows the server is responding!
				iElapsed = $.now() / 1000 - ', $upcontext['started'], ';
				mins = parseInt(iElapsed / 60);
				secs = parseInt(iElapsed - mins * 60);
				document.getElementById("mins_elapsed").innerHTML = mins;
				document.getElementById("secs_elapsed").innerHTML = secs;

				getNextItem();
				return true;
			}

			// What if we timeout?!
			function retTimeout(attemptAgain)
			{
				// Oh noes...
				if (!attemptAgain)
				{
					document.getElementById("error_block").style.display = "";
					document.getElementById("error_message").innerHTML = "Server has not responded for ', ($timeLimitThreshold * 10), ' seconds. It may be worth waiting a little longer or otherwise please click <a href=\"#\" onclick=\"retTimeout(true); return false;\">here<" + "/a> to try this step again";
				}
				else
				{
					document.getElementById("error_block").style.display = "none";
					getNextItem();
				}
			}';

		// Start things off assuming we've not errored.
		if (empty($upcontext['error_message']))
			echo '
			getNextItem();';

		echo '
		// ]]></script>';
	}
	return;
}

function template_database_xml()
{
	global $upcontext, $theme, $options, $txt;

	echo '
	<file num="', $upcontext['cur_file_num'], '" items="', $upcontext['total_items'], '" debug_items="', $upcontext['debug_items'], '">', $upcontext['cur_file_name'], '</file>
	<item num="', $upcontext['current_item_num'], '">', $upcontext['current_item_name'], '</item>
	<debug num="', $upcontext['current_debug_item_num'], '" percent="', isset($upcontext['substep_progress']) ? $upcontext['substep_progress'] : '-1', '" complete="', empty($upcontext['completed_step']) ? 0 : 1, '">', $upcontext['current_debug_item_name'], '</debug>';

	if (!empty($upcontext['error_message']))
		echo '
	<error>', $upcontext['error_message'], '</error>';
}

function template_upgrade_complete()
{
	global $upcontext, $settings, $upgradeurl, $disable_security, $theme, $boarddir, $db_prefix, $boardurl;

	echo '
	<h3>That wasn\'t so hard, was it? Now you are ready to use <a href="', $boardurl, '/index.php">your installation of Wedge</a>. Hope you like it!</h3>
	<form action="', $boardurl, '/index.php">';

	if (!empty($upcontext['can_delete_script']))
		echo '
		<label><input type="checkbox" onclick="doTheDelete();"> Delete this upgrade.php and its data files now.</label> <em>(doesn\'t work on all servers.)</em>
		<script><!-- // --><![CDATA[
			function doTheDelete()
			{
				$.get(weUrl("', $upgradeurl, '?delete=1&ts_" + $.now()));
				this.disabled = true;
			}
		// ]]></script><br>';

	echo '<br>
			If you had any problems with this upgrade, or have any problems using Wedge, please don\'t hesitate to <a href="http://wedge.org/">look to us for assistance</a>.<br>
			<br>
			', sprintf($txt['go_to_your_forum'], $boardurl . '/index.php'), '<br>
			<br>
			', $txt['good_luck'];
}
