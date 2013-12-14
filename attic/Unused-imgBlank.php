<?php

/**
 * Shortcut for use in <img src="', src_blankGif(), '">
 */
function img_blankGif()
{
	if (!we::is('ie6,ie7'))
		return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
	// !!! Alternatively, the shorter non-standard 42-byte version, but it compresses slightly worse.
	//	return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAEALAAAAAABAAEAAAIBTAA7';

	return ASSETS . '/blank.gif';
}
