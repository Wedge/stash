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
		'href_english' => 'http://www.wedgeforum.com/',
		'author_english' => 'Nao',

		'subject_french' => 'Projet Wedge lancé',
		'message_french' => 'Nao et Arantor sont fiers de vous annoncer le lancement du projet Wedge, un fork de SMF. Cette zone sera mise à jour dès qu\'une version publique sera disponible.',
		'href_french' => 'http://www.wedgeforum.com/',
		'author_french' => 'Nao',
	),

	array(
		'time' => 1233807001,

		'subject_english' => 'SMF 2.0 RC1 Public Released',
		'message_english' => 'Simple Machines are very pleased to announce the release of the first Release Candidate of SMF 2.0. Please visit the Simple Machines site for more information on how you can help test this new release.',
		'href_english' => 'http://www.simplemachines.org/community/index.php?topic=290609.0',
		'author_english' => 'Compuart',
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
