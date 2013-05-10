(function($) {


Craft.EntryPreviewMode = Garnish.Base.extend({

	$form: null,
	$previewModeBtn: null,
	$editor: null,
	$closeBtn: null,
	$iframe: null,
	iframeDocument: null,

	basePostData: null,
	inPreviewMode: false,
	fields: null,
	lastPostData: null,
	updateIframeInterval: null,
	loading: false,
	checkAgain: false,

	init: function()
	{
		this.$form = $('#entry-form');
		this.$previewModeBtn = $('#previewmode-btn');

		this.basePostData = {};
		var $hiddenInputs = this.$form.children('input[type=hidden]');
		for (var i = 0; i < $hiddenInputs.length; i++)
		{
			var $input = $($hiddenInputs[i]);
			this.basePostData[$input.attr('name')] = $input.val();
		}

		this.addListener(this.$previewModeBtn, 'click', 'togglePreviewMode');
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
		if (!this.$editor)
		{
			this.$editor = $('<div id="previewmode-editor"></div>').appendTo(Garnish.$bod);
			this.$closeBtn = $('<div id="previewmode-closebtn" class="btn">'+Craft.t('Done')+'</div>').appendTo(this.$editor);
			this.$iframe = $('<iframe id="previewmode-iframe" frameborder="0" />').appendTo(Garnish.$bod);
			this.iframeDocument = this.$iframe[0].contentWindow.document;

			this.addListener(this.$closeBtn, 'click', 'hidePreviewMode');
		}

		// Move all the fields into the editor rather than copying them
		// so any JS that's referencing the elements won't break.
		this.fields = [];
		var $fields = this.$form.children('.field').add(this.$form.children(':not(#entry-settings)').children('.field'));
		for (var i= 0; i < $fields.length; i++)
		{
			var $field = $($fields[i]),
				$clone = $field.clone().insertAfter($field);

			$field.insertBefore(this.$closeBtn);

			this.fields.push({
				$field: $field,
				$clone: $clone
			});
		}

		this.$iframe.css('left', Garnish.$win.width());
		this.$iframe.show();

		this.addListener(Garnish.$win, 'resize', 'setIframeWidth');
		this.setIframeWidth();

		this.$editor.show().animate({
			left: 0
		});

		this.$iframe.animate({
			left: Craft.EntryPreviewMode.formWidth
		}, $.proxy(function() {
			this.updateIframe();
			this.updateIframeInterval = setInterval($.proxy(this, 'updateIframe'), 1000);
		}, this));

		this.inPreviewMode = true;
	},

	hidePreviewMode: function()
	{
		this.removeListener(Garnish.$win, 'resize');
		clearInterval(this.updateIframeInterval);

		for (var i = 0; i < this.fields.length; i++)
		{
			this.fields[i].$clone.replaceWith(this.fields[i].$field);
		}

		var windowWidth = Garnish.$win.width();

		this.$editor.animate({
			left: -400
		}, $.proxy(function() {
			this.$editor.hide();
		}, this));

		this.$iframe.animate({
			left: windowWidth
		}, $.proxy(function() {
			this.$iframe.hide();
		}, this));

		this.inPreviewMode = false;
	},

	setIframeWidth: function()
	{
		this.$iframe.width(Garnish.$win.width()-Craft.EntryPreviewMode.formWidth);
	},

	updateIframe: function()
	{
		if (this.loading)
		{
			this.checkAgain = true;
			return;
		}

		// Has the post data changed?
		var postData = Garnish.getPostData(this.$editor);

		if (!this.lastPostData || !Craft.compare(postData, this.lastPostData))
		{
			this.lastPostData = postData;
			this.loading = true;

			var data = $.extend({}, postData, this.basePostData);

			$.post(Craft.getSiteUrl(Craft.actionTrigger+'/entries/previewEntry'), data, $.proxy(function(response)
			{
				this.iframeDocument.open();
				this.iframeDocument.write(response);
				this.iframeDocument.close();

				this.loading = false;

				if (this.checkAgain)
				{
					this.checkAgain = false;
					this.updateIframe();
				}

			}, this));
		}
	}

},
{
	formWidth: 400
});


})(jQuery);
