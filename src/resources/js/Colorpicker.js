Craft.ColorPicker = Garnish.Base.extend({

	init: function(id)
	{
		if (!Craft.ColorPicker.doesBrowserSupportColorInputs())
		{
			var $input = $('#'+id),
				$container = $('<div class="color" />'),
				$preview = $('<div class="colorpreview" />').appendTo($container),
				$hiddenInput = $('<input type="hidden" />').appendTo($container);

			if ($input.val())
			{
				$preview.css('background-color', $input.val());
				$hiddenInput.val($input.val());
			}

			$hiddenInput.attr('name', $input.attr('name'));

			$input.replaceWith($container);

			$container.colorPicker({
				doRender: true,
				cssAddon:'.cp-xy-slider:active {cursor:none;}',
				opacity: false,
				scrollResize: false,
				renderCallback: function ($elm, toggled) {
					if (toggled == true) {
						$container.addClass('active');
					}
					else if (toggled == false)
					{
						$container.removeClass('active');
					}
					else
					{
						$preview.css('backgroundColor', '#' + this.color.colors.HEX);
						$hiddenInput.val('#' + this.color.colors.HEX);
					}
				}
			});
		}
	}

}, {
	_browserSupportsColorInputs: null,

	doesBrowserSupportColorInputs: function()
	{
		if (Craft.ColorPicker._browserSupportsColorInputs === null)
		{
			var input = document.createElement('input');
			input.setAttribute('type', 'color');
			Craft.ColorPicker._browserSupportsColorInputs = (input.type == 'color');
		}

		return Craft.ColorPicker._browserSupportsColorInputs;
	}
});