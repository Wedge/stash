<?php

/*	As of 1.0's release, all links to this file will include $_GET['version'], which either:
		- will contain 'CVS'.
		- will be 'SMF 1.1' or higher.
		- will be 'SMF 1.0'.

	If this is not set (most likely) or is lower than 'SMF 1.0' (very unlikely, right now)
	the forum is old!  After some very normal period of time, the script should quite
	possibly start logging the referring URL - this will help us find people using older
	versions, and try to convince them to upgrade.
*/

// Try to make sure this is kept up to date every time it loads.
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');

header('Content-Type: text/javascript');

if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	list($modified_since) = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if (strtotime($modified_since) >= filemtime(__FILE__))
	{
		header('HTTP/1.1 304 Not Modified');
		die;
	}
}

echo 'window.smfVersion = "Wedge 0.1 (SMF 2.0 RC4)";';

?>