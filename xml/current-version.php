<?php

/*	All links to this file will include $_GET['version'], which either:
		- will contain 'SVN'. (Not currently the case though...)
		- will be '0.1' or higher.

	After some very normal period of time, the script should quite possibly
	start logging the referring URL - this will help us find people using
	older versions, and try to convince them to upgrade.
	(Note from Nao: err... WHAT?!)
*/

// Try to make sure this is kept up to date every time it loads.
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: Wed, 25 Aug 2010 17:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');

header('Content-Type: text/javascript');

if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	list ($modified_since) = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if (strtotime($modified_since) >= filemtime(__FILE__))
	{
		header('HTTP/1.1 304 Not Modified');
		exit;
	}
}

echo 'window.weVersion = "0.1";';
