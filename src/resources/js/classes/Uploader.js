/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend({

    uploader: null,
	allowedKinds: null,
	_rejectedFiles: [],

	_extensionList: null,
	_fileCounter: 0,

    init: function($element, settings)
    {
        settings = $.extend({}, this.defaultSettings, settings);

		var events = settings.events;
		delete settings.events;

		if ( settings.allowedKinds && settings.allowedKinds.length)
		{
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
			$(document).bind('drop dragover', function (e) {
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
    setParams: function (paramObject)
    {
        this.uploader.fileupload('option', {formData: paramObject});
    },

    /**
     * Get the number of uploads in progress
     * @returns {*}
     */
    getInProgress: function ()
    {
        return this.uploader.fileupload('active');
    },

	/**
	 * Return true, if this is the last upload
	 * @returns {boolean}
	 */
	isLastUpload: function ()
	{
		return this.getInProgress() == 1;
	},

	/**
	 * Called on file add
	 */
	onFileAdd: function (e, data)
	{
		if (!this._extensionList)
		{
			this._extensionList = [];
			for (var kind in this.allowedKinds)
			{
				for (var ext in Craft.fileKinds[this.allowedKinds[kind]])
				{
					this._extensionList.push(Craft.fileKinds[this.allowedKinds[kind]][ext]);
				}
			}
		}

		data.process().done($.proxy(function ()
		{
			var file = data.files[0];
			var matches = file.name.match(/\.([a-z0-4_]+)$/i);
			var fileExtension = matches[1];
			if ($.inArray(fileExtension, this._extensionList) > -1)
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
	},

	processErrorMessages: function ()
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
        dropzone: null,
		pasteZone: null,
		fileInput: null,
		autoUpload: true,
		sequentialUploads: true,
		maxFileSize: Craft.maxUploadSize,
		alloweKinds: null,
		events: {}
	}
});
