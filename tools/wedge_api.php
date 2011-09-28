<?php
/**
 * Wedge
 *
 * we_api.php
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

// !!! Groups, Member data?  Pull specific fields?

/*	This file includes functions that may help integration with other scripts
	and programs, such as portals.  It is independent of Wedge, and meant to run
	without disturbing your script.  It defines several functions, all of
	which start with the we_ prefix.  These are:

	bool we_setLoginCookie(int length, string username or int id_member,
			string password, bool encrypted = true)
		- sets a cookie and session variables to log the user in, for length
		  seconds from now.
		- will find the id_member for you if you specify a username.
		- please ensure that the username has slashes added to it.
		- does no authentication, but if the cookie is wrong it won't work.
		- expects the password to be pre-encrypted if encrypted is true.
		- returns false on failure (unlikely!), true on success.
		- you should call we_authenticateUser after calling this.

	bool we_authenticateUser()
		- authenticates the user with the current cookie ro session data.
		- loads data into the $we_user_info variable.
		- returns false if it was unable to authenticate, true otherwise.
		- it would be good to call this at the beginning.

	int we_registerMember(string username, string email, string password,
			array extra_fields = none, array theme_options = none)
		// !!!

	void we_logOnline(string action = $_GET['action'])
		- logs the currently authenticated user or guest as online.
		- may not always log, because it delays logging if at all possible.
		- uses the action specified as the action in the log, a good example
		  would be "coppermine" or similar.

	bool we_is_online(string username or int id_member)
		- checks if the specified member is currently online.
		- will find the appropriate id_member if username is given instead.
		- returns true if they are online, false otherwise.

	string we_logError(string error_message, string file, int line)
		- logs an error, assuming error logging is enabled.
		- filename and line should be __FILE__ and __LINE__, respectively.
		- returns the error message. (ie. die(log_error($msg));)

	string we_formatTime(int time)
		- formats the timestamp time into a readable string.
		- adds the appropriate offsets to make the time equivalent to the
		  user's time.
		- return the readable representation as a string.

	resource we_query(string query, string file, int line)
		- executes a query using Wedge's database connection.
		- keeps a count of queries in the $we_settings['db_count'] setting.
		- if an error occurs while executing the query, additionally logs an
		  error in Wedge's error log with the proper information.
		- does not do any crashed table prevention.

	bool we_allowedTo(string permission)
		- checks to see if the user is allowed to do the specified permission
		  or any of an array of permissions.
		- always returns true for administrators.
		- does not account for banning restrictions.
		- caches all available permissions upon first call.
		- does not provide access to board permissions.
		- returns null if no connection to the database has been made, and
		  true or false depending on the user's permissions.

	void we_loadThemeData(int id_theme = default)
		- if no id_theme is passed, the user's default theme will be used.
		- allows 'theme' in the URL to specify the theme, only if id_theme is
		  not passed.
		- loads theme settings into $we_settings['theme'].
		- loads theme options into $we_user_info['theme'].
		- does nothing if no connection has been made to the database.
		- should be called after loading user information.

	void we_loadSession()
		- loads the session, whether from the database or from files.
		- makes the session_id available in $we_user_info.
		- will override session handling if the setting is enabled in Wedge's
		  configuration.

	bool we_sessionOpen(string save_path, string session_name)
	bool we_sessionClose()
	bool we_sessionRead(string session_id)
	bool we_sessionWrite(string session_id, string data)
	bool we_sessionDestroy(string session_id)
	bool we_sessionGC(int max_lifetime)
		- called only by internal PHP session handling functions.

	---------------------------------------------------------------------------
	It also defines the following important variables:

	array $we_settings
		- includes all the major settings from Settings.php, as well as all
		  those from the settings table.
		- if we_loadThemeData has been called, the theme settings will be
		  available from the theme index.

	array $we_user_info
		- only contains useful information after authentication.
		- major indexes are is_guest and is_admin, which easily and quickly
		  tell you about the user's status.
		- also includes id, name, email, messages, unread_messages, and many
		  other values from the members table.
		- you can also use the groups index to find what groups the user is in.
		- if we_loadSession has been called, the session code is stored under
		  session_id.
		- if we_loadThemeData has been called, the theme options will be
		  available from the theme index.
*/

// This is just because Wedge in general hates magic quotes at runtime.
if (function_exists('set_magic_quotes_runtime'))
	@set_magic_quotes_runtime(0);

// Hopefully the forum is in the same place as this script.
require_once(dirname(__FILE__) . '/Settings.php');

global $we_settings, $we_user_info, $we_connection;

// If $maintenance is set to 2, don't connect to the database at all.
if ($maintenance != 2)
{
	define('WEDGE', 1);

	if (empty($smcFunc))
		$smcFunc = array();

	require_once($sourcedir . '/Errors.php');
	require_once($sourcedir . '/Subs.php');
	require_once($sourcedir . '/Load.php');
	require_once($sourcedir . '/Security.php');
	require_once($sourcedir . '/Subs-Auth.php');
	require_once($sourcedir . '/Subs-Database.php');
	$db_connection = we_db_initiate($db_server, $db_name, $db_user, $db_passwd, $db_prefix, array('non_fatal' => true));

	$request = $smcFunc['db_query']('', '
		SELECT variable, value
		FROM {db_prefix}settings', array());
	$we_settings = array();
	while ($row = $smcFunc['db_fetch_row']($request))
		$we_settings[$row[0]] = $row[1];
	$smcFunc['db_free_result']($request);
}

// Load stuff from the Settings.php file into $we_settings.
$we_settings['cookiename'] = $cookiename;
$we_settings['language'] = $language;
$we_settings['forum_name'] = $mbname;
$we_settings['forum_url'] = $boardurl;
$we_settings['webmaster_email'] = $webmaster_email;
$we_settings['db_prefix'] = $db_prefix;

$we_user_info = array();

// Actually set the login cookie...
function we_setLoginCookie($cookie_length, $id, $password = '', $encrypted = true)
{
	// This should come from Settings.php, hopefully.
	global $we_connection, $we_settings;

	// The $id is not numeric; it's probably a username.
	if (!is_int($id))
	{
		if (!$we_connection)
			return false;

		// Save for later use.
		$username = $id;

		$result = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {raw:we_db_prefix}members
			WHERE member_name = {string:username}
			LIMIT 1',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'username' => $username,
		));
		list ($id) = $smcFunc['db_fetch_row']($result);
		$smcFunc['db_free_result']($result);

		// It wasn't found, after all?
		if (empty($id))
		{
			$id = (int) $username;
			unset($username);
		}
	}

	// Oh well, I guess it just was not to be...
	if (empty($id))
		return false;

	// The password isn't encrypted, do so.
	if (!$encrypted)
	{
		if (!$we_connection)
			return false;

		$result = $smcFunc['db_query']('', '
			SELECT member_name, password_salt
			FROM {raw:we_db_prefix}members
			WHERE id_member = {int:id_member}
			LIMIT 1',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'id_member' => (int) $id,
		));
		list ($username, $salt) = $smcFunc['db_fetch_row']($result);
		$smcFunc['db_free_result']($result);

		if (empty($username))
			return false;

		$password = sha1(sha1(strtolower($username) . $password) . $salt);
	}

	function we_cookie_url($local, $global)
	{
		global $we_settings;
		// Use PHP to parse the URL, hopefully it does its job.
		$parsed_url = parse_url($we_settings['forum_url']);

		// Set the cookie to the forum's path only?
		if (empty($parsed_url['path']) || !$local)
			$parsed_url['path'] = '';

		// This is probably very likely for apis and such, no?
		if ($global)
		{
			// Try to figure out where to set the cookie; this can be confused, though.
			if (preg_match('~(?:[^\.]+\.)?(.+)\z~i', $parsed_url['host'], $parts) == 1)
				$parsed_url['host'] = '.' . $parts[1];
		}
		// If both options are off, just use no host and /.
		elseif (!$local)
			$parsed_url['host'] = '';
	}

	// The cookie may already exist, and have been set with different options.
	$cookie_state = (empty($we_settings['localCookies']) ? 0 : 1) | (empty($we_settings['globalCookies']) ? 0 : 2);
	if (isset($_COOKIE[$we_settings['cookiename']]))
	{
		$array = @unserialize($_COOKIE[$we_settings['cookiename']]);

		if (isset($array[3]) && $array[3] != $cookie_state)
		{
			$cookie_url = we_cookie_url($array[3] & 1 > 0, $array[3] & 2 > 0);
			setcookie($we_settings['cookiename'], serialize(array(0, '', 0)), time() - 3600, $parsed_url['path'] . '/', $parsed_url['host'], 0, true);
		}
	}

	// Get the data and path to set it on.
	$data = serialize(empty($id) ? array(0, '', 0) : array($id, $password, time() + $cookie_length));
	$parsed_url = we_cookie_url(!empty($we_settings['localCookies']), !empty($we_settings['globalCookies']));

	// Set the cookie, $_COOKIE, and session variable.
	setcookie($we_settings['cookiename'], $data, time() + $cookie_length, $parsed_url['path'] . '/', $parsed_url['host'], 0, true);
	$_COOKIE[$we_settings['cookiename']] = $data;
	$_SESSION['login_' . $we_settings['cookiename']] = $data;

	return true;
}

function we_authenticateUser()
{
	global $we_connection, $we_settings;

	// No connection, no authentication!
	if (!$we_connection)
		return false;

	// Check first the cookie, then the session.
	if (isset($_COOKIE[$we_settings['cookiename']]))
	{
		$_COOKIE[$we_settings['cookiename']] = stripslashes($_COOKIE[$we_settings['cookiename']]);

		// Fix a security hole in PHP 4.3.9 and below...
		if (preg_match('~^a:[34]:\{i:0;(i:\d{1,6}|s:[1-8]:"\d{1,8}");i:1;s:(0|40):"([a-fA-F0-9]{40})?";i:2;[id]:\d{1,14};(i:3;i:\d;)?\}$~', $_COOKIE[$we_settings['cookiename']]) == 1)
		{
			list ($id_member, $password) = @unserialize($_COOKIE[$we_settings['cookiename']]);
			$id_member = !empty($id_member) ? (int) $id_member : 0;
		}
		else
			$id_member = 0;
	}
	elseif (isset($_SESSION['login_' . $we_settings['cookiename']]))
	{
		list ($id_member, $password, $login_span) = @unserialize(stripslashes($_SESSION['login_' . $we_settings['cookiename']]));
		$id_member = !empty($id_member) && $login_span > time() ? (int) $id_member : 0;
	}
	else
		$id_member = 0;

	// Don't even bother if they have no authentication data.
	if (!empty($id_member))
	{
		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {raw:we_db_prefix}
			WHERE id_member = {int:id_member}
			LIMIT 1',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'id_member' => $id_member,
		));
		// Did we find 'im?  If not, junk it.
		if (mysql_num_rows($request) != 0)
		{
			// The base settings array.
			$we_user_info += $smcFunc['db_fetch_assoc']($request);

			if (strlen($password) == 40)
				$check = sha1($we_user_info['passwd'] . $we_user_info['password_salt']) == $password;
			else
				$check = false;

			// Wrong password or not activated - either way, you're going nowhere.
			$id_member = $check && ($we_user_info['is_activated'] == 1 || $we_user_info['is_activated'] == 11) ? $we_user_info['id_member'] : 0;
		}
		else
			$id_member = 0;
		$smcFunc['db_free_result']($request);
	}

	if (empty($id_member))
		$we_user_info = array('groups' => array(-1));
	else
	{
		if (empty($we_user_info['additional_groups']))
			$we_user_info['groups'] = array($we_user_info['id_group'], $we_user_info['id_post_group']);
		else
			$we_user_info['groups'] = array_merge(
				array($we_user_info['id_group'], $we_user_info['id_post_group']),
				explode(',', $we_user_info['additional_groups'])
			);
	}

	// A few things to make life easier...
	$we_user_info['id'] =& $we_user_info['id_member'];
	$we_user_info['username'] =& $we_user_info['member_name'];
	$we_user_info['name'] =& $we_user_info['real_name'];
	$we_user_info['email'] =& $we_user_info['email_address'];
	$we_user_info['messages'] =& $we_user_info['instant_messages'];
	$we_user_info['unread_messages'] =& $we_user_info['unread_messages'];
	$we_user_info['language'] = empty($we_user_info['lngfile']) || empty($we_settings['userLanguage']) ? $we_settings['language'] : $we_user_info['lngfile'];
	$we_user_info['is_guest'] = $id_member == 0;
	$we_user_info['is_admin'] = in_array(1, $we_user_info['groups']);

	// This might be set to "forum default"...
	if (empty($we_user_info['time_format']))
		$we_user_info['time_format'] = $we_settings['time_format'];

	return !$we_user_info['is_guest'];
}

function we_registerMember($username, $email, $password, $extra_fields = array(), $theme_options = array())
{
	global $we_settings, $we_connection;

	// No connection means no registrations...
	if (!$we_connection)
		return false;

	// Can't use that username.
	if (preg_match('~[<>&"\'=\\\]~', $username) === 1 || $username === '_' || $username === '|' || strpos($username, '[code') !== false || strpos($username, '[/code') !== false || strlen($username) > 25)
		return false;

	// Make sure the email is valid too.
	if (empty($email) || !preg_match('~^[\w=+/-][\w=\'+/\.-]*@[\w-]+(\.[\w-]+)*(\.\w{2,6})$~', $email) || strlen($email) > 255)
		return false;

	// !!! Validate username isn't already used?  Validate reserved, etc.?

	$register_vars = array(
		'member_name' => "'$username'",
		'real_name' => "'$username'",
		'email_address' => "'" . addslashes($email) . "'",
		'passwd' => "'" . sha1(strtolower($username) . $password) . "'",
		'password_salt' => "'" . substr(md5(mt_rand()), 0, 4) . "'",
		'posts' => '0',
		'date_registered' => (string) time(),
		'is_activated' => '1',
		'personal_text' => "'" . addslashes($we_settings['default_personal_text']) . "'",
		'pm_email_notify' => '1',
		'id_theme' => '0',
		'id_post_group' => '4',
		'lngfile' => "''",
		'buddy_list' => "''",
		'pm_ignore_list' => "''",
		'message_labels' => "''",
		'website_title' => "''",
		'website_url' => "''",
		'location' => "''",
		'time_format' => "''",
		'signature' => "''",
		'avatar' => "''",
		'usertitle' => "''",
		'member_ip' => "''",
		'member_ip2' => "''",
		'secret_question' => "''",
		'secret_answer' => "''",
		'validation_code' => "''",
		'additional_groups' => "''",
		'smiley_set' => "''",
		'password_salt' => "''",
	);

	$register_vars = array_values($extra_fields + $register_vars);
	// We could, build a custom function to figure out if it is a string or int, but assuming string is quicker.
	$register_keys = array_combine(array_keys($extra_fields + $register_vars), array_fill(0, count($register_vars), 'string'));

	$smcFunc['db_insert']('insert',
		$we_settings['db_prefix'] . 'members',
		$register_keys,
		$register_vars,
		array('id_member')
	);
	$id_member = $smcFunc['db_insert_id']($we_connection);

	$smcFunc['db_query']('', '
		UPDATE {raw:we_db_prefix}
		SET value = value + 1
		WHERE variable = {string:totalMembers}',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'totalMembers' => 'totalMembers',
		));
	$smcFunc['db_insert']('replace',
		$we_settings['db_prefix'] . 'settings',
		array('variable' => 'string', 'value' => 'string'),
		array(
			array('latestMember', $id_member),
			array('latestRealName', $username),
		),
		array('variable', 'value')
	);
	$smcFunc['db_query']('', '
		UPDATE {raw:we_db_prefix}log_activity
		SET registers = registers + 1
		WHERE date = {string:cur_date}',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'cur_date' => strftime('%Y-%m-%d'),
		));
	if ($smcFunc['db_affected_rows']($we_connection) == 0)
		$smcFunc['db_insert']('insert',
			$we_settings['db_prefix'] . 'log_activity',
			array('date' => 'string', 'registers' => 'int'),
			array(strftime('%Y-%m-%d'), 1),
			array('date', 'registers')
		);

	// Theme variables too?
	if (!empty($theme_options))
	{
		$inserts = array();
		foreach ($theme_options as $var => $val)
			$inserts[] = array($memberID, substr($var, 0, 255), substr($val, 0, 65534));

		$smcFunc['db_insert']('insert',
			$we_settings['db_prefix'] . 'themes',
			array('id_member' => 'int', 'variable' => 'string-255', 'value' => 'string-65534'),
			$inserts,
			array('id_member', 'id_theme')
		);
	}

	return $id_member;
}

// Log the current user online.
function we_logOnline($action = null)
{
	global $we_settings, $we_connection, $we_user_info;

	if (!$we_connection)
		return false;

	// Determine number of seconds required.
	$lastActive = $we_settings['lastActive'] * 60;

	// Don't mark them as online more than every so often.
	if (empty($_SESSION['log_time']) || $_SESSION['log_time'] < (time() - 8))
		$_SESSION['log_time'] = time();
	else
		return;

	$serialized = $_GET;
	$serialized['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
	unset($serialized['sesc']);
	if ($action !== null)
		$serialized['action'] = $action;

	$serialized = addslashes(serialize($serialized));

	// Guests use 0, members use id_member.
	if ($we_user_info['is_guest'])
	{
		$smcFunc['db_query']('', '
			DELETE FROM {raw:we_db_prefix}log_online
			WHERE log_time < {int:last_active} OR session = {string:cur_ip}',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'last_active' => time() - $lastActive,
				'cur_ip' => 'ip' . $_SERVER['REMOTE_ADDR'],
		));
		$smcFunc['db_insert']('insert',
			$we_settings['db_prefix'] . 'log_online',
			array('session' => 'string', 'id_member' => 'int', 'ip' => 'raw', 'url' => 'string', 'url' => 'string', 'log_time' => 'int'),
			array('ip' . $_SERVER['REMOTE_ADDR'], 0, 'IFNULL(INET_ATON("' . $_SERVER['REMOTE_ADDR'] . '"), 0)', $serialized, time()),
			array('session', 'id_member')
		);
	}
	else
	{
		$smcFunc['db_query']('', '
			DELETE FROM {raw:we_db_prefix}log_online
			WHERE log_time < {int:last_active} OR id_member = {int:cur_id} OR session = {string:cur_session}',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'last_active' => time() - $lastActive,
				'cur_id' => $we_user_info['id'],
				'cur_session' => @session_id(),
		));
		$smcFunc['db_insert']('',
			$we_settings['db_prefix'] . 'log_online',
			array('session' => 'string', 'id_member' => 'int', 'ip' => 'raw', 'url' => 'string', 'log_time' => 'int'),
			array(@session_id(), $we_user_info['id'], 'IFNULL(INET_ATON("' . $_SERVER['REMOTE_ADDR'] . '"), 0)', $serialized, time()),
			array('session', 'id_member')
		);
	}
}

function we_is_online($user)
{
	global $we_settings, $we_connection;

	if (!$we_connection)
		return false;

	$result = $smcFunc['db_query']('', '
		SELECT lo.id_member
		FROM {raw:we_db_prefix}log_online AS lo' . (!is_int($user) ? '
			LEFT JOIN {raw:we_db_prefix}members AS mem ON (mem.id_member = lo.id_member)' : '') . '
		WHERE lo.id_member = {int:user}' . (!is_int($user) ? ' OR mem.member_name = {string:user}' : '') . '
		LIMIT 1',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'user' => (int) $user,
		));
	$return = mysql_num_rows($result) != 0;
	$smcFunc['db_free_result']($result);

	return $return;
}

// Log an error, if the option is on.
function we_logError($error_message, $file = null, $line = null)
{
	global $we_settings, $we_connection;

	// Check if error logging is actually on and we're connected...
	if (empty($we_settings['enableErrorLogging']) || !$we_connection)
		return $error_message;

	// Basically, htmlspecialchars it minus &. (for entities!)
	$error_message = strtr($error_message, array('<' => '&lt;', '>' => '&gt;', '"' => '&quot;'));
	$error_message = strtr($error_message, array('&lt;br /&gt;' => '<br />', '&lt;b&gt;' => '<strong>', '&lt;/b&gt;' => '</strong>', "\n" => '<br />'));

	// Add a file and line to the error message?
	if ($file != null)
		$error_message .= '<br />' . $file;
	if ($line != null)
		$error_message .= '<br />' . $line;

	// Just in case there's no id_member or IP set yet.
	if (empty($we_user_info['id']))
		$we_user_info['id'] = 0;

	// Insert the error into the database.
	$smcFunc['db_insert']('insert',
		$we_settings['db_prefix'] . 'log_errors',
		array(
			'id_member' => 'int', 'log_time' => 'int', 'ip' => 'string', 'url' => 'string', 'message' => 'string', 'session' => 'string'
		),
		array($we_user_info['id'], time(), $_SERVER['REMOTE_ADDR'], empty($_SERVER['QUERY_STRING']) ? '' : addslashes(htmlspecialchars('?' . $_SERVER['QUERY_STRING']))), addslashes($error_message), @session_id(),
		array(
			'id_error'
	));

	// Return the message to make things simpler.
	return $error_message;
}

// Format a time to make it look purdy.
function we_formatTime($log_time)
{
	global $we_user_info, $we_settings;

	// Offset the time - but we can't have a negative date!
	$time = max($log_time + (@$we_user_info['time_offset'] + $we_settings['time_offset']) * 3600, 0);

	// Format some in caps, and then any other characters..
	return strftime(strtr(!empty($we_user_info['time_format']) ? $we_user_info['time_format'] : $we_settings['time_format'], array('%a' => ucwords(strftime('%a', $time)), '%A' => ucwords(strftime('%A', $time)), '%b' => ucwords(strftime('%b', $time)), '%B' => ucwords(strftime('%B', $time)))), $time);
}

// Mother, may I?
function we_allowedTo($permission)
{
	global $we_settings, $we_user_info, $we_connection;

	if (!$we_connection)
		return null;

	// Administrators can do all, and everyone can do nothing.
	if ($we_user_info['is_admin'] || empty($permission))
		return true;

	if (!isset($we_user_info['permissions']))
	{
		$result = $smcFunc['db_query']('', '
			SELECT permission, add_deny
			FROM {raw:we_db_prefix}permissions
			WHERE id_group IN ({array_int:user_groups})',
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'user_groups' => $we_user_info['groups'],
		));
		$removals = array();
		$we_user_info['permissions'] = array();
		while ($row = $smcFunc['db_fetch_assoc']($result))
		{
			if (empty($row['add_deny']))
				$removals[] = $row['permission'];
			else
				$we_user_info['permissions'][] = $row['permission'];
		}
		$smcFunc['db_free_result']($result);

		// And now we get rid of the removals ;).
		if (!empty($we_settings['permission_enable_deny']))
			$we_user_info['permissions'] = array_diff($we_user_info['permissions'], $removals);
	}

	// So.... can you?
	if (!is_array($permission) && in_array($permission, $we_user_info['permissions']))
		return true;
	elseif (is_array($permission) && count(array_intersect($permission, $we_user_info['permissions'])) != 0)
		return true;
	else
		return false;
}

function we_loadThemeData($id_theme = 0)
{
	global $we_settings, $we_user_info, $we_connection;

	if (!$we_connection)
		return null;

	// The theme was specified by parameter.
	if (!empty($id_theme))
		$theme = (int) $id_theme;
	// The theme was specified by REQUEST.
	elseif (!empty($_REQUEST['theme']))
	{
		$theme = (int) $_REQUEST['theme'];
		$_SESSION['id_theme'] = $theme;
	}
	// The theme was specified by REQUEST... previously.
	elseif (!empty($_SESSION['id_theme']))
		$theme = (int) $_SESSION['id_theme'];
	// The theme is just the user's choice. (might use ?board=1;theme=0 to force board theme.)
	elseif (!empty($we_user_info['theme']) && !isset($_REQUEST['theme']))
		$theme = $we_user_info['theme'];
	// The theme is the forum's default.
	else
		$theme = $we_settings['theme_guests'];

	// Verify the id_theme... no foul play.
	if (!empty($we_settings['knownThemes']) && !empty($we_settings['theme_allow']))
	{
		$themes = explode(',', $we_settings['knownThemes']);
		if (!in_array($theme, $themes))
			$theme = $we_settings['theme_guests'];
		else
			$theme = (int) $theme;
	}
	else
		$theme = (int) $theme;

	$member = empty($we_user_info['id']) ? -1 : $we_user_info['id'];

	// Load variables from the current or default theme, global or this user's.
	$result = $smcFunc['db_query']('', '
		SELECT variable, value, id_member, id_theme
		FROM {raw:we_db_prefix}themes
		WHERE id_member IN (-1, 0, {int:current_member})
			AND id_theme' . ($theme == 1 ? ' = 1' : ' IN ({int:current_theme}, 1)'),
			array(
				'we_db_prefix' => $we_settings['db_prefix'],
				'current_member' => $member,
				'current_theme' => $theme,
		));
	// Pick between $we_settings['theme'] and $we_user_info['theme'] depending on whose data it is.
	$themeData = array(0 => array(), $member => array());
	while ($row = $smcFunc['db_fetch_assoc']($result))
	{
		// If this is the themedir of the default theme, store it.
		if (in_array($row['variable'], array('theme_dir', 'theme_url', 'images_url')) && $row['id_theme'] == '1' && empty($row['id_member']))
			$themeData[0]['default_' . $row['variable']] = $row['value'];

		// If this isn't set yet, is a theme option, or is not the default theme..
		if (!isset($themeData[$row['id_member']][$row['variable']]) || $row['id_theme'] != '1')
			$themeData[$row['id_member']][$row['variable']] = substr($row['variable'], 0, 5) == 'show_' ? $row['value'] == '1' : $row['value'];
	}
	$smcFunc['db_free_result']($result);

	$we_settings['theme'] = $themeData[0];
	$we_user_info['theme'] = $themeData[$member];

	if (!empty($themeData[-1]))
		foreach ($themeData[-1] as $k => $v)
		{
			if (!isset($we_user_info['theme'][$k]))
				$we_user_info['theme'][$k] = $v;
		}

	$we_settings['theme']['theme_id'] = $theme;

	$we_settings['theme']['actual_theme_url'] = $we_settings['theme']['theme_url'];
	$we_settings['theme']['actual_images_url'] = $we_settings['theme']['images_url'];
	$we_settings['theme']['actual_theme_dir'] = $we_settings['theme']['theme_dir'];
}

// Attempt to start the session, unless it already has been.
function we_loadSession()
{
	global $HTTP_SESSION_VARS, $we_connection, $we_settings, $we_user_info;

	// Attempt to change a few PHP settings.
	@ini_set('session.use_cookies', true);
	@ini_set('session.use_only_cookies', false);
	@ini_set('arg_separator.output', '&amp;');

	// If it's already been started... probably best to skip this.
	if ((@ini_get('session.auto_start') == 1 && !empty($we_settings['databaseSession_enable'])) || session_id() == '')
	{
		// Attempt to end the already-started session.
		if (@ini_get('session.auto_start') == 1)
			@session_write_close();

		// This is here to stop people from using bad junky PHPSESSIDs.
		if (isset($_REQUEST[session_name()]) && preg_match('~^[A-Za-z0-9]{32}$~', $_REQUEST[session_name()]) == 0 && !isset($_COOKIE[session_name()]))
			$_COOKIE[session_name()] = md5(md5('we_sess_' . time()) . mt_rand());

		// Use database sessions?
		if (!empty($we_settings['databaseSession_enable']) && $we_connection)
			session_set_save_handler('we_sessionOpen', 'we_sessionClose', 'we_sessionRead', 'we_sessionWrite', 'we_sessionDestroy', 'we_sessionGC');
		elseif (@ini_get('session.gc_maxlifetime') <= 1440 && !empty($we_settings['databaseSession_lifetime']))
			@ini_set('session.gc_maxlifetime', max($we_settings['databaseSession_lifetime'], 60));

		session_start();
	}

	// Set the randomly generated code.
	if (!isset($_SESSION['rand_code']))
		$_SESSION['rand_code'] = md5(session_id() . mt_rand());
	$we_user_info['session_id'] =& $_SESSION['rand_code'];

	if (!isset($_SESSION['USER_AGENT']))
		$_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}

function we_sessionOpen($save_path, $session_name)
{
	return true;
}

function we_sessionClose()
{
	return true;
}

function we_sessionRead($session_id)
{
	global $we_settings;

	if (preg_match('~^[a-zA-Z0-9,-]{16,32}$~', $session_id) == 0)
		return false;

	// Look for it in the database.
	$result = $smcFunc['db_query']('', '
		SELECT data
		FROM {raw:we_db_prefix}sessions
		WHERE session_id = {string:session_id}
		LIMIT 1',
		array(
			'we_db_prefix' => $we_settings['db_prefix'],
			'session_id' => $session_id,
		)
	);
	list ($sess_data) = $smcFunc['db_fetch_row']($result);
	$smcFunc['db_free_result']($result);

	return $sess_data;
}

function we_sessionWrite($session_id, $data)
{
	global $we_settings, $we_connection;

	if (preg_match('~^[a-zA-Z0-9,-]{16,32}$~', $session_id) == 0)
		return false;

	// First try to update an existing row...
	$result = $smcFunc['db_query']('', '
		UPDATE {raw:we_db_prefix}sessions
		SET data = {string:data}, last_update = {int:last_update}
		WHERE session_id = {string:session_id}',
		array(
			'we_db_prefix' => $we_settings['db_prefix'],
			'last_update' => time(),
			'data' => $data,
			'session_id' => $session_id,
		)
	);

	// If that didn't work, try inserting a new one.
	if ($smcFunc['db_affected_rows']() == 0)
		$result = $smcFunc['db_insert']('ignore',
			$we_settings['db_prefix'] . 'sessions',
			array('session_id' => 'string', 'data' => 'string', 'last_update' => 'int'),
			array($session_id, $data, time()),
			array('session_id')
		);

	return $result;
}

function we_sessionDestroy($session_id)
{
	global $we_settings;

	if (preg_match('~^[a-zA-Z0-9,-]{16,32}$~', $session_id) == 0)
		return false;

	// Just delete the row...
	return $smcFunc['db_query']('', '
		DELETE FROM {raw:we_db_prefix}sessions
		WHERE session_id = {string:session_id}',
		array(
			'we_db_prefix' => $we_settings['db_prefix'],
			'session_id' => $session_id,
		));
}

function we_sessionGC($max_lifetime)
{
	global $we_settings;

	// Just set to the default or lower?  Ignore it for a higher value. (hopefully)
	if ($max_lifetime <= 1440 && !empty($we_settings['databaseSession_lifetime']))
		$max_lifetime = max($we_settings['databaseSession_lifetime'], 60);

	// Clean up ;).
	return $smcFunc['db_query']('', '
		DELETE FROM {raw:we_db_prefix}sessions
		WHERE last_update < {int:old_sessions}',
		array(
			'we_db_prefix' => $we_settings['db_prefix'],
			'old_sessions' => time() - $max_lifetime,
		));
}

?>