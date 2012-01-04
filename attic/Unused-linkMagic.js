
// This will add an extra class to any external links, except those with title="-".
// Ignored for now because it needs some improvement to the domain name detection.
function linkMagic()
{
	$('a[title!="-"]').each(function () {
		var hre = this.href;
		if (hre && hre.length > 0 && (hre.indexOf(window.location.hostname) == -1) && (hre.indexOf('://') != -1))
			$(this).addClass('xt');
	});
}
