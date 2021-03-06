<?php

// Look a bit for SSI.php...
if (is_file(dirname(__FILE__) . '/SSI.php'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (is_file(dirname(dirname(__FILE__)) . '/SSI.php'))
	require_once(dirname(dirname(__FILE__)) . '/SSI.php');
else
	exit('SSI.php wasn\'t found - please put this in your Wedge directory.');

// Load Subs-Package for the parse functions.
loadSource('Subs-Package');

// Show the page's header - down below.
show_header();

// A file was uploaded... test it out.
if (isset($_FILES['mod_file']['name']) && $_FILES['mod_file']['name'] != '' && is_uploaded_file($_FILES['mod_file']['tmp_name']))
{
	// A boardmod format file?
	if (substr($_FILES['mod_file']['name'], -4) == '.mod')
		$actions = parseBoardmod(@implode('', @file($_FILES['mod_file']['tmp_name'])), true, false);
	// Oh... an xml style one?
	else
		$actions = parseModification(@implode('', @file($_FILES['mod_file']['tmp_name'])), true, false);
}
// Path to the file... easier.
elseif (isset($_REQUEST['mod_file']))
{
	if (substr($_REQUEST['mod_file'], -4) == '.mod')
		$actions = parseBoardmod(@implode('', @file($_REQUEST['mod_file'])), true, false);
	else
		$actions = parseModification(@implode('', @file($_REQUEST['mod_file'])), true, false);
}

// No actions, eh?  Guess they need to pick a file.
if (empty($actions))
{
	echo '
	<div class="panel">
		<h2>Testing Modifications</h2>
		<h4>This tool is a development utility for testing xml style modification files and making sure they work as planned.  It does not require that you package it up, just that you type the path in below.</h4>

		<form action="', $_SERVER['PHP_SELF'], '" method="post" enctype="multipart/form-data">
			Path to file: <input name="mod_file" size="40" value=""><br />
			<br />
			Upload it instead: <input type="file" name="mod_file" size="40"><br />
			<br />
			<div class="righttext" style="margin: 1ex;"><input type="submit" value="Test it!" class="submit" /></div>
		</form>
	</div>';
}
// Ah, I see the results have come in....
else
{
	echo '
	<div class="panel">
		<h2>Test Results</h2>
		<h4>The ', isset($_FILES['mod_file']['name']) ? 'file you uploaded' : $_REQUEST['mod_file'] . ' file', ' was parsed and the following results were returned.</h4>

		<ol>';

	// List out each action and its details.
	foreach ($actions as $action)
	{
		if ($action['type'] == 'chmod')
			continue;

		if ($action['type'] == 'opened')
			echo '
			<li>The file ', $action['filename'], ' was opened for output.</li>';
		elseif ($action['type'] == 'saved')
			echo '
			<li>The file ', $action['filename'], ' was saved.<br /><br /></li>';
		elseif ($action['type'] == 'append')
			echo '
			<li>An append operation in ', $action['filename'], ' succeeded.</li>';
		elseif ($action['type'] == 'replace')
			echo '
			<li>A search and replace operation in ', $action['filename'], ' succeeded.</li>';
		elseif ($action['type'] == 'error')
			echo '
			<li>', $action['debug'], '</li>';
		elseif ($action['type'] == 'missing')
			echo '
			<li>', $action['debug'], ' (', $action['filename'], ')</li>';
		elseif ($action['type'] == 'failure')
			echo '
			<li><strong>The following could not be found (or should not have been) in ', $action['filename'], ':</strong> <em>(note that it may not look like you typed it, this is normal.)</em><br />
			<pre style="width: 98%; overflow: auto; border: 1px solid red;">', $action['search'], '</pre></li>';
		elseif ($action['type'] == 'result')
		{
			echo '
		</ol>

		', $action['status'] ? 'The modification would have installed properly, had this not been a test.' : '
		<strong>Warning:</strong> This modification had errors in it!';
			$done = true;
		}
	}

	// If there was no "done" message, we need to close the <ol> - just a nitpicky html thing.
	if (empty($done))
		echo '
		</ol>';

	echo '
		<br />
		<br />
		<h2>Test again?</h2>
		<h4></h4>';

	// If it wasn't uploaded, we can give a link to try again ;).
	if (!empty($_REQUEST['mod_file']))
		echo '
		You can <a href="', $_SERVER['PHP_SELF'], '?mod_file=', $_REQUEST['mod_file'], '">test this modification file again</a>, or you can <a href="', $_SERVER['PHP_SELF'], '">go back and test another</a>.';
	else
		echo '
		You might want to <a href="', $_SERVER['PHP_SELF'], '">go back and test another modification file</a>.';

	echo '
	</div>';
}

// Okay, done.
show_footer();

function show_header()
{
	echo '<!DOCTYPE html>
<html>
<head>
	<title>Wedge Package SDK</title>
	<script src="', TEMPLATES, '/scripts/script.js"></script>
	<style><!--
		body
		{
			font-family: Verdana, sans-serif;
			background-color: #d4d4d4;
			margin: 0;
		}
		body, td
		{
			font-size: 10pt;
		}
		div#header
		{
			background-color: white;
			padding: 22px 4% 12px 4%;
			font-family: Georgia, serif;
			font-size: xx-large;
			border-bottom: 1px solid black;
			height: 40px;
		}
		div#content
		{
			padding: 20px 30px;
		}
		div.error_message
		{
			border: 2px dashed red;
			background-color: #e1e1e1;
			margin: 1ex 4ex;
			padding: 1.5ex;
		}
		div.panel
		{
			border: 1px solid gray;
			background-color: #f0f0f0;
			margin: 1ex 0;
			padding: 1.2ex;
		}
		div.panel h2
		{
			margin: 0;
			margin-bottom: 0.5ex;
			padding-bottom: 3px;
			border-bottom: 1px dashed black;
			font-size: 14pt;
			font-weight: normal;
		}
		div.panel h4
		{
			margin: 0;
			margin-bottom: 2ex;
			font-size: 10pt;
			font-weight: normal;
		}
		form
		{
			margin: 0;
		}
		.righttext
		{
			margin-left: auto;
			margin-right: 0;
			text-align: right;
		}
	--></style>
</head>
<body>
	<div id="header">
		<a href="http://wedge.org/" target="_blank"><img id="wedgelogo" src="', ASSETS, '/wedgelogo.png" style="float: right;" alt="Wedge" /></a>
		<div>Wedge Package SDK</div>
	</div>
	<div id="content">';
}

function show_footer()
{
	echo '
	</div>
</body>
</html>';
}
