/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend({

    $buttons: null,
    $uploadButton: null,
    $progressBar: null,

    uploader: null,
    promptHandler: null,
    progressBar: null,

    initialSourceKey: null,
    isIndexBusy: false,
    _uploadFileProgress: {},
    uploadedFileIds: [],

	init: function(elementType, $container, settings)
	{

        // Piggyback some callbacks
        settings.onSelectSource = this.addCallback(settings.onSelectSource, $.proxy(this, '_selectSource'));
        settings.onAfterHtmlInit = this.addCallback(settings.onAfterHtmlInit, $.proxy(this, '_initializeComponents'));
        settings.onUpdateElements = this.addCallback(settings.onUpdateElements, $.proxy(this, '_onUpdateElements'));

        this.base(elementType, $container, settings);
    },

    /**
     * Initialize the uploader.
     *
     * @private
     */
    _initializeComponents: function ()
    {
        if (!this.$buttons)
        {
            this.$buttons = $('<div class="buttons"></div>').prependTo(this.$sidebar);
        }

        if (!this.$uploadButton)
        {
            this.$uploadButton = $('<div class="assets-upload"></div>').prependTo(this.$buttons);
        }

        if (!this.$progressBar)
        {
            this.$progressBar = $('<div class="assets-uploadprogress hidden"><div class="assets-progressbar"><div class="assets-pb-bar"></div></div></div>').appendTo(this.$main);
        }

        this.promptHandler = new Assets.PromptHandler();
        this.progressBar = new Assets.ProgressBar(this.$progressBar);

        var uploaderCallbacks = {
            onSubmit:     $.proxy(this, '_onUploadSubmit'),
            onProgress:   $.proxy(this, '_onUploadProgress'),
            onComplete:   $.proxy(this, '_onUploadComplete')
        };

        this.uploader = new Assets.Uploader (this.$uploadButton, uploaderCallbacks);
    },

    /**
     * Select a different source.
     *
     * @param sourceKey
     * @private
     */
    _selectSource: function (sourceKey)
    {
        this.uploader.setParams({folderId: sourceKey.split(':')[1]});
    },

    /**
     * React on upload submit.
     *
     * @param id
     * @private
     */
    _onUploadSubmit: function(id) {
        // prepare an upload batch
        if (! this.uploader.getInProgress()) {

            this.setIndexBusy();

            // Initial values
            this.progressBar.resetProgressBar();
            this._uploadFileProgress = {};
            this.uploadedFileIds = [];
        }

        // Prepare tracking
        this._uploadFileProgress[id] = 0;

    },

    /**
     * Update uploaded byte count.
     */
    _onUploadProgress: function(id, fileName, loaded, total) {
        this._uploadFileProgress[id] = loaded / total;
        this._updateUploadProgress();
    },

    /**
     * Update Progress Bar.
     */
    _updateUploadProgress: function() {
        var totalPercent = 0;

        for (var id in this._uploadFileProgress) {
            totalPercent += this._uploadFileProgress[id];
        }

        var width = Math.round(100 * totalPercent / this._uploadTotalFiles);
        this.progressBar.setProgressPercentage(width);
    },

    /**
     * On Upload Complete.
     */
    _onUploadComplete: function(id, fileName, response) {
        this._uploadFileProgress[id] = 1;
        this._updateUploadProgress();

        if (response.success || response.prompt) {

            // TODO respect the select settings
            // Add the uploaded file to the selected ones, if appropriate
            this.uploadedFileIds.push(response.fileId);

            // If there is a prompt, add it to the queue
            if (response.prompt)
            {
                this.promptHandler.addPrompt(response);
            }
        }

        // is this the last file?
        if (! this.uploader.getInProgress()) {

            this.setIndexAvailable();
            this.progressBar.hideProgressBar();

            if (this.promptHandler.getPromptCount())
            {
                this.promptHandler.showBatchPrompts($.proxy(this, '_uploadFollowup'));
            }
            else
            {
                this.updateElements();
            }
        }
    },

    setIndexBusy: function () {
        this.isIndexBusy = true;
    },

    setIndexAvailable: function () {
        this.isIndexBusy = false;
    },

    /**
     * Follow up to an upload that triggered at least one conflict resolution prompt.
     *
     * @param returnData
     * @private
     */
    _uploadFollowup: function(returnData)
    {
        this.setIndexBusy();
        this.progressBar.resetProgressBar();

        this.promptHandler.resetPrompts();

        var finalCallback = $.proxy(function()
        {
            this.setIndexBusy();
            this.progressBar.hideProgressBar();
            this.updateElements();
        }, this);

        this.progressBar.setItemCount(returnData.length);

        var doFollowup = $.proxy(function(parameterArray, parameterIndex, callback)
        {
            var postData = {
                additionalInfo: parameterArray[parameterIndex].additionalInfo,
                fileName:       parameterArray[parameterIndex].fileName,
                userResponse:   parameterArray[parameterIndex].choice
            };

            Craft.postActionRequest('assets/uploadFile', postData, $.proxy(function(data)
            {
                if (typeof data.fileId != "undefined")
                {
                    this.uploadedFileIds.push(data.fileId);
                }
                parameterIndex++;
                this.progressBar.incrementProcessedItemCount(1);
                this.progressBar.updateProgressBar();

                if (parameterIndex == parameterArray.length)
                {
                    callback();
                }
                else
                {
                    doFollowup(parameterArray, parameterIndex, callback);
                }
            }, this));
        }, this);

        doFollowup(returnData, 0, finalCallback);
    },

    /**
     * Perform actions after updating elements
     * @private
     */
    _onUpdateElements: function ()
    {
        // See if we have freshly uploaded files to add to selection
        if (this.uploadedFileIds.length)
        {
            var item = null;
            for (var i = 0; i < this.uploadedFileIds.length; i++)
            {
                item = this.$main.find('[data-id=' + this.uploadedFileIds[i] + ']');
                this.selector.selectItem(item);
            }

            // Reset the list.
            this.uploadedFileIds = [];
        }
    }
});

// Register it!
Craft.registerElementIndexClass('Asset', Craft.AssetIndex);
