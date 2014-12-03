(function($) {


Craft.LivePreview = Garnish.Base.extend(
{
	$extraFields: null,
	$trigger: null,
	$spinner: null,
	$shade: null,
	$editor: null,
	$dragHandle: null,
	$iframeContainer: null,
	$iframe: null,
	$fieldPlaceholder: null,

	previewUrl: null,
	basePostData: null,
	inPreviewMode: false,
	fields: null,
	lastPostData: null,
	updateIframeInterval: null,
	loading: false,
	checkAgain: false,

	dragger: null,
	dragStartEditorWidth: null,

	_editorWidth: null,
	_editorWidthInPx: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.LivePreview.defaults);

		// Should preview requests use a specific URL?
		// This won't affect how the request gets routed (the action param will override it),
		// but it will allow the templates to change behavior based on the request URI.
		if (this.settings.previewUrl)
		{
			this.previewUrl = this.settings.previewUrl;
		}
		else
		{
			this.previewUrl = Craft.baseSiteUrl.replace(/\/+$/, '') + '/';
		}

		// Load the preview over SSL if the current request is
		if (document.location.protocol == 'https:')
		{
			this.previewUrl = this.previewUrl.replace(/^http:/, 'https:');
		}

		// Set the base post data
		this.basePostData = $.extend({
			action: this.settings.previewAction,
			livePreview: true
		}, this.settings.previewParams);

		if (Craft.csrfTokenName)
		{
			this.basePostData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		// Find the DOM elements
		this.$extraFields = $(this.settings.extraFields);
		this.$trigger = $(this.settings.trigger);
		this.$spinner = this.settings.spinner ? $(this.settings.spinner) : this.$trigger.find('.spinner');
		this.$fieldPlaceholder = $('<div/>');

		// Set the initial editor width
		this.editorWidth = Craft.getLocalStorage('LivePreview.editorWidth', Craft.LivePreview.defaultEditorWidth);

		// Event Listeners
		this.addListener(this.$trigger, 'activate', 'toggle');

		Craft.cp.on('beforeSaveShortcut', $.proxy(function()
		{
			if (this.inPreviewMode)
			{
				this.moveFieldsBack();
			}
		}, this));
	},

	get editorWidth()
	{
		return this._editorWidth;
	},

	get editorWidthInPx()
	{
		return this._editorWidthInPx;
	},

	set editorWidth(width)
	{
		// Is this getting set in pixels?
		if (width >= 1)
		{
			var inPx = width;
			width /= Garnish.$win.width();
		}
		else
		{
			var inPx = Math.round(width * Garnish.$win.width());
		}

		// Make sure it's no less than the minimum
		if (inPx < Craft.LivePreview.minEditorWidthInPx)
		{
			inPx = Craft.LivePreview.minEditorWidthInPx;
			width = inPx / Garnish.$win.width();
		}

		this._editorWidth = width;
		this._editorWidthInPx = inPx;
	},

	toggle: function()
	{
		if (this.inPreviewMode)
		{
			this.exit();
		}
		else
		{
			this.enter();
		}
	},

	enter: function()
	{
		if (this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeEnter');

		$(document.activeElement).blur();

		if (!this.$editor)
		{
			this.$shade = $('<div class="modal-shade dark"></div>').appendTo(Garnish.$bod).css('z-index', 2);
			this.$editor = $('<div class="lp-editor"></div>').appendTo(Garnish.$bod)
			this.$iframeContainer = $('<div class="lp-iframe-container" />').appendTo(Garnish.$bod);
			this.$iframe = $('<iframe class="lp-iframe" frameborder="0" />').appendTo(this.$iframeContainer);
			this.$dragHandle = $('<div class="lp-draghandle"></div>').appendTo(Garnish.$bod);

			var $header = $('<header class="header"></header>').appendTo(this.$editor),
				$closeBtn = $('<div class="btn">'+Craft.t('Done')+'</div>').appendTo($header),
				$heading = $('<h1>'+Craft.t('Live Preview')+'</h1>').appendTo($header);

			this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
				axis:          Garnish.X_AXIS,
				onDragStart:   $.proxy(this, '_onDragStart'),
				onDrag:        $.proxy(this, '_onDrag'),
				onDragStop:    $.proxy(this, '_onDragStop')
			});

			this.addListener($closeBtn, 'click', 'exit');
		}

		// Set the sizes
		this.handleWindowResize();
		this.addListener(Garnish.$win, 'resize', 'handleWindowResize');

		this.$editor.css(Craft.left, -this.editorWidthInPx+'px');
		this.$editor.css('width', this.editorWidthInPx+'px');

		this.$iframeContainer.css(Craft.right, -this.getIframeWidth());

		// Move all the fields into the editor rather than copying them
		// so any JS that's referencing the elements won't break.
		this.fields = [];
		var $fields = $(this.settings.fields);

		for (var i= 0; i < $fields.length; i++)
		{
			var $field = $($fields[i]),
				$clone = this._getClone($field);

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
		this.trigger('enter');
	},

	handleWindowResize: function()
	{
		// Reset the width so the min width is enforced
		this.editorWidth = this.editorWidth;

		// Update the editor/iframe sizes
		this.updateWidths();
	},

	slideIn: function()
	{
		$('html').addClass('noscroll');
		this.$spinner.addClass('hidden');

		this.$shade.velocity('fadeIn');

		this.$editor.show().velocity('stop').animateLeft(0, 'slow', $.proxy(function()
		{
			this.trigger('slideIn');
			Garnish.$win.trigger('resize');
		}, this));
		this.$iframeContainer.show().velocity('stop').animateRight(0, 'slow', $.proxy(function()
		{
			this.updateIframeInterval = setInterval($.proxy(this, 'updateIframe'), 1000);

			this.addListener(Garnish.$bod, 'keyup', function(ev)
			{
				if (ev.keyCode == Garnish.ESC_KEY)
				{
					this.exit();
				}
			});
		}, this));
	},

	exit: function()
	{
		if (!this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeExit');

		$('html').removeClass('noscroll');

		this.removeListener(Garnish.$win, 'resize');
		this.removeListener(Garnish.$bod, 'keyup');

		if (this.updateIframeInterval)
		{
			clearInterval(this.updateIframeInterval);
		}

		this.moveFieldsBack();

		var windowWidth = Garnish.$win.width();

		this.$shade.delay(200).velocity('fadeOut');

		this.$editor.velocity('stop').animateLeft(-this.editorWidthInPx, 'slow', $.proxy(function()
		{
			for (var i = 0; i < this.fields.length; i++)
			{
				this.fields[i].$newClone.remove();
			}
			this.$editor.hide();
			this.trigger('slideOut');
		}, this));

		this.$iframeContainer.velocity('stop').animateRight(-this.getIframeWidth(), 'slow', $.proxy(function()
		{
			this.$iframeContainer.hide();
		}, this));

		this.inPreviewMode = false;
		this.trigger('exit');
	},

	moveFieldsBack: function()
	{
		for (var i = 0; i < this.fields.length; i++)
		{
			var field = this.fields[i];
			field.$newClone = this._getClone(field.$field);

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
		return Garnish.$win.width()-this.editorWidthInPx;
	},

	updateWidths: function()
	{
		this.$editor.css('width', this.editorWidthInPx+'px');
		this.$dragHandle.css(Craft.left, this.editorWidthInPx+'px');
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
		var postData = $.extend(Garnish.getPostData(this.$editor), Garnish.getPostData(this.$extraFields));

		if (!this.lastPostData || !Craft.compare(postData, this.lastPostData))
		{
			this.lastPostData = postData;
			this.loading = true;

			var data = $.extend({}, postData, this.basePostData),
				$doc = $(this.$iframe[0].contentWindow.document),
				scrollX = $doc.scrollLeft(),
				scrollY = $doc.scrollTop();

			$.post(this.previewUrl, data, $.proxy(function(response)
			{
				var html = response +
					'<script type="text/javascript">window.scrollTo('+scrollX+', '+scrollY+');</script>';

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

	_getClone: function($field)
	{
		var $clone = $field.clone();

		// clone() won't account for input values that have changed since the original HTML set them
		Garnish.copyInputValues($field, $clone);

		// Remove any id= attributes
		$clone.attr('id', '');
		$clone.find('[id]').attr('id', '');

		return $clone;
	},

	_onDragStart: function()
	{
		this.dragStartEditorWidth = this.editorWidthInPx;
		this.$iframeContainer.addClass('dragging');
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
		this.$iframeContainer.removeClass('dragging');
		Craft.setLocalStorage('LivePreview.editorWidth', this.editorWidth);
	}
},
{
	defaultEditorWidth: .33,
	minEditorWidthInPx: 200,

	defaults: {
		trigger: '.livepreviewbtn',
		spinner: null,
		fields: null,
		extraFields: null,
		previewUrl: null,
		previewAction: null,
		previewParams: {}
	}
});

Craft.LivePreview.init = function(settings)
{
	Craft.livePreview = new Craft.LivePreview(settings);
};


})(jQuery);
