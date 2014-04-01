/**
 * Asset Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend(
{
	requestId: 0,
	hud: null,
	fieldId: 0,
	uploader: null,
	progressBar: null,

	init: function(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey, fieldId)
	{
		this.base(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey);
		this.fieldId = fieldId;
		this._attachDragEvents();
	},

	_attachDragEvents: function()
	{
		this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(this.$container));

		var options = {
			url: Craft.getActionUrl('assets/expressUpload'),
			dropZone: this.$container,
			formData: {
				fieldId: this.fieldId,
				entryId: $('input[name=entryId]').val()
			}
		};

		if (typeof this.criteria.kind != "undefined")
		{
			options.allowedKinds = this.criteria.kind;
		}

		options.events = {};
		options.events.fileuploadstart = $.proxy(this, '_onUploadStart');
		options.events.fileuploadprogressall = $.proxy(this, '_onUploadProgress');
		options.events.fileuploaddone = $.proxy(this, '_onUploadComplete');

		this.uploader = new Craft.Uploader(this.$container, options);
	},

	/**
	 * Add the freshly uploaded file to the input field.
	 *
	 * @param element
	 */
	selectUploadedFile: function(element)
	{
		// Check if we're able to add new elements
		if (this.limit)
		{
			if (this.totalElements + 1 == this.limit)
			{
				return;
			}
		}

		var $newElement = element.$element;

		// Make a couple tweaks
		$newElement.addClass('removable');
		$newElement.prepend('<input type="hidden" name="'+this.name+'[]" value="'+element.id+'">' +
			'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>');

		$newElement.appendTo(this.$elementsContainer);

		var margin = -($newElement.outerWidth()+10);

		this.$addElementBtn.css('margin-'+Craft.left, margin+'px');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = 0;
		this.$addElementBtn.animate(animateCss, 'fast');

		this.$elements = this.$elements.add($newElement);
		this.initElements($newElement);

		this.totalElements ++;

		if (this.limit && this.totalElements == this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}

		if (this.modal)
		{
			this.modal.elementIndex.rememberDisabledElementId(element.id);
		}

	},

	/**
	 * On upload start.
	 *
	 * @param event
	 * @private
	 */
	_onUploadStart: function(event)
	{
		this.progressBar.$progressBar.css({
			top: Math.round(this.$container.outerHeight() / 2) - 6
		});

		this.$container.addClass('uploading');
		this.progressBar.resetProgressBar();
		this.progressBar.showProgressBar();
	},

	/**
	 * On upload progress.
	 *
	 * @param event
	 * @param data
	 * @private
	 */
	_onUploadProgress: function(event, data)
	{
		var progress = parseInt(data.loaded / data.total * 100, 10);
		this.progressBar.setProgressPercentage(progress);
	},

	/**
	 * On a file being uploaded.
	 *
	 * @param event
	 * @param data
	 * @private
	 */
	_onUploadComplete: function(event, data)
	{
		var html = $(data.result.html);
		$('head').append(data.result.css);

		this.selectUploadedFile(Craft.getElementInfo(html));

		// Last file
		if (this.uploader.isLastUpload())
		{
			this.progressBar.hideProgressBar();
			this.$container.removeClass('uploading');
		}

		this.forceModalRefresh();
	}
});
