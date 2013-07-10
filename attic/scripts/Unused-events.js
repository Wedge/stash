
/*
	A nice example of importing events from another DOM element.
	The trick is that they can get their events from at least two sources:
	- HTML on* attribute: iterate through .attributes and set the new attribute
	  in case the source attribute starts with "on".
	- jQuery: iterate through .data("events") and bind their handler, but only
	  if the namespace isn't specialChange (meaning it's a special event created
	  by jQuery to emulate bubbling in Internet Explorer.)
*/

var $events = $.extend({}, $orig.data("events")), i, j, attr;
for (i = 0, attr = $orig[0].attributes, j = attr.length; i < j; i++)
	if (attr[i].name.indexOf("on") === 0)
		$display[0].setAttribute(attr[i].name, $orig[0].getAttribute(attr[i].name));
// Now attach any events added through jQuery.
for (i in $events)
	for (j in $events[i])
		if ($events[i][j].namespace != "specialChange")
			$display.on(i, $events[i][j].handler);
