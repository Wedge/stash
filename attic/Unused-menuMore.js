
// This code needs to be put into a DOM ready event, e.g. $(function () {}), and will
// ensure the main menu only uses one line of text. The remaining items will be moved
// to a new 'More' menu entry, but resizing the window won't update it.
// Feel free to improve, re-use, or whatever.

	$('#main_menu').css('display', 'inline-block');
	if ($('#main_menu').height() > 50 || $('#main_menu').width() > $(window).width() * 0.75)
		$('#main_menu').append('<li><span id="m_more"></span><h4>' + $txt['more'] + '</h4><ul></ul></li>');

	while ($('#main_menu').height() > 50 || $('#main_menu').width() > $(window).width() * 0.75)
		$('#main_menu > li:last').prev().prependTo($('#main_menu > li:last > ul')).find('h4').contents().unwrap().parent().find('>span').remove();
