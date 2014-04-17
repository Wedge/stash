<?php

// This is the place where I'll commit things of interest I wrote or saw elsewhere.

/**
 * Add this anywhere to show a debug stack after the script exits unexpectedly.
 */
function shutdown_find_exit()
{
    var_dump($GLOBALS['dbg_stack']);
}
register_shutdown_function('shutdown_find_exit');
function write_dbg_stack()
{
    $GLOBALS['dbg_stack'] = debug_backtrace();
}
register_tick_function('write_dbg_stack');
declare(ticks=1);


/**
 * Convert between a base-10 number and a base-62 compressed string.
 */
class Base62
{
	public static $charlist = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	public static function encode($from)
	{
		for ($base = strlen(self::$charlist), $converted = ''; $from > 0; $from = floor($from / $base))
			$converted = self::$charlist[(integer) $from % $base] . $converted;
		return $converted;
	}
	public static function decode($from)
	{
		for ($number = $i = 0, $base = strlen(self::$charlist), $pos = strlen($from); $pos; $pos--)
			$number += strpos(self::$charlist, $from[$pos - 1]) * pow($base, $i++);
		return $number;
	}
}

// echo Base62::encode(253500), ' ', Base62::decode('3vdK');
