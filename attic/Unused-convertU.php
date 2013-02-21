<?php

/*
	This piece of code was used at some point in Subs-Cache:wedge_cache_js, until
	it was discovered that gzipping actually likes \u00xx characters better than their UTF representation.
	So, obviously, it had to go... I'm keeping it for reference.
*/

$final = preg_replace(
	'~(?<!\\\\)\\\\u([0-9a-f]{4})~e',
	'html_entity_decode(\'&#\' . hexdec(\'$1\') . \';\', ENT_NOQUOTES, \'UTF-8\')',
	$packed_js->compiledCode
);
