
// This will show a quick drop-down when you hover/click some element,
// depending on the author's wishes. I'm not using this right now, because
// it was intended for the language selector and I went for a select-box instead.
//
// src: element that can generate a drop-down
// url: if specified, this should run an Ajax query that's used to generate the drop-down.
//      if not specified, the contents should be available directly in the HTML.
// hoverable: whether or not we should open the drop-down on hover (recommended only if the HTML already contains it, of course!)

// *** Generic drop-down creator.
function dropDown(src, url, hoverable)
{
	var oContainerDiv,

	// Show the list of icons after the user clicked the original icon.
	openPopup = function (oDiv, iMessageId)
	{
		var iCurMessageId = iMessageId, oCurDiv = oDiv;

		if (!oContainerDiv)
		{
			// Create a container div.
			oContainerDiv = $('<div id="iconlist"></div>').hide().css('width', oCurDiv.offsetWidth).appendTo('body');

			// Start to fetch its contents.
			show_ajax();
			$.post(weUrl('action=ajax;sa=messageicons'), { board: we_board }, function (XMLDoc)
			{
				hide_ajax();
				$('icon', XMLDoc).each(function (key, iconxml)
				{
					oContainerDiv.append(
						$('<div class="item"></div>')
							.mousedown(function ()
							{
								// Event handler for clicking on one of the icons.
								var thisicon = this;
								show_ajax();

								$.post(
									weUrl('action=jsmodify;' + we_sessvar + '=' + we_sessid),
									{
										topic: we_topic,
										msg: iCurMessageId,
										icon: $(iconxml).attr('value')
									},
									function (oXMLDoc)
									{
										hide_ajax();
										if (!$('error', oXMLDoc).length)
											$('img', oCurDiv).attr('src', $('img', thisicon).attr('src'));
									}
								);
							})
							.append($(iconxml).text())
					);
				});
			});
		}

		// Show the container, and position it.
		oContainerDiv.fadeIn().css({
			top: $(oCurDiv).offset().top + oDiv.offsetHeight,
			left: $(oCurDiv).offset().left - 1
		});
	};

	var oContainers = [];
	$(src).addClass('iconbox').on(hoverable ? 'mouseenter' : 'click', function () {
		var that = $(this).addClass('hove');
		if (!oContainers[src])
		{
			oContainers[src] = $('<div class="dd"/>').hide().appendTo('body');
			if (url)
			{
				show_ajax();
				$.post(weUrl(url), function (html) {
					hide_ajax();
					oContainers[src].append(html);
					// If user clicks outside, this will close the list.
					$('body').on('mousedown.dd', function () {
						oContainers[src].fadeOut();
						that.removeClass('hove');
						$('body').off('mousedown.dd');
					});
				});
			}
			else
				oContainers[src].html(that.find('.dd').removeClass());
		}

		if (!url)
			oContainers[src].fadeIn().css({
				top: that.offset().top + that.outerHeight(true),
				left: that.offset().left
			});

		// If user clicks outside, this will close the list.
		$('body').on('mousedown.dd', function () {
			oContainers[src].fadeOut();
			that.removeClass('hove');
			$('body').off('mousedown.dd');
		});
	});
}
