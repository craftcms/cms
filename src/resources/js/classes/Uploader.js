/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend(
{
    uploader: null,
	allowedKinds: null,
	_rejectedFiles: [],
	$element: null,
	_extensionList: null,
	_fileCounter: 0,

    init: function($element, settings)
    {
		this._rejectedFiles = [];
		this.$element = $element;
		this.allowedKinds = null;
		this._extensionList = null;
		this._fileCounter = 0;

        settings = $.extend({}, this.defaultSettings, settings);

		var events = settings.events;
		delete settings.events;

		if ( settings.allowedKinds && settings.allowedKinds.length)
		{
			if (typeof settings.allowedKinds == "string")
			{
				settings.allowedKinds = [settings.allowedKinds];
			}

			this.allowedKinds = settings.allowedKinds;
			delete settings.allowedKinds;
			settings.autoUpload = false;
		}

		this.uploader = $element.fileupload(settings);
		for (var event in events)
		{
			this.uploader.on(event, events[event]);
		}

		if (settings.dropZone != null)
		{
			$(document).bind('drop dragover', function(e)
			{
				e.preventDefault();
			});
		}

		if (this.allowedKinds)
		{
			this.uploader.on('fileuploadadd', $.proxy(this, 'onFileAdd'));
		}
	},

    /**
     * Set uploader parameters
     * @param paramObject
     */
    setParams: function(paramObject)
    {
        this.uploader.fileupload('option', {formData: paramObject});
    },

    /**
     * Get the number of uploads in progress
     * @returns {*}
     */
    getInProgress: function()
    {
        return this.uploader.fileupload('active');
    },

	/**
	 * Return true, if this is the last upload
	 * @returns {boolean}
	 */
	isLastUpload: function()
	{
		return this.getInProgress() == 1;
	},

	/**
	 * Called on file add
	 */
	onFileAdd: function(e, data)
	{
		e.stopPropagation();

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

		data.process().done($.proxy(function()
		{
			var file = data.files[0];
			var matches = file.name.match(/\.([a-z0-4_]+)$/i);
			var fileExtension = matches[1];
			if ($.inArray(fileExtension.toLowerCase(), this._extensionList) > -1)
			{
				data.submit();
			}
			else
			{
				this._rejectedFiles.push('"' + file.name + '"');
			}

			if (++this._fileCounter == data.originalFiles.length)
			{
				this._fileCounter = 0;
				this.processErrorMessages();
			}

		}, this));

		return true;
	},

	processErrorMessages: function()
	{
		if (this._rejectedFiles.length)
		{
			if (this._rejectedFiles.length == 1)
			{
				var str = "The file {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}
			else
			{
				var str = "The files {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.join(", "), kinds: this.allowedKinds.join(", ")});
			this._rejectedFiles = [];
			alert(str);
		}
	},

    defaultSettings: {
        dropZone: null,
		pasteZone: null,
		fileInput: null,
		autoUpload: true,
		sequentialUploads: true,
		maxFileSize: Craft.maxUploadSize,
		alloweKinds: null,
		events: {}
	}
});
