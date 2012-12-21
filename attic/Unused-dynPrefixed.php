<?php
/*
	This is a rewrite of a Subs-Cache.php function to add CSS prefixes only when needed.
	I didn't use it in the end, because it would have forced us to create one cache file for every
	single browser version, instead of just the one.

	Okay, interestingly, I DID end up doing a cache file for every version, but without using
	this piece of code, which I had forgotten about... And while it's more readable, it's also less flexible
	because it only allows for boolean prefixed/unprefixed, instead of allowing to completely remove a rule.
*/

// Numbers taken from http://caniuse.com
$rule_list = array(
	// Effect				  IE Firefox Chrome Safari  iOS Opera Android Generic
	'box-sizing'	=> array(  8,     -1,    10,     5, 5.1,  9.6,    2.2,     -1 ),
	'box-shadow'	=> array(  9,      4,    10,     5, 5.1, 10.5,     -1,     -1 ),
	'border-radius'	=> array(  9,      4,     5,     4,   5, 10.5,    2.2,     -1 ),
	'transition'	=> array( -1,     -1,    -1,    -1,  -1,   -1,     -1,     -1 ),
);

// And now let's magically transform those CSS3 rules that are prominent enough in Wedge to get the honor of a custom function.
$final = preg_replace_callback('~(?<!-)(?:' . implode('|', array_keys($rule_list)) . '):[^\n;]+[\n;]~', 'wedge_fix_browser_css', $final);

function wedge_fix_browser_css($matches)
{
	global $prefix, $rule_list;

	$index = we::is('ie') ? 0 : (we::is('firefox') ? 1 : (we::is('chrome') ? 2 : (we::is('ios') ? 3 : (we::is('safari') ? 4 : (we::is('opera') ? 5 : (we::is('android') ? 6 : 7))))));

	foreach ($rule_list as $key => $rule)
		if (strpos($matches[0], $key) === 0)
			return $rule[$index] === -1 || we::$browser['version'] < $rule[$index] ? $prefix . $matches[0] : $matches[0];

	return $matches[0];
}

?>