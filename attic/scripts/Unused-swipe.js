/*
	This code will add left & right swipe events to your page.
	In theory, it's a "standard" finger movement on mobile devices,
	and is used by many native applications, but getting it to work
	fine across all devices in a web application is way trickier,
	because the browser itself might hook into the events, or
	simply not register them correctly, for reasons unknown...
*/

	// Swipe event; only checks for left/right swipes, so as not to disrupt other events.
	var swipe_origin, swipe_end;
	$(document)
		.on('touchstart', function (e)
		{
			if ((e.originalEvent.touches[0].target.clientWidth != e.originalEvent.touches[0].target.scrollWidth)
				|| (e.originalEvent.touches[0].target.parentNode.clientWidth != e.originalEvent.touches[0].target.parentNode.scrollWidth))
				return;
			swipe_origin = {
				x: e.originalEvent.touches[0].pageX,
				y: e.originalEvent.touches[0].pageY
			};
			swipe_end = {
				x: e.originalEvent.touches[0].pageX,
				y: e.originalEvent.touches[0].pageY
			};
		})
		.on('touchmove', function (e)
		{
			swipe_end = {
				x: e.originalEvent.touches[0].pageX,
				y: e.originalEvent.touches[0].pageY
			};
			// Starting a left/right swipe, no vertical scrolling..? Cancel horizontal scrolling.
			if (Math.abs(swipe_origin.x - swipe_end.x) > 10 && Math.abs(swipe_origin.x - swipe_end.x) > Math.abs(swipe_origin.y - swipe_end.y) * 1.5)
				e.preventDefault();
		})
		.on('touchend', function (e)
		{
			// Left/right swipe...? Here, iOS seems to be much more sensitive than Android.
			if (e.originalEvent.changedTouches.length == 1 // holds whatever was in 'touches' before touchend, we just need to ensure it only has one finger.
				&& Math.abs(swipe_origin.x - swipe_end.x) > window.innerWidth / 5
				&& Math.abs(swipe_origin.x - swipe_end.x) > Math.abs(swipe_origin.y - swipe_end.y) * 1.5)
				$(document).trigger(swipe_origin.x - swipe_end.x > 0 ? 'swipeleft' : 'swiperight');
		});

	if (is_touch)
	{
		$(document).one('swipeleft', onswipeleft);
		$(document).on('swiperight', onswiperight);
	}

/*
	And this is a freebie, I didn't think it deserved its own file...
	This one will prevent an element scroll from bubbling up the DOM when
	you reach its end; it can be useful in situations where you open a sidebar
	through a swipe, and it was my intention to have it in Wedge, but unfortunately
	it turned out that not all devices support this either, and I didn't care enough.
*/

	$(element).on('DOMMouseScroll mousewheel touchmove', function (e)
	{
		var t = $(this), delta = e.originalEvent.wheelDelta || -e.originalEvent.detail;
		if (!delta)
		{
			if (old_y < e.originalEvent.pageY && t.scrollTop() == t[0].scrollHeight - t.innerHeight())
				e.preventDefault();
			if (old_y > e.originalEvent.pageY && t.scrollTop() == 0)
				e.preventDefault();
			old_y = e.originalEvent.pageY;
		}
		else if (delta > 0 ? t.scrollTop() === 0 : t.scrollTop() == t[0].scrollHeight - t.innerHeight())
			e.preventDefault();
	});

	$(element).off('DOMMouseScroll mousewheel touchmove');
