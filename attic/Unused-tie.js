
/*
	Original code by RevSystems (jquery.tie.js plugin), modified by Nao
	for use in select boxes only. The version below doesn't work, even
	though I got it working earlier, but it doesn't matter -- I'm probably
	never going to use it in the final code, since I'd rather review all
	select boxes and have them handle their own refreshes.
*/

var delayTimeout;
$orig.find("option,optgroup").andSelf().bind("updated.sb", function () {
		clearTimeout(delayTimeout);
		delayTimeout = setTimeout(reloadSB);
	});

///////////////////////

// override existing jQuery functions to trigger an extra update event.
$.fx.prototype.oldUpdate = $.fx.prototype.update;
$.fx.prototype.update = function ()
{
	$(this.elem).trigger("updated");
	return this.oldUpdate.apply(this, arguments);
};

// This list of overloaded methods is customized for our select boxes...
// Also to be considered: "before", "after", "removeAttr" (1 argument each). "remove" and "empty" (no arguments).
// And "css" (2 arguments or more, or 1 argument where typeof arguments[0] === "object")
$.each(["attr", "append", "prepend", "text", "html"], function (index, funcName)
{
	$.fn["old_" + funcName] = $.fn[funcName];
	$.fn[funcName] = function ()
	{
		if (this.length && arguments.length && (funcName != "attr" || arguments[0] != "selected") && this[0].nodeName.toLowerCase() != "body")
			this.trigger("updated");
		return $.fn["old_" + funcName].apply(this, arguments);
	};
});
