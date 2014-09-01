/**
 * postParameters    - an object of POST data to pass along with each Ajax request
 * modalClass        - class to add to the modal window to allow customization
 * uploadButton      - jQuery object of the element that should open the file chooser
 * uploadAction      - upload to this location (in form of "controller/action")
 * deleteButton      - jQuery object of the element that starts the image deletion process
 * deleteMessage     - confirmation message presented to the user for image deletion
 * deleteAction      - delete image at this location (in form of "controller/action")
 * cropAction        - crop image at this (in form of "controller/action")
 * areaToolOptions   - object with some options for the area tool selector
 *   aspectRatio     - aspect ration to enforce in form of "width:height". If empty, then select area is freeform
 *   intialRectangle - object with options for the initial rectangle
 *     mode          - if set to auto, then the part selected will be the maximum size in the middle of image
 *     x1            - top left x coordinate of th rectangle, if the mode is not set to auto
 *     x2            - bottom right x coordinate of th rectangle, if the mode is not set to auto
 *     y1            - top left y coordinate of th rectangle, if the mode is not set to auto
 *     y2            - bottom right y coordinate of th rectangle, if the mode is not set to auto
 *
 * onImageDelete     - callback to call when image is deleted. First parameter will containt respone data.
 * onImageSave       - callback to call when an cropped image is saved. First parameter will contain response data.
 */


/**
 * Image Upload tool.
 */
Craft.ImageUpload = Garnish.Base.extend(
{
	_imageHandler: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ImageUpload.defaults);
		this._imageHandler = new Craft.ImageHandler(settings);
	}
},
{
	$modalContainerDiv: null,

	defaults: {
		postParameters: {},

		modalClass: "",
		uploadButton: {},
		uploadAction: "",

		deleteButton: {},
		deleteMessage: "",
		deleteAction: "",

		cropAction:"",

		areaToolOptions:
		{
			aspectRatio: "1:1",
			initialRectangle: {
				mode: "auto",
				x1: 0,
				x2: 0,
				y1: 0,
				y2: 0
			}
		},

		onImageDelete: function(response)
		{
			location.reload();
		},
		onImageSave: function(response)
		{
			location.reload();
		}
	}
});


Craft.ImageHandler = Garnish.Base.extend(
{
	modal: null,
	progressBar: null,
	$container: null,

	init: function(settings)
	{
		this.setSettings(settings);

		var _this = this;

		var element = settings.uploadButton;
		var $uploadInput = $('<input type="file" name="image-upload" id="image-upload" />').hide().insertBefore(element);

		this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').insertBefore(element));
		this.progressBar.$progressBar.css({
			top: Math.round(element.outerHeight() / 2) - 6
		});

		this.$container = element.parent();

		var options = {
			url: Craft.getActionUrl(this.settings.uploadAction),
			fileInput: $uploadInput,

			element:    this.settings.uploadButton[0],
			action:     Craft.actionUrl + '/' + this.settings.uploadAction,
			formData:   this.settings.postParameters,
			events:     {
				fileuploadstart: $.proxy(function()
				{
					this.$container.addClass('uploading');
					this.progressBar.resetProgressBar();
					this.progressBar.showProgressBar();
				}, this),
				fileuploadprogressall: $.proxy(function(data)
				{
					var progress = parseInt(data.loaded / data.total * 100, 10);
					this.progressBar.setProgressPercentage(progress);
				}, this),
				fileuploaddone: $.proxy(function(event, data)
				{
					this.progressBar.hideProgressBar();
					this.$container.removeClass('uploading');

					var response = data.result;

					if (Craft.ImageUpload.$modalContainerDiv == null)
					{
						Craft.ImageUpload.$modalContainerDiv = $('<div class="modal fitted"></div>').addClass(settings.modalClass).appendTo(Garnish.$bod);
					}

					if (response.html)
					{
						Craft.ImageUpload.$modalContainerDiv.empty().append(response.html);

						if (!this.modal)
						{
							this.modal = new Craft.ImageModal(Craft.ImageUpload.$modalContainerDiv, {
								postParameters: settings.postParameters,
								cropAction:     settings.cropAction
							});

							this.modal.imageHandler = _this;
						}
						else
						{
							this.modal.show();
						}

						this.modal.bindButtons();
						this.modal.addListener(this.modal.$saveBtn, 'click', 'saveImage');
						this.modal.addListener(this.modal.$cancelBtn, 'click', 'cancel');

						this.modal.removeListener(Garnish.Modal.$shade, 'click');

						setTimeout($.proxy(function()
						{
							Craft.ImageUpload.$modalContainerDiv.find('img').load($.proxy(function()
							{
								var profileTool = new Craft.ImageAreaTool(settings.areaToolOptions);
								profileTool.showArea(this.modal);
								this.modal.cropAreaTool = profileTool;
							}, this));
						}, this), 1);
					}
				}, this)
			},
			acceptFileTypes: /(jpg|jpeg|gif|png)/
		};

		// Add the CSRF token
		options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;

		this.uploader = new Craft.Uploader(element, options);


		$(settings.deleteButton).click(function()
		{
			if (confirm(settings.deleteMessage))
			{
				$(this).parent().append('<div class="blocking-modal"></div>');
				Craft.postActionRequest(settings.deleteAction, settings.postParameters, $.proxy(function(response, textStatus)
				{
					if (textStatus == 'success')
					{
						_this.onImageDelete.apply(_this, [response]);
					}

				}, this));

			}
		});
		$(settings.uploadButton).on('click', function(event)
		{
			$(this).siblings('input[type=file]').click();
		});

	},

	onImageSave: function(data)
	{
		this.settings.onImageSave.apply(this, [data]);
	},

	onImageDelete: function(data)
	{
		this.settings.onImageDelete.apply(this, [data]);
	}
});


Craft.ImageModal = Garnish.Modal.extend(
{
	$container: null,
	$saveBtn: null,
	$cancelBtn: null,

	areaSelect: null,
	factor: null,
	source: null,
	_postParameters: null,
	_cropAction: "",
	imageHandler: null,
	originalWidth: 0,
	originalHeight: 0,
	constraint: 0,
	cropAreaTool: null,


	init: function($container, settings)
	{
		this.cropAreaTool = null;
		this.base($container, settings);
		this._postParameters = settings.postParameters;
		this._cropAction = settings.cropAction;
		this.addListener(this.$container, 'resize', $.proxy(this, '_onResize'));
		this.addListener(Garnish.$bod, 'resize', $.proxy(this, '_onResize'));
	},

	_onResize: function ()
	{
		var $img = this.$container.find('img'),
			leftDistance = parseInt(this.$container.css('left'), 10),
			topDistance = parseInt(this.$container.css('top'), 10);

		var quotient = this.originalWidth / this.originalHeight,
			leftAvailable = leftDistance - 10,
			topAvailable = topDistance - 10;

		if (leftAvailable / quotient > topAvailable)
		{
			newWidth = this.$container.width() + (topAvailable * quotient);
		}
		else
		{
			newWidth = this.$container.width() + leftAvailable;
		}
		// Set the size so that the image always fits into a constraint x constraint box
		newWidth = Math.min(newWidth, this.constraint, this.constraint * quotient, this.originalWidth);
		this.$container.width(newWidth);

		var newWidth = this.$container.width(),
			factor = newWidth / this.originalWidth,
			newHeight = this.originalHeight * factor;

		$img.height(newHeight).width(newWidth);
		this.factor = factor;
		if (this.cropAreaTool)
		{
			$img.imgAreaSelect({instance: true}).update();
		}
	},

	bindButtons: function()
	{
		this.$saveBtn = this.$container.find('.submit:first');
		this.$cancelBtn = this.$container.find('.cancel:first');
	},

	cancel: function()
	{
		this.hide();
		this.areaSelect.setOptions({remove: true, hide: true, disable: true});
		this.$container.empty();
	},

	saveImage: function()
	{
		var selection = this.areaSelect.getSelection();
		var params = {
			x1: Math.round(selection.x1 / this.factor),
			x2: Math.round(selection.x2 / this.factor),
			y1: Math.round(selection.y1 / this.factor),
			y2: Math.round(selection.y2 / this.factor),
			source: this.source
		};

		params = $.extend(this._postParameters, params);

		Craft.postActionRequest(this._cropAction, params, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				if (response.error)
				{
					Craft.cp.displayError(response.error);
				}
				else
				{
					this.imageHandler.onImageSave.apply(this.imageHandler, [response]);
				}
			}

			this.hide();
			this.$container.empty();
			this.areaSelect.setOptions({remove: true, hide: true, disable: true});


		}, this));

		this.areaSelect.setOptions({disable: true});
		this.removeListener(this.$saveBtn, 'click');
		this.removeListener(this.$cancelBtn, 'click');

		this.$container.find('.crop-image').fadeTo(50, 0.5);
	}

});


Craft.ImageAreaTool = Garnish.Base.extend(
{
	$container: null,

	init: function(settings)
	{
		this.$container = Craft.ImageUpload.$modalContainerDiv;
		this.setSettings(settings);
	},

	showArea: function(referenceObject)
	{
		var $target = this.$container.find('img');


		var areaOptions = {
			aspectRatio: this.settings.aspectRatio,
			maxWidth: $target.width(),
			maxHeight: $target.height(),
			instance: true,
			resizable: true,
			show: true,
			persistent: true,
			handles: true,
			parent: $target.parent(),
			classPrefix: 'imgareaselect'
		};

		var areaSelect = $target.imgAreaSelect(areaOptions);

		var x1 = this.settings.initialRectangle.x1;
		var x2 = this.settings.initialRectangle.x2;
		var y1 = this.settings.initialRectangle.y1;
		var y2 = this.settings.initialRectangle.y2;

		if (this.settings.initialRectangle.mode == "auto")
		{
			var proportions = this.settings.aspectRatio.split(":");
			var rectangleWidth = 0;
			var rectangleHeight = 0;


			// [0] - width proportion, [1] - height proportion
			if (proportions[0] > proportions[1])
			{
				rectangleWidth = $target.width();
				rectangleHeight = rectangleWidth * proportions[1] / proportions[0];
			} else if (proportions[0] > proportions[1])
			{
				rectangleHeight = $target.height();
				rectangleWidth = rectangleHeight * proportions[0] / proportions[1];
			} else {
				rectangleHeight = rectangleWidth = Math.min($target.width(), $target.height());
			}
			x1 = Math.round(($target.width() - rectangleWidth) / 2);
			y1 = Math.round(($target.height() - rectangleHeight) / 2);
			x2 = x1 + rectangleWidth;
			y2 = y1 + rectangleHeight;

		}
		areaSelect.setSelection(x1, y1, x2, y2);
		areaSelect.update();

		referenceObject.areaSelect = areaSelect;
		referenceObject.factor = $target.data('factor');
		referenceObject.originalHeight = $target.attr('height') / referenceObject.factor;
		referenceObject.originalWidth = $target.attr('width') / referenceObject.factor;
		referenceObject.constraint = $target.data('constraint');
		referenceObject.source = $target.attr('src').split('/').pop();
		referenceObject.updateSizeAndPosition();
	}
});
