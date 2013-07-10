
// Based on:
// http://devongovett.wordpress.com/2009/04/06/text-overflow-ellipsis-for-firefox-via-jquery/

//	.ellipsis
//		text-overflow: ellipsis
//		overflow: hidden

(function ($) {
	$.fn.ellipsis = function (enableUpdating) {
		var
			el = $(this), text = originalText = el.html(), w = el.width(),
			t = $(this.cloneNode(true)).hide().css({
				position: 'absolute',
				overflow: 'visible',
				maxWidth: 'inherit'
			});
		t.css(t.css('width') == 'auto' ? 'height' : 'width', 'auto');

		el.after(t).attr('title', text);
		while (text.length > 0 && t.width() > el.width())
		{
			text = text.slice(0, -1);
			t.html(text + '&hell;');
		}
		el.html(t.html());
		t.remove();

		if (enableUpdating)
		{
			var oldW = el.width();
			setInterval(function () {
				if (el.width() != oldW)
				{
					oldW = el.width();
					el.html(originalText);
					el.ellipsis();
				}
			}, 1000);
		}
	};

	var s = document.documentElement.style;
	if (!('textOverflow' in s || 'OTextOverflow' in s))
		$('.ellipsis').each(function () { $(this).ellipsis($(this).hasClass('update')); });
})(jQuery);
