<?php
/****************************************************************
* Aeva Media													*
* © Noisen.com & SMF-Media.com									*
*****************************************************************
* db_aeva.php - database installer								*
*****************************************************************
* Users of this software are bound by the terms of the			*
* Aeva Media license. You can view it in the license_am.txt		*
* file, or online at http://noisen.com/license-am2.php			*
*																*
* For support and updates, go to http://aeva.noisen.com			*
****************************************************************/

// This is the DB installer/upgrader for Aeva Media
// This runs standalone if it's in the same directory as SSI.php. It may also run via the Package Manager
// Does edits for both SMF 1.1 and SMF 2.0

// OK, first load the stuff
global
	$smcFunc, $db_prefix, $db_type, $db_name, $db_passwd, $db_user, $db_server,
	$context, $boarddir, $modSettings, $scripturl, $boardurl, $boarddir, $sourcedir;

$doing_manual_install = false;
$no_prefix = array('no_prefix' => true);
$primary_groups = array(0 => 0);

if (!defined('SMF') && file_exists(dirname(__FILE__) . '/SSI.php'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
	$doing_manual_install = true;
}
elseif (!defined('SMF'))
	die('The installer wasn\'t able to connect to SMF! Make sure that you are either installing this via the Package Manager or the SSI.php file is in the same directory.');

if (isset($_GET['delete']))
{
	@unlink(__FILE__);

	// From SMF
	header('Location: http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']) . dirname($_SERVER['PHP_SELF']) . '/Themes/default/images/blank.gif');
	exit;
}

if (file_exists($boarddir . '/MGalleryItem.php'))
	@chmod($boarddir . '/MGalleryItem.php', 0644);

if ($doing_manual_install)
	echo '<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<title>Aeva Media Database Installer</title>',
	theme_base_css(), '
</head>
<body>
	<br><br>';

// Step 1 -- Install Aeva auto-embedder variables
$update = array(
	'embed_enabled' => 1, // Auto-embedding enabled by default
	'media_enabled' => 1, // Gallery enabled by default
	'embed_lookups' => 1,
	'embed_max_per_post' => 12,
	'embed_max_per_page' => 12,
	'embed_yq' => 0,
	'embed_titles' => 0,
	'embed_inlinetitles' => 1,
	'embed_noscript' => 0,
	'embed_expins' => 1,
	'embed_quotes' => 0,
	'embed_incontext' => 1,
	'embed_fix_html' => 1,
	'embed_includeurl' => 1,
	'embed_debug' => 0,
	'embed_adult' => 0,
	'embed_nonlocal' => 0,
	'embed_mp3' => 0,
	'embed_flv' => 0,
	'embed_avi' => 0,
	'embed_divx' => 0,
	'embed_mov' => 0,
	'embed_wmp' => 0,
	'embed_real' => 0,
	'embed_swf' => 0,
);

foreach ($update as $name => $value)
	wesql::insert('ignore', '{db_prefix}settings', array('variable' => 'string', 'value' => 'string'), array($name, $value));

// wesql::query("DELETE FROM {db_prefix}settings WHERE (variable LIKE 'aevac_%') OR (variable IN ('aeva_quicktime', 'aeva_windowsmedia', 'aeva_realmedia', 'aeva_flash', 'aeva_youtube', 'aeva_ytitles', 'aeva_hq', 'aeva_quality', 'aeva_nossi', 'aeva_copyright', 'aeva_latest_version', 'aeva_version_test'))");

// Some variables
$aevaprefix = '{db_prefix}media_';
// The new settings
$newsettings = array(
	'installed_on' => time(),
	'data_dir_path' => $boarddir . '/media',
	'data_dir_url' => $boardurl . '/media',
	'max_dir_files' => '150',
	'num_items_per_page' => '15',
	'max_dir_size' => '51400',
	'max_file_size' => '1024',
	'max_width' => '2048',
	'max_height' => '1536',
	'allow_over_max' => '1',
	'upload_security_check' => '0',
	'jpeg_compression' => '80',
	'num_unapproved_items' => '0',
	'num_unapproved_albums' => '0',
	'num_unapproved_comments' => '0',
	'num_unapproved_item_edits' => '0',
	'num_unapproved_album_edits' => '0',
	'num_reported_items' => '0',
	'num_reported_comments' => '0',
	'recent_item_limit' => '5',
	'random_item_limit' => '5',
	'recent_comments_limit' => '10',
	'recent_albums_limit' => '10',
	'total_items' => '0',
	'total_comments' => '0',
	'total_albums' => '0',
	'total_contests' => '0',
	'show_sub_albums_on_index' => '1',
	'enable_re-rating' => '0',
	'use_metadata_date' => '1',
	'max_thumb_width' => '120',
	'max_thumb_height' => '120',
	'max_preview_width' => '500',
	'max_preview_height' => '500',
	'max_bigicon_width' => '200',
	'max_bigicon_height' => '200',
	'max_thumbs_per_page' => '100',
	'max_title_length' => '30',
	'show_extra_info' => '1',
	'entities_convert' => '0',
	'clear_thumbnames' => '1',
	'image_handler' => 1,
	'enable_cache' => 0,
	'use_zoom' => 1,
	'show_linking_code' => 1,
	'album_edit_unapprove' => 1,
	'item_edit_unapprove' => 1,
	'album_columns' => '1',
	'disable_rss' => 0,
	'disable_playlists' => 0,
	'disable_comments' => 0,
	'disable_ratings' => 0,
	'my_docs' => 'txt,rtf,pdf,xls,doc,ppt,docx,xlsx,pptx,xml,html,htm,php,css,js,zip,rar,ace,arj,7z,gz,tar,tgz,bz,bzip2,sit',
);
$altered_mem = array();
$table_names = array('albums', 'comments', 'fields', 'field_data', 'files', 'log_media', 'log_ratings', 'media', 'perms', 'quotas', 'settings', 'variables');

// Get the table list
$tables = array();
wesql::extend();
wesql::extend('packages');
$tmp = wedbExtra::list_tables();
foreach ($tmp as $t)
	if (substr($db_prefix, 0, strlen($db_name) + 3) != '`' . $db_name . '`.')
		$tables[] = $t;
	else
		$tables[] = '`' . $db_name . '`.' . $t;

foreach ($table_names as $name)
	if (!in_array($db_prefix . 'media_' . $name, $tables) && in_array($db_prefix . 'aeva_' . $name, $tables))
		wesql::query('ALTER TABLE {db_prefix}aeva_' . $name . ' RENAME TO {db_prefix}media_' . $name, array());
foreach ($table_names as $name)
	if (!in_array($db_prefix . 'media_' . $name, $tables) && in_array($db_prefix . 'mgallery_' . $name, $tables))
		wesql::query('ALTER TABLE {db_prefix}mgallery_' . $name . ' RENAME TO {db_prefix}media_' . $name, array());
if (!in_array($db_prefix . 'media_playlists', $tables) && in_array($db_prefix . 'mgallery_playlists', $tables))
	wesql::query('ALTER TABLE {db_prefix}mgallery_playlists RENAME TO {db_prefix}media_playlists', array());
if (!in_array($db_prefix . 'media_playlist_data', $tables) && in_array($db_prefix . 'mgallery_playlist_data', $tables))
	wesql::query('ALTER TABLE {db_prefix}mgallery_playlist_data RENAME TO {db_prefix}media_playlist_data', array());

wesql::query('
	UPDATE IGNORE {db_prefix}permissions
	SET permission = REPLACE(permission, {string:mgallery}, {string:aeva})
', array('mgallery' => 'mgallery_', 'aeva' => 'aeva_'));

// Create the tables

// The media_items table
wedbPackages::create_table(
	'{db_prefix}media_items',
	array(
		array('name' => 'id_media', 'type' => 'INT', 'auto' => true),
		array('name' => 'id_member', 'type' => 'INT', 'default' => 0),
		array('name' => 'member_name', 'type' => 'VARCHAR', 'default' => '', 'size' => '25'),
		array('name' => 'last_edited', 'type' => 'INT', 'default' => 0),
		array('name' => 'last_edited_by', 'type' => 'INT', 'default' => 0),
		array('name' => 'last_edited_name', 'type' => 'TEXT'),
		array('name' => 'id_file', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_thumb', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_preview', 'type' => 'INT', 'default' => 0),
		array('name' => 'type', 'type' => 'VARCHAR', 'size' => '10', 'default' => 'image'),
		array('name' => 'album_id', 'type' => 'INT', 'default' => 0),
		array('name' => 'rating', 'type' => 'INT', 'default' => 0),
		array('name' => 'voters', 'type' => 'MEDIUMINT', 'default' => 0),
		array('name' => 'weighted', 'type' => 'FLOAT', 'default' => 0),
		array('name' => 'title', 'type' => 'VARCHAR', 'size' => '255', 'default' => '(No title)'),
		array('name' => 'description', 'type' => 'TEXT'),
		array('name' => 'approved', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
		array('name' => 'time_added', 'type' => 'INT', 'default' => '0'),
		array('name' => 'views', 'type' => 'INT', 'default' => '0'),
		array('name' => 'downloads', 'type' => 'INT', 'default' => '0'),
		array('name' => 'last_viewed', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
		array('name' => 'keywords', 'type' => 'TEXT'),
		array('name' => 'embed_url', 'type' => 'TEXT'),
		array('name' => 'id_last_comment', 'type' => 'INT', 'default' => '0'),
		array('name' => 'log_last_access_time', 'type' => 'INT', 'default' => '0'),
		array('name' => 'num_comments', 'type' => 'INT', 'default' => '0'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_media')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_items';

// The media_files table
wedbPackages::create_table(
	'{db_prefix}media_files',
	array(
		array('name' => 'id_file', 'type' => 'INT', 'auto' => true),
		array('name' => 'filesize', 'type' => 'INT', 'size' => '20', 'default' => '0'),
		array('name' => 'filename', 'type' => 'TEXT'),
		array('name' => 'width', 'type' => 'INT', 'default' => '1', 'size' => '4'),
		array('name' => 'height', 'type' => 'INT', 'default' => '1', 'size' => '4'),
		array('name' => 'directory', 'type' => 'TEXT'),
		array('name' => 'id_album', 'type' => 'INT', 'default' => '0', 'size' => '20'),
		array('name' => 'transparency', 'type' => 'ENUM(\'\', \'transparent\', \'opaque\')', 'default' => ''),
		array('name' => 'meta', 'type' => 'TEXT'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_file')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_files';

// The media_albums table
wedbPackages::create_table(
	'{db_prefix}media_albums',
	array(
		array('name' => 'id_album', 'type' => 'INT', 'auto' => true),
		array('name' => 'album_of', 'type' => 'INT', 'default' => '0'),
		array('name' => 'featured', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
		array('name' => 'name', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'description', 'type' => 'TEXT'),
		array('name' => 'master', 'type' => 'INT', 'default' => '0'),
		array('name' => 'icon', 'type' => 'INT', 'default' => '0'),
		array('name' => 'bigicon', 'type' => 'INT', 'default' => '0'),
		array('name' => 'passwd', 'type' => 'VARCHAR', 'size' => '64', 'default' => ''),
		array('name' => 'directory', 'type' => 'TEXT'),
		array('name' => 'parent', 'type' => 'INT', 'default' => '0'),
		array('name' => 'access', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'access_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'approved', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
		array('name' => 'a_order', 'type' => 'INT', 'default' => '0'),
		array('name' => 'child_level', 'type' => 'INT', 'default' => '0'),
		array('name' => 'id_last_media', 'type' => 'INT', 'default' => '0'),
		array('name' => 'num_items', 'type' => 'INT', 'default' => '0'),
		array('name' => 'options', 'type' => 'TEXT'),
		array('name' => 'id_perm_profile', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_quota_profile', 'type' => 'INT', 'default' => 0),
		array('name' => 'hidden', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
		array('name' => 'allowed_members', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'allowed_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'denied_members', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'denied_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''),
		array('name' => 'id_topic', 'type' => 'INT', 'default' => '0'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_album')
		),
		array(
			'columns' => array('album_of')
		),
		array(
			'columns' => array('id_album', 'album_of', 'featured')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_albums';

// The media_settings table
wedbPackages::create_table(
	'{db_prefix}media_settings',
	array(
		array('name' => 'name', 'type' => 'VARCHAR', 'size' => '30', 'default' => ''),
		array('name' => 'value', 'type' => 'TEXT'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('name')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_settings';

// The media_variables table
wedbPackages::create_table(
	'{db_prefix}media_variables',
	array(
		array('name' => 'id', 'type' => 'INT', 'auto' => true),
		array('name' => 'type', 'type' => 'VARCHAR', 'size' => '15', 'default' => ''),
		array('name' => 'val1', 'type' => 'TEXT'),
		array('name' => 'val2', 'type' => 'TEXT'),
		array('name' => 'val3', 'type' => 'TEXT'),
		array('name' => 'val4', 'type' => 'TEXT'),
		array('name' => 'val5', 'type' => 'TEXT'),
		array('name' => 'val6', 'type' => 'TEXT'),
		array('name' => 'val7', 'type' => 'TEXT'),
		array('name' => 'val8', 'type' => 'TEXT'),
		array('name' => 'val9', 'type' => 'TEXT'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_variables';

// The media_comments table
wedbPackages::create_table(
	'{db_prefix}media_comments',
	array(
		array('name' => 'id_comment', 'type' => 'INT', 'default' => '', 'auto' => true),
		array('name' => 'id_member', 'type' => 'INT', 'default' => '0'),
		array('name' => 'id_media', 'type' => 'INT', 'default' => '0'),
		array('name' => 'id_album', 'type' => 'INT', 'default' => '0'),
		array('name' => 'message', 'type' => 'TEXT'),
		array('name' => 'posted_on', 'type' => 'INT', 'default' => '0'),
		array('name' => 'last_edited', 'type' => 'INT', 'default' => '0'),
		array('name' => 'last_edited_by', 'type' => 'INT', 'default' => '0'),
		array('name' => 'last_edited_name', 'type' => 'VARCHAR', 'size' => '25', 'default' => ''),
		array('name' => 'approved', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_comment')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_comments';

// The media_log_media table
wedbPackages::create_table(
	'{db_prefix}media_log_media',
	array(
		array('name' => 'id_media', 'type' => 'INT', 'default' => '0'),
		array('name' => 'id_member', 'type' => 'INT', 'default' => '0'),
		array('name' => 'time', 'type' => 'INT', 'default' => '0'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_media', 'id_member')
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_log_media';

// The media_log_ratings table
wedbPackages::create_table(
	'{db_prefix}media_log_ratings',
	array(
		array('name' => 'id_media', 'type' => 'INT', 'default' => '0'),
		array('name' => 'id_member', 'type' => 'INT', 'default' => '0'),
		array('name' => 'rating', 'type' => 'INT', 'default' => '0'),
		array('name' => 'time', 'type' => 'INT', 'default' => '0')
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_media', 'id_member'),
		)
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_log_ratings';

// The permissions table
wedbPackages::create_table(
	'{db_prefix}media_perms',
	array(
		array('name' => 'id_group', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_profile', 'type' => 'INT', 'default' => 0),
		array('name' => 'permission', 'type' => 'VARCHAR', 'size' => 255, 'default' => ''),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_group', 'id_profile', 'permission'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_perms';

// The member group quotas table
wedbPackages::create_table(
	'{db_prefix}media_quotas',
	array(
		array('name' => 'id_profile', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_group', 'type' => 'INT', 'default' => 0),
		array('name' => 'type', 'type' => 'VARCHAR', 'size' => 10, 'default' => ''),
		array('name' => 'quota', 'type' => 'INT', 'default' => 0),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_profile', 'id_group', 'type'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_quotas';

// The custom fields table
wedbPackages::create_table(
	'{db_prefix}media_fields',
	array(
		array('name' => 'id_field', 'type' => 'INT', 'auto' => true, 'default' => 0),
		array('name' => 'name', 'type' => 'VARCHAR', 'size' => 100, 'default' => ''),
		array('name' => 'type', 'type' => 'VARCHAR', 'size' => 20, 'default' => 'text'),
		array('name' => 'options', 'type' => 'TEXT'),
		array('name' => 'required', 'type' => 'TINYINT', 'size' => 1, 'default' => 0),
		array('name' => 'searchable', 'type' => 'TINYINT', 'size' => 1, 'default' => 0),
		array('name' => 'description', 'type' => 'TEXT'),
		array('name' => 'bbc', 'type' => 'TINYINT', 'size' => 1, 'default' => 0),
		array('name' => 'albums', 'type' => 'TEXT'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_field'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_fields';

// The custom field's data table
wedbPackages::create_table(
	'{db_prefix}media_field_data',
	array(
		array('name' => 'id_field', 'type' => 'INT', 'default' => 0),
		array('name' => 'id_media', 'type' => 'INT', 'default' => 0),
		array('name' => 'value', 'type' => 'TEXT'),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_field', 'id_media'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_field_data';

// Foxy! playlists
wedbPackages::create_table(
	'{db_prefix}media_playlists',
	array(
		array('name' => 'id_playlist', 'type' => 'int', 'unsigned' => true, 'size' => 11, 'auto' => true),
		array('name' => 'id_member', 'type' => 'int', 'unsigned' => true, 'size' => 10, 'default' => 0),
		array('name' => 'name', 'type' => 'varchar', 'size' => 80, 'default' => ''),
		array('name' => 'description', 'type' => 'text'),
		array('name' => 'views', 'type' => 'int', 'unsigned' => true, 'size' => 11, 'default' => 0),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_playlist'),
		),
		array(
			'type' => 'key',
			'name' => 'name',
			'columns' => array('name'),
		),
		array(
			'type' => 'key',
			'name' => 'views',
			'columns' => array('views'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_playlists';

// Foxy! playlist contents
wedbPackages::create_table(
	'{db_prefix}media_playlist_data',
	array(
		array('name' => 'id_playlist', 'type' => 'int', 'unsigned' => true, 'size' => 11, 'default' => 0),
		array('name' => 'id_media', 'type' => 'int', 'unsigned' => true, 'size' => 10, 'default' => 0),
		array('name' => 'play_order', 'type' => 'int', 'unsigned' => true, 'size' => 5, 'auto' => true),
		array('name' => 'description', 'type' => 'text'),
	),
	array(
		array(
			'type' => 'primary',
			// play_order needs to be in *second* position (not 1st, not 3rd),
			// so that secondary (i.e. per-playlist) auto-increment will trigger.
			'columns' => array('id_playlist', 'play_order', 'id_media'),
		),
	),
	$no_prefix,
	'ignore'
);
$created_tables[] = '{db_prefix}media_playlist_data';

$mem_columns = wedbPackages::list_columns($db_prefix . 'members', false, $no_prefix);
$file_columns = wedbPackages::list_columns('{db_prefix}media_files', false, $no_prefix);
$album_columns = wedbPackages::list_columns('{db_prefix}media_albums', false, $no_prefix);
$media_columns = wedbPackages::list_columns('{db_prefix}media_items', false, $no_prefix);
$field_columns = wedbPackages::list_columns('{db_prefix}media_fields', false, $no_prefix);
$quota_columns = wedbPackages::list_columns('{db_prefix}media_quotas', false, $no_prefix);

if (!in_array('description', $field_columns))
	wesql::query('ALTER TABLE {db_prefix}media_fields CHANGE `desc` description TEXT NOT NULL', array());
if (!in_array('quota', $quota_columns))
	wesql::query('ALTER TABLE {db_prefix}media_quotas CHANGE `limit` quota INT NOT NULL DEFAULT 0', array());

if (!in_array('id_perm_profile', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'id_perm_profile', 'type' => 'INT', 'default' => 0), $no_prefix);
if (!in_array('id_quota_profile', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'id_quota_profile', 'type' => 'INT', 'default' => 0), $no_prefix);
if (!in_array('hidden', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'hidden', 'type' => 'TINYINT', 'size' => '1', 'default' => 0), $no_prefix);
if (!in_array('allowed_members', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'allowed_members', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''), $no_prefix);
if (!in_array('allowed_write', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'allowed_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''), $no_prefix);
if (!in_array('denied_members', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'denied_members', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''), $no_prefix);
if (!in_array('denied_write', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'denied_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''), $no_prefix);
if (!in_array('id_topic', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'id_topic', 'type' => 'INT', 'default' => 0), $no_prefix);
if (!in_array('bigicon', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'bigicon', 'type' => 'INT', 'default' => 0), $no_prefix);
if (!in_array('access_write', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'access_write', 'type' => 'VARCHAR', 'size' => '255', 'default' => ''), $no_prefix);
if (!in_array('master', $album_columns))
{
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'master', 'type' => 'INT', 'default' => 0), $no_prefix);
	wedbPackages::add_index('{db_prefix}media_albums', array('name' => 'id_master', 'columns' => array('master')), $no_prefix);
	wesql::query('UPDATE {db_prefix}media_albums SET master = id_album WHERE parent = 0', array());
	$alb = array();
	$continue = true;
	$unstick = 0;
	while ($continue)
	{
		// This may very well crash on SQLite and maybe PGSQL... Ah, who cares?
		wesql::query('
			UPDATE {db_prefix}media_albums AS a1, {db_prefix}media_albums AS a2
			SET a1.master = a2.master
			WHERE (a1.parent = a2.id_album) AND (a1.master = 0) AND (a2.master != 0)',
			array());
		$continue = (wesql::affected_rows() > 0) && ($unstick++ < 100);
	}
}
if (!in_array('featured', $album_columns))
{
	// Retrieve all non-admin primary groups used by members...
	$request = wesql::query('SELECT id_group FROM {db_prefix}members GROUP BY id_group ORDER BY id_group', array());
	while ($row = wesql::fetch_row($request))
		$primary_groups[(int) $row[0]] = (int) $row[0];
	wesql::free_result($request);
	unset($primary_groups[1]);

	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'featured', 'type' => 'TINYINT', 'size' => '1', 'default' => '0'), $no_prefix);
	wesql::query('
		UPDATE {db_prefix}media_albums
		SET featured = 1, album_of = 1, access_write = {string:write}
		WHERE type = {string:general}',
		array(
			'general' => 'general',
			'write' => implode(',', array_keys($primary_groups)),
		)
	);
	wedbPackages::add_index('{db_prefix}media_albums', array('name' => 'id_of', 'columns' => array('id_album', 'album_of', 'featured')), $no_prefix);
	wedbPackages::remove_column('{db_prefix}media_albums', 'type', $no_prefix);
}
else
	wesql::query('UPDATE {db_prefix}media_albums SET album_of = 1 WHERE album_of = 0', array());
if (in_array('member_name', $album_columns))
	wedbPackages::remove_column('{db_prefix}media_albums', 'member_name', $no_prefix);

// If mgal_* fields are in there, rename them. Otherwise, just create the media_* fields.
if (!in_array('media_items', $mem_columns))
{
	if (in_array('mgal_total_items', $mem_columns))
		wedbPackages::change_column($db_prefix . 'members', 'mgal_total_items', array('name' => 'media_items', 'type' => 'INT', 'null' => null, 'default' => 0), $no_prefix);
	else
		wedbPackages::add_column($db_prefix . 'members', array('name' => 'media_items', 'type' => 'INT', 'null' => null, 'default' => '0'), $no_prefix);
	$altered_mem[1] = true;
}
if (!in_array('media_comments', $mem_columns))
{
	if (in_array('mgal_total_comments', $mem_columns))
		wedbPackages::change_column($db_prefix . 'members', 'mgal_total_comments', array('name' => 'media_comments', 'type' => 'INT', 'null' => null, 'default' => 0), $no_prefix);
	else
		wedbPackages::add_column($db_prefix . 'members', array('name' => 'media_comments', 'type' => 'INT', 'null' => null, 'default' => '0'), $no_prefix);
	$altered_mem[2] = true;
}
if (!in_array('media_unseen', $mem_columns))
{
	if (in_array('mgal_unseen', $mem_columns))
		wedbPackages::change_column($db_prefix . 'members', 'mgal_unseen', array('name' => 'media_unseen', 'type' => 'INT', 'null' => null, 'default' => -1), $no_prefix);
	else
		wedbPackages::add_column($db_prefix . 'members', array('name' => 'media_unseen', 'type' => 'INT', 'null' => null, 'default' => '-1'), $no_prefix);
	$altered_mem[3] = true;
}

// I'd rather use a TEXT field, but if SELECT @@sql_mode returns a strict mode, it may cause issues...
if (!in_array('misc', $mem_columns))
{
	wedbPackages::add_column($db_prefix . 'members', array('name' => 'misc', 'type' => 'VARCHAR', 'size' => '255', 'null' => null, 'default' => ''), $no_prefix);
	$altered_mem[4] = true;
}

if (!in_array('transparency', $file_columns))
{
	wedbPackages::add_column('{db_prefix}media_files', array('name' => 'transparency', 'type' => 'ENUM(\'\', \'transparent\', \'opaque\')', 'default' => ''), $no_prefix);
	wesql::query('
		UPDATE {db_prefix}media_files SET transparency = {string:transparent} WHERE id_file < 5', array('transparent' => 'transparent'));
}
if (!in_array('meta', $file_columns))
	wedbPackages::add_column('{db_prefix}media_files', array('name' => 'meta', 'type' => 'TEXT'), $no_prefix);
if (!in_array('options', $album_columns))
	wedbPackages::add_column('{db_prefix}media_albums', array('name' => 'options', 'type' => 'TEXT'), $no_prefix);
if (!in_array('id_preview', $media_columns))
{
	wedbPackages::add_column('{db_prefix}media_items', array('name' => 'id_preview', 'type' => 'INT', 'default' => '0'), $no_prefix);
	wedbPackages::change_column('{db_prefix}media_items', 'type', array('type' => 'VARCHAR', 'size' => '10', 'default' => 'image'), $no_prefix);
}
if (!in_array('downloads', $media_columns))
{
	wedbPackages::add_column('{db_prefix}media_items', array('name' => 'downloads', 'type' => 'INT', 'default' => '0'), $no_prefix);
	wedbPackages::change_column('{db_prefix}media_albums', 'access', array('size' => 255), $no_prefix);
}
if (!in_array('weighted', $media_columns))
	wedbPackages::add_column('{db_prefix}media_items', array('name' => 'weighted', 'type' => 'FLOAT', 'default' => '0'), $no_prefix);
wedbPackages::change_column('{db_prefix}media_items', 'title', array('size' => 255), $no_prefix);
wedbPackages::change_column('{db_prefix}media_albums', 'name', array('size' => 255), $no_prefix);
wedbPackages::change_column('{db_prefix}media_settings', 'value', array('type' => 'TEXT'), $no_prefix);

$media_keys = wedbPackages::list_indexes('{db_prefix}media_items', false, $no_prefix);
// id_thumb index is needed for the Check Orphans maintenance task.
if (!in_array('id_thumb', $media_keys))
	wedbPackages::add_index('{db_prefix}media_items', array('name' => 'id_thumb', 'columns' => array('id_thumb')), $no_prefix);
if (!in_array('time_added', $media_keys))
	wedbPackages::add_index('{db_prefix}media_items', array('name' => 'time_added', 'columns' => array('time_added')), $no_prefix);
if (!in_array('album_id', $media_keys))
	wedbPackages::add_index('{db_prefix}media_items', array('name' => 'album_id', 'columns' => array('album_id')), $no_prefix);

// Permissions processing...
if (!in_array($db_prefix . 'media_perms', $tables) && !in_array($db_prefix . 'mgallery_perms', $tables))
{
	// Insert a brand new profile
	wesql::insert(
		'ignore',
		'{db_prefix}media_variables',
		array(
			'type' => 'string',
			'val1' => 'string',
		),
		array(
			'perm_profile',
			'Default',
		)
	);
	$id_profile = wesql::insert_id('{db_prefix}media_variables');

	// Bypass to a small issue
	if ($id_profile == 1)
	{
		$id_profile = 2;
		wesql::query('
			UPDATE {db_prefix}media_variables
			SET id = 2
			WHERE id = 1',
			array()
		);
		// I think only MySQL supports the AUTO_INCREMENT setting.
		if (!empty($db_type) && $db_type === 'mysql')
			wesql::query('
				ALTER TABLE {db_prefix}media_variables AUTO_INCREMENT = 3', array());
	}

	// Get existing permissions
	$request = wesql::query('
		SELECT permission, add_deny, id_group
		FROM {db_prefix}permissions',
		array()
	);
	$removals = array();
	$perms = array();
	while ($row = wesql::fetch_assoc($request))
	{
		if (!in_array($row['permission'], array('media_download_item', 'media_add_videos', 'media_add_audios', 'media_add_images', 'media_add_embeds', 'media_add_docs', 'media_rate_items', 'media_edit_own_com', 'media_edit_own_item', 'media_comment', 'media_report_item', 'media_report_com', 'media_auto_approve_com', 'media_auto_approve_item', 'media_multi_upload', 'media_multi_download', 'media_whoratedwhat')))
			continue;

		if (!isset($perms[$row['id_group']]))
			$perms[$row['id_group']] = array();
		if (!isset($removals[$row['id_group']]))
			$removals[$row['id_group']] = array();

		if (empty($row['add_deny']))
			$removals[$row['id_group']][] = substr($row['permission'], 9);
		else
			$perms[$row['id_group']][] = substr($row['permission'], 9);
	}
	wesql::free_result($request);

	if (!empty($modSettings['permission_enable_deny']))
		foreach ($perms as $group => $permarray)
			$perms[$group] = array_diff($perms[$group], $removals[$group]);

	// Insert it to the profile
	foreach ($perms as $group => $permArray)
		foreach ($permArray as $perm)
			wesql::insert('ignore',
				'{db_prefix}media_perms',
				array(
					'id_profile' => 'int',
					'id_group' => 'int',
					'permission' => 'string',
				),
				array(
					$id_profile,
					$group,
					$perm,
				)
			);

	// Update the albums - old style, so we're considering featured albums as 'general'
	wesql::query('
		UPDATE {db_prefix}media_albums
		SET id_perm_profile = {int:profile}
		WHERE featured = 1',
		array(
			'profile' => $id_profile,
		)
	);
	wesql::query('
		UPDATE {db_prefix}media_albums
		SET id_perm_profile = 1
		WHERE featured = 0',
		array()
	);
}
// Import the settings now
$setting_entries = array();
foreach ($newsettings as $name => $value)
{
	wesql::insert('ignore', '{db_prefix}media_settings', array('name' => 'string', 'value' => 'string'), array($name, $value));
	if (wesql::affected_rows() > 0)
		$setting_entries[] = $name;
}

// Insert the mandatory data
wesql::query('DELETE FROM {db_prefix}media_files WHERE id_file <= 4');
wesql::query('DELETE FROM {db_prefix}media_settings WHERE name = \'doc_files\'');
wesql::query('DELETE FROM {db_prefix}media_settings WHERE name = \'version\'');

wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(1, 'music.png', 4118, 'generic_images', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(2, 'film.png', 2911, 'generic_images', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(3, 'camera.png', 2438, 'generic_images', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(4, 'folder.png', 2799, 'generic_images', 48, 48, 0, '')
);

$request = wesql::query('
	SELECT value FROM {db_prefix}media_settings WHERE name = {string:data_dir}',
	array('data_dir' => 'data_dir_path'));
list ($data_dir) = wesql::fetch_row($request);
$data_dir .= '/generic_images/';
$ex_data_dir = $boarddir . '/media/generic_images/';
$cam = $data_dir . 'camera.png';
if ((!file_exists($cam) || filesize($cam) == 665) && filesize($ex_data_dir . 'camera.png') == 2438)
{
	@copy($ex_data_dir . 'camera.png', $data_dir . 'camera.png');
	@copy($ex_data_dir . 'film.png', $data_dir . 'film.png');
	@copy($ex_data_dir . 'music.png', $data_dir . 'music.png');
	@copy($ex_data_dir . 'folder.png', $data_dir . 'folder.png');
}
wesql::free_result($request);

if (file_exists($sourcedir . '/Aeva-Subs-Vital.php'))
{
	require_once($sourcedir . '/Aeva-Subs-Vital.php');
	if (function_exists('media_allowed_types'))
	{
		$aty = media_allowed_types();
		$aty['do'][] = 'default';
		foreach ($aty['do'] as $ty)
			if (!file_exists($data_dir . $ty . '.png') && file_exists($ex_data_dir . $ty . '.png'))
				@copy($ex_data_dir . $ty . '.png', $data_dir . $ty . '.png');
	}
}

// OK, time to report, output all the stuff to be shown to the user
echo '
<table class="center tborder" style="width: 550px"><tr><td>
<div class="titlebg" style="padding: 1ex">
	Aeva Media Database Installer
</div>
<div class="windowbg2 wrc">';

// Tell them what has been done
echo '<b>Creating / Updating Tables</b>
<br>
<ul class="normallist">';
$my_db_prefix = preg_replace('/`[^`]+`\./', '', $db_prefix);
foreach ($created_tables as $table_name)
{
	$table_name = str_replace('{db_prefix}', $db_prefix, $table_name);
	if (in_array($table_name, $tables))
		echo '
	<li>Table <i>'.$table_name.'</i> already exists.</li>';
	else
		echo '
	<li>Table <i>'.$table_name.'</i> created.</li>';
}
if (isset($altered_mem[1]))
	echo '
	<li>Altered '.$my_db_prefix.'members table, added field "media_items".</li>';
if (isset($altered_mem[2]))
	echo '
	<li>Altered '.$my_db_prefix.'members table, added field "media_comments".</li>';
if (isset($altered_mem[3]))
	echo '
	<li>Altered '.$my_db_prefix.'members table, added field "media_unseen".</li>';
if (isset($altered_mem[4]))
	echo '
	<li>Altered '.$my_db_prefix.'members table, added field "aeva" (stores user settings).</li>';

echo '
</ul>

<b>Initializing settings</b><br>
<ul class="normallist">';

if (count($setting_entries) == count($newsettings))
	echo '
	<li>All ', count($setting_entries), ' records have been inserted into the settings table.</li>';
else
	echo '
	<li>', empty($setting_entries) ? 'No' : count($setting_entries), ' new record(s) have been inserted into the settings table.</li>', !empty($setting_entries) ? '
	<li>Full list: <span style="color: #888">' . implode(', ', $setting_entries) . '</span></li>' : '';

echo '
</ul>
<div style="padding-top: 25px">
	<span style="font-weight: bold; color: green">Your database update has been completed successfully!</span>

	<br><br><strong>If this is the first time you install the gallery</strong>, you should now go to the Admin area, Members section, and head to the
	<a href="', $scripturl, '?action=admin;area=media_perms;', $context['session_query'], '" style="text-decoration: underline">Aeva Media Permissions</a>
	section. Create and manage your permission profiles and apply them to your albums. No one will be able to access the gallery until you enable permissions.

	<br><br><strong>If you\'re experiencing errors</strong> "Method Not Implemented", "403" or "406" when using Aeva Media, you probably need to disable mod_security (an Apache module). Please open your package file and read the instructions in the <strong>mod_security.htaccess</strong> file.
	<br><br>If it doesn\'t work for you, you\'re out of luck. Ask your host to help you, or disable whatever feature doesn\'t work.';

if ($doing_manual_install && (is_writable(dirname(__FILE__)) || is_writable(__FILE__)))
	echo '
	<br><br>
	<label for="delete_self"><input type="checkbox" id="delete_self" onclick="doTheDelete(this);"> Delete this file.</label> <i>(doesn\'t work on all servers.)</i>
	<script><!-- // --><![CDATA[
		function doTheDelete(theCheck)
		{
			document.getElementById("delete_upgrader").src = "', $_SERVER['PHP_SELF'], '?delete=1&ts_" + (new Date().getTime());
			theCheck.disabled = true;
		}
	// ]]></script>
	<img src="', $boardurl, '/Themes/default/images/blank.gif" alt="" id="delete_upgrader">';

echo '
	<br><br><b>Thank you for trying out Aeva Media!</b>
</div>
<div style="font-size: 9px; margin-top: 20px;" class="centertext">
	Aeva Media &copy; <a href="http://noisen.com/">noisen</a> / <a href="http://smf-media.com">smf-media</a>
</div>
</div>
</td></tr></table>
<br>';

if ($doing_manual_install)
	echo '
</body></html>';

?>
