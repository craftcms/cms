(function($)
{
	$.Redactor.prototype.source = function()
	{
		return {
			init: function()
			{
				var button = this.button.addFirst('html', 'HTML');
				this.button.addCallback(button, this.source.toggle);

				var style = {
					'width': '100%',
					'margin': '0',
					'background': '#111',
					'box-sizing': 'border-box',
					'color': 'rgba(255, 255, 255, .8)',
					'font-size': '14px',
					'outline': 'none',
					'padding': '16px',
					'line-height': '22px',
					'font-family': 'Menlo, Monaco, Consolas, "Courier New", monospace'
				};

				this.source.$textarea = $('<textarea />');
				this.source.$textarea.css(style).hide();

				if (this.opts.type === 'textarea')
				{
					this.core.box().append(this.source.$textarea);
				}
				else
				{
					this.core.box().after(this.source.$textarea);
				}

				this.core.element().on('destroy.callback.redactor', $.proxy(function()
				{
					this.source.$textarea.remove();

				}, this));

			},
			toggle: function()
			{
				return (this.source.$textarea.hasClass('open')) ? this.source.hide() : this.source.show();
			},
			hide: function()
			{
				this.source.$textarea.removeClass('open').hide();

				var code = this.source.$textarea.val();

				code = this.paragraphize.load(code);

				this.code.start(code);
				this.button.enableAll();
				this.core.editor().show().focus();
				this.code.sync();
			},
			show: function()
			{
				var height = this.core.editor().innerHeight();
				var code = this.code.get();

				code = code.replace(/\n\n/g, "\n");

				this.core.editor().hide();
				this.button.disableAll('html');
				this.source.$textarea.val(code).height(height).addClass('open').show();

				this.source.$textarea[0].setSelectionRange(0, 0);
				this.source.$textarea.focus();
			}
		};
	};
})(jQuery);