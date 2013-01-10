<?php

/*
	This is just a silly example of a Custom template file, where users
	can override, add or prepend code to an existing function. This is
	most useful for modifying a function in the index template without
	having to create a new theme based on the current one.

	Seriously, you have no idea how much some people hate doing theme work.
	(And yes, that includes me. -- Nao)
*/

function template_sidebar_feed_override()
{
	global $txt;

	echo '
		<we:title>
			<div class="feed_icon"></div>
			', $txt['feed'], '
		</we:title>
		<br>
		<em>...SPOILERS!</em>';
}
