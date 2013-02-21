// define the Assets global
if (typeof Assets == 'undefined')
{
	Assets = {};
}


/**
 * File Manager.
 */
Assets.FileManager = Garnish.Base.extend({

	$activeFolder: null,

	init: function($manager, settings)
	{
		this.$manager = $manager;
        this.setSettings(settings, Assets.FileManager.defaults);

		this.$toolbar = $('.toolbar', this.$manager);
        this.$upload = $('.buttons .assets-upload');
        this.$search = $('> .search input.text', this.$toolbar);
        this.$spinner = $('.temp-spinner', this.$manager);
        this.$status = $('.asset-status');
        this.$scrollpane = null;
        this.$folders = Blocks.cp.$sidebarNav.find('.assets-folders:first');
        this.$folderContainer = $('.folder-container');

		this.$viewAsThumbsBtn = $('a.thumbs', this.$toolbar);
		this.$viewAsListBtn   = $('a.list', this.$toolbar);

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
        this.offset = 0;
        this.nextOffset = 0;
        this.lastPageReached = false;

        this.selectedFileIds = [];
        this.folders = [];

        this.folderSelect = null;
        this.fileSelect = null;
        this.filesView = null;
        this.fileDrag = null;
        this.folderDrag = null;

        this._promptCallback = function (){};


        if (this.settings.mode == 'full')
        {
            this.$scrollpane = Garnish.$win;
        }
        else
        {
            this.$scrollpane = this.$manager;
        }

		// -------------------------------------------
		// Assets states
		// -------------------------------------------

		this.currentState = {
			view: 'thumbs',
			currentFolder: null,
            folders: {}
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

        /**
         * Store folder state.
         *
         * @param folderId
         * @param state
         */
        this.setFolderState = function (folderId, state)
        {
            if (typeof this.currentState.folders != "object")
            {
                var folders = {};
            }
            else
            {
                folders = this.currentState.folders;
            }

            folders[folderId] = state;
            this.storeState('folders', folders);
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

		if (this.$upload.length == 2)
		{
			$(this.$upload[1]).replaceWith($(this.$upload[0]).clone(true));
		}

        // -------------------------------------------
        //  Folders
        // -------------------------------------------

        // initialize the folder select
        this.folderSelect = new Garnish.Select(this.$folders, {
            selectedClass:     'sel',
            multi:             false,
            waitForDblClick:   false,
            vertical:          true,
            onSelectionChange: $.proxy(this, 'loadFolderContents')
        });

        // initialize top-level folders
        this.$topFolderUl = this.$folders;
        this.$topFolderLis = this.$topFolderUl.children().filter('li');

        // stop initializing everything if there are no folders
        if (! this.$topFolderLis.length) return;

        for (var i = 0; i < this.$topFolderLis.length; i++)
        {
            folder = new Assets.FileManagerFolder(this, this.$topFolderLis[i], 1);
        }

		// ---------------------------------------
		// Asset events
		// ---------------------------------------

		// Switch between views
		this.$viewAsThumbsBtn.click($.proxy(function () {
			this.selectViewType('thumbs');
			this.markActiveViewButton();
		}, this));

		this.$viewAsListBtn.click($.proxy(function () {
			this.selectViewType('list');
			this.markActiveViewButton();
		}, this));

		// Figure out if we need to store the folder state for the first time.
		if (typeof this.currentState.currentFolder == "undefined" || this.currentState.currentFolder == null) {
			this.storeState('currentFolder', this.$folders.find('a[data-folder]').attr('data-folder'));
		}


		this.markActiveFolder(this.currentState.currentFolder);
		this.markActiveViewButton();

        // expand folders
        for (var folder in this.currentState.folders)
        {
            if (this.currentState.folders[folder] == 'expanded'
                && typeof this.folders[folder] !== 'undefined'
                && this.folders[folder].hasSubfolders())
            {
                this.folders[folder]._prepForSubfolders();
                this.folders[folder].expand();
            }
        }


        this.loadFolderContents();
	},

	/**
	 * Select the view type to use.
	 *
	 * @param type
	 */
	selectViewType: function (type) {
		this.storeState('view', type);
		this.loadFolderContents();
	},

	/**
	 * Add the class to the appropriate view button and remove from the other.
	 */
	markActiveViewButton: function () {
		if (this.currentState.view == 'thumbs') {
			this.$viewAsThumbsBtn.addClass('active');
			this.$viewAsListBtn.removeClass('active');
            this.$folderContainer.addClass('assets-tv-file').removeClass('assets-listview').removeClass('assets-tv-bigthumb');
		} else {
			this.$viewAsThumbsBtn.removeClass('active');
			this.$viewAsListBtn.addClass('active');
            this.$folderContainer.removeClass('assets-tv-file').addClass('assets-listview').removeClass('assets-tv-bigthumb');
		}
	},

	/**
	 * Mark a folder as selected.
	 *
	 * @param folderId
	 */
	markActiveFolder: function (folderId){
		if (this.$activeFolder)
		{
			this.$activeFolder.removeClass('sel');
		}

		this.$activeFolder = this.$folders.find('a[data-folder=' + folderId + ']:first').addClass('sel');

		if (Blocks.cp.$altSidebarNavBtn)
		{
			Blocks.cp.$altSidebarNavBtn.text(this.$activeFolder.text());
		}
	},

    /**
     * Load the folder contents by selected folder.
     */
    loadFolderContents: function () {
        var folderElement = this.$folders.find('a.sel');

        if (folderElement.length == 0)
        {
            folderElement = this.$folders.find('li:first');
        }

        this.markActiveFolder(folderElement.attr('data-folder'));
        this.storeState('currentFolder', folderElement.attr('data-folder'));

        this.updateFiles();
	},

	/**
	 * Load the folder view.
	 */
	updateFiles: function (callback) {

        this._setUploadFolder(this.getCurrentFolderId());

        this.setAssetsBusy();

        this.offset = 0;
        this.nextOffset = 0;

        // TODO Dragging
        //this.fileDrag.removeAllItems();

        this._beforeLoadFiles();

        var postData = this._prepareFileViewPostData();

        // destroy previous select & view
        if (this.fileSelect) this.fileSelect.destroy();
        if (this.filesView) this.filesView.destroy();
        this.fileSelect = this.filesView = null;


        Blocks.postActionRequest('assets/viewFolder', postData, $.proxy(function(data, textStatus) {

			if (data.requestId != this.requestId) {
				return;
			}

            this.$folderContainer.attr('data', this.getCurrentFolderId());
			this.$folderContainer.html(data.html);

            // initialize the files view
            if (this.currentState.view == 'list')
            {
                this.filesView = new Assets.ListView($('> .folder-contents > .listview', this.$folderContainer), {
                    orderby: this.state.orderby,
                    sort:    this.state.sort,
                    onSortChange: $.proxy(function(orderby, sort)
                    {
                        this.setState({
                            orderby: orderby,
                            sort: sort
                        });
                        this.updateFiles();
                    }, this)
                });
            }
            else
            {
                this.filesView = new Assets.ThumbView($('> .folder-contents > .thumbs', this.$folderContainer));
            }

            // initialize the files multiselect
            this.fileSelect = new Garnish.Select(this.$files, {
                selectedClass:     'assets-selected',
                multi:             this.settings.multiSelect,
                waitForDblClick:   (this.settings.multiSelect && this.settings.mode == 'select'),
                vertical:          (this.currentState.view == 'list'),
                onSelectionChange: $.proxy(this, '_onFileSelectionChange'),
                $scrollpane:       this.$scrollpane
            });

            var $files = this.filesView.getItems().not('.assets-disabled');

            this._afterLoadFiles(data, $files);

            // did this happen immediately after an upload?
            this._onFileSelectionChange();

            // scroll to the first selected file
            if (this.selectedFileIds.length)
            {
                var $selected = this.fileSelect.getSelectedItems();
                Garnish.scrollContainerToElement(this.$scrollpane, $selected);
            }

            // -------------------------------------------
            //  callback
            //
            if (typeof callback == 'function')
            {
                callback();
            }
            //
            // -------------------------------------------

            // Initialize the next-page loader if necessary

            this.setAssetsAvailable();

            this._initializePageLoader();
		}, this));
	},

    /**
     * Perform actions before file loading.
     * @private
     */
    _beforeLoadFiles: function () {

        if (typeof this.settings.onBeforeUpdateFiles == 'function')
        {
            this.settings.onBeforeUpdateFiles();
        }

    },

    /**
     * Prepare the array for POST request for file view.
     */
    _prepareFileViewPostData: function (folderId) {
        return {
            requestId: ++this.requestId,
            folderId: this.currentState.currentFolder,
            viewType: this.currentState.view
        };
    },

    /**
     * Called right after loading files
     */
    _afterLoadFiles: function (data, $files)
    {
        // This way we will suffer an extra request upon reaching the last page, but we don't have to set the page size anywhere
        if (data.total > 0)
        {
            this.nextOffset += data.total;
            this.lastPageReached = false;
        }
        else
        {
            this.lastPageReached = true;
        }

        this.fileSelect.addItems($files);

        if (this.settings.mode == 'full')
        {
            // TODO file dragging
            //this.fileDrag.addItems($files);
        }

        // double-click handling
        this.addListener($files, 'dblclick', function(ev)
        {
            switch (this.settings.mode)
            {
                case 'select':
                {
                    clearTimeout(this.fileSelect.clearMouseUpTimeout());
                    this.settings.onSelect();
                    break;
                }

                case 'full':
                {
                    this._showProperties(ev);
                    break;
                }
            }
        });

        // -------------------------------------------
        //  TODO Context Menus
        // -------------------------------------------
        /*
        var menuOptions = [{ label: Assets.lang.view_file, onClick: $.proxy(this, '_viewFile') }];

        if (this.settings.mode == 'full')
        {
            menuOptions.push({ label: Assets.lang.edit_file, onClick: $.proxy(this, '_showProperties') });
            menuOptions.push({ label: Assets.lang.rename, onClick: $.proxy(this, '_renameFile') });
            menuOptions.push('-');
            menuOptions.push({ label: Assets.lang._delete, onClick: $.proxy(this, '_deleteFile') });
        }

        this._singleFileMenu = new Garnish.ContextMenu($files, menuOptions, {
            menuClass: 'assets-contextmenu'
        });

        if (this.settings.mode == 'full')
        {
            this._multiFileMenu = new Garnish.ContextMenu($files, [
                { label: Assets.lang._delete, onClick: $.proxy(this, '_deleteFiles') }
            ], {
                menuClass: 'assets-contextmenu'
            });

            this._multiFileMenu.disable();
        }*/
    },

    /**
     * Initialize the page loader.
     */
    _initializePageLoader: function ()
    {
        if (!this.lastPageReached)
        {
            var handler = function () {
                if(!this.$manager.hasClass('assets-page-loading') && Garnish.$win.scrollTop() + Garnish.$win.height() > Garnish.$doc.height() - 400) {
                    this.$manager.addClass('assets-page-loading');
                    Garnish.$win.unbind('scroll', $.proxy(handler, this));
                    this.loadMoreFiles();
                }
            };
            handler.call(this);
            Garnish.$win.bind('scroll', $.proxy(handler, this));
        }


    },

    /**
     * Load more files
     */
    loadMoreFiles: function ()
    {
        this.requestId++;
        this._beforeLoadFiles();
        var postData = this._prepareFileViewPostData();

        postData.offset = this.nextOffset;

        // run the ajax post request
        Blocks.postActionRequest('assets/viewFolder', postData, $.proxy(function(data, textStatus) {
            this.$manager.removeClass('assets-page-loading');

            if (textStatus == 'success')
            {
                // ignore if this isn't the current request
                if (data.requestId != this.requestId) return;

                if (this.currentState.view == 'list')
                {
                    $newFiles = $(data.html).find('tbody>tr');
                }
                else
                {
                    $newFiles = $(data.html).find('ul li');
                }

                if ($newFiles.length > 0)
                {
                    $enabledFiles = $newFiles.not('.assets-disabled');
                    if (this.filesView != null)
                    {
                        this.filesView.addItems($newFiles);
                        this._afterLoadFiles(data, $enabledFiles);
                    }

                    this._initializePageLoader();
                }

            }
        }, this));
    },

    /**
     * Display file properties window
     */
    _showProperties: function (ev) {

        this.setAssetsBusy();
        var params = {
            requestId: ++this.requestId,
            fileId: $(ev.target).is('li') ? $(ev.target).attr('data-file') : $(ev.target).parents('li').attr('data-file')
        };

        Blocks.postActionRequest('assets/viewFile', params, $.proxy(function(data, textStatus) {
            if (data.requestId != this.requestId) {
                return;
            }

            this.setAssetsAvailable();

            $modalContainerDiv = this.$modalContainerDiv;

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

                var params = $('form#file-fields').serialize();

                Blocks.postActionRequest('assets/saveFileContent', params, $.proxy(function(data, textStatus) {
                    this.hide();
                }, this));
            });

        }, this));
    },

    /**
     * On Selection Change
     */
    _onFileSelectionChange: function()
    {
        if (this.settings.mode == 'full')
        {
            // TODO COntext menu
           /* if (this.fileSelect.getTotalSelected() == 1)
            {
                this._singleFileMenu.enable();
                this._multiFileMenu.disable();
            }
            else
            {
                this._singleFileMenu.disable();
                this._multiFileMenu.enable();
            }*/
        }

        // update our internal array of selected files
        this.selectedFileIds = [];
        var $selected = this.fileSelect.getSelectedItems();

        for (var i = 0; i < $selected.length; i++)
        {
            this.selectedFileIds.push($($selected[i]).attr('data-id'));
        }

        // -------------------------------------------
        //  onSelectionChange callback
        //
        if (typeof this.settings.onSelectionChange == 'function')
        {
            this.settings.onSelectionChange();
        }
        //
        // -------------------------------------------
    },

	/**
	 * Gets current folder id - if none is set in the current state, then grabs it from the $folderContainer attribute.
	 * If that is empty, then grabs the first folder
	 *
	 * @return mixed
	 */
	getCurrentFolderId: function ()
    {
		if (this.currentState.currentFolder == null || this.currentState.currentFolder == 0 || typeof this.currentState.currentFolder == "undefined")
        {
            var folderId = this.$folderContainer.attr('data-folder');
            if (folderId == null || typeof folderId == "undefined")
            {
                folderId = this.$folders.find('a[data-folder]').attr('data-folder');
            }
			this.storeState('currentFolder', folderId);
		}

		return this.currentState.currentFolder;
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

			this.setAssetsBusy();

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
                this.setAssetsAvailable();
                if (this.promptArray.length)
                {
                    this._showBatchPrompts(this.promptArray, this._uploadFollowup);
                }
                else
                {
                    this.loadFolderContents();
                }
			} else {
				// just skip to hiding the progress bar
				this._hideProgressBar();
				this.setAssetsAvailable();
			}
		}


	},

    _uploadFollowup: function(returnData)
    {
        this.promptArray = [];

        var finalCallback = $.proxy(function()
        {
            this.setAssetsAvailable();
            this.loadFolderContents();
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
    },

    setAssetsBusy: function ()
    {
        this.$spinner.show();
    },

    setAssetsAvailable: function ()
    {
        this.$spinner.hide();
    },

    isAssetsAvailable: function ()
    {
        return this.$spinner.is(':visible');
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
