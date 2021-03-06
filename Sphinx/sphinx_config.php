<?php
/**
 * Handles installation and configuration of the Sphinx module.
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

if (!file_exists(dirname(__FILE__) . '/SSI.php'))
	exit('Please move this file to the main Wedge directory and make sure SSI.php is part of that directory.');

$sphinx_ver = '0.9.7-rc2';

require(dirname(__FILE__) . '/SSI.php');

// Kick the guests.
is_not_guest();

// Kick the non-admin
if (!we::$is_admin)
	exit('You need admin permission to use this tool.');

if (!isset($_REQUEST['step']))
	step_0();
else
{
	$cur_step = 'step_' . (int) $_REQUEST['step'];
	$cur_step();
}

function step_0()
{
	global $txt;

	template_sphinx_config_above('Introduction');

	echo '
	<p>
		This configuration tool is designed to guide you through the installation of the Sphinx full-text search engine, specifically for Wedge forums. Following the steps in this tool will tell how to install Sphinx, will configure Wedge for using Sphinx, and will create a configuration file that will be needed for Sphinx based on Wedge\'s settings. Make sure you have the latest version of this tool, so that the latest improvements have been implemented.
	</p>
	<h4>What is Sphinx?</h4>
	<p>
		Sphinx is an Open Source full-text search engine. It can index texts and find documents within fractions of seconds, a lot faster than MySQL. Sphinx consists of a few components:
	</p><p>
		There\'s the <em>indexer</em> that creates the full-text index from the existing tables in MySQL. The indexer is run as a cron job each time, allowing it to update the index once in a while. Based on the configuration file, the indexer knows how to connect to MySQL and which tables it needs to query.
	</p><p>
		Another important component is the search deamon (called <em>searchd</em>). This deamon runs as a process and awaits requests for information from the fulltext indexes. External processes, like the webserver, can send a query to it. The search deamon will then consult the index and return the result to the external process.
	</p>

	<h4>When should Sphinx be used for Wedge?</h4>
	<p>
		Basically Sphinx starts to get interesting when MySQL is unable to do the job of indexing the messages properly. In most cases, a board needs to have at least 300,000 messages before that point has been reached. Also if you want to make sure the search queries don\'t affect the database performance, you can choose to put Sphinx on a different server than the database server.
	</p>

	<h4>Requirements for Sphinx</h4>
	<ul>
		<li>Root access to the server you\'re installing Sphinx</li>
		<li>Linux 2.4.x+ / Windows 2000/XP / FreeBSD 4.x+ / NetBSD 1.6 (this tool will assume Linux as operating system)</li>
		<li>A working C++ compiler</li>
		<li>A good make program</li>
	</ul>
	<form action="' . $_SERVER['PHP_SELF'] . '?step=1" method="post">
		<div style="margin: 1ex; text-align: ', empty($txt['lang_rtl']) ? 'right' : 'left', ';">
			<input type="submit" value="Proceed" class="submit">
		</div>
	</form>
	';

	template_sphinx_config_below();
}

function step_1()
{
	global $sphinx_ver, $txt;

	template_sphinx_config_above('Installing Sphinx');

	echo '
	<p>
		This tool will assume you will be installing Sphinx version ', $sphinx_ver, '. A newer version might be available and, if so, would probably be better. Just understand that the steps below and the working of the search engine might be different in future versions of Sphinx.
	</p>
	<h4>Retrieving and unpacking the package</h4>

	Grab the file from the Sphinx website:<br>
	<tt>[~]#  wget http://www.sphinxsearch.com/downloads/sphinx-', $sphinx_ver, '.tar.gz</tt><br>
	<br>
	Untar the package:<br>
	<tt>[~]#  tar -xzvf sphinx-', $sphinx_ver, '.tar.gz</tt><br>
	<br>
	Go to the Sphinx directory:<br>
	<tt>[~]#  cd sphinx-', $sphinx_ver, '</tt>

	<h4>Editing the sources of Sphinx</h4>
	In the current version there are a few things that need to be changed before compiling Sphinx for a better result. Pick your favourite text editor and edit the following:<br>
	<br>

	<div style="background-color: white; overflow: auto; margin: 10px;">
		<small><strong>Find (src/sphinx.cpp):</strong></small><br>
		<pre>			if (!( GetPriority(iEntry) < GetPriority(iParent) ))</pre><br>
		<small><strong>Replace:</strong></small><br>
		<pre>			if ( !COMP::IsLess ( m_dMatches [ m_dIndexes[iEntry] ], m_dMatches [ m_dIndexes[iParent] ], m_tState ) )</pre>
	</div>

	<div style="background-color: white; overflow: auto; margin: 10px;">
		<small><strong>Find (src/sphinx.cpp):</strong></small><br>
		<pre>				if ( GetPriority(iChild+1) < GetPriority(iChild) )</pre><br>
		<small><strong>Replace:</strong></small><br>
		<pre>				if ( COMP::IsLess ( m_dMatches [ m_dIndexes[iChild+1] ], m_dMatches [ m_dIndexes[iChild] ], m_tState ) )</pre>
	</div>

	<div style="background-color: white; overflow: auto; margin: 10px;">
		<small><strong>Find (src/sphinx.cpp):</strong></small><br>
		<pre>			if ( GetPriority(iChild) < GetPriority(iEntry) )</pre><br>
		<small><strong>Replace:</strong></small><br>
		<pre>			if ( COMP::IsLess ( m_dMatches [ m_dIndexes[iChild] ], m_dMatches [ m_dIndexes[iEntry] ], m_tState ) )</pre>
	</div>

	<h4>Compiling Sphinx</h4>
	Configure Sphinx (generally no options are needed):<br>
	<tt>[~]#  ./configure</tt><br>
	<br>
	If everything went well, run the make tool:<br>
	<tt>[~]#  make</tt><br>
	<br>
	If that went well too, make the install:<br>
	<tt>[~]#  make install</tt><br>

	<form action="' . $_SERVER['PHP_SELF'] . '?step=2" method="post">
		<div style="margin: 1ex; text-align: ', empty($txt['lang_rtl']) ? 'right' : 'left', ';">
			<input type="submit" value="Proceed" class="submit">
		</div>
	</form>';
}

function step_2()
{
	global $context, $settings, $txt;

	template_sphinx_config_above('Configure Wedge for Sphinx');

	echo '
	A few settings can be configured allowing to customize the search engine. Generally all options can be left untouched.<br>
	<br>
	<form action="' . $_SERVER['PHP_SELF'] . '?step=3" method="post">
		<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 2ex;">
			<tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_data_path_input">Index data path:</label></td>
				<td>
					<input name="sphinx_data_path" id="sphinx_data_path_input" value="', isset($settings['sphinx_data_path']) ? $settings['sphinx_data_path'] : '/var/sphinx/data', '" size="65">
					<div style="font-size: smaller; margin-bottom: 2ex;">This is the path that will be containing the search index files used by Sphinx.</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_log_path_input">Log path:</label></td>
				<td>
					<input name="sphinx_log_path" id="sphinx_log_path_input" value="', isset($settings['sphinx_log_path']) ? $settings['sphinx_log_path'] : '/var/sphinx/log', '" size="65">
					<div style="font-size: smaller; margin-bottom: 2ex;">Server path that will contain the log files created by Sphinx.</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_stopword_path_input">Stopword path:</label></td>
				<td>
					<input name="sphinx_stopword_path" id="sphinx_stopword_path_input" value="', isset($settings['sphinx_stopword_path']) ? $settings['sphinx_stopword_path'] : '', '" size="65">
					<div style="font-size: smaller; margin-bottom: 2ex;">The server path to the stopword list (leave empty for no stopword list).</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_indexer_mem_input">Memory limit indexer:</label></td>
				<td>
					<input name="sphinx_indexer_mem" id="sphinx_indexer_mem_input" value="', isset($settings['sphinx_indexer_mem']) ? $settings['sphinx_indexer_mem'] : '32', '" size="4"> MB
					<div style="font-size: smaller; margin-bottom: 2ex;">The maximum amount of (RAM) memory the indexer is allowed to be using.</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_searchd_server_input">Search deamon server:</label></td>
				<td>
					<input name="sphinx_searchd_server" id="sphinx_searchd_server_input" value="', isset($settings['sphinx_searchd_server']) ? $settings['sphinx_searchd_server'] : 'localhost', '" size="65">
					<div style="font-size: smaller; margin-bottom: 2ex;">Server the Sphinx search deamon resides on.</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_searchd_port_input">Search deamon port:</label></td>
				<td>
					<input name="sphinx_searchd_port" id="sphinx_searchd_port_input" value="', isset($settings['sphinx_searchd_port']) ? $settings['sphinx_searchd_port'] : '3312', '" size="4">
					<div style="font-size: smaller; margin-bottom: 2ex;">Port on which the search deamon will listen.</div>
				</td>
			</tr><tr>
				<td width="20%" valign="top" class="textbox"><label for="sphinx_max_results_input">Maximum # matches:</label></td>
				<td>
					<input name="sphinx_max_results" id="sphinx_max_results_input" value="', isset($settings['sphinx_max_results']) ? $settings['sphinx_max_results'] : '2000', '" size="4">
					<div style="font-size: smaller; margin-bottom: 2ex;">Maximum amount of matches the search deamon will return.</div>
				</td>
			</tr>
		</table>
		<div style="margin: 1ex; text-align: ', empty($txt['lang_rtl']) ? 'right' : 'left', ';">
			<input type="submit" value="Proceed" class="submit">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</div>
	</form>';

	template_sphinx_config_below();
}

function step_3()
{
	global $context, $settings, $txt;

	checkSession();

	updateSettings(array(
		'sphinx_data_path' => rtrim($_POST['sphinx_data_path'], '/'),
		'sphinx_log_path' => rtrim($_POST['sphinx_log_path'], '/'),
		'sphinx_stopword_path' => $_POST['sphinx_stopword_path'],
		'sphinx_indexer_mem' => (int) $_POST['sphinx_indexer_mem'],
		'sphinx_searchd_server' => $_POST['sphinx_searchd_server'],
		'sphinx_searchd_port' => (int) $_POST['sphinx_searchd_port'],
		'sphinx_max_results' => (int) $_POST['sphinx_max_results'],
	));

	if (!isset($settings['sphinx_indexed_msg_until']))
		updateSettings(array(
			'sphinx_indexed_msg_until' => '1',
		));

	template_sphinx_config_above('Configure Wedge for Sphinx');
	echo '
	Your configuration has been saved successfully. The next time you run this tool, your configuration will automatically be loaded.
	<h4>Generating a configuration file</h4>
	Based on the settings you submitted in the previous screen, this tool can generate a configuration file for you that will be used by Sphinx. Press the button below to generate the configuration file, and upload it to /usr/local/etc/sphinx.conf (default configuration).<br>
	<br>
	<form action="' . $_SERVER['PHP_SELF'] . '?step=999" method="post" target="_blank">
		<input type="submit" value="Generate sphinx.conf" class="submit">
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
	</form><br>

	<h4>Some file actions</h4>
	Move Sphinx\' PHP API file to the Sources directory of your Wedge installation (the path used here is merely an example):<br>
	<tt>[~]#  mv api/sphinxapi.php /home/mydomain/public_html/wedge/Sources</tt><br>
	<br>
	Create directories for storing the indexes:<br>', strpos($settings['sphinx_data_path'], '/var/sphinx/') === false ? '' : '
	<tt>[~]#  mkdir /var/sphinx</tt><br>', '
	<tt>[~]#  mkdir ' . $settings['sphinx_data_path'] . '</tt><br>
	<tt>[~]#  mkdir ' . $settings['sphinx_log_path'] . '</tt><br>
	<br>
	Make the data and log directories writable:<br>
	<tt>[~]#  chmod 666 ' . $settings['sphinx_data_path'] . '</tt><br>
	<tt>[~]#  chmod 666 ' . $settings['sphinx_log_path'] . '</tt><br>

	<h4>Indexing time!</h4>
	It\'s time to create the full-text index:<br>
	<tt>[~]#  indexer --config /usr/local/etc/sphinx.conf --all</tt><br>
	<br>
	If that went successful, we can test run the search deamon. Start it by typing:<br>
	<tt>[~]#  searchd --config /usr/local/etc/sphinx.conf</tt><br>
	<br>
	If everything worked so far, congratulations, Sphinx has been installed and works! Next step is modifying Wedge\'s search to work with Sphinx.

	<h4>Configuring Wedge</h4>
	Upload the package file to the \'Packages\' directory and apply it in Wedge\'s package manager. (Note, this is totally wrong. Wedge uses plugins.)<br><br>
	Select \'Sphinx\' as database index below and press \'Change Search Index\'. Test your search function afterwards, it should work now!<br>
	<br>
	<form action="' . $_SERVER['PHP_SELF'] . '?step=888" method="post" target="_blank">
		<select name="search_index">
			<option value=""', empty($settings['search_index']) ? ' selected' : '', '>(None)</option>
			<option value="fulltext"', !empty($settings['search_index']) && $settings['search_index'] === 'fulltext' ? ' selected' : '', '>Fulltext</option>
			<option value="custom"', !empty($settings['search_index']) && $settings['search_index'] === 'custom' ? ' selected' : '', '>Custom index</option>
			<option value="sphinx"', !empty($settings['search_index']) && $settings['search_index'] === 'sphinx' ? ' selected' : '', '>Sphinx</option>
		</select>
		<input type="submit" value="Change Search Index" class="submit">
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
	</form><br>
	<br>

	<h4>Creating a cron job for the indexer</h4>
	In order to keep the full-text index up to date, you need to add a cron job that will update the index from time to time. The configuration file defines two indexes: <tt>wedge_delta_index</tt>, an index that only stores the recent changes and can be called frequently.  <tt>wedge_base_index</tt>, an index that stores the full database and should be called less frequently.

	Adding the following lines to /etc/crontab would let the index rebuild every day (at 3 am) and update the most recently changed messages each hour:<br>
	<tt># search indexer<br>
	10 3 * * * /usr/local/bin/indexer --config /usr/local/etc/sphinx.conf --rotate wedge_base_index<br>
	0 * * * * /usr/local/bin/indexer --config /usr/local/etc/sphinx.conf --rotate wedge_delta_index</tt><br>';

	template_sphinx_config_below();
}

function step_888()
{
	checkSession();

	if (in_array($_REQUEST['search_index'], array('', 'custom', 'sphinx')))
		updateSettings(array(
			'search_index' => $_REQUEST['search_index'],
		));

	echo 'Setting has been saved. This window can be closed.';
}

function step_999()
{
	global $context, $db_server, $db_name, $db_user, $db_passwd, $db_prefix, $settings;

	$humungousTopicPosts = 200;

	ob_end_clean();
	header('Pragma: ');
	if (!we::is('gecko'))
		header('Content-Transfer-Encoding: binary');
	header('Connection: close');
	header('Content-Disposition: attachment; filename="sphinx.conf"');
	header('Content-Type: ' . (we::is('ie,opera') ? 'application/octetstream' : 'application/octet-stream'));

	$weight_factors = array(
		'age',
		'length',
		'first_message',
		'pinned',
	);
	$weight = array();
	$weight_total = 0;
	foreach ($weight_factors as $weight_factor)
	{
		$weight[$weight_factor] = empty($settings['search_weight_' . $weight_factor]) ? 0 : (int) $settings['search_weight_' . $weight_factor];
		$weight_total += $weight[$weight_factor];
	}

	if ($weight_total === 0)
	{
		$weight = array(
			'age' => 25,
			'length' => 25,
			'first_message' => 25,
			'pinned' => 25,
		);
		$weight_total = 100;
	}

	echo '#
# Sphinx configuration file (sphinx.conf), configured for Wedge.
#
# By default the location of this file would probably be:
# /usr/local/etc/sphinx.conf

source wedge_source
{
	type = mysql
	strip_html = 1
	sql_host = ', $db_server, '
	sql_user = ', $db_user, '
	sql_pass = ', $db_passwd, '
	sql_db = ', $db_name, '
	sql_port = 3306
	sql_query_pre = SET NAMES utf8
	sql_query_pre =	\
		REPLACE INTO ', $db_prefix, 'settings (variable, value) \
		SELECT \'sphinx_indexed_msg_until\', MAX(id_msg) \
		FROM ', $db_prefix, 'messages
	sql_query_range = \
		SELECT 1, value \
		FROM ', $db_prefix, 'settings \
		WHERE variable = \'sphinx_indexed_msg_until\'
	sql_range_step = 1000
	sql_query =	\
		SELECT \
			m.is_msg, m.id_topic, m.id_board, IF(m.id_member = 0, 4294967295, m.id_member) AS id_member, m.poster_time, m.body, m.subject, \
			t.num_replies + 1 AS num_replies, CEILING(1000000 * ( \
				IF(m.id_msg < 0.7 * s.value, 0, (m.id_msg - 0.7 * s.value) / (0.3 * s.value)) * ' . $weight['age'] . ' + \
				IF(t.num_replies < 200, t.num_replies / 200, 1) * ' . $weight['length'] . ' + \
				IF(m.id_msg = t.id_first_msg, 1, 0) * ' . $weight['first_message'] . ' + \
				IF(t.is_pinned = 0, 0, 1) * ' . $weight['pinned'] . ' \
			) / ' . $weight_total . ') AS relevance \
		FROM ', $db_prefix, 'messages AS m, ', $db_prefix, 'topics AS t, ', $db_prefix, 'settings AS s \
		WHERE t.id_topic = m.id_topic \
			AND s.variable = \'maxMsgID\' \
			AND m.id_msg BETWEEN $start AND $end
	sql_group_column = id_topic
	sql_group_column = id_board
	sql_group_column = id_member
	sql_date_column = poster_time
	sql_date_column = relevance
	sql_date_column = num_replies
	sql_query_info = \
		SELECT * \
		FROM ', $db_prefix, 'messages \
		WHERE id_msg = $id
}

source wedge_delta_source : wedge_source
{
	sql_query_pre = SET NAMES utf8
	sql_query_range = \
		SELECT s1.value, s2.value \
		FROM ', $db_prefix, 'settings AS s1, ', $db_prefix, 'settings AS s2 \
		WHERE s1.variable = \'sphinx_indexed_msg_until\' \
			AND s2.variable = \'maxMsgID\'
}

index wedge_base_index
{
	source = wedge_source
	path = ', $settings['sphinx_data_path'], '/wedge_sphinx_base.index', empty($settings['sphinx_stopword_path']) ? '' : '
	stopwords = ' . $settings['sphinx_stopword_path'], '
	min_word_len = 2
	charset_type = utf-8
	charset_table = 0..9, A..Z->a..z, _, a..z
}

index wedge_delta_index : wedge_base_index
{
	source = wedge_delta_source
	path = ', $settings['sphinx_data_path'], '/wedge_sphinx_delta.index
}

index wedge_index
{
	type = distributed
	local = wedge_base_index
	local = wedge_delta_index
}

indexer
{
	mem_limit = ', (int) $settings['sphinx_indexer_mem'], 'M
}

searchd
{
	port = ', (int) $settings['sphinx_searchd_port'], '
	log = ', $settings['sphinx_log_path'], '/searchd.log
	query_log = ', $settings['sphinx_log_path'], '/query.log
	read_timeout = 5
	max_children = 30
	pid_file = ', $settings['sphinx_data_path'], '/searchd.pid
	max_matches = 1000
}
';

	flush();
}

function template_sphinx_config_above($title)
{
	echo '<!DOCTYPE html>
<html>
<head>
	<title>Wedge Sphinx Configuration Utility</title>',
	theme_base_js(1),
	theme_base_css(), '
</head>
<body>
	<div id="header">
		<a href="http://wedge.org/" target="_blank"><img id="wedgelogo" src="', ASSETS, '/wedgelogo.png" alt="Wedge"></a>
		<div title="A wedge is like a pyramid...">Wedge Sphinx Configuration Utility</div>
	</div>
	<div id="content">
		<table class="w100 cp0 cs0" style="padding-top: 1ex;">
		<tr>
			<td class="top">
				<div class="panel">
					<h2>', $title, '</h2>';
}

function template_sphinx_config_below()
{

	echo '
				</div>
			</td>
		</tr>
	</table>
	</div>
</body>
</html>';
}
