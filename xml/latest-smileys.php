<?php

/*	To make this work, we just need to do a few things.

	- load basic information for several sets, such that they can
	  "navigate" around to look at them in the panel.
	- remember that we need to have control on the color scheme
	  (white on black, etc.); we've got the element, so we can change it.
	- the url to install is:
window.we_script . '?action=smileys;sa=install;set_gz=' + url_to_package + ';' + window.weSessionQuery
	- only packages from the .wedge.org domain will be accepted.
	- we've got their language in $_GET['language'].

*/

header('Content-Type: text/javascript');

?>window.weLatestSmileys = "If you ever see something instead of this text, then make sure Hell hasn't frozen over. Thank you.";