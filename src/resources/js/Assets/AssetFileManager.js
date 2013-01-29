// define the Assets global
if (typeof Assets == 'undefined')
{
	Assets = {};
}


/**
 * File Manager.
 */
Assets.FileManager = Garnish.Base.extend({

	init: function($manager, settings)
	{
		this.$manager = $manager;

		this.setSettings(settings, Assets.FileManager.defaults);

		this.$toolbar = $('.toolbar', this.$manager);

		this.$viewAsThumbsBtn = $('a.thumbs', this.$toolbar);
		this.$viewAsListBtn   = $('a.list', this.$toolbar);

		this.$upload = $('.buttons .assets-upload');

		this.$search = $('> .search input.text', this.$toolbar);

		this.$spinner = $('.temp-spinner', this.$manager);

		this.$status = $('.asset-status');

		this.$sources = $('.assets-sources');

		this.$folderContainer = $('.folder-container');

		this.$uploadProgress = $('> .assets-fm-uploadprogress', this.$manager);
		this.$uploadProgressBar = $('.assets-fm-pb-bar', this.$uploadProgress);

        this.$modalContainerDiv = null;
        this.$prompt = null;
        this.$promptApplyToRemainingContainer = null;
        this.$promptApplyToRemainingCheckbox = null;
        this.$promptApplyToRemainingLabel = null;
        this.$promptButtons = null;

        this.modal = null;

		this.sort = 'asc';
        this.requestId = 0;
        this.promptArray = [];

        this.selectedFileIds = [];

        this._promptCallback = function (){};

		// -------------------------------------------
		// Assets states
		// -------------------------------------------

		this.currentState = {
			view: 'thumbs',
			current_source: null,
			current_folder: null
		};

		this.storageKey = 'Blocks_Assets_' + this.settings.namespace;

		if (typeof(localStorage) !== "undefined") {
			if (typeof(localStorage[this.storageKey]) == "undefined") {
				localStorage[this.storageKey] = JSON.stringify(this.currentState);
			} else {
				this.currentState = JSON.parse(localStorage[this.storageKey]);
			}
		}

		this.storeState = function (key, value) {
			this.currentState[key] = value;
			if (typeof(localStorage) !== "undefined") {
				localStorage[this.storageKey] = JSON.stringify(this.currentState);
			}
		};


		// -------------------------------------------
		//  File Uploads
		// -------------------------------------------

		this.uploader = new qqUploader.FileUploader({
			element:      this.$upload[0],
			action:       Blocks.actionUrl + '/assets/uploadFile',
			template:     '<div class="assets-qq-uploader">'
				+   '<div class="assets-qq-upload-drop-area"></div>'
				+   '<a href="javascript:;" class="btn submit assets-qq-upload-button" data-icon="↑" style="position: relative; overflow: hidden; direction: ltr; ">' + Blocks.t('Upload files') + '</a>'
				+   '<ul class="assets-qq-upload-list"></ul>'
				+ '</div>',

			fileTemplate: '<li>'
				+   '<span class="assets-qq-upload-file"></span>'
				+   '<span class="assets-qq-upload-spinner"></span>'
				+   '<span class="assets-qq-upload-size"></span>'
				+   '<a class="assets-qq-upload-cancel" href="#">Cancel</a>'
				+   '<span class="assets-qq-upload-failed-text">Failed</span>'
				+ '</li>',

			classes:      {
				button:     'assets-qq-upload-button',
				drop:       'assets-qq-upload-drop-area',
				dropActive: 'assets-qq-upload-drop-area-active',
				list:       'assets-qq-upload-list',

				file:       'assets-qq-upload-file',
				spinner:    'assets-qq-upload-spinner',
				size:       'assets-qq-upload-size',
				cancel:     'assets-qq-upload-cancel',

				success:    'assets-qq-upload-success',
				fail:       'assets-qq-upload-fail'
			},

			onSubmit:     $.proxy(this, '_onUploadSubmit'),
			onProgress:   $.proxy(this, '_onUploadProgress'),
			onComplete:   $.proxy(this, '_onUploadComplete')
		});

		// ---------------------------------------
		// Asset events
		// ---------------------------------------

		this.$sources.find('a').click($.proxy(function (event) {
			this.selectSource($(event.target));
		}, this));

		// Switch between views
		this.$viewAsThumbsBtn.click($.proxy(function () {
			this.selectViewType('thumbs');
			this.markActiveViewButton();
		}, this));

		this.$viewAsListBtn.click($.proxy(function () {
			this.selectViewType('list');
			this.markActiveViewButton();
		}, this));

		// Figure out if we need to store the source state for the first time.
		if (this.currentState.current_source == null) {
			this.storeState('current_source', this.$sources.find('a[data-source]').attr('data-source'));
		}

		this.markActiveSource(this.currentState.current_source);
		this.markActiveViewButton();

		this.reloadFolderView();
	},

	/**
	 * Select the view type to use.
	 *
	 * @param type
	 */
	selectViewType: function (type) {
		this.storeState('view', type);
		this.reloadFolderView();
	},

	/**
	 * Add the class to the appropriate view button and remove from the other.
	 */
	markActiveViewButton: function () {
		if (this.currentState.view == 'thumbs') {
			this.$viewAsThumbsBtn.addClass('active');
			this.$viewAsListBtn.removeClass('active');
		} else {
			this.$viewAsThumbsBtn.removeClass('active');
			this.$viewAsListBtn.addClass('active');
		}
	},

	/**
	 * Mark a source as selected.
	 *
	 * @param sourceId
	 */
	markActiveSource: function (sourceId) {
		this.$sources.find('a').removeClass('sel');
		this.$sources.find('a[data-source=' + sourceId + ']').addClass('sel');
	},

	/**
	 * Select the source.
	 *
	 * @param sourceElement jQuery object with the link element
	 */
	selectSource: function (sourceElement) {
		this.markActiveSource(sourceElement.attr('data-source'));
		this.storeState('current_source', sourceElement.attr('data-source'));
		this.storeState('current_folder', sourceElement.attr('data-folder'));

		this.reloadFolderView();
		this._setUploadFolder(this.getCurrentFolderId());
	},

	reloadFolderView: function () {
		this.loadFolderView(this.getCurrentFolderId());
	},

	/**
	 * Load the folder view.
	 */
	loadFolderView: function (folderId) {

		this.$spinner.show();

		var params = {
			requestId: ++this.requestId,
			folderId: folderId,
			viewType: this.currentState.view
		};

		Blocks.postActionRequest('assets/viewFolder', params, $.proxy(function(data, textStatus) {

			this.storeState('current_folder', folderId);

			if (data.requestId != this.requestId) {
				return;
			}
			this.$folderContainer.attr('data', folderId);
			this.$folderContainer.html(data.html);

			this._setUploadFolder(folderId);

			this.$spinner.hide();

			this.applyFolderBindings();

		}, this));
	},

	applyFolderBindings: function () {

		// Make ourselves available
		var _this = this;

		// File blocks editing
		this.$folderContainer.find('.open-file').dblclick(function () {
			_this.$spinner.show();
			var params = {
				requestId: ++_this.requestId,
				fileId: $(this).attr('data-file')
			};

			Blocks.postActionRequest('assets/viewFile', params, $.proxy(function(data, textStatus) {
				if (data.requestId != this.requestId) {
					return;
				}

				this.$spinner.hide();

                $modalContainerDiv = _this.$modalContainerDiv;

				if ($modalContainerDiv == null) {
					$modalContainerDiv = $('<div class="modal"></div>').addClass().appendTo(Garnish.$bod);
				}

				if (this.modal == null) {
					this.modal = new Garnish.Modal();
				}

				$modalContainerDiv.empty().append(data.headHtml);
				$modalContainerDiv.append(data.bodyHtml);
				$modalContainerDiv.append(data.footHtml);
				this.modal.setContainer($modalContainerDiv);

				this.modal.show();

				this.modal.addListener(Garnish.Modal.$shade, 'click', function () {
					this.hide();
				});

				this.modal.addListener(this.modal.$container.find('.btn.cancel'), 'click', function () {
					this.hide();
				});

				this.modal.addListener(this.modal.$container.find('.btn.submit'), 'click', function () {
					this.removeListener(Garnish.Modal.$shade, 'click');

					var params = $('form#file-blocks').serialize();

					Blocks.postActionRequest('assets/saveFile', params, $.proxy(function(data, textStatus) {
						this.hide();
					}, this));
				});

			}, _this));
		});

		this.$folderContainer.find('.open-folder').dblclick(function () {
			_this.$spinner.show();
			_this.loadFolderView($(this).attr('data-folder'));
		});

	},

	/**
	 * Gets current folder id - if none is set in the current state, then grabs it from the $folderContainer attribute.
	 * If that is empty, then grabs one from the selected source.
	 *
	 * @return mixed
	 */
	getCurrentFolderId: function () {
		if (this.currentState.current_folder == null || typeof this.currentState.current_folder == "udefined") {
			this.storeState('current_folder', this.$folderContainer.attr('data-folder'));
		}
		if (this.currentState.current_folder == 0 || this.currentState.current_folder == null) {
			this.storeState('current_folder', this.$sources.find('a[data-source=' + this.currentState.current_source + ']').attr('data-folder'));
		}

		return this.currentState.current_folder;
	},

	/**
	 * Set the upload folder parameter for uploader.
	 *
	 * @param folderId
	 * @private
	 */
	_setUploadFolder: function (folderId) {
		this.uploader.setParams({folderId: folderId});
	},

	/**
	 * Set Status
	 */
	_setStatus: function(msg) {
		this.$status.html(msg);
	},

	// -------------------------------------------
	//  Uploading
	// -------------------------------------------

	/**
	 * Set Upload Status
	 */
	_setUploadStatus: function() {
		this._setStatus('');
	},

	/**
	 * On Upload Submit
	 */
	_onUploadSubmit: function(id, fileName) {
		// is this the first file?
		if (! this.uploader.getInProgress()) {

			this.$spinner.show();

			// prepare the progress bar
			this.$uploadProgress.show();
			this.$uploadProgressBar.width('0%');
			this._uploadFileProgress = {};

			this._uploadTotalFiles = 1;
			this._uploadedFiles = 0;
		}
		else {
			this._uploadTotalFiles++;
		}

		// get ready to start recording the progress for this file
		this._uploadFileProgress[id] = 0;

		this._setUploadStatus();
	},

	/**
	 * On Upload Progress
	 */
	_onUploadProgress: function(id, fileName, loaded, total) {
		this._uploadFileProgress[id] = loaded / total;
		this._updateProgressBar();
	},

	/**
	 * On Upload Complete
	 */
	_onUploadComplete: function(id, fileName, response) {
		this._uploadFileProgress[id] = 1;
		this._updateProgressBar();

		if (response.success || response.prompt) {
			this._uploadedFiles++;

            if (this.settings.multiSelect || !this.selectedFileIds.length)
            {
                this.selectedFileIds.push(response.fileId);
            }

			this._setUploadStatus();

            if (response.prompt)
            {
                this.promptArray.push(response);
            }

		}

		// is this the last file?
		if (! this.uploader.getInProgress()) {
			if (this._uploadedFiles) {
                this.$spinner.hide();
                if (this.promptArray.length)
                {
                    this._showBatchPrompts(this.promptArray, this._uploadFollowup);
                }
                else
                {
                    this.reloadFolderView();
                }
			} else {
				// just skip to hiding the progress bar
				this._hideProgressBar();
				this.$spinner.hide();
			}
		}


	},

    _uploadFollowup: function(returnData)
    {
        this.promptArray = [];

        var finalCallback = $.proxy(function()
        {
            this.$spinner.hide();
            this.reloadFolderView();
        }, this);

        var doFollowup = $.proxy(function(parameterArray, parameterIndex, callback)
        {
            var postData = {
                additionalInfo: parameterArray[parameterIndex].additionalInfo,
                fileName:       parameterArray[parameterIndex].fileName,
                userResponse:   parameterArray[parameterIndex].choice
            };

            $.post(Blocks.actionUrl + '/assets/uploadFile', postData, $.proxy(function(data)
            {
                ++parameterIndex;

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
	 * Update Progress Bar
	 */
	_updateProgressBar: function() {
		var totalPercent = 0;

		for (var id in this._uploadFileProgress) {
			totalPercent += this._uploadFileProgress[id];
		}

		var width = Math.round(100 * totalPercent / this._uploadTotalFiles) + '%';
		this.$uploadProgressBar.width(width);
	},

	/**
	 * Hide Progress Bar
	 */
	_hideProgressBar: function() {
		this.$uploadProgress.fadeOut($.proxy(function() {
			this.$uploadProgress.hide();
		}, this));
	},

    /**
     * Show the user prompt with a given message and choices, plus an optional "Apply to remaining" checkbox.
     *
     * @param string message
     * @param array choices
     * @param function callback
     * @param int itemsToGo
     */
    _showPrompt: function(message, choices, callback, itemsToGo)
    {
        this._promptCallback = callback;

        if (this.modal == null) {
            this.modal = new Garnish.Modal();
        }

        if (this.$modalContainerDiv == null) {
            this.$modalContainerDiv = $('<div class="modal"></div>').addClass().appendTo(Garnish.$bod);
        }

        this.$prompt = $('<div class="body"></div>').appendTo(this.$modalContainerDiv.empty());

        this.$promptMessage = $('<p class="assets-prompt-msg"/>').appendTo(this.$prompt);

        $('<p>').html(Blocks.t('What do you want to do?')).appendTo(this.$prompt);

        this.$promptApplyToRemainingContainer = $('<label class="assets-applytoremaining"/>').appendTo(this.$prompt).hide();
        this.$promptApplyToRemainingCheckbox = $('<input type="checkbox"/>').appendTo(this.$promptApplyToRemainingContainer);
        this.$promptApplyToRemainingLabel = $('<span/>').appendTo(this.$promptApplyToRemainingContainer);
        this.$promptButtons = $('<div class="buttons"/>').appendTo(this.$prompt);


        this.modal.setContainer(this.$modalContainerDiv);


        this.$promptMessage.html(message);

        for (var i = 0; i < choices.length; i++)
        {
            var $btn = $('<div class="assets-btn btn" data-choice="'+choices[i].value+'">' + choices[i].title + '</div>');

            this.addListener($btn, 'activate', function(ev)
            {
                var choice = ev.currentTarget.getAttribute('data-choice'),
                    applyToRemaining = this.$promptApplyToRemainingCheckbox.prop('checked');

                this._selectPromptChoice(choice, applyToRemaining);
            });

            this.$promptButtons.append($btn).append('<br />');
        }

        if (itemsToGo)
        {
            this.$promptApplyToRemainingContainer.show();
            this.$promptApplyToRemainingLabel.html(Blocks.t('Apply this to the {number} remaining conflicts', {number: itemsToGo}));
        }

        this.modal.show();
        this.modal.removeListener(Garnish.Modal.$shade, 'click');
        this.addListener(Garnish.Modal.$shade, 'click', '_cancelPrompt');

    },

    /**
     * Handles when a user selects one of the prompt choices.
     *
     * @param object ev
     */
    _selectPromptChoice: function(choice, applyToRemaining)
    {
        this.$prompt.fadeOut('fast', $.proxy(function() {
            this.modal.hide();
            this._promptCallback(choice, applyToRemaining);
        }, this));
    },

    /**
     * Cancels the prompt.
     */
    _cancelPrompt: function()
    {
        this._selectPromptChoice('cancel', true);
    },

    /**
     * Shows a batch of prompts.
     *
     * @param array   promts
     * @param funtion callback
     */
    _showBatchPrompts: function(prompts, callback)
    {
        this._promptBatchData = prompts;
        this._promptBatchCallback = callback;
        this._promptBatchReturnData = [];
        this._promptBatchNum = 0;

        this._showNextPromptInBatch();
    },

    /**
     * Shows the next prompt in the batch.
     */
    _showNextPromptInBatch: function()
    {
        var prompt = this._promptBatchData[this._promptBatchNum].prompt,
            remainingInBatch = this._promptBatchData.length - (this._promptBatchNum + 1);

        this._showPrompt(prompt.message, prompt.choices, $.proxy(this, '_handleBatchPromptSelection'), remainingInBatch);
    },

    /**
     * Handles a prompt choice selection.
     *
     * @param string choice
     * @param bool   applyToRemaining
     */
    _handleBatchPromptSelection: function(choice, applyToRemaining)
    {
        var prompt = this._promptBatchData[this._promptBatchNum],
            remainingInBatch = this._promptBatchData.length - (this._promptBatchNum + 1);

        // Record this choice
        this._promptBatchReturnData.push({
            fileName:       prompt.fileName,
            choice:         choice,
            additionalInfo: prompt.additionalInfo
        });

        // Are there any remaining items in the batch?
        if (remainingInBatch)
        {
            // Get ready to deal with the next prompt
            this._promptBatchNum++;

            // Apply the same choice to the remaining items?
            if (applyToRemaining)
            {
                this._handleBatchPromptSelection(choice, true);
            }
            else
            {
                // Show the next prompt
                this._showNextPromptInBatch();
            }
        }
        else
        {
            // All done! Call the callback
            if (typeof this._promptBatchCallback == 'function')
            {
                this._promptBatchCallback(this._promptBatchReturnData);
            }
        }
    }

},
{
	defaults: {
		mode:          'full',
		multiSelect:   true,
		kinds:         'any',
		disabledFiles: [],
		namespace:     'panel'
	}
});
