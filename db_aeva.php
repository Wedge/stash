<?php
/**
 * Wedge
 *
 * Creation of media-related database tables.
 * Original code by Dragoooon and Nao.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

global
	$db_prefix, $db_name, $context, $boarddir, $modSettings, $scripturl, $boardurl, $boarddir;

$doing_manual_install = false;
$no_prefix = array('no_prefix' => true);

if (!defined('SMF') && file_exists(dirname(__FILE__) . '/SSI.php'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
	$doing_manual_install = true;
}
elseif (!defined('SMF'))
	die('The installer wasn\'t able to connect to Wedge! Make sure that you are either installing this via the Package Manager or the SSI.php file is in the same directory.');

if (isset($_GET['delete']))
{
	@unlink(__FILE__);

	header('Location: http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']) . dirname($_SERVER['PHP_SELF']) . '/Themes/default/images/blank.gif');
	exit;
}

if (file_exists($boarddir . '/MGalleryItem.php'))
	@chmod($boarddir . '/MGalleryItem.php', 0644);

if ($doing_manual_install)
	echo '<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<title>Media Database Installer</title>',
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

// Create the tables

// Media items
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

// Media files
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

// Media albums
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
		// Linked topics... Maybe it's pointless storing them in here?
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

// Item comments
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

// Permissions
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

// Membergroup quotas
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

// Custom fields
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

// Data table for custom fields
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

// Playlists
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

// Playlist contents
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

// Get the table list
$tables = array();
wesql::extend();
wesql::extend('packages');
$tmp = wedbExtra::list_tables();
foreach ($tmp as $t)
	$tables[] = substr($db_prefix, 0, strlen($db_name) + 3) != '`' . $db_name . '`.' ? $t : '`' . $db_name . '`.' . $t;

// Permissions processing...
if (!in_array($db_prefix . 'media_perms', $tables))
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
	$id_profile = wesql::insert_id();

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
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(1, 'music.png', 4118, 'icons', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(2, 'film.png', 2911, 'icons', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(3, 'camera.png', 2438, 'icons', 48, 48, 0, '')
);
wesql::insert('',
	'{db_prefix}media_files',
	array('id_file' => 'int', 'filename' => 'string-255', 'filesize' => 'int', 'directory' => 'string-255', 'width' => 'int', 'height' => 'int', 'id_album' => 'int', 'meta' => 'string-255'),
	array(4, 'folder.png', 2799, 'icons', 48, 48, 0, '')
);

echo '
<table class="center tborder" style="width: 550px"><tr><td>
<div class="titlebg" style="padding: 1ex">
	Aeva Media Database Installer
</div>
<div class="windowbg2 wrc">
	<span style="font-weight: bold; color: green">Your database update has been completed successfully!</span>

	<br><br><strong>If this is the first time you install the gallery</strong>, you should now go to the Admin area, Members section, and head to the
	<a href="', $scripturl, '?action=admin;area=media_perms;', $context['session_query'], '" style="text-decoration: underline">Media Permissions</a>
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
</div>
</td></tr></table>
<br>';

if ($doing_manual_install)
	echo '
</body></html>';

?>
