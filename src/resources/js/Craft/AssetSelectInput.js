/**
 * Asset Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend(
{
	requestId: 0,
	hud: null,
	uploader: null,
	progressBar: null,

	originalFilename: '',
	originalExtension: '',

	init: function (settings) {
		settings.editorSettings = {
			onShowHud: $.proxy(this.resetOriginalFilename, this),
			onCreateForm: $.proxy(this._renameHelper, this),
			validators: [$.proxy(this.validateElementForm, this)]
		};

		this.base(settings);
		this._attachUploader();
	},

	/**
	 * Attach the uploader with drag event handler
	 */
	_attachUploader: function () {
		this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(this.$container));

		var options = {
			url: Craft.getActionUrl('assets/expressUpload'),
			dropZone: this.$container,
			formData: {
				fieldId: this.settings.fieldId,
				elementId: this.settings.sourceElementId
			}
		};

		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
			// Add the CSRF token
			options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		if (typeof this.settings.criteria.kind != "undefined") {
			options.allowedKinds = this.settings.criteria.kind;
		}

		options.canAddMoreFiles = $.proxy(this, 'canAddMoreFiles');

		options.events = {};
		options.events.fileuploadstart = $.proxy(this, '_onUploadStart');
		options.events.fileuploadprogressall = $.proxy(this, '_onUploadProgress');
		options.events.fileuploaddone = $.proxy(this, '_onUploadComplete');

		this.uploader = new Craft.Uploader(this.$container, options);
	},

	/**
	 * Add the freshly uploaded file to the input field.
	 */
	selectUploadedFile: function (element) {
		// Check if we're able to add new elements
		if (!this.canAddMoreElements()) {
			return;
		}

		var $newElement = element.$element;

		// Make a couple tweaks
		$newElement.addClass('removable');
		$newElement.prepend('<input type="hidden" name="' + this.settings.name + '[]" value="' + element.id + '">' +
			'<a class="delete icon" title="' + Craft.t('Remove') + '"></a>');

		$newElement.appendTo(this.$elementsContainer);

		var margin = -($newElement.outerWidth() + 10);

		this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

		var animateCss = {};
		animateCss['margin-' + Craft.left] = 0;
		this.$addElementBtn.velocity(animateCss, 'fast');

		this.addElements($newElement);

		delete this.modal;
	},

	/**
	 * On upload start.
	 */
	_onUploadStart: function (event) {
		this.progressBar.$progressBar.css({
			top: Math.round(this.$container.outerHeight() / 2) - 6
		});

		this.$container.addClass('uploading');
		this.progressBar.resetProgressBar();
		this.progressBar.showProgressBar();
	},

	/**
	 * On upload progress.
	 */
	_onUploadProgress: function (event, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		this.progressBar.setProgressPercentage(progress);
	},

	/**
	 * On a file being uploaded.
	 */
	_onUploadComplete: function (event, data) {
		if (data.result.error) {
			alert(data.result.error);
		}
		else {
			var html = $(data.result.html);
			Craft.appendHeadHtml(data.result.headHtml);
			this.selectUploadedFile(Craft.getElementInfo(html));
		}

		// Last file
		if (this.uploader.isLastUpload()) {
			this.progressBar.hideProgressBar();
			this.$container.removeClass('uploading');
		}
	},

	/**
	 * We have to take into account files about to be added as well
	 */
	canAddMoreFiles: function (slotsTaken) {
		return (!this.settings.limit || this.$elements.length + slotsTaken < this.settings.limit);
	},

	/**
	 * Parse the passed filename into the base filename and extension.
	 *
	 * @param filename
	 * @returns {{extension: string, baseFileName: string}}
	 */
	_parseFilename: function (filename) {
		var parts = filename.split('.'),
			extension = '';

		if (parts.length > 1) {
			extension = parts.pop();
		}
		var baseFileName = parts.join('.');
		return {extension: extension, baseFileName: baseFileName};
	},

	/**
	 * A helper function or the filename field.
	 * @private
	 */
	_renameHelper: function ($form) {
		$('.renameHelper', $form).on('focus', $.proxy(function (e) {
			input = e.currentTarget;
			var filename = this._parseFilename(input.value);

			if (this.originalFilename == "" && this.originalExtension == "") {
				this.originalFilename = filename.baseFileName;
				this.originalExtension = filename.extension;
			}

			var startPos = 0,
				endPos = filename.baseFileName.length;

			if (typeof input.selectionStart != "undefined") {
				input.selectionStart = startPos;
				input.selectionEnd = endPos;
			} else if (document.selection && document.selection.createRange) {
				// IE branch
				input.select();
				var range = document.selection.createRange();
				range.collapse(true);
				range.moveEnd("character", endPos);
				range.moveStart("character", startPos);
				range.select();
			}

		}, this));
	},

	resetOriginalFilename: function () {
		this.originalFilename = "";
		this.originalExtension = "";
	},

	validateElementForm: function () {
		var $filenameField = $('.renameHelper', this.elementEditor.hud.$hud.data('elementEditor').$form);
		var filename = this._parseFilename($filenameField.val());

		if (filename.extension != this.originalExtension) {
			// Blank extension
			if (filename.extension == "") {
				// If filename changed as well, assume removal of extension a mistake
				if (this.originalFilename != filename.baseFileName) {
					$filenameField.val(filename.baseFileName + '.' + this.originalExtension);
					return true;
				} else {
					// If filename hasn't changed, make sure they want to remove extension
					return confirm(Craft.t("Are you sure you want to remove the extension “.{ext}”?", {ext: this.originalExtension}));
				}
			} else {
				// If the extension has changed, make sure it s intentional
				return confirm(Craft.t("Are you sure you want to change the extension from “.{oldExt}” to “.{newExt}”?",
					{
						oldExt: this.originalExtension,
						newExt: filename.extension
					}));
			}
		}
		return true;
	}
});
