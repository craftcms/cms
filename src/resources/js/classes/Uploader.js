/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend({

    uploader: null,

    init: function($element, settings)
    {
        settings = $.extend(this.defaultSettings, settings);

		var events = settings.events;
		delete settings.events;

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

    defaultSettings: {
        dropzone: null,
		pasteZone: null,
		fileInput: null,
		autoUpload: true,
		sequentialUploads: true,
		maxFileSize: Craft.maxUploadSize,
		events: {}
	}
});
