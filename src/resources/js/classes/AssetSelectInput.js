/**
 * Element Select input
 */

Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend({

	requestId: 0,
	hud: null,
	fieldId: 0,

	init: function(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey, fieldId)
	{
		this.base(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey);
		this.fieldId = fieldId;
		this._attachHUDEvents();
		this._attachDragEvents();
	},

	selectElements: function (elements)
	{
		console.log(elements);
		this.base(elements);
		this._attachHUDEvents();
	},

	onHide: function ()
	{
		console.log('canceling, sir');
	},

	_attachHUDEvents: function ()
	{
		this.removeListener(this.$elements, 'dlbclick');
		this.addListener(this.$elements, 'dblclick', $.proxy(this, '_editProperties'));
	},

	_editProperties: function (event)
	{
		var $target = $(event.currentTarget);
		if (!$target.data('ElementEditor'))
		{
			var settings = {
				elementId: $target.attr('data-id'),
				$trigger: $target,
				loadContentAction: 'assets/editFileContent',
				saveContentAction: 'assets/saveFileContent'
			};
			$target.data('ElementEditor', new Craft.ElementEditor(settings));
		}

		$target.data('ElementEditor').show();
	},

	_attachDragEvents: function ()
	{
		var elem = this.$container;

		var progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(elem));
		progressBar.$progressBar.css({
			top: Math.round(elem.outerHeight() / 2) - 6
		});

		$(document).bind('drop dragover', function (e) {
			e.preventDefault();
		});

		var options = {
			url: Craft.getActionUrl('assets/expressUpload'),
			dropZone: elem,
			pasteZone: null,
			fileInput: null,
			formData: {
				fieldId: this.fieldId,
				entryId: $('input[name=entryId]').val()
			},
			autoUpload: true,
			sequentialUploads: true
		};

		var _this = this;
		elem.fileupload(options)
			.bind('fileuploadstart', function (event)
			{
				elem.addClass('uploading');
				progressBar.resetProgressBar();
				progressBar.showProgressBar();
			})
			.bind('fileuploadprogressall', function (event, data)
			{
				var progress = parseInt(data.loaded / data.total * 100, 10);
				progressBar.setProgressPercentage(progress);
			})
			.bind('fileuploaddone', function (event, data)
			{
				var html = $(data.result.html);
				$('head').append(data.result.css);

				_this.selectUploadedFile(Craft.getElementInfo(html));

				// Last file
				if ($(this).fileupload('active') == 1)
				{
					progressBar.hideProgressBar();
					elem.removeClass('uploading');
				}
			});

	},

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

		this.$addElementBtn.css('margin-left', margin+'px');
		this.$addElementBtn.animate({
			marginLeft: 0
		}, 'fast');

		this.$elements = this.$elements.add($newElement);
		this.initElements($newElement);

		this.totalElements ++;

		if (this.limit && this.totalElements == this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}
	}
});
