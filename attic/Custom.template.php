<?php

/*
	This is just a silly example of a Custom template file, where users
	can override, add or prepend code to an existing function. This is
	most useful for modifying a function in the index template without
	having to create a new theme based on the current one.

	Another way to override a function would be to add a custom.xml file
	to your desired skin, and add this code:

	<template name="template_sidebar_feed">
		global $txt;

		echo '
	<section>
		<we:title>
			<div class="feed_icon">', $txt['feed'], '</div>
		</we:title>
		<p>
			<em>...SPOILERS!</em>
		</p>
	</section>';
	</template>

	The custom.xml technique is more flexible, as it allows to only
	override the function in some situations, but you're losing
	the benefit of PHP-specific syntax highlighting in your editor.

	Seriously, you have no idea how much some people hate doing theme work.
	(And yes, that includes me. -- Nao)
*/

function template_sidebar_feed_override()
{
	global $txt;

	echo '
	<section>
		<we:title>
			<div class="feed_icon">', $txt['feed'], '</div>
		</we:title>
		<p>
			<em>...SPOILERS!</em>
		</p>
	</section>';
}
