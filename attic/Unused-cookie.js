
// This function helps retrieve a cookie value. It's currently unused.
// If we need it later, it's easy enough to restore it...
// Note that despite its length, it compresses better than single-line equivalents.
function weCookie(sKey)
{
	var aNameValuePair, ret = null;
	$.each((document.cookie || '').split(';'), function ()
	{
		aNameValuePair = this.split('=');
		if ($.trim(aNameValuePair[0]) === sKey)
		{
			ret = decodeURIComponent(aNameValuePair[1]);
			return false;
		}
	});
	return ret;
}
