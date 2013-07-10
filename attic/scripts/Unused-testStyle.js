
function _testStyle(sty, undefined)
{
	var uc = sty[0].toUpperCase() + sty.slice(1), stys = [ sty, 'Moz' + uc, 'Webkit' + uc, 'Khtml' + uc, 'ms' + uc, 'O' + uc ], i;
	for (i in stys) if (_w.style[stys[i]] !== undefined) return true;
	return false;
}

// Has your browser got the goods?
// These variables aren't used, but you can now use them in your custom scripts.
// In short: if (!can_borderradius) inject_rounded_border_emulation_hack();
var
	_w = document.createElement('wedgerocks'),
	can_borderradius = _testStyle('borderRadius'),
	can_boxshadow = _testStyle('boxShadow');
