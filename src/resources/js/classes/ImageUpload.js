/**
 * postParameters     - an object of POST data to pass along with each Ajax request
 * modalClass         - class to add to the modal window to allow customization
 * uploadButton       - jQuery object of the element that should open the file chooser
 * uploadAction       - upload to this location (in form of "controller/action")
 * deleteButton       - jQuery object of the element that starts the image deletion process
 * deleteMessage      - confirmation message presented to the user for image deletion
 * deleteAction       - delete image at this location (in form of "controller/action")
 * cropAction         - crop image at this (in form of "controller/action")
 * areaToolOptions    - object with some options for the area tool selector
 *   aspectRatio      - decimal aspect ratio of width/height
 *   initialRectangle - object with options for the initial rectangle
 *     mode           - if set to auto, then the part selected will be the maximum size in the middle of image
 *     x1             - top left x coordinate of th rectangle, if the mode is not set to auto
 *     x2             - bottom right x coordinate of th rectangle, if the mode is not set to auto
 *     y1             - top left y coordinate of th rectangle, if the mode is not set to auto
 *     y2             - bottom right y coordinate of th rectangle, if the mode is not set to auto
 *
 * onImageDelete     - callback to call when image is deleted. First parameter will contain response data.
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
	},

	destroy: function()
	{
		this._imageHandler.destroy();
		delete this._imageHandler;

		this.base();
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

		constraint: 500,

		areaToolOptions:
		{
			aspectRatio: "1",
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

		var element = settings.uploadButton;
		var $uploadInput = $('<input type="file" name="image-upload"/>').hide().insertBefore(element);

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
			formData:   typeof this.settings.postParameters === 'object' ? this.settings.postParameters : {},
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

					if (response.error)
					{
						alert(response.error);
						return;
					}

					if (Craft.ImageUpload.$modalContainerDiv == null)
					{
						Craft.ImageUpload.$modalContainerDiv = $('<div class="modal fitted"></div>').addClass(settings.modalClass).appendTo(Garnish.$bod);
					}

					if (response.fileName)
					{
						this.source = response.fileName;
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

							this.modal.imageHandler = this;
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
								var areaTool = new Craft.ImageAreaTool(settings.areaToolOptions, this.modal);
								areaTool.showArea();
								this.modal.cropAreaTool = areaTool;
							}, this));
						}, this), 1);
					}
				}, this)
			},
			acceptFileTypes: /(jpg|jpeg|gif|png)/
		};

		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
		{
			// Add the CSRF token
			options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		this.uploader = new Craft.Uploader(element, options);


		this.addListener($(settings.deleteButton), 'click', function(ev)
		{
			if (confirm(settings.deleteMessage))
			{
				$(ev.currentTarget).parent().append('<div class="blocking-modal"></div>');
				Craft.postActionRequest(settings.deleteAction, settings.postParameters, $.proxy(function(response, textStatus)
				{
					if (textStatus == 'success')
					{
						this.onImageDelete(response);
					}

				}, this));

			}
		});

		this.addListener($(settings.uploadButton), 'click', function(ev)
		{
			$(ev.currentTarget).siblings('input[type=file]').click();
		});

	},

	onImageSave: function(data)
	{
		this.settings.onImageSave.apply(this, [data]);
	},

	onImageDelete: function(data)
	{
		this.settings.onImageDelete.apply(this, [data]);
	},

	destroy: function()
	{
		this.progressBar.destroy();
		delete this.progressBar;

		if (this.modal)
		{
			this.modal.destroy();
			delete this.modal;
		}

		if (this.uploader)
		{
			this.uploader.destroy();
			delete this.uploader;
		}

		this.base();
	}
});


Craft.ImageModal = Garnish.Modal.extend(
{
	$container: null,
	$saveBtn: null,
	$cancelBtn: null,

	areaSelect: null,
	source: null,
	_postParameters: null,
	_cropAction: "",
	imageHandler: null,
	cropAreaTool: null,


	init: function($container, settings)
	{
		this.cropAreaTool = null;
		this.base($container, settings);
		this._postParameters = settings.postParameters;
		this._cropAction = settings.cropAction;
	},

	bindButtons: function()
	{
		this.$saveBtn = this.$container.find('.submit:first');
		this.$cancelBtn = this.$container.find('.cancel:first');
	},

	cancel: function()
	{
		this.hide();
		this.$container.remove();
		this.destroy();
	},

	saveImage: function()
	{
		var selection = this.areaSelect.tellSelect();

		var params = {
			x1: selection.x,
			y1: selection.y,
			x2: selection.x2,
			y2: selection.y2,
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
			this.$container.remove();
			this.destroy();
		}, this));

		this.areaSelect.setOptions({disable: true});
		this.removeListener(this.$saveBtn, 'click');
		this.removeListener(this.$cancelBtn, 'click');

		this.$container.find('.crop-image').fadeTo(50, 0.5);
	}

});


Craft.ImageAreaTool = Garnish.Base.extend(
{
	api:             null,
	$container:      null,
	containingModal: null,

	init: function(settings, containingModal)
	{
		this.$container = Craft.ImageUpload.$modalContainerDiv;
		this.setSettings(settings);
		this.containingModal = containingModal;
	},

	showArea: function()
	{
		var $target = this.$container.find('img');

		var cropperOptions = {
			aspectRatio: this.settings.aspectRatio,
			maxSize: [$target.width(), $target.height()],
			bgColor: 'none'
		};


		var initCropper = $.proxy(function (api)
		{
			this.api = api;

			var x1 = this.settings.initialRectangle.x1;
			var x2 = this.settings.initialRectangle.x2;
			var y1 = this.settings.initialRectangle.y1;
			var y2 = this.settings.initialRectangle.y2;

			if (this.settings.initialRectangle.mode == "auto")
			{
				var rectangleWidth = 0;
				var rectangleHeight = 0;

				if (this.settings.aspectRatio == "")
				{
					rectangleWidth = $target.width();
					rectangleHeight = $target.height();
				}
				else if (this.settings.aspectRatio > 1)
				{
					rectangleWidth = $target.width();
					rectangleHeight = rectangleWidth / this.settings.aspectRatio;
				}
				else if (this.settings.aspectRatio < 1)
				{
					rectangleHeight = $target.height();
					rectangleWidth = rectangleHeight * this.settings.aspectRatio;
				}
				else
				{
					rectangleHeight = rectangleWidth = Math.min($target.width(), $target.height());
				}

				x1 = Math.round(($target.width() - rectangleWidth) / 2);
				y1 = Math.round(($target.height() - rectangleHeight) / 2);
				x2 = x1 + rectangleWidth;
				y2 = y1 + rectangleHeight;

			}
			this.api.setSelect([x1, y1, x2, y2]);

			this.containingModal.areaSelect = this.api;
			this.containingModal.source = $target.attr('src').split('/').pop();
			this.containingModal.updateSizeAndPosition();

		}, this);

		$target.Jcrop(cropperOptions, function ()
		{
			initCropper(this);
		});
	}
});
