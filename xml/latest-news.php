<?php

// !!! Needs serious rewriting!

header('Content-Type: text/javascript');

if (empty($_GET['format']))
	$_GET['format'] = '%B %d, %Y, %I:%M:%S %p';

$latest_news = array(
	array(
		'time' => 1282748400,

		'subject_english' => 'Wedge Project Started',
		'message_english' => 'Nao and Arantor are very pleased to announce they started working on Wedge, a fork of SMF. This spot will be updated as soon as a usable version is out.',
		'href_english' => 'http://wedge.org/',
		'author_english' => 'Nao',

		'subject_french' => 'Projet Wedge lancé',
		'message_french' => 'Nao et Arantor sont fiers de vous annoncer le lancement du projet Wedge, un fork de SMF. Cette zone sera mise à jour dès qu\'une version publique sera disponible.',
		'href_french' => 'http://wedge.org/',
		'author_french' => 'Nao',
	),

	array(
		'time' => 1233807001,

		'subject_english' => 'SMF 2.0 RC1 Public Released',
		'message_english' => 'Simple Machines are very pleased to announce the release of the first Release Candidate of SMF 2.0. Please visit the Simple Machines site for more information on how you can help test this new release.',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=290609.0',
		'author_english' => 'Compuart',
	)
);

echo '
window.wedgeAnnouncements = [';

$format = isset($_GET['format']) ? $_GET['format'] : '%e %B %Y';
$language = isset($_GET['language']) ? $_GET['language'] : 'english';
$format = str_replace(
	array('%b',				'%h',				'%B',			'%a',			'%A'),
	array('$shortmonth-%m',	'$shortmonth-%m',	'$month-%m',	'$shortday-%w',	'$day-%w'),
	$format
);

for ($i = 0, $n = count($latest_news); $i < $n; $i++)
{
	echo '
	{
		subject: \'', addslashes(isset($latest_news[$i]['subject_' . $language]) ? $latest_news[$i]['subject_' . $language] : $latest_news[$i]['subject_english']), '\',
		href: \'', addslashes(isset($latest_news[$i]['href_' . $language]) ? $latest_news[$i]['href_' . $language] : $latest_news[$i]['href_english']), '\',
		time: \'', addslashes(strftime($format, $latest_news[$i]['time'])), '\',
		author: \'', addslashes(isset($latest_news[$i]['author_' . $language]) ? $latest_news[$i]['author_' . $language] : $latest_news[$i]['author_english']), '\',
		message: \'', addslashes(isset($latest_news[$i]['message_' . $language]) ? $latest_news[$i]['message_' . $language] : $latest_news[$i]['message_english']), '\'
	}';

	if ($i != $n - 1)
		echo ',';
}

echo '
];';

/*
	Area for putting possible future update information, you can set the following variables.

		window.smfUpdateNotice: Override the default window notice.
		window.smfUpdatePackage: Name of the update package to use.
		window.smfUpdateTitle: Override default title display in window.
		window.smfUpdateCritical: If set will make the notice displayed red (or critical for the theme.)

	Note: In the smfUpdateNotice message, an element should exist with the id update-link.

	Example:

*/

/*
if (false && window.smfVersion < "1.0") // !!!
{
	window.smfUpdateNotice = 'Wedge 1.0 Final has now been released. To take advantage of the improvements available in Wedge 1.0, we recommend upgrading as soon as possible.';
	window.smfUpdateCritical = false;
}

if (document.getElementById("yourVersion"))
{
	var yourVersion = document.getElementById("yourVersion").innerHTML;
	if (yourVersion == "1.0.4")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-0-5_package.tar.gz";
	}
}

*/

?>

if (document.getElementById('credits'))
	document.getElementById('credits').innerHTML = document.getElementById('credits').innerHTML.replace(/anyone we may have missed/, '<span title="And you thought you had escaped the credits, hadn\'t you, Zef Hemel?">anyone we may have missed</span>');
