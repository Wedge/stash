/*
	Before I wrote the PHP code to soft-merge posts (see Subs-Template.php),
	I wrote a prototype in JavaScript, which worked well enough to gather interest,
	but had at least one major issue for me: message anchors didn't work properly,
	at least when targeting an element that would be merged into another.
	The PHP rewrite fixes this, but I like the JS version better, so I'm including
	it here, if only for nostalgia of some code that only lived for a few days in Wedge...
*/

var post, ex_post, $area, $ex_area;
$('#forumposts .msg').not('.postheader').each(function ()
{
	post = $(this).find('.umme').data('id');
	$area = $(this).find('.postarea');
	if (ex_post == post)
	{
		$ex_area.find('.signature').remove();
		$('<div/>').addClass('merged msg').attr('id', $(this).attr('id')).append($area.contents()).appendTo($ex_area);
		$(this).prev('hr').andSelf().remove();
	}
	else
	{
		ex_post = post;
		$ex_area = $area;
	}
});
