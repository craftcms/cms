(function($)
{
	$.Redactor.prototype.fullscreen = function()
	{
		return {
			langs: {
				en: {
					"fullscreen": "Fullscreen"
				}
			},
			init: function()
			{
				this.fullscreen.isOpen = false;

				var button = this.button.add('fullscreen', this.lang.get('fullscreen'));
				this.button.setIcon(button, '<i class="re-icon-expand"></i>');
				this.button.addCallback(button, this.fullscreen.toggle);

				if (this.opts.fullscreen)
				{
					this.fullscreen.toggle();
				}

			},
			enable: function()
			{
				this.fullscreen.isOpened = false;
				this.button.changeIcon('fullscreen', 'retract');
				this.fullscreen.isOpen = true;

				if (!this.opts.fullscreen)
				{
					this.selection.save();
				}

				if (this.opts.toolbarExternal)
				{
					this.fullscreen.toolcss = {};
					this.fullscreen.boxcss = {};
					this.fullscreen.toolcss.width = this.$toolbar.css('width');
					this.fullscreen.toolcss.top = this.$toolbar.css('top');
					this.fullscreen.toolcss.position = this.$toolbar.css('position');
					this.fullscreen.boxcss.top = this.$box.css('top');
				}

				this.fullscreen.height = this.core.editor().height();

				if (this.opts.maxHeight)
				{
					this.core.editor().css('max-height', '');
				}

				if (this.opts.minHeight)
				{
					this.core.editor().css('min-height', '');
				}

				if (!this.$fullscreenPlaceholder)
				{
					this.$fullscreenPlaceholder = $('<div/>');
				}

				this.$fullscreenPlaceholder.insertAfter(this.$box);

				this.core.box().appendTo(document.body);
				this.core.box().addClass('redactor-box-fullscreen');

				$('body').addClass('redactor-body-fullscreen');
				$('body, html').css('overflow', 'hidden');

				this.fullscreen.resize();

				if (!this.opts.fullscreen)
				{
					this.selection.restore();
				}

				this.toolbar.observeScrollDisable();
				$(window).on('resize.redactor-plugin-fullscreen', $.proxy(this.fullscreen.resize, this));
				$(document).scrollTop(0, 0);

				var self = this;
				setTimeout(function()
				{
					self.fullscreen.isOpened = true;
				}, 10);

			},
			disable: function()
			{
				this.button.changeIcon('fullscreen', 'expand');
				this.fullscreen.isOpened = undefined;
				this.fullscreen.isOpen = false;
				this.selection.save();

				$(window).off('resize.redactor-plugin-fullscreen');
				$('body, html').css('overflow', '');

				this.core.box().insertBefore(this.$fullscreenPlaceholder);
				this.$fullscreenPlaceholder.remove();

				this.core.box().removeClass('redactor-box-fullscreen').css({ width: 'auto', height: 'auto' });
				this.core.box().removeClass('redactor-box-fullscreen');

				if (this.opts.toolbarExternal)
				{
					this.core.box().css('top', this.fullscreen.boxcss.top);
					this.core.toolbar().css({
						'width': this.fullscreen.toolcss.width,
						'top': this.fullscreen.toolcss.top,
						'position': this.fullscreen.toolcss.position
					});
				}

				if (this.opts.minHeight)
				{
					this.core.editor().css('minHeight', this.opts.minHeight);
				}

				if (this.opts.maxHeight)
				{
					this.core.editor().css('maxHeight', this.opts.maxHeight);
				}

				this.core.editor().css('height', 'auto');
				this.selection.restore();
			},
			toggle: function()
			{
				return (this.fullscreen.isOpen) ? this.fullscreen.disable() : this.fullscreen.enable();
			},
			resize: function()
			{
				if (!this.fullscreen.isOpen)
				{
					return;
				}

				var toolbarHeight = this.button.toolbar().height();
				var padding = parseInt(this.core.editor().css('padding-top')) + parseInt(this.core.editor().css('padding-bottom'));
				var height = $(window).height() - toolbarHeight - padding;

				this.core.box().width($(window).width()).height(height);

				if (this.opts.toolbarExternal)
				{
					this.core.toolbar().css({
						'top': '0px',
						'position': 'absolute',
						'width': '100%'
					});

					this.core.box().css('top', toolbarHeight + 'px');
				}

				this.core.editor().height(height);
			}
		};
	};
})(jQuery);