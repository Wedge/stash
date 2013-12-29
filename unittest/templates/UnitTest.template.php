<?php

function template_ut_index()
{
	global $context;

	echo '
	<div id="mainframe">
		<div id="bodyarea">
			<div id="ut_target"></div>
		</div>
	</div>

	<script type="text/javascript" src="', $context['ut_js_url'], '/unittest.js"></script>
	<script type="text/javascript"><!-- // --><![CDATA[
		var we_script = \'./index.php\';
		var oUnitTest = new UnitTest({
			aTests: [';
	foreach ($context['tests'] as $test)
	{
		echo '
				{
					sId: \'', $test['id'], '\',
					bIsMultiThreadSafe: ', $test['isMultiThreadSafe'] ? 'true' : 'false', ',
					aSubTests: [';
		foreach ($test['sub_tests'] as $subTest)
			echo '
						{
							sId: \'', $subTest['id'], '\',
							sName: \'', $subTest['name'], '\',
							sDescription: \'', $subTest['description'], '\'
						}', empty($subTest['isLast']) ? ',' : '';
		echo '
					]
				}', empty($test['isLast']) ? ',' : '';
	}
	echo '
			],
			sTargetDivId: \'ut_target\',
			sSelf: \'oUnitTest\'
		});
	';

echo '
	// ]]></script>';

}

function template_ut_test_results()
{
	global $context;

	echo '<', '?xml version="1.0" encoding="UTF-8"?', '>
<wedge>
	<results>';
	foreach ($context['test_results'] as $testResult)
		echo '
		<result test_id="', $testResult['test_id'], '" sub_test_id="', $testResult['sub_test_id'], '" passed="', $testResult['passed'] ? '1' : '0', '">', $testResult['error_msg'], '</result>';
	echo '
	</results>
</wedge>';
}
