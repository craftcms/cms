/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend(
{
    uploader: null,
	allowedKinds: null,
	_rejectedFiles: {},
	$element: null,
	_extensionList: null,
	_totalFileCounter: 0,
	_validFileCounter: 0,
	settings: null,

    init: function($element, settings)
    {
		this._rejectedFiles = {"size": [], "type": [], "limit": []};
		this.$element = $element;
		this.allowedKinds = null;
		this._extensionList = null;
		this._totalFileCounter = 0;
		this._validFileCounter = 0;

        settings = $.extend({}, this.defaultSettings, settings);

		var events = settings.events;
		delete settings.events;

		if (settings.allowedKinds && settings.allowedKinds.length)
		{
			if (typeof settings.allowedKinds == "string")
			{
				settings.allowedKinds = [settings.allowedKinds];
			}

			this.allowedKinds = settings.allowedKinds;
			delete settings.allowedKinds;
		}

		settings.autoUpload = false;

		this.uploader = $element.fileupload(settings);
		for (var event in events)
		{
			this.uploader.on(event, events[event]);
		}

		this.settings = settings;

		this.uploader.on('fileuploadadd', $.proxy(this, 'onFileAdd'));
	},

    /**
     * Set uploader parameters.
     */
    setParams: function(paramObject)
    {
		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
		{
			// Add the CSRF token
			paramObject[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

        this.uploader.fileupload('option', {formData: paramObject});
    },

    /**
     * Get the number of uploads in progress.
     */
    getInProgress: function()
    {
        return this.uploader.fileupload('active');
    },

	/**
	 * Return true, if this is the last upload.
	 */
	isLastUpload: function()
	{
		return this.getInProgress() == 1;
	},

	/**
	 * Called on file add.
	 */
	onFileAdd: function(e, data)
	{
		e.stopPropagation();

		var validateExtension = false;

		if (this.allowedKinds)
		{
			if (!this._extensionList)
			{
				this._extensionList = [];

				for (var i = 0; i < this.allowedKinds.length; i++)
				{
					var allowedKind = this.allowedKinds[i];

					for (var j = 0; j < Craft.fileKinds[allowedKind].length; j++)
					{
						var ext = Craft.fileKinds[allowedKind][j];
						this._extensionList.push(ext);
					}
				}
			}
			validateExtension = true;
		}

		// Make sure that file API is there before relying on it
		data.process().done($.proxy(function()
		{
			var file = data.files[0];
			var pass = true;
			if (validateExtension)
			{

				var matches = file.name.match(/\.([a-z0-4_]+)$/i);
				var fileExtension = matches[1];
				if ($.inArray(fileExtension.toLowerCase(), this._extensionList) == -1)
				{
					pass = false;
					this._rejectedFiles.type.push('“' + file.name + '”');
				}
			}

			if (file.size > this.settings.maxFileSize)
			{
				this._rejectedFiles.size.push('“' + file.name + '”');
				pass = false;
			}

			// If the validation has passed for this file up to now, check if we're not hitting any limits
			if (pass && typeof this.settings.canAddMoreFiles == "function" && !this.settings.canAddMoreFiles(this._validFileCounter))
			{
				this._rejectedFiles.limit.push('“' + file.name + '”');
				pass = false;
			}

			if (pass)
			{
				this._validFileCounter++;
				data.submit();
			}

			if (++this._totalFileCounter == data.originalFiles.length)
			{
				this._totalFileCounter = 0;
				this._validFileCounter = 0;
				this.processErrorMessages();
			}

		}, this));

		return true;
	},

	/**
	 * Process error messages.
	 */
	processErrorMessages: function()
	{
		if (this._rejectedFiles.type.length)
		{
			if (this._rejectedFiles.type.length == 1)
			{
				var str = "The file {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}
			else
			{
				var str = "The files {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.type.join(", "), kinds: this.allowedKinds.join(", ")});
			this._rejectedFiles.type = [];
			alert(str);
		}

		if (this._rejectedFiles.size.length)
		{
			if (this._rejectedFiles.size.length == 1)
			{
				var str = "The file {files} could not be uploaded, because it exceeds the maximum upload size of {size}.";
			}
			else
			{
				var str = "The files {files} could not be uploaded, because they exceeded the maximum upload size of {size}.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.size.join(", "), size: Craft.maxUploadSize});
			this._rejectedFiles.size = [];
			alert(str);
		}

		if (this._rejectedFiles.limit.length)
		{
			if (this._rejectedFiles.limit.length == 1)
			{
				var str = "The file {files} could not be uploaded, because the field limit has been reached.";
			}
			else
			{
				var str = "The files {files} could not be uploaded, because the field limit has been reached.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.limit.join(", "), size: Craft.maxUploadSize});
			this._rejectedFiles.limit = [];
			alert(str);
		}
	},

    defaultSettings: {
        dropZone: null,
		pasteZone: null,
		fileInput: null,
		sequentialUploads: true,
		maxFileSize: Craft.maxUploadSize,
		alloweKinds: null,
		events: {},
		canAddMoreFiles: null
	}
});
