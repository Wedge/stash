<?php

// !!! This needs serious rewriting IF WE'RE EVER GOING TO USE THIS AT ALL!

/*	To make this work, we just need to do a few things.

	- load basic information for several packages, such that they can
	  "navigate" around to look at them in the panel.
	- give a better offering by checking window.weInstalledPackages
	  which holds the ids of all installed packages.
	- remember that we need to have control on the color scheme
	  (white on black, etc.); we've got the element, so we can change it.
	- the url to install is:
		window.we_script . '?action=pgdownload;auto;package=' + url_to_package + ';' + window.weSessionQuery
	- only packages from the .simplemachines.org domain will be accepted.
	- we've got their language in $_GET['language'].
	- we know their forum version in window.weVersion.

*/

exit();

include_once('/home/sites/simplemachines.org/public_html/community/SSI.php');
include_once('/home/sites/custom.simplemachines.org/public_html/mods/ModSiteSettings.php');
include_once('/home/sites/custom.simplemachines.org/public_html/mods/ModSiteDBSettings.php');
include_once('/home/sites/simplemachines.org/security/settings_customize.php');

unset($_SESSION['language']);

echo "var actionurl = '?action=admin;area=packages;sa=download;get;package=';";

// Pull the Wedge versions out of the table.
if (($mod_site['wedge_versions'] = cache_get_data('site_wedge_versions', 86400)) == null)
{
	$result = $smcFunc['db_query']('', "
		SELECT id_ver, ver_name
		FROM {raw:customize_prefix}wedgeVersions
		WHERE public = 1",
		array(
			'customize_prefix' => $customize_prefix,
		)
	);

	$mod_site['wedge_versions'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($result))
		$mod_site['wedge_versions'][$row['id_ver']] = $row['vername'];
	$smcFunc['db_free_result']($result);

	if (!empty($modSettings['cache_enable']))
		cache_put_data('site_wedge_versions', $mod_site['wedge_versions'], 86400);
}

// This is javascript and nothing else.
header('Content-Type: text/javascript');
?>

// if (typeof window.weVersion != "undefined" && window.weVersion == "1.0")
//	window.weLatestPackages = 'A few small problems have been found in 1.0. You can install <a href="' + window.we_script + actionurl + 'http://custom.simplemachines.org/mods/downloads/wedge_1-0-1_update.tar.gz;' + window.weSessionQuery + '">this patch (click here to install)</a> to easily update yourself to the latest version.<br /><br />If you have any problems applying it, you can use the version posted on the downloads page - although, any modifications you have installed will need to be uninstalled.  Please post on the <a href="http://www.simplemachines.org/community/index.php">forum</a> if you need more help.';
// else if (typeof window.wedgeVersion == "undefined")
//	window.wedgeLatestPackages = 'For the package manager to function properly, please upgrade to the latest version of Wedge.';
// else
{
var we_modificationInfo = {
<?php

// Save some queries, do some caching.
if (($data = cache_get_data('site_latest_packages', 3600)) == null)
{
	$context['cust_packs'] = array(
		'latest_ids' => array(),
		'moment_id' => 0
	);

	$request = $smcFunc['db_query']('', '
		(
			SELECT
				ms.id_mod, ms.mod_name, ms.modified_time, ms.downloads, ms.submit_time, 1 AS type,
				ms.wedge_versions, ms.id_attach_package, a.filename, ms.description, ms.latest_version
			FROM {raw:mod_prefix}mods AS ms
				LEFT JOIN {db_prefix}attachments AS a ON (a.id_attach = ms.id_attach_package)
			WHERE ms.approved = 1
				AND ms.id_attach_package != 0
				AND ms.id_type != 11
			ORDER BY ms.id_mod DESC
			LIMIT 3
		)
		UNION ALL
		(
			SELECT
				ms.id_mod, ms.mod_name, ms.modified_time, ms.downloads, ms.submit_time, 2 AS type,
				ms.wedge_versions, ms.id_attach_package, a.filename, ms.description, ms.latest_version
			FROM {raw:mod_prefix}mods AS ms
				LEFT JOIN {db_prefix}attachments AS a ON (a.id_attach = ms.id_attach_package)
			WHERE ms.approved = 1
				AND ms.id_attach_package != 0
				AND ms.id_type != 11
			ORDER BY RAND()
			LIMIT 1
		)',
		array(
			'mod_prefix' => $mod_site_db_name . '.' . $mod_site_db_prefix,
		)
	);
	$mods = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		censorText($row['mod_name']);
		censorText($row['description']);

		$mods[$row['id_mod']] = array(
			'id' => $row['id_mod'],
			'attach_id' => $row['id_attach_package'],
			'attach_filename' => $row['filename'],
			'short_name' => strlen($row['mod_name']) <= 20 ? $row['mod_name'] : substr($row['mod_name'], 0, 20) . '...',
			'name' => $row['mod_name'],
			'version' => $row['latest_version'],
			'submit_time' => timeformat($row['submit_time']),
			'modify_time' => timeformat($row['modified_time']),
			'description' => parse_bbc($row['description']),
			'downloads' => $row['downloads'],
			'wedge_versions' => explode(',', $row['wedge_versions']),
			'is_latest' => $row['type'] == 1,
			'is_last' => $row['type'] == 2,
		);

		// is_latest check.
		if ($row['type'] == 1)
			$context['cust_packs']['latest_ids'][] = $row['id_mod'];
		else
			$context['cust_packs']['moment_id'] = $row['id_mod'];
	}
	$smcFunc['db_free_result']($request);

	foreach ($mod_site['wedge_versions'] as $i => $ver)
	{
		if (isset($mod_site['wedge_versions'][trim($ver)]))
			$context['mod']['wedge_versions'][$i] = $mod_site['wedge_versions'][trim($ver)];
		else
			unset($context['mod']['wedge_versions'][$i]);
	}

	if (!empty($modSettings['cache_enable']))
		cache_put_data('site_latest_packages', array($mods, $mod_site['wedge_versions'], $context['cust_packs']), 86400);
}
// Otherwise we split the data up.
else
	list ($mods, $mod_site['wedge_versions'], $context['cust_packs']) = $data;

foreach ($mods as $mod)
	echo '
	', $mod['id'], ': {
		name: \'', addcslashes($mod['name'], "'"), ' ', addcslashes($mod['version'], "'"), '\',
		versions: [\'', implode('\', \'', $mod['wedge_versions']), '\'],
		desc: \'', addcslashes($mod['description'], "'"), '\',
		file: \'', addcslashes($mod['attach_filename'], "'"), '\'
	}', empty($mod['is_last']) ? ',' : '';

?>
};
var we_latestModifications = [<?php echo implode(', ', $context['cust_packs']['latest_ids']); ?>];

function we_packagesMoreInfo(id)
{
	window.weLatestPackages_temp = document.getElementById("weLatestPackagesWindow").innerHTML;

	document.getElementById('weLatestPackagesWindow').innerHTML = '<h3 style="margin: 0; padding: 4px;">' + we_modificationInfo[id].name + '</h3>\
		<h4 style="padding: 4px; margin: 0;"><a href="' + window.we_script + actionurl + 'http://custom.simplemachines.org/mods/downloads/' + id + '/' + we_modificationInfo[id].file + ';' + window.weSessionQuery + '">Install Now!</a></h4>\
		<div style="margin: 4px;">' + we_modificationInfo[id].desc.replace(/<a href/g, '<a href') + '</div>\
		<div class="title" style="padding: 4px; margin: 0"><a href="javascript:we_packagesBack();void(0);">(go back)</a></div>');
}

function we_packagesBack()
{
	document.getElementById('weLatestPackagesWindow').innerHTML = window.weLatestPackages_temp;
	window.scrollTo(0, findTop(document.getElementById("weLatestPackagesWindow")) - 10);
}

window.weLatestPackages = '\
	<div id="weLatestPackagesWindow"style="overflow: auto;">\
		<h3 style="margin: 0; padding: 4px;">New Packages:</h3>\
		<img src="http://www.simplemachines.org/smf/images/package.png" width="102" height="98" style="float: right; margin: 4px;" alt="(package)" />\
		<ul style="list-style: none; margin-top: 3px; padding: 0 4px;">';

for (var i = 0; i < we_latestModifications.length; i++)
{
	var id_mod = we_latestModifications[i];

	window.weLatestPackages += '<li><a href="javascript:we_packagesMoreInfo(' + id_mod + ');void(0);">' + we_modificationInfo[id_mod].name + '</a></li>';
}

window.weLatestPackages += '\
		</ul>';

window.weLatestPackages += '\
		<h3 style="margin: 0; padding: 4px;">Package of the Moment:</h3>\
		<div style="padding: 0 4px;">\
			<a href="javascript:we_packagesMoreInfo(<?php echo $context['cust_packs']['moment_id']; ?>);void(0);"><?php echo addcslashes($mods[$context['cust_packs']['moment_id']]['name'], "'"), ' ', addcslashes($mods[$context['cust_packs']['moment_id']]['version'], "'"); ?></a>\
		</div>';

window.weLatestPackages += '\
	</div>';
}

function findTop(el)
{
	if (typeof el.tagName == "undefined")
		return 0;

	var skipMe = in_array(el.tagName.toLowerCase(), el.parentNode ? ["tr", "tbody", "form"] : []);
	var coordsParent = el.parentNode ? "parentNode" : "offsetParent";

	if (el[coordsParent] == null || typeof el[coordsParent].offsetTop == "undefined")
		return skipMe ? 0 : el.offsetTop;
	else
		return (skipMe ? 0 : el.offsetTop) + findTop(el[coordsParent]);
}

function in_array(item, array)
{
	for (var i in array)
	{
		if (array[i] == item)
			return true;
	}

	return false;
}
