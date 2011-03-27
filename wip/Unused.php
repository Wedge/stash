<?php

/**
 * Shortcut for use in <img src="', src_blankGif(), '">
 */
function img_blankGif()
{
	global $browser;
	if (!$browser['is_ie8down'] || $browser['is_ie8'])
		return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
	// !!! Alternatively, the shorter non-standard 42-byte version, but it compresses slightly worse.
	//	return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAEALAAAAAABAAEAAAIBTAA7';

	global $settings;
	return $settings['images_url'] . '/blank.gif';
}

?>
