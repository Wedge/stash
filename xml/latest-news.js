<?php

header('Content-Type: text/javascript');

if (empty($_GET['format']))
	$_GET['format'] = '%B %d, %Y, %I:%M:%S %p';

$latest_news = array(
	array(
		'time' => 1233807001,

		'subject_english' => 'SMF 2.0 RC1 Public Released',
		'message_english' => 'Simple Machines are very pleased to annouce the release of the first Release Candidate of SMF 2.0. Please visit the Simple Machines site for more information on how you can help test this new release.',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=290609.0',
		'author_english' => 'Compuart',
	),

	array(
		'time' => 1188041365,

		'subject_english' => 'SMF 2.0 Beta 1 Released to Charter Members',
		'message_english' => 'Simple Machines are pleased to announce the first beta of SMF 2.0 has been released to our Charter Members. Visit the Simple Machines site for information on what\'s new',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=190812.0',
		'author_english' => 'Grudge',
	),

	array(
		'time' => 1120005510,

		'subject_english' => 'SMF 1.1 Beta 3 Public',
		'message_english' => 'The first public beta of SMF 1.1 has been released!  Please read the announcement for details - and only update if you are certain you are comfortable with beta software.  There is no package manager style update for this version.',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=40085.0',
		'author_english' => '[Unknown]',

		'subject_finnish' => 'SMF 1.1 Beta 3 Public',
		'message_finnish' => 'Ensimmäinen julkinen beta SMF 1.1:stä on julkaistu! Ole hyvä ja lue tiedotteesta tarkemmin - ja päivitä vain jos olet varma että haluat käyttää beta vaiheessa olevaa ohjelmistoa.  Tähän versioon ei voi päivittää pakettien hallinnan kautta.',

		'subject_french' => 'SMF 1.1 Beta 3 &Eacute;dition Publique',
		'message_french' => 'La premi&egrave;re version beta publique de SMF 1.1 est sortie&nbsp;!  Veuillez lire le sujet d\'annonces pour plus de d&eacute;tails - et veuillez ne mettre &agrave; jour votre forum que si vous &ecirc;tes confortable avec les logiciels en version de test.  Il n\'y a aucune mise &agrave; jour via le Gestionnaire de paquets possible pour cette version.',

		'subject_german' => 'SMF 1.1 Beta 3 Public',
		'message_german' => 'Die erste &ouml;ffentliche Beta von SMF 1.1 steht zum Download bereit! Bitte lesen Sie das Ank&uuml;ndigungsthema f&uuml;r weitere Informationen und aktualisieren Sie Ihr Forum nur, wenn Sie sich mit Beta Software gen&uuml;gend auskennen! F&uuml;r diese Version gibt es kein Paket-Manager Update.',
	),

	array(
		'time' => 1082579416,

		'subject_english' => 'SMF 1.0 Beta 5 for Charter Members',
		'message_english' => 'Beta 5 is now ready for out Charter Members.  It includes a lot of changes and new features, so be sure to upgrade as soon as possible!',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=9850.0',
		'author_english' => '[Unknown]',

		'subject_german' => 'SMF 1.0 Beta 5 f&uuml;r Charter Members',
		'message_german' => 'Beta 5 steht den Charter Members zur Verf&uuml;gung. Sie enth&auml;lt eine Vielzahl an Ver&auml;nderungen und neuen Funktionen. Bitte aktualisieren Sie Ihre Version so bald wie m&ouml;glich!',

		'subject_spanish' => 'SMF 1.0 Beta 5 para Charter Members',
		'message_spanish' => 'Beta 5 est&aacute; ahora lista para fuera los Miembros de la Carta constitucional.  ¡Incluye muchos cambios y los nuevos rasgos, as&iacute; que est&eacute; seguro actualizar lo m&aacute;s pronto posible!',
	)
);

echo '
window.smfAnnouncements = [';

for ($i = 0, $n = count($latest_news); $i < $n; $i++)
{
	echo '
	{
		subject: \'', addslashes(isset($latest_news[$i]['subject_' . @$_GET['language']]) ? $latest_news[$i]['subject_' . @$_GET['language']] : $latest_news[$i]['subject_english']), '\',
		href: \'', addslashes(isset($latest_news[$i]['href_' . @$_GET['language']]) ? $latest_news[$i]['href_' . @$_GET['language']] : $latest_news[$i]['href_english']), '\',
		time: \'', addslashes(strftime(@$_GET['format'], $latest_news[$i]['time'])), '\',
		author: \'', addslashes(isset($latest_news[$i]['author_' . @$_GET['language']]) ? $latest_news[$i]['author_' . @$_GET['language']] : $latest_news[$i]['author_english']), '\',
		message: \'', addslashes(isset($latest_news[$i]['message_' . @$_GET['language']]) ? $latest_news[$i]['message_' . @$_GET['language']] : $latest_news[$i]['message_english']), '\'
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

if (window.smfVersion < "SMF 1.1" && window.smfVersion != "SMF 1.0.4")
{
	window.smfUpdateNotice = 'Please <a href="" id="update-link">update now</a>';
	window.smfUpdatePackage = "http://custom.simplemachines.org/mods/download/" + window.smfVersion.replace(/[\. ]/g, "_") + "_to_1-0-4.tar.gz";
	window.smfUpdateTitle = "Update Title!";
	window.smfUpdateCritical = true;
}

*/

?>

if (window.smfVersion < "SMF 1.1")
{
	window.smfUpdateNotice = 'SMF 1.1 Final has now been released. To take advantage of the improvements available in SMF 1.1 we recommend upgrading as soon as is practical.';
	window.smfUpdateCritical = false;
}

if (document.getElementById("yourVersion"))
{
	var yourVersion = document.getElementById("yourVersion").innerHTML;
	if (yourVersion == "SMF 1.0.4")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-0-5_package.tar.gz";
	}
	else if (yourVersion == "SMF 1.0.5" || yourVersion == "SMF 1.0.6")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.7_1.1-RC2-1.tar.gz";
		window.smfUpdateCritical = false;
	}
	else if (yourVersion == "SMF 1.0.7")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-0-8_package.tar.gz";
	}
	else if (yourVersion == "SMF 1.0.8")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1-0-9_1-1-rc3-1.tar.gz";
	}
	else if (yourVersion == "SMF 1.0.9")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-0-10_patch.tar.gz";
	}
	else if (yourVersion == "SMF 1.0.10" || yourVersion == "SMF 1.1.2")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.1.3_1.0.11.tar.gz";
	}
	else if (yourVersion == "SMF 1.0.11" || yourVersion == "SMF 1.1.3" || yourVersion == "SMF 2.0 beta 1")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.12_1.1.4_2.0.b1.1.tar.gz";
		window.smfUpdateCritical = true;
	}
	else if (yourVersion == "SMF 1.0.12" || yourVersion == "SMF 1.1.4" || yourVersion == "SMF 2.0 beta 3 Public")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.13_1.1.5_2.0-b3.1.zip";
	}
	else if (yourVersion == "SMF 1.0.13" || yourVersion == "SMF 1.1.5")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.14_1.1.6.zip";
		window.smfUpdateCritical = true;
	}
	else if (yourVersion == "SMF 1.0.14" || yourVersion == "SMF 1.1.6")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.15_1.1.7.zip";
		window.smfUpdateCritical = true;
	}
	else if (yourVersion == "SMF 1.0.15" || yourVersion == "SMF 1.1.7")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_patch_1.0.16_1.1.8.zip";
		window.smfUpdateCritical = false;
	}
	else if (yourVersion == "SMF 1.1")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-1-1_patch.tar.gz";
	}
	else if (yourVersion == "SMF 1.1.1")
	{
		window.smfUpdatePackage = "http://custom.simplemachines.org/mods/downloads/smf_1-1-2_patch.tar.gz";
	}
}

if (document.getElementById('credits'))
	document.getElementById('credits').innerHTML = document.getElementById('credits').innerHTML.replace(/anyone we may have missed/, '<span title="And you thought you had escaped the credits, hadn\'t you, Zef Hemel?">anyone we may have missed</span>');
