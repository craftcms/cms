(function($) {


Craft.LivePreview = Garnish.Base.extend(
{
	$form: null,
	$btn: null,
	$spinner: null,
	$shade: null,
	$editor: null,
	$dragHandle: null,
	$iframeContainer: null,
	$iframe: null,
	$fieldPlaceholder: null,

	postUrl: null,
	locale: null,
	basePostData: null,
	inPreviewMode: false,
	fields: null,
	lastPostData: null,
	updateIframeInterval: null,
	loading: false,
	checkAgain: false,

	editorWidth: null,
	dragger: null,
	dragStartEditorWidth: null,

	init: function(entryUrl, locale)
	{
		if (entryUrl)
		{
			this.postUrl = entryUrl;
		}
		else
		{
			this.postUrl = Craft.baseSiteUrl.replace(/\/+$/, '') + '/';
		}

		this.locale = locale;

		// Load the preview over SSL if the current request is
		if (document.location.protocol == 'https:')
		{
			this.postUrl = this.postUrl.replace(/^http:/, 'https:');
		}

		this.$form = $('#entry-form');
		this.$btn = $('#livepreview-btn');
		this.$spinner = $('#livepreview-spinner');
		this.$fieldPlaceholder = $('<div/>');

		this.basePostData = {
			action: 'entries/previewEntry',
			locale: this.locale
		};

		var $hiddenInputs = this.$form.children('input[type=hidden]');
		for (var i = 0; i < $hiddenInputs.length; i++)
		{
			var $input = $($hiddenInputs[i]);
			this.basePostData[$input.attr('name')] = $input.val();
		}

		this.editorWidth = Craft.getLocalStorage('LivePreview.editorWidth', Craft.LivePreview.defaultEditorWidth);
		this.containEditorWidth();

		this.addListener(this.$btn, 'activate', 'togglePreviewMode');

		Craft.cp.on('beforeSaveShortcut', $.proxy(function()
		{
			if (this.inPreviewMode)
			{
				this.moveFieldsBack();
			}
		}, this));
	},

	containEditorWidth: function()
	{
		if (this.editorWidth < 200)
		{
			this.editorWidth = 200;
			return true;
		}

		if (this.editorWidth > Garnish.$win.width() - 200)
		{
			this.editorWidth = Garnish.$win.width() - 200;
			return true;
		}

		return false;
	},

	togglePreviewMode: function()
	{
		if (this.inPreviewMode)
		{
			this.hidePreviewMode();
		}
		else
		{
			this.showPreviewMode();
		}
	},

	showPreviewMode: function()
	{
		if (this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeShowPreviewMode');

		$(document.activeElement).blur();

		if (!this.$editor)
		{
			this.$shade = $('<div class="modal-shade dark"></div>').appendTo(Garnish.$bod).css('z-index', 2);
			this.$editor = $('<div id="livepreview-editor"></div>').appendTo(Garnish.$bod)
			this.$iframeContainer = $('<div id="livepreview-iframe-container" />').appendTo(Garnish.$bod);
			this.$iframe = $('<iframe id="livepreview-iframe" frameborder="0" />').appendTo(this.$iframeContainer);
			this.$dragHandle = $('<div id="livepreview-draghandle"></div>').appendTo(Garnish.$bod);

			var $header = $('<header class="header"></header>').appendTo(this.$editor),
				$closeBtn = $('<div class="btn">'+Craft.t('Done')+'</div>').appendTo($header),
				$heading = $('<h1>'+Craft.t('Live Preview')+'</h1>').appendTo($header);

			this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
				axis:          Garnish.X_AXIS,
				onDragStart:   $.proxy(this, '_onDragStart'),
				onDrag:        $.proxy(this, '_onDrag'),
				onDragStop:    $.proxy(this, '_onDragStop')
			});

			this.addListener($closeBtn, 'click', 'hidePreviewMode');
		}

		this.$editor.css(Craft.left, -this.editorWidth+'px');
		this.$editor.css('width', this.editorWidth+'px');

		var iframeWidth = this.getIframeWidth();
		this.$iframeContainer.css(Craft.right, -iframeWidth);
		this.$iframeContainer.css('width', iframeWidth);
		this.addListener(Garnish.$win, 'resize', 'updateWidths');
		this.updateWidths();

		// Move all the fields into the editor rather than copying them
		// so any JS that's referencing the elements won't break.
		this.fields = [];
		var $fields = $('#fields > .field, #fields > div > div > .field');

		for (var i= 0; i < $fields.length; i++)
		{
			var $field = $($fields[i]),
				$clone = $field.clone();

			// It's important that the actual field is added to the DOM *after* the clone,
			// so any radio buttons in the field get deselected from the clone rather than the actual field.
			this.$fieldPlaceholder.insertAfter($field);
			$field.detach();
			this.$fieldPlaceholder.replaceWith($clone);
			$field.appendTo(this.$editor);

			this.fields.push({
				$field: $field,
				$clone: $clone
			});
		}

		Garnish.$win.trigger('resize');

		if (this.updateIframe())
		{
			this.$spinner.removeClass('hidden');
			this.addListener(this.$iframe, 'load', function()
			{
				this.slideIn();
				this.removeListener(this.$iframe, 'load');
			});
		}
		else
		{
			this.slideIn();
		}

		this.inPreviewMode = true;
		this.trigger('showPreviewMode');
	},

	slideIn: function()
	{
		$('html').addClass('noscroll');
		this.$spinner.addClass('hidden');

		this.$shade.fadeIn();

		this.$editor.show().stop().animateLeft(0, 'slow', $.proxy(function()
		{
			this.trigger('slideIn');
		}, this));
		this.$iframeContainer.show().stop().animateRight(0, 'slow', $.proxy(function()
		{
			this.updateIframeInterval = setInterval($.proxy(this, 'updateIframe'), 1000);

			this.addListener(Garnish.$bod, 'keyup', function(ev)
			{
				if (ev.keyCode == Garnish.ESC_KEY)
				{
					this.hidePreviewMode();
				}
			});
		}, this));
	},

	hidePreviewMode: function()
	{
		if (!this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeHidePreviewMode');

		$('html').removeClass('noscroll');

		this.removeListener(Garnish.$win, 'resize');
		this.removeListener(Garnish.$bod, 'keyup');

		if (this.updateIframeInterval)
		{
			clearInterval(this.updateIframeInterval);
		}

		this.moveFieldsBack();

		var windowWidth = Garnish.$win.width();

		this.$shade.delay(200).fadeOut();

		this.$editor.stop().animateLeft(-this.editorWidth, 'slow', $.proxy(function()
		{
			for (var i = 0; i < this.fields.length; i++)
			{
				this.fields[i].$newClone.remove();
			}
			this.$editor.hide();
			this.trigger('slideOut');
		}, this));

		this.$iframeContainer.stop().animateRight(-this.getIframeWidth(), 'slow', $.proxy(function()
		{
			this.$iframeContainer.hide();
		}, this));

		this.inPreviewMode = false;
		this.trigger('hidePreviewMode');
	},

	moveFieldsBack: function()
	{
		for (var i = 0; i < this.fields.length; i++)
		{
			var field = this.fields[i];
			field.$newClone = field.$field.clone();

			// It's important that the actual field is added to the DOM *after* the clone,
			// so any radio buttons in the field get deselected from the clone rather than the actual field.
			this.$fieldPlaceholder.insertAfter(field.$field);
			field.$field.detach();
			this.$fieldPlaceholder.replaceWith(field.$newClone);
			field.$clone.replaceWith(field.$field);
		}

		Garnish.$win.trigger('resize');
	},

	getIframeWidth: function()
	{
		return Garnish.$win.width()-this.editorWidth;
	},

	updateWidths: function()
	{
		this.containEditorWidth();
		this.$editor.css('width', this.editorWidth+'px');
		this.$dragHandle.css(Craft.left, this.editorWidth+'px');
		this.$iframeContainer.width(this.getIframeWidth());
	},

	updateIframe: function(force)
	{
		if (force)
		{
			this.lastPostData = null;
		}

		if (!this.inPreviewMode)
		{
			return false;
		}

		if (this.loading)
		{
			this.checkAgain = true;
			return false;
		}

		// Has the post data changed?
		var postData = Garnish.getPostData(this.$editor);

		if (!this.lastPostData || !Craft.compare(postData, this.lastPostData))
		{
			this.lastPostData = postData;
			this.loading = true;

			var data = $.extend({}, postData, this.basePostData),
				scrollTop = $(this.$iframe[0].contentWindow.document).scrollTop();

			$.post(this.postUrl, data, $.proxy(function(response)
			{
				var html = response +
					'<script type="text/javascript">document.body.scrollTop = '+scrollTop+';</script>';

				// Set the iframe to use the same bg as the iframe body,
				// to reduce the blink when reloading the DOM
				this.$iframe.css('background', $(this.$iframe[0].contentWindow.document.body).css('background'));

				this.$iframe[0].contentWindow.document.open();
				this.$iframe[0].contentWindow.document.write(html);
				this.$iframe[0].contentWindow.document.close();

				this.loading = false;

				if (this.checkAgain)
				{
					this.checkAgain = false;
					this.updateIframe();
				}

			}, this));

			return true;
		}
		else
		{
			return false;
		}
	},

	_onDragStart: function()
	{
		this.dragStartEditorWidth = this.editorWidth;
	},

	_onDrag: function()
	{
		if (Craft.orientation == 'ltr')
		{
			this.editorWidth = this.dragStartEditorWidth + this.dragger.mouseDistX;
		}
		else
		{
			this.editorWidth = this.dragStartEditorWidth - this.dragger.mouseDistX;
		}

		this.updateWidths();
	},

	_onDragStop: function()
	{
		Craft.setLocalStorage('LivePreview.editorWidth', this.editorWidth);
	}
},
{
	defaultEditorWidth: 400
});


})(jQuery);
