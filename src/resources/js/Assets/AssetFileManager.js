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
		this.$spinner = $('.spinner', this.$manager);
		this.$status = $('.asset-status');
		this.$scrollpane = null;
		this.$folders = Craft.cp.$sidebarNav.find('.assets-folders:first');
		this.$folderContainer = $('.folder-container');

		this.$search = $('> .search', this.$toolbar);
		this.$searchInput = $('input', this.$search);
		this.$searchOptions = $('.search-options', this.$search);
		this.$searchModeCheckbox = $('input', this.$searchOptions);

		this.$viewAsThumbsBtn = $('a.thumbs', this.$toolbar);
		this.$viewAsListBtn   = $('a.list', this.$toolbar);

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

		this.searchTimeout = null;
		this.searchVal = '';
		this.showingSearchOptions = false;

		this.selectedFileIds = [];
		this.folders = [];

		this.folderSelect = null;
		this.fileSelect = null;
		this.filesView = null;
		this.fileDrag = null;
		this.folderDrag = null;

		this._singleFileMenu = [];
		this._multiFileMenu = [];

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
			folders: {},
			searchMode: 'shallow',
			orderBy: 'filename',
			sortOrder: 'ASC'
		};

		this.storageKey = 'Craft_Assets_' + this.settings.namespace;

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

        var uploaderCallbacks = {
            onSubmit:     $.proxy(this, '_onUploadSubmit'),
            onProgress:   $.proxy(this, '_onUploadProgress'),
            onComplete:   $.proxy(this, '_onUploadComplete')
        };

		this.uploader = new Assets.Uploader(this.$upload, uploaderCallbacks);

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

		// Overriding the method provided by Garnish.
		this.folderSelect.originalKeyDown = this.folderSelect.onKeyDown;
		this.folderSelect.onKeyDown = $.proxy(this._folderSelectKeyboardNavigation, this);

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
		// Search
		// ---------------------------------------
		if (this.currentState.searchMode == 'deep')
		{
			this.$searchModeCheckbox.prop('checked', true);
		}

		// ---------------------------------------
		// Folder dragging
		// ---------------------------------------
		this.folderDrag = new Garnish.DragDrop({
			activeDropTargetClass: 'sel assets-fm-dragtarget',
			helperOpacity: 0.5,

			filter: $.proxy(function()
			{
				// return each of the selected <a>'s parent <li>s
				var $selected = this.folderSelect.getSelectedItems(),
					draggees = [];

				for (var i = 0; i < $selected.length; i++)
				{
					var li = $($selected[i]).parent()[0];

					// ignore top-level folders
					if ($.inArray(li, this.$topFolderLis) != -1)
					{
						this.folderSelect.deselectItem($($selected[i]));
						continue;
					}

					draggees.push(li);
				}

				return $(draggees);
			}, this),

			helper: $.proxy(function($folder)
			{
				var $helper = $('<ul class="assets-fm-folderdrag" />').append($folder);

				// collapse this folder
				$folder.removeClass('expanded');

				// set the helper width to the folders container width
				$helper.width(this.$folders[0].scrollWidth);

				return $helper;
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				for (var folderId in this.folders)
				{
					var folder = this.folders[folderId];

					if (folder.visible && $.inArray(folder.$li[0], this.folderDrag.$draggee) == -1)
					{
						targets.push(folder.$a);
					}
				}

				return targets;
			}, this),

			onDragStart: $.proxy(function()
			{
				this.tempExpandedFolders = [];

				// hide the expanded draggees' subfolders
				this.folderDrag.$draggee.filter('.expanded').removeClass('expanded').addClass('expanded-tmp')
			}, this),

			onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

			onDragStop: $.proxy(function()
			{
				// show the expanded draggees' subfolders
				this.folderDrag.$draggee.filter('.expanded-tmp').removeClass('expanded-tmp').addClass('expanded');

				// Only move if we have a valid target and we're not trying to move into our direct parent
				if (
					this.folderDrag.$activeDropTarget
						&& this.folderDrag.$activeDropTarget.siblings('ul').find('>li').filter(this.folderDrag.$draggee).length == 0)
				{

					var targetFolderId = this.folderDrag.$activeDropTarget.attr('data-id');

					this._collapseExtraExpandedFolders(targetFolderId);

					// get the old folder IDs, and sort them so that we're moving the most-nested folders first
					var folderIds = [];

					for (var i = 0; i < this.folderDrag.$draggee.length; i++)
					{
						var $a = $('> a', this.folderDrag.$draggee[i]),
							folderId = $a.attr('data-id'),
							folder = this.folders[folderId];

						// make sure it's not already in the target folder
						if (folder.parent.id != targetFolderId)
						{
							folderIds.push(folderId);
						}
					}

					if (folderIds.length)
					{
						folderIds.sort();
						folderIds.reverse();

						this.setAssetsBusy();
						this._initProgressBar();


						var parameterArray = [];

						for (var i = 0; i < folderIds.length; i++)
						{
							parameterArray.push({
								folderId: folderIds[i],
								parentId: targetFolderId
							});
						}

						this.responseArray = [];

						// increment, so to avoid displaying folder files that are being moved
						this.requestId++;

						/*
						 Here's the rundown:
						 1) Send all the folders being moved
						 2) Get results:
						 a) For all conflicting, receive prompts and resolve them to get:
						 b) For all valid move operations: by now server has created the needed folders
						 in target destination. Server returns an array of file move operations
						 c) server also returns a list of all the folder id changes
						 d) and the data-id of node to be removed, in case of conflict
						 e) and a list of folders to delete after the move
						 3) From data in 2) build a large file move operation array
						 4) Create a request loop based on this, so we can display progress bar
						 5) when done, delete all the folders and perform other maintenance
						 6) Champagne
						 */

						// this will hold the final list of files to move
						var fileMoveList = [];

						// these folders have to be deleted at the end
						var folderDeleteList = [];

						// this one tracks the changed folder ids
						var changedFolderIds = {};

						var removeFromTree = [];

						var onMoveFinish = $.proxy(function(responseArray)
						{
							this.promptArray = [];

							// loop trough all the responses
							for (var i = 0; i < responseArray.length; i++)
							{
								var data = responseArray[i];

								// if succesful and have data, then update
								if (data.success)
								{
									if (data.transferList && data.deleteList && data.changedFolderIds)
									{
										for (var ii = 0; ii < data.transferList.length; ii++)
										{
											fileMoveList.push(data.transferList[ii]);
										}
										for (var ii = 0; ii < data.deleteList.length; ii++)
										{
											folderDeleteList.push(data.deleteList[ii]);
										}
										for (var oldFolderId in data.changedFolderIds)
										{
											changedFolderIds[oldFolderId] = data.changedFolderIds[oldFolderId];
										}
										removeFromTree.push(data.removeFromTree);
									}
								}

								// push prompt into prompt array
								if (data.prompt)
								{
									this.promptArray.push(data);
								}

								if (data.error)
								{
									alert(data.error);
								}
							}

							if (this.promptArray.length > 0)
							{
								// define callback for completing all prompts
								var promptCallback = $.proxy(function(returnData)
								{
									this.promptArray = [];
									this.$folderContainer.html('');

									var newParameterArray = [];

									// loop trough all returned data and prepare a new request array
									for (var i = 0; i < returnData.length; i++)
									{
										if (returnData[i].choice == 'cancel')
										{
											continue;
										}

										parameterArray[0].action = returnData[i].choice;
										newParameterArray.push(parameterArray[0]);

									}

									// start working on them lists, baby
									if (newParameterArray.length == 0)
									{
										$.proxy(this, '_performActualFolderMove', fileMoveList, folderDeleteList, changedFolderIds, removeFromTree)();
									}
									else
									{
										// start working
										this.setAssetsBusy();
										this._initProgressBar();

										// move conflicting files again with resolutions now
										moveFolder(newParameterArray, 0, onMoveFinish);
									}
								}, this);

								this._showBatchPrompts(this.promptArray, promptCallback);

								this.setAssetsAvailable();
								this._hideProgressBar();
							}
							else
							{
								$.proxy(this, '_performActualFolderMove', fileMoveList, folderDeleteList, changedFolderIds, removeFromTree)();
							}

						}, this);

						var moveFolder = $.proxy(function(parameterArray, parameterIndex, callback)
						{
							if (parameterIndex == 0)
							{
								this.responseArray = [];
							}

							Craft.postActionRequest('assets/moveFolder', parameterArray[parameterIndex], $.proxy(function(data)
							{
								parameterIndex++;
								var width = Math.min(100, Math.round(100 * parameterIndex / parameterArray.length)) + '%';
								this.$uploadProgressBar.width(width);

								this.responseArray.push(data);

								if (parameterIndex >= parameterArray.length)
								{
									callback(this.responseArray);
								}
								else
								{
									moveFolder(parameterArray, parameterIndex, callback);
								}
							}, this));
						}, this);

						// initiate the folder move with the built array, index of 0 and callback to use when done
						moveFolder(parameterArray, 0, onMoveFinish);

						// skip returning dragees until we get the Ajax response
						return;
					}
				}
				else
				{
					this._collapseExtraExpandedFolders();
				}

				this.folderDrag.returnHelpersToDraggees();
			}, this)
		});

		// ---------------------------------------
		// File dragging
		// ---------------------------------------
		this.fileDrag = new Garnish.DragDrop({
			activeDropTargetClass: 'sel assets-fm-dragtarget',
			helperOpacity: 0.5,

			filter: $.proxy(function()
			{
				return this.fileSelect.getSelectedItems();
			}, this),

			helper: $.proxy(function($file)
			{
				return this.filesView.getDragHelper($file);
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				for (var folderId in this.folders)
				{
					var folder = this.folders[folderId];

					if (folder.visible)
					{
						targets.push(folder.$a);
					}
				}

				return targets;
			}, this),

			onDragStart: $.proxy(function()
			{
				this.tempExpandedFolders = [];

				$selectedFolders = this.folderSelect.getSelectedItems();
				$selectedFolders.removeClass('sel');
			}, this),

			onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

			onDragStop: $.proxy(function()
			{
				if (this.fileDrag.$activeDropTarget)
				{
					// keep it selected
					this.fileDrag.$activeDropTarget.addClass('sel');

					var targetFolderId = this.fileDrag.$activeDropTarget.attr('data-id');

					var originalFileIds = [],
						newFileNames = [];


					for (var i = 0; i < this.fileDrag.$draggee.length; i++)
					{
						var originalFileId = this.fileDrag.$draggee[i].getAttribute('data-id'),
							fileName = this.fileDrag.$draggee[i].getAttribute('data-fileName');

						originalFileIds.push(originalFileId);
						newFileNames.push(fileName);
					}

					// are any files actually getting moved?
					if (originalFileIds.length)
					{
						this.setAssetsBusy();
						this._initProgressBar();


						// for each file to move a separate request
						var parameterArray = [];
						for (var i = 0; i < originalFileIds.length; i++)
						{
							parameterArray.push({
								fileId: originalFileIds[i],
								folderId: targetFolderId,
								fileName: newFileNames[i]
							});
						}

						// define the callback for when all file moves are complete
						var onMoveFinish = $.proxy(function(responseArray)
						{
							this.promptArray = [];

							// loop trough all the responses
							for (var i = 0; i < responseArray.length; i++)
							{
								var data = responseArray[i];

								// push prompt into prompt array
								if (data.prompt)
								{
									this.promptArray.push(data);
								}

								if (data.error)
								{
									alert(data.error);
								}
							}

							this.setAssetsAvailable();
							this._hideProgressBar();

							if (this.promptArray.length > 0)
							{
								// define callback for completing all prompts
								var promptCallback = $.proxy(function(returnData)
								{
									this.$folderContainer.html('');

									var newParameterArray = [];

									// loop trough all returned data and prepare a new request array
									for (var i = 0; i < returnData.length; i++)
									{
										if (returnData[i].choice == 'cancel')
										{
											continue;
										}

										// find the matching request parameters for this file and modify them slightly
										for (var ii = 0; ii < parameterArray.length; ii++)
										{
											if (parameterArray[ii].fileName == returnData[i].fileName)
											{
												parameterArray[ii].action = returnData[i].choice;
												newParameterArray.push(parameterArray[ii]);
											}
										}
									}

									// nothing to do, carry on
									if (newParameterArray.length == 0)
									{
										this.loadFolderContents();
									}
									else
									{
										// start working
										this.setAssetsBusy();
										this._initProgressBar();


										// move conflicting files again with resolutions now
										this._moveFile(newParameterArray, 0, onMoveFinish);
									}
								}, this);

								this.fileDrag.fadeOutHelpers();
								this._showBatchPrompts(this.promptArray, promptCallback);

							}
							else
							{
								this.fileDrag.fadeOutHelpers();
								this.loadFolderContents();
							}
						}, this);

						// initiate the file move with the built array, index of 0 and callback to use when done
						this._moveFile(parameterArray, 0, onMoveFinish);

						// skip returning dragees
						return;
					}
				}
				else
				{
					this._collapseExtraExpandedFolders();
				}

				// re-select the previously selected folders
				$selectedFolders.addClass('sel');

				this.fileDrag.returnHelpersToDraggees();
			}, this)
		});

		/**
		 * Move file.
		 */
		this._moveFile = $.proxy(function(parameterArray, parameterIndex, callback)
		{
			if (parameterIndex == 0)
			{
				this.responseArray = [];
			}

			Craft.postActionRequest('assets/moveFile', parameterArray[parameterIndex], $.proxy(function(data)
			{
				parameterIndex++;
				var width = Math.min(100, Math.round(100 * parameterIndex / parameterArray.length)) + '%';
				this.$uploadProgressBar.width(width);

				this.responseArray.push(data);

				if (parameterIndex >= parameterArray.length)
				{
					callback(this.responseArray);
				}
				else
				{
					this._moveFile(parameterArray, parameterIndex, callback);
				}
			}, this));
		}, this);

		/**
		 * Really move the folder. Like really. For real.
		 */
		this._performActualFolderMove = $.proxy(function(fileMoveList, folderDeleteList, changedFolderIds, removeFromTree)
		{
			this.setAssetsBusy();
			this._initProgressBar();


			var moveCallback = $.proxy(function(folderDeleteList, changedFolderIds, removeFromTree)
			{
				var folder;

				// change the folder ids
				for (var previousFolderId in changedFolderIds)
				{
					var newValue = changedFolderIds[previousFolderId].newId;
					var newParent = changedFolderIds[previousFolderId].newParentId;

					var previousFolder = this.folders[previousFolderId];
					this.folders[newValue] = previousFolder;
					this.folders[newValue].id = newValue;

					$('li.assets-fm-folder > a[data-id="'+previousFolderId+'"]:first').attr('data-id', newValue);
					folder = this.folders[newValue];
					folder.moveTo(newParent);
					folder.select();
				}

				// delete the old folders
				for (var i = 0; i < folderDeleteList.length; i++)
				{
					Craft.postActionRequest('assets/deleteFolder', {folderId: folderDeleteList[i]});
				}

				if (removeFromTree.length > 0)
				{
					// remove from tree the obsolete nodes
					for (var i = 0; i < removeFromTree.length; i++)
					{
						if (removeFromTree[i].length)
						{
							this.folders[removeFromTree[i]].onDelete(true);
						}
					}
				}

				this.setAssetsAvailable();
				this._hideProgressBar();

				this.loadFolderContents();
				this.folderDrag.returnHelpersToDraggees();

			}, this);

			if (fileMoveList.length > 0)
			{
				this._moveFile(fileMoveList, 0, $.proxy(function()
				{
					moveCallback(folderDeleteList, changedFolderIds, removeFromTree);
				}, this));
			}
			else
			{
				moveCallback(folderDeleteList, changedFolderIds, removeFromTree);
			}
		}, this);

		// ---------------------------------------
		// Asset events
		// ---------------------------------------

		this.$searchInput.keydown($.proxy(this, '_onSearchKeyDown'));
		this.$searchModeCheckbox.change($.proxy(this, '_onSearchModeChange'));

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
			this.storeState('currentFolder', this.$folders.find('a[data-id]').attr('data-id'));
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
	 *
	 * @param ev
	 * @private
	 */
	_folderSelectKeyboardNavigation: function (ev)
	{
		var currentFolder = this.folders[this.getCurrentFolderId()];

		switch (ev.keyCode)
		{
			case Garnish.LEFT_KEY:
			{
				if (currentFolder.hasSubfolders() && currentFolder.expanded)
				{
					currentFolder.collapse();
				}
				else
				{
					if (currentFolder.depth > 1)
					{
						currentFolder.parent.select();
					}
				}
				break;
			}

			case Garnish.RIGHT_KEY:
			{
				if (currentFolder.hasSubfolders() && !currentFolder.expanded)
				{
					currentFolder.expand();
				}
				break;
			}
		}

		this.folderSelect.originalKeyDown(ev);
	},

	/**
	 * On Search Key Down
	 */
	_onSearchKeyDown: function(event)
	{
		// ignore if meta/ctrl key is down
		if (event.metaKey || event.ctrlKey) return;

		event.stopPropagation();

		// clear the last timeout
		clearTimeout(this.searchTimeout);

		setTimeout($.proxy(function()
		{
			switch (event.keyCode)
			{
				case 13: // return
				{
					event.preventDefault();
					this._checkKeywordVal();
					break;
				}

				case 27: // esc
				{
					event.preventDefault();
					this.$searchInput.val('');
					this._checkKeywordVal();
					break;
				}

				default:
				{
					this.searchTimeout = setTimeout($.proxy(this, '_checkKeywordVal'), 500);
				}
			}

		}, this), 0);
	},

	/**
	 * Check the keyword value.
	 */
	_checkKeywordVal: function()
	{
		// has the value changed?
		if (this.searchVal !== (this.searchVal = this.$searchInput.val()))
		{
			if (this.searchVal && !this.showingSearchOptions)
			{
				this._showSearchOptions();
			}
			else if (!this.searchVal && this.showingSearchOptions)
			{
				this._hideSearchOptions()
			}


			this.updateFiles();
		}
	},

	/**
	 * Show search options.
	 */
	_showSearchOptions: function()
	{
		this.showingSearchOptions = true;
		this.$searchOptions.stop().slideDown('fast');
	},

		/**
		 * Hide search options
		 * @private
		 */
	_hideSearchOptions: function()
	{
		this.showingSearchOptions = false;
		this.$searchOptions.stop().slideUp('fast');
	},

	/**
	 * Change search mode.
	 */
	_onSearchModeChange: function()
	{
		if (this.$searchModeCheckbox.prop('checked'))
		{
			var searchMode = 'deep';
		}
		else
		{
			var searchMode = 'shallow';
		}

		this.storeState('searchMode', searchMode);
		this.updateFiles();
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

		this.$activeFolder = this.$folders.find('a[data-id=' + folderId + ']:first').addClass('sel');

		if (Craft.cp.$altSidebarNavBtn)
		{
			Craft.cp.$altSidebarNavBtn.text(this.$activeFolder.text());
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

		this.markActiveFolder(folderElement.attr('data-id'));
		this.storeState('currentFolder', folderElement.attr('data-id'));

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

		this._singleFileMenu = [];
		this._multiFileMenu = [];

		if (this.settings.mode == 'full')
		{
			this.fileDrag.removeAllItems();
		}

		this._beforeLoadFiles();

		var postData = this._prepareFileViewPostData();

		// destroy previous select & view
		if (this.fileSelect) this.fileSelect.destroy();
		if (this.filesView) this.filesView.destroy();
		this.fileSelect = this.filesView = null;


		Craft.postActionRequest('assets/viewFolder', postData, $.proxy(function(data, textStatus) {

			if (data.requestId != this.requestId) {
				return;
			}

			this.$folderContainer.attr('data', this.getCurrentFolderId());
			this.$folderContainer.html(data.html);

			// initialize the files view
			if (this.currentState.view == 'list')
			{
				this.filesView = new Assets.ListView($('> .folder-contents > .listview', this.$folderContainer), {
					orderby: this.currentState.orderBy,
					sort:    this.currentState.sortOrder,
					onSortChange: $.proxy(function(orderby, sort)
					{
						this.storeState('orderBy', orderby);
						this.storeState('sortOrder', sort);
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
		var params = {
			requestId: ++this.requestId,
			folderId: this.currentState.currentFolder,
			viewType: this.currentState.view,
			keywords: this.$searchInput.val(),
			searchMode: this.currentState.searchMode
		};

		if (this.currentState.view == 'list')
		{
			params.orderBy = this.currentState.orderBy;
			params.sortOrder = this.currentState.sortOrder;
		}

		return params;
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
			this.fileDrag.addItems($files);
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

		var menuOptions = [{ label: Craft.t('View file'), onClick: $.proxy(this, '_viewFile') }];

		if (this.settings.mode == 'full')
		{
			menuOptions.push({ label: Craft.t('Edit properties'), onClick: $.proxy(this, '_showProperties') });
			menuOptions.push({ label: Craft.t('Rename file'), onClick: $.proxy(this, '_renameFile') });
			menuOptions.push('-');
			menuOptions.push({ label: Craft.t('Delete file'), onClick: $.proxy(this, '_deleteFile') });
		}


		this._singleFileMenu.push(new Garnish.ContextMenu($files, menuOptions, {menuClass: 'menu assets-contextmenu'}));

		if (this.settings.mode == 'full')
		{
			var menu = new Garnish.ContextMenu($files, [
				{ label: Craft.t('Delete'), onClick: $.proxy(this, '_deleteFiles') }
			], {menuClass: 'menu assets-contextmenu'});
			menu.disable();
			this._multiFileMenu.push(menu);
		}
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
		if (this.lastPageReached)
		{
			return;
		}

		this.requestId++;
		this._beforeLoadFiles();
		var postData = this._prepareFileViewPostData();

		postData.offset = this.nextOffset;

		// run the ajax post request
		Craft.postActionRequest('assets/viewFolder', postData, $.proxy(function(data, textStatus) {
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

					this.$folderContainer.append($(data.html).find('style'));

					this._initializePageLoader();
				}

			}
		}, this));
	},

	/**
	 * View a file
	 */
	_viewFile: function (ev) {
		window.open($(ev.currentTarget).attr('data-url'));
	},

	/**
	 * Rename File
	 */
	_renameFile: function(event)
	{
		var dataTarget = this._getDataContainer(event);
		var fileId = dataTarget.attr('data-id'),
			oldName = dataTarget.attr('data-filename'),
			newName = prompt(Craft.t("Rename file"), oldName);

		if (newName && newName != oldName)
		{
			this.setAssetsBusy();

			var postData = {
				fileId:   fileId,
				folderId: dataTarget.attr('data-folder'),
				fileName: newName
			};

			var handleRename = function(data, textStatus)
			{
				this.setAssetsAvailable();

				if (textStatus == 'success')
				{
					if (data.prompt)
					{
						this._showPrompt(data.prompt, data.prompt.choices, $.proxy(function (choice) {
							if (choice != 'cancel')
							{
								postData.action = choice;
								Craft.postActionRequest('assets/moveFile', postData, $.proxy(handleRename, this));
							}
						}, this));
					}

					if (data.success)
					{
						this.updateFiles();
					}

					if (data.error)
					{
						alert(data.error);
					}
				}
			};

			Craft.postActionRequest('assets/moveFile', postData, $.proxy(handleRename, this));
		}
	},

	/**
	 * Delete a file
	 */
	_deleteFile: function (ev) {

		var dataTarget = this._getDataContainer(ev);
		var fileId = dataTarget.attr('data-id');

		var fileName = dataTarget.attr('data-fileName');

		if (confirm(Craft.t('Are you sure you want to delete “{file}”?', {file: fileName})))
		{
			this.setAssetsBusy();

			Craft.postActionRequest('assets/deleteFile', {fileId: fileId}, $.proxy(function(data, textStatus) {
				this.setAssetsAvailable();

				if (textStatus == 'success')
				{
					if (data.error)
					{
						alert(data.error);
					}

					this.updateFiles();

				}
			}, this));
		}
	},

	/**
	 * Delete multiple files.
	 */
	_deleteFiles: function () {

		if (confirm(Craft.t("Are you sure you want to delete these {number} files?", {number: this.fileSelect.getTotalSelected()})))
		{
			this.setAssetsBusy();

			var postData = {};

			for (var i = 0; i < this.selectedFileIds.length; i++)
			{
				postData['fileId['+i+']'] = this.selectedFileIds[i];
			}

			Craft.postActionRequest('assets/deleteFile', postData, $.proxy(function(data, textStatus) {
				this.setAssetsAvailable();

				if (textStatus == 'success')
				{

					if (data.error)
					{
						alert(data.error);
					}

					this.updateFiles();
				}
			}, this));
		}
	},

	/**
	 * Display file properties window
	 */
	_showProperties: function (ev) {

		this.setAssetsBusy();

		var dataTarget = this._getDataContainer(ev);

		var params = {
			requestId: ++this.requestId,
			fileId: dataTarget.attr('data-id')
		};

		Craft.postActionRequest('assets/viewFile', params, $.proxy(function(data, textStatus) {
			if (data.requestId != this.requestId) {
				return;
			}

			this.setAssetsAvailable();

			$modalContainerDiv = this.$modalContainerDiv;

            if ($modalContainerDiv == null) {
				$modalContainerDiv = $('<div class="modal assets-modal"></div>').appendTo(Garnish.$bod);
			}

            $modalContainerDiv.empty().append(data.headHtml);
            $modalContainerDiv.append(data.bodyHtml);
            $modalContainerDiv.append(data.footHtml);

            if (this.modal == null) {
				this.modal = new Garnish.Modal($modalContainerDiv, {});
			}
            else
            {
                this.modal.setContainer($modalContainerDiv);
                this.modal.show();
            }

			this.modal.removeListener(this.modal.$shade, 'click');

			this.modal.addListener(this.modal.$container.find('.btn.cancel'), 'click', function () {
				this.hide();
			});

			this.modal.addListener(this.modal.$container.find('.btn.submit'), 'click', function () {
				this.removeListener(this.$shade, 'click');

				var params = $('form#file-fields').serialize();

				Craft.postActionRequest('assets/saveFileContent', params, $.proxy(function(data, textStatus) {
					this.hide();
				}, this));
			});

		}, this));
	},

	/**
	 * Get data container from an event.
	 *
	 * @param ev
	 * @return jQuery
	 */
	_getDataContainer: function (ev)
	{
		if (typeof ev.currentTarget != "undefined")
		{
			target = ev.currentTarget;
		}

		if (this.currentState.view == 'thumbs')
		{
			return $(target).is('li') ? $(target) : $(target).parents('li');
		}
		else
		{
			return $(target).is('tr') ? $(target) : $(target).parents('tr');
		}

	},

	/**
	 * Collapse Extra Expanded Folders
	 */
	_collapseExtraExpandedFolders: function(dropTargetFolderId)
	{
		clearTimeout(this.expandDropTargetFolderTimeout);

		for (var i = this.tempExpandedFolders.length-1; i >= 0; i--)
		{
			var folder = this.tempExpandedFolders[i];

			if (! dropTargetFolderId || !folder.isParent(dropTargetFolderId))
			{
				folder.collapse();
				this.tempExpandedFolders.splice(i, 1);
			}
		}
	},

	/**
	 * On Selection Change
	 */
	_onFileSelectionChange: function()
	{
		if (this.settings.mode == 'full')
		{
			var i = 0;
			if (this.fileSelect.getTotalSelected() == 1)
			{
				for (i = 0; i < this._singleFileMenu.length; i++)
				{
					this._singleFileMenu[i].enable();
					this._multiFileMenu[i].disable();
				}

			}
			else if (this.fileSelect.getTotalSelected() > 1)
			{
				for (i = 0; i < this._singleFileMenu.length; i++)
				{
					this._singleFileMenu[i].disable();
					this._multiFileMenu[i].enable();
				}
			}
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
			var folderId = this.$folderContainer.attr('data-id');
			if (folderId == null || typeof folderId == "undefined")
			{
				folderId = this.$folders.find('a[data-id]').attr('data-id');
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
			this._initProgressBar();

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
				this._hideProgressBar();

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
		this.setAssetsBusy();
		this._initProgressBar();


		this.promptArray = [];

		var finalCallback = $.proxy(function()
		{
			this.setAssetsAvailable();
			this._hideProgressBar();
			this.loadFolderContents();
		}, this);

		var doFollowup = $.proxy(function(parameterArray, parameterIndex, callback)
		{
			var postData = {
				additionalInfo: parameterArray[parameterIndex].additionalInfo,
				fileName:       parameterArray[parameterIndex].fileName,
				userResponse:   parameterArray[parameterIndex].choice
			};

			Craft.postActionRequest('assets/uploadFile', postData, $.proxy(function(data)
			{
				parameterIndex++;
				var width = Math.min(100, Math.round(100 * parameterIndex / parameterArray.length)) + '%';

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
	 * Hide progress bar.
	 */
	_hideProgressBar: function() {
		// Fade to almost invisible, apply class to hide and bring opacity back to 1.
		// This is done to make sure that the class is hiding the element, not a style property
		this.$uploadProgress.fadeTo('fast', 0.01, $.proxy(function() {
			this.$uploadProgress.addClass('hidden').fadeTo(1, 1, function () {});
		}, this));
	},

	/**
	 * Initialize progress bar.
	 */
	_initProgressBar: function () {

		this.$uploadProgressBar.width('0%');
		this.$uploadProgress.removeClass('hidden');
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

		$('<p>').html(Craft.t('What do you want to do?')).appendTo(this.$prompt);

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
			this.$promptApplyToRemainingLabel.html(' ' + Craft.t('Apply this to the {number} remaining conflicts?', {number: itemsToGo}));
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
		this.$spinner.removeClass('hidden');
	},

	setAssetsAvailable: function ()
	{
		this.$spinner.addClass('hidden');
	},

	isAssetsAvailable: function ()
	{
		return this.$spinner.hasClass('hidden');
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
