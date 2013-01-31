<?php

	/*
		This piece of code was used at some point in Subs-Cache:wedge_cache_js, until
		it was discovered that gzipping actually likes \u00xx characters better than their UTF representation.
		So, obviously, it had to go... I'm keeping it for reference.
	*/

	$must_decode = version_compare(PHP_VERSION, '5.4', '<');
	$packed_js = json_decode($packed_js, $must_decode ? 0 : JSON_UNESCAPED_UNICODE);
	$final = $packed_js->compiledCode;
	if ($must_decode)
		$final = preg_replace(
			'~(?<!\\\\)\\\\u([0-9a-f]{4})~e',
			'html_entity_decode(\'&#\' . hexdec(\'$1\') . \';\', ENT_NOQUOTES, \'UTF-8\')',
			$final
		);
