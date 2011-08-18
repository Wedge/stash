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

global $context, $boarddir;

if (file_exists($boarddir . '/MGalleryItem.php'))
	@chmod($boarddir . '/MGalleryItem.php', 0644);

echo '<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<title>Media Database Installer</title>',
	theme_base_css(), '
</head>
<body>';

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
	<span style="font-weight: bold; color: green">Success!</span>

	<br><br>You should now go to the Admin area, Members section, and head to the
	<a href="index.php?action=admin;area=media_perms;', $context['session_query'], '" style="text-decoration: underline">Media Permissions</a>
	section. Create and manage your permission profiles and apply them to your albums. No one will be able to access the gallery until you enable permissions.

	<br><br><strong>If you\'re experiencing errors</strong> "Method Not Implemented", "403" or "406" when using Aeva Media, you probably need to disable mod_security (an Apache module). Please open your package file and read the instructions in the <strong>mod_security.htaccess</strong> file.
	<br><br>If it doesn\'t work for you, you\'re out of luck. Ask your host to help you, or disable whatever feature doesn\'t work.
</div>
</td></tr></table>
<br>
</body></html>';

?>
