/**
 * Wedge
 *
 * Code for handling encryption in JavaScript, and a few related helper functions.
 *
 * @package wedge
 * @copyright 2010-2013 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

// Convert a string to an 8 bit representation (like in PHP).
String.prototype.php_to8bit = function ()
{
	var n, sReturn = '', c = String.fromCharCode;

	for (var i = 0, iTextLen = this.length; i < iTextLen; i++)
	{
		n = this.charCodeAt(i);
		if (n < 128)
			sReturn += c(n);
		else if (n < 2048)
			sReturn += c(192 | n >> 6) + c(128 | n & 63);
		else if (n < 65536)
			sReturn += c(224 | n >> 12) + c(128 | n >> 6 & 63) + c(128 | n & 63);
		else
			sReturn += c(240 | n >> 18) + c(128 | n >> 12 & 63) + c(128 | n >> 6 & 63) + c(128 | n & 63);
	}

	return sReturn;
};

// Character-level replacement function.
String.prototype.php_strtr = function (sFrom, sTo)
{
	return this.replace(new RegExp('[' + sFrom + ']', 'g'), function (sMatch) {
		return sTo.charAt(sFrom.indexOf(sMatch));
	});
};

// Simulate PHP's strtolower (in SOME cases, PHP uses ISO-8859-1 case folding.)
String.prototype.php_strtolower = function ()
{
	return typeof we_iso_case_folding == 'boolean' && we_iso_case_folding ? this.php_strtr(
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ\x8a\x8c\x8e\x9f\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde',
		'abcdefghijklmnopqrstuvwxyz\x9a\x9c\x9e\xff\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf7\xf8\xf9\xfa\xfb\xfc\xfd\xfe'
	) : this.php_strtr(
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'abcdefghijklmnopqrstuvwxyz'
	);
};

function hashLoginPassword(doForm, cur_session_id)
{
	// Are they using an email address?
	if (doForm.user.value.indexOf('@') != -1)
		return;

	// Unless the browser is Opera, the password will not save properly.
	if (!('opera' in window))
		doForm.passwrd.autocomplete = 'off';

	doForm.hash_passwrd.value = hex_sha1(hex_sha1(doForm.user.value.php_to8bit().php_strtolower() + doForm.passwrd.value.php_to8bit()) + cur_session_id);

	// It looks nicer to fill it with asterisks, but Firefox will try to save that.
	doForm.passwrd.value = is_ff != -1 ? '' : doForm.passwrd.value.replace(/./g, '*');
}

function hashAdminPassword(doForm, username, cur_session_id)
{
	doForm.admin_hash_pass.value = hex_sha1(hex_sha1(username.php_to8bit().php_strtolower() + doForm.admin_pass.value.php_to8bit()) + cur_session_id);
	doForm.admin_pass.value = doForm.admin_pass.value.replace(/./g, '*');
}

/*!
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS 180-1
 * Version 2.2 Copyright Paul Johnston 2000 - 2009.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */

/**
 * This function takes a string argument and returns a hex-encoded SHA1 hash.
 */
function hex_sha1(s)
{
	/*
		Perform a simple self-test to see if the VM is working.
		Only bother to uncomment if you need to test it...

		function sha1_vm_test()
		{
			return hex_sha1('abc') == 'a9993e364706816aba3e25717850c26c9cd0d89d';
		}
	*/

	/*
	 * Calculate the SHA-1 of an array of big-endian words, and a bit length
	 */
	function binb_sha1(x, len)
	{
		/* append padding */
		x[len >> 5] |= 0x80 << (24 - len % 32);
		x[((len + 64 >> 9) << 4) + 15] = len;

		var i, w = Array(80), a = 1732584193, b = -271733879, c = -1732584194, d = 271733878, e = -1009589776;

		for (i = 0; i < x.length; i += 16)
		{
			var olda = a, oldb = b, oldc = c, oldd = d, olde = e, t, j;

			for (j = 0; j < 80; j++)
			{
				if (j < 16)
					w[j] = x[i + j];
				else
					w[j] = rol(w[j - 3] ^ w[j - 8] ^ w[j - 14] ^ w[j - 16], 1);
				t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)), safe_add(safe_add(e, w[j]), sha1_kt(j)));
				e = d;
				d = c;
				c = rol(b, 30);
				b = a;
				a = t;
			}

			a = safe_add(a, olda);
			b = safe_add(b, oldb);
			c = safe_add(c, oldc);
			d = safe_add(d, oldd);
			e = safe_add(e, olde);
		}
		return [a, b, c, d, e];
	}

	/*
	 * Perform the appropriate triplet combination function for the current
	 * iteration
	 */
	function sha1_ft(t, b, c, d)
	{
		return (t < 20) ? (b & c) | ((~b) & d) : ((t < 40) ? b ^ c ^ d : ((t < 60) ? (b & c) | (b & d) | (c & d) : b ^ c ^ d));
	}

	/*
	 * Determine the appropriate additive constant for the current iteration
	 */
	function sha1_kt(t)
	{
		return (t < 20) ? 1518500249 : (t < 40) ? 1859775393 : (t < 60) ? -1894007588 : -899497514;
	}

	/*
	 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
	 * to work around bugs in some JS interpreters.
	 */
	function safe_add(x, y)
	{
		var lsw = (x & 0xFFFF) + (y & 0xFFFF), msw = (x >> 16) + (y >> 16) + (lsw >> 16);

		return (msw << 16) | (lsw & 0xFFFF);
	}

	/*
	 * Bitwise rotate a 32-bit number to the left.
	 */
	function rol(num, cnt)
	{
		return (num << cnt) | (num >>> (32 - cnt));
	}

	/*
	 * Calculate the SHA1 of a raw string
	 */
	function rstr_sha1(s)
	{
		return binb2rstr(binb_sha1(rstr2binb(s), s.length * 8));
	}

	/*
	 * Encode a string as utf-8.
	 * For efficiency, this assumes the input is valid utf-16.
	 */
	function str2rstr_utf8(input)
	{
		var output = "";
		var i = -1;
		var x, y;

		while(++i < input.length)
		{
			/* Decode utf-16 surrogate pairs */
			x = input.charCodeAt(i);
			y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
			if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
			{
				x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
				i++;
			}

			/* Encode output as utf-8 */
			if (x <= 0x7F)
				output += String.fromCharCode(x);
			else if (x <= 0x7FF)
				output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F), 0x80 | (x & 0x3F));
			else if (x <= 0xFFFF)
				output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F), 0x80 | ((x >>> 6 ) & 0x3F), 0x80 | (x & 0x3F));
			else if (x <= 0x1FFFFF)
				output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07), 0x80 | ((x >>> 12) & 0x3F), 0x80 | ((x >>> 6) & 0x3F), 0x80 | (x & 0x3F));
		}
		return output;
	}

	/*
	 * Encode a string as utf-16
	 */
	function str2rstr_utf16le(input)
	{
		var i, output = '';
		for (i = 0; i < input.length; i++)
			output += String.fromCharCode( input.charCodeAt(i) & 0xFF, (input.charCodeAt(i) >>> 8) & 0xFF);
		return output;
	}

	function str2rstr_utf16be(input)
	{
		var i, output = '';
		for (i = 0; i < input.length; i++)
			output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF, input.charCodeAt(i) & 0xFF);
		return output;
	}

	/*
	 * Convert a raw string to an array of big-endian words
	 * Characters >255 have their high-byte silently ignored.
	 */
	function rstr2binb(input)
	{
		var i, output = Array(input.length >> 2);
		for (i = 0; i < output.length; i++)
			output[i] = 0;
		for (i = 0; i < input.length * 8; i += 8)
			output[i >> 5] |= (input.charCodeAt(i / 8) & 0xFF) << (24 - i % 32);
		return output;
	}

	/*
	 * Convert an array of big-endian words to a string
	 */
	function binb2rstr(input)
	{
		var i, output = '';
		for (i = 0; i < input.length * 32; i += 8)
			output += String.fromCharCode((input[i >> 5] >>> (24 - i % 32)) & 0xFF);
		return output;
	}

	/*
	 * Convert a raw string to a hex string
	 */
	function rstr2hex(input)
	{
		// hexcase ? "0123456789ABCDEF" : "0123456789abcdef"
		var i, x, output = '', hex_tab = '0123456789abcdef';
		for (i = 0; i < input.length; i++)
		{
			x = input.charCodeAt(i);
			output += hex_tab.charAt((x >>> 4) & 0x0F) + hex_tab.charAt(x & 0x0F);
		}
		return output;
	}

	return rstr2hex(rstr_sha1(str2rstr_utf8(s)));
}
