<?php

/**
 * A fallback in case json_encode() is disabled via php.ini.
 * Taken from http://snipplr.com/view/13911.22389/
 * Chose to use a much longer version instead... Probably safer?
 *
 * Performance is about 25x slower than json_encode(), and 10x faster
 * than weJSON::encode(), which means all of these functions execute
 * within 1ms in most cases. So, no need to bother about speed.
 */
function json_encode_fallback($v)
{
	if ($v == null)
		return 'null';

	if (is_array($v))
	{
		// Non-associative array..?
		if (!count($v) || array_keys($v) === range(0, count($v) - 1))
			return '[' . join(',', array_map(__FUNCTION__, $v)) . ']';

		foreach ($v as $k => $val)
			$v[$k] = call_user_func(__FUNCTION__, $k) . ':' . call_user_func(__FUNCTION__, $val);

		return '{' . join(',', $v) . '}';
	}

	return '"' . addslashes(preg_replace('/(\n|\r|\t)/i', '', strval($v))) . '"';
}
