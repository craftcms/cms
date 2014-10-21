/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend(
{
	$uploadButton: null,
	$uploadInput: null,
	$progressBar: null,
	$folders: null,
	$previouslySelectedFolder: null,

	uploader: null,
	promptHandler: null,
	progressBar: null,

	initialSourceKey: null,
	isIndexBusy: false,
	_uploadTotalFiles: 0,
	_uploadFileProgress: {},
	_uploadedFileIds: [],
	_selectedFileIds: [],

	_singleFileMenu: null,
	_multiFileMenu: null,

	_fileDrag: null,
	_folderDrag: null,
	_expandDropTargetFolderTimeout: null,
	_tempExpandedFolders: [],

	init: function(elementType, $container, settings)
	{
		this.base(elementType, $container, settings);

		if (this.settings.context == 'index')
		{
			this.initIndexMode();
		}
	},

	initSource: function($source)
	{
		this.base($source);

		this._createFolderContextMenu($source);

		if (this.settings.context == 'index')
		{
			if (this._folderDrag && this._getSourceLevel($source) > 1)
			{
				this._folderDrag.addItems($source.parent());
			}

			if (this._fileDrag)
			{
				this._fileDrag.updateDropTargets();
			}
		}
	},

	deinitSource: function($source)
	{
		this.base($source);

		// Does this source have a context menu?
		var contextMenu = $source.data('contextmenu');

		if (contextMenu)
		{
			contextMenu.destroy();
		}

		if (this.settings.context == 'index')
		{
			if (this._folderDrag && this._getSourceLevel($source) > 1)
			{
				this._folderDrag.removeItems($source.parent());
			}

			if (this._fileDrag)
			{
				this._fileDrag.updateDropTargets();
			}
		}
	},

	_getSourceLevel: function($source)
	{
		return $source.parentsUntil('nav', 'ul').length;
	},

	/**
	 * Full blown Assets.
	 */
	initIndexMode: function()
	{
		// ---------------------------------------
		// File dragging
		// ---------------------------------------

		this._fileDrag = new Garnish.DragDrop({
			activeDropTargetClass: 'sel assets-fm-dragtarget',
			helperOpacity: 0.5,

			filter: $.proxy(function()
			{
				return this.elementSelect.getSelectedItems();
			}, this),

			helper: $.proxy(function($file)
			{
				return this._getDragHelper($file);
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				for (var i = 0; i < this.$sources.length; i++)
				{
					targets.push($(this.$sources[i]));
				}

				return targets;
			}, this),

			onDragStart: $.proxy(function()
			{
				this._tempExpandedFolders = [];

				this.$previouslySelectedFolder = this.$source.removeClass('sel');

			}, this),

			onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

			onDragStop: $.proxy(this, '_onFileDragStop')
		});

		// Folder dragging
		// ---------------------------------------------------------------------

		this._folderDrag = new Garnish.DragDrop(
		{
			activeDropTargetClass: 'sel assets-fm-dragtarget',
			helperOpacity: 0.5,

			filter: $.proxy(function()
			{
				// Return each of the selected <a>'s parent <li>s, except for top level drag attempts.
				var $selected = this.sourceSelect.getSelectedItems(),
					draggees = [];

				for (var i = 0; i < $selected.length; i++)
				{
					var $source = $($selected[i]).parent();

					if ($source.hasClass('sel') && this._getSourceLevel($source) > 1)
					{
						draggees.push($source[0]);
					}
				}

				return $(draggees);
			}, this),

			helper: $.proxy(function($draggeeHelper)
			{
				var $helperSidebar = $('<div class="sidebar" style="padding-top: 0; padding-bottom: 0;"/>'),
					$helperNav = $('<nav/>').appendTo($helperSidebar),
					$helperUl = $('<ul/>').appendTo($helperNav);

				$draggeeHelper.appendTo($helperUl).removeClass('expanded');
				$draggeeHelper.children('a').addClass('sel');

				// Match the style
				$draggeeHelper.css({
					'padding-top':    this._folderDrag.$draggee.css('padding-top'),
					'padding-right':  this._folderDrag.$draggee.css('padding-right'),
					'padding-bottom': this._folderDrag.$draggee.css('padding-bottom'),
					'padding-left':   this._folderDrag.$draggee.css('padding-left')
				});

				return $helperSidebar;
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				// Tag the dragged folder and it's subfolders
				var draggedSourceIds = [];
				this._folderDrag.$draggee.find('a[data-key]').each(function ()
				{
					draggedSourceIds.push($(this).data('key'));
				});

				for (var i = 0; i < this.$sources.length; i++)
				{
					var $source = $(this.$sources[i]);
					if (!Craft.inArray($source.data('key'), draggedSourceIds))
					{
						targets.push($source);
					}
				}

				return targets;
			}, this),

			onDragStart: $.proxy(function()
			{
				this._tempExpandedFolders = [];
			}, this),

			onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

			onDragStop: $.proxy(this, '_onFolderDragStop')
		});

	},

	_onFileDragStop: function()
	{
		if (this._fileDrag.$activeDropTarget)
		{
			// keep it selected
			this.sourceSelect.selectItem(this._fileDrag.$activeDropTarget);

			var targetFolderId = this._getFolderIdFromSourceKey(this._fileDrag.$activeDropTarget.data('key')),
				originalFileIds = [],
				newFileNames = [];

			// For each file, prepare array data.
			for (var i = 0; i < this._fileDrag.$draggee.length; i++)
			{
				var originalFileId = Craft.getElementInfo(this._fileDrag.$draggee[i]).id,
					fileName = Craft.getElementInfo(this._fileDrag.$draggee[i]).url.split('/').pop();

				if (fileName.indexOf('?') !== -1)
				{
					fileName = fileName.split('?').shift();
				}

				originalFileIds.push(originalFileId);
				newFileNames.push(fileName);
			}

			// are any files actually getting moved?
			if (originalFileIds.length)
			{
				this.setIndexBusy();

				this._positionProgressBar();
				this.progressBar.resetProgressBar();
				this.progressBar.setItemCount(originalFileIds.length);
				this.progressBar.showProgressBar();


				// for each file to move a separate request
				var parameterArray = [];
				for (i = 0; i < originalFileIds.length; i++)
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
					this.promptHandler.resetPrompts();

					// loop trough all the responses
					for (var i = 0; i < responseArray.length; i++)
					{
						var data = responseArray[i];

						// push prompt into prompt array
						if (data.prompt)
						{
							this.promptHandler.addPrompt(data);
						}

						if (data.error)
						{
							alert(data.error);
						}
					}

					this.setIndexAvailable();
					this.progressBar.hideProgressBar();

					if (this.promptHandler.getPromptCount())
					{
						// define callback for completing all prompts
						var promptCallback = $.proxy(function(returnData)
						{
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
								this._selectSourceByFolderId(targetFolderId);
							}
							else
							{
								// start working
								this.setIndexBusy();
								this.progressBar.resetProgressBar();
								this.progressBar.setItemCount(this.promptHandler.getPromptCount());
								this.progressBar.showProgressBar();

								// move conflicting files again with resolutions now
								this._moveFile(newParameterArray, 0, onMoveFinish);
							}
						}, this);

						this._fileDrag.fadeOutHelpers();
						this.promptHandler.showBatchPrompts(promptCallback);
					}
					else
					{
						this._fileDrag.fadeOutHelpers();
						this._selectSourceByFolderId(targetFolderId);
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
		this.sourceSelect.selectItem(this.$previouslySelectedFolder);

		this._fileDrag.returnHelpersToDraggees();
	},

	_onFolderDragStop: function()
	{
		// Only move if we have a valid target and we're not trying to move into our direct parent
		if (
			this._folderDrag.$activeDropTarget &&
			this._folderDrag.$activeDropTarget.siblings('ul').find('>li').filter(this._folderDrag.$draggee).length == 0
		)
		{
			var targetFolderId = this._getFolderIdFromSourceKey(this._folderDrag.$activeDropTarget.data('key'));

			this._collapseExtraExpandedFolders(targetFolderId);

			// get the old folder IDs, and sort them so that we're moving the most-nested folders first
			var folderIds = [];

			for (var i = 0; i < this._folderDrag.$draggee.length; i++)
			{
				var $a = $('> a', this._folderDrag.$draggee[i]),
					folderId = this._getFolderIdFromSourceKey($a.data('key')),
					$source = this._getSourceByFolderId(folderId);

				// make sure it's not already in the target folder
				if (this._getFolderIdFromSourceKey(this._getParentSource($source).data('key')) != targetFolderId)
				{
					folderIds.push(folderId);
				}
			}

			if (folderIds.length)
			{
				folderIds.sort();
				folderIds.reverse();

				this.setIndexBusy();
				this._positionProgressBar();
				this.progressBar.resetProgressBar();
				this.progressBar.setItemCount(folderIds.length);
				this.progressBar.showProgressBar();

				var responseArray = [];
				var parameterArray = [];

				for (var i = 0; i < folderIds.length; i++)
				{
					parameterArray.push({
						folderId: folderIds[i],
						parentId: targetFolderId
					});
				}

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
					this.promptHandler.resetPrompts();

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
							this.promptHandler.addPrompt(data);
						}

						if (data.error)
						{
							alert(data.error);
						}
					}

					if (this.promptHandler.getPromptCount())
					{
						// define callback for completing all prompts
						var promptCallback = $.proxy(function(returnData)
						{
							this.promptHandler.resetPrompts();
							this.setNewElementDataHtml('');

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
								this.setIndexBusy();
								this.progressBar.resetProgressBar();
								this.progressBar.setItemCount(this.promptHandler.getPromptCount());
								this.progressBar.showProgressBar();

								// move conflicting files again with resolutions now
								moveFolder(newParameterArray, 0, onMoveFinish);
							}
						}, this);

						this.promptHandler.showBatchPrompts(promptCallback);

						this.setIndexAvailable();
						this.progressBar.hideProgressBar();
					}
					else
					{
						$.proxy(this, '_performActualFolderMove', fileMoveList, folderDeleteList, changedFolderIds, removeFromTree, targetFolderId)();
					}
				}, this);

				var moveFolder = $.proxy(function(parameterArray, parameterIndex, callback)
				{
					if (parameterIndex == 0)
					{
						responseArray = [];
					}

					Craft.postActionRequest('assets/moveFolder', parameterArray[parameterIndex], $.proxy(function(data, textStatus)
					{
						parameterIndex++;
						this.progressBar.incrementProcessedItemCount(1);
						this.progressBar.updateProgressBar();

						if (textStatus == 'success')
						{
							responseArray.push(data);
						}

						if (parameterIndex >= parameterArray.length)
						{
							callback(responseArray);
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

		this._folderDrag.returnHelpersToDraggees();
	},

	/**
	 * Really move the folder. Like really. For real.
	 */
	_performActualFolderMove: function(fileMoveList, folderDeleteList, changedFolderIds, removeFromTree, targetFolderId)
	{
		this.setIndexBusy();
		this.progressBar.resetProgressBar();
		this.progressBar.setItemCount(1);
		this.progressBar.showProgressBar();

		var moveCallback = $.proxy(function(folderDeleteList, changedFolderIds, removeFromTree)
		{
			//Move the folders around in the tree
			var topFolderLi = $();
			var folderToMove = $();
			var topMovedFolderId = 0;

			// Change the folder ids
			for (var previousFolderId in changedFolderIds)
			{
				folderToMove = this._getSourceByFolderId(previousFolderId);

				// Change the id and select the containing element as the folder element.
				folderToMove = folderToMove
									.attr('data-key', 'folder:' + changedFolderIds[previousFolderId].newId)
									.data('key', 'folder:' + changedFolderIds[previousFolderId].newId).parent();

				if (topFolderLi.length == 0 || topFolderLi.parents().filter(folderToMove).length > 0)
				{
					topFolderLi = folderToMove;
					topFolderMovedId = changedFolderIds[previousFolderId].newId;
				}
			}

			if (topFolderLi.length == 0)
			{
				this.setIndexAvailable();
				this.progressBar.hideProgressBar();
				this._folderDrag.returnHelpersToDraggees();

				return;
			}

			var topFolder = topFolderLi.find('>a');

			// Now move the uppermost node.
			var siblings = topFolderLi.siblings('ul, .toggle');
			var parentSource = this._getParentSource(topFolder);

			var newParent = this._getSourceByFolderId(targetFolderId);
			this._prepareParentForChildren(newParent);
			this._appendSubfolder(newParent, topFolderLi);

			topFolder.after(siblings);

			this._cleanUpTree(parentSource);
			this.$sidebar.find('ul>ul, ul>.toggle').remove();

			// delete the old folders
			for (var i = 0; i < folderDeleteList.length; i++)
			{
				Craft.postActionRequest('assets/deleteFolder', {folderId: folderDeleteList[i]});
			}

			this.setIndexAvailable();
			this.progressBar.hideProgressBar();
			this._folderDrag.returnHelpersToDraggees();
			this._selectSourceByFolderId(topFolderMovedId);

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
	},

	/**
	 * Get parent source for a source.
	 *
	 * @param $source
	 * @returns {*}
	 * @private
	 */
	_getParentSource: function($source)
	{
		if (this._getSourceLevel($source) > 1)
		{
			return $source.parent().parent().siblings('a');
		}
	},

	/**
	 * Move a file using data from a parameter array.
	 *
	 * @param parameterArray
	 * @param parameterIndex
	 * @param callback
	 * @private
	 */
	_moveFile: function(parameterArray, parameterIndex, callback)
	{
		if (parameterIndex == 0)
		{
			this.responseArray = [];
		}

		Craft.postActionRequest('assets/moveFile', parameterArray[parameterIndex], $.proxy(function(data, textStatus)
		{
			this.progressBar.incrementProcessedItemCount(1);
			this.progressBar.updateProgressBar();

			if (textStatus == 'success')
			{
				this.responseArray.push(data);

				// If assets were just merged we should get the referece tags updated right away
				Craft.cp.runPendingTasks();
			}

			parameterIndex++;

			if (parameterIndex >= parameterArray.length)
			{
				callback(this.responseArray);
			}
			else
			{
				this._moveFile(parameterArray, parameterIndex, callback);
			}

		}, this));
	},

	_selectSourceByFolderId: function(targetFolderId)
	{
		var $targetSource = this._getSourceByFolderId(targetFolderId);

		// Make sure that all the parent sources are expanded and this source is visible.
		var $parentSources = $targetSource.parent().parents('li');

		for (var i = 0; i < $parentSources.length; i++)
		{
			var $parentSource = $($parentSources[i]);

			if (!$parentSource.hasClass('expanded'))
			{
				$parentSource.find('> .toggle').click();
			}
		};

		this.selectSource($targetSource);
		this.updateElements();
	},

	/**
	 * Initialize the uploader.
	 *
	 * @private
	 */
	onAfterHtmlInit: function()
	{
		if (!this.$uploadButton)
		{
			this.$uploadButton = $('<div class="btn submit assets-upload-button" data-icon="upload" style="position: relative; overflow: hidden;" role="button">' + Craft.t('Upload files') + '</div>');
			this.addButton(this.$uploadButton);

			this.$uploadInput = $('<input type="file" multiple="multiple" name="assets-upload" />').hide().insertBefore(this.$uploadButton);
		}

		this.promptHandler = new Craft.PromptHandler();
		this.progressBar = new Craft.ProgressBar(this.$main, true);

		var options = {
			url: Craft.getActionUrl('assets/uploadFile'),
			fileInput: this.$uploadInput,
			dropZone: this.$main
		};

		options.events = {
			fileuploadstart:       $.proxy(this, '_onUploadStart'),
			fileuploadprogressall: $.proxy(this, '_onUploadProgress'),
			fileuploaddone:        $.proxy(this, '_onUploadComplete')
		};

		if (typeof this.settings.criteria.kind != "undefined")
		{
			options.allowedKinds = this.settings.criteria.kind;
		}

		this.uploader = new Craft.Uploader (this.$uploadButton, options);

		this.$uploadButton.on('click', $.proxy(function()
		{
			if (this.$uploadButton.hasClass('disabled'))
			{
				return;
			}
			if (!this.isIndexBusy)
			{
				this.$uploadButton.parent().find('input[name=assets-upload]').click();
			}
		}, this));

		this.base();
	},

	onSelectSource: function()
	{
		this.uploader.setParams({folderId: this._getFolderIdFromSourceKey(this.sourceKey)});
		if (!this.$source.attr('data-upload'))
		{
			this.$uploadButton.addClass('disabled');
		}
		else
		{
			this.$uploadButton.removeClass('disabled');
		}
		this.base();
	},

	_getFolderIdFromSourceKey: function(sourceKey)
	{
		return sourceKey.split(':')[1];
	},

	/**
	 * React on upload submit.
	 *
	 * @param id
	 * @private
	 */
	_onUploadStart: function(event)
	{
		this.setIndexBusy();

		// Initial values
		this._positionProgressBar();
		this.progressBar.resetProgressBar();
		this.progressBar.showProgressBar();
	},

	/**
	 * Update uploaded byte count.
	 */
	_onUploadProgress: function(event, data)
	{
		var progress = parseInt(data.loaded / data.total * 100, 10);
		this.progressBar.setProgressPercentage(progress);
	},

	/**
	 * On Upload Complete.
	 */
	_onUploadComplete: function(event, data)
	{
		var response = data.result;
		var fileName = data.files[0].name;

		var doReload = true;

		if (response.success || response.prompt)
		{
			// Add the uploaded file to the selected ones, if appropriate
			this._uploadedFileIds.push(response.fileId);

			// If there is a prompt, add it to the queue
			if (response.prompt)
			{
				this.promptHandler.addPrompt(response);
			}
		}
		else
		{
			if (response.error)
			{
				alert(Craft.t('Upload failed for {filename}. The error message was: ”{error}“', { filename: fileName, error: response.error }));
			}
			else
			{
				alert(Craft.t('Upload failed for {filename}.', { filename: fileName }));
			}

			doReload = false;
		}

		// for the last file, display prompts, if any. If not - just update the element view.
		if (this.uploader.isLastUpload())
		{
			this.setIndexAvailable();
			this.progressBar.hideProgressBar();

			if (this.promptHandler.getPromptCount())
			{
				this.promptHandler.showBatchPrompts($.proxy(this, '_uploadFollowup'));
			}
			else
			{
				if (doReload)
				{
					this.updateElements();
				}
			}
		}
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
			this.setIndexAvailable();
			this.progressBar.hideProgressBar();
			this.updateElements();
		}, this);

		this.progressBar.setItemCount(returnData.length);

		var doFollowup = $.proxy(function(parameterArray, parameterIndex, callback)
		{
			var postData = {
				newFileId:    parameterArray[parameterIndex].fileId,
				fileName:     parameterArray[parameterIndex].fileName,
				userResponse: parameterArray[parameterIndex].choice
			};

			Craft.postActionRequest('assets/uploadFile', postData, $.proxy(function(data, textStatus)
			{
				if (textStatus == 'success' && data.fileId)
				{
					this._uploadedFileIds.push(data.fileId);
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

		this.progressBar.showProgressBar();
		doFollowup(returnData, 0, finalCallback);
	},

	/**
	 * Perform actions after updating elements
	 * @private
	 */
	onUpdateElements: function(append)
	{
		this.base(append);

		if (this.settings.context == 'index')
		{
			var $elements = this.$elementContainer.children(':not(.disabled)');
			this._initElementSelect($elements);
			this._attachElementEvents($elements);
			this._initElementDragger($elements);
		}

		// See if we have freshly uploaded files to add to selection
		if (this._uploadedFileIds.length)
		{
			var $item = null;
			for (var i = 0; i < this._uploadedFileIds.length; i++)
			{
				$item = this.$main.find('.element[data-id=' + this._uploadedFileIds[i] + ']:first').parent();
				if (this.getSelectedSourceState('mode') == 'table')
				{
					$item = $item.parent();
				}

				this.elementSelect.selectItem($item);
			}

			// Reset the list.
			this._uploadedFileIds = [];
		}
	},

	_initElementSelect: function($children)
	{
		if (typeof this.elementSelect == "object" && this.elementSelect != null)
		{
			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		var elementSelect = new Garnish.Select(this.$elementContainer, $children, {
			multi: true,
			vertical: (this.getSelectedSourceState('mode') == 'table'),
			onSelectionChange: $.proxy(this, '_onElementSelectionChange')
		});

		this.setElementSelect(elementSelect);
	},

	_onElementSelectionChange: function()
	{
		this._enableElementContextMenu();
		var selected = this.elementSelect.getSelectedItems();
		this._selectedFileIds = [];

		for (var i = 0; i < selected.length; i++)
		{
			this._selectedFileIds[i] = Craft.getElementInfo(selected[i]).id;
		}
	},

	_attachElementEvents: function($elements)
	{
		// Doubleclick opens the HUD for editing
		this.removeListener($elements, 'dblclick');
		this.addListener($elements, 'dblclick', $.proxy(this, '_editProperties'));

		// Context menus
		this._destroyElementContextMenus();
		this._createElementContextMenus($elements);
	},

	_initElementDragger: function($elements)
	{
		this._fileDrag.removeAllItems();
		this._fileDrag.addItems($elements);
	},

	_editProperties: function(event)
	{
		var $element = $(event.currentTarget).find('.element');
		new Craft.ElementEditor($element);
	},

	_createElementContextMenus: function($elements)
	{
		var settings = {menuClass: 'menu'};

		var menuOptions = [{ label: Craft.t('View file'), onClick: $.proxy(this, '_viewFile') }];
		menuOptions.push({ label: Craft.t('Edit properties'), onClick: $.proxy(this, '_showProperties') });
		menuOptions.push({ label: Craft.t('Rename file'), onClick: $.proxy(this, '_renameFile') });
		menuOptions.push({ label: Craft.t('Copy reference tag'), onClick: $.proxy(this, '_copyRefTag') });
		menuOptions.push('-');
		menuOptions.push({ label: Craft.t('Delete file'), onClick: $.proxy(this, '_deleteFile') });
		this._singleFileMenu = new Garnish.ContextMenu($elements, menuOptions, settings);

		menuOptions = [{ label: Craft.t('Delete'), onClick: $.proxy(this, '_deleteFiles') }];
		this._multiFileMenu = new Garnish.ContextMenu($elements, menuOptions, settings);

		this._enableElementContextMenu();
	},

	_destroyElementContextMenus: function()
	{
		if (this._singleFileMenu !== null)
		{
			this._singleFileMenu.destroy();
		}

		if (this._multiFileMenu !== null)
		{
			this._singleFileMenu.destroy();
		}
	},

	_enableElementContextMenu: function()
	{
		this._multiFileMenu.disable();
		this._singleFileMenu.disable();

		if (this.elementSelect.getTotalSelected() == 1)
		{
			this._singleFileMenu.enable();
		}
		else if (this.elementSelect.getTotalSelected() > 1)
		{
			this._multiFileMenu.enable();
		}
	},

	_showProperties: function(event)
	{
		$(event.currentTarget).dblclick();
	},

	_viewFile: function(event)
	{
		window.open(Craft.getElementInfo(event.currentTarget).url);
	},

	/**
	 * Rename File
	 */
	_renameFile: function(event)
	{
		var $target = $(event.currentTarget),
			fileId = Craft.getElementInfo($target).id,
			oldName = Craft.getElementInfo($target).url.split('/').pop();

		if (oldName.indexOf('?') !== -1)
		{
			oldName = oldName.split('?').shift();
		}

		var newName = prompt(Craft.t("Rename file"), oldName);

		if (newName && newName != oldName)
		{
			this.setIndexBusy();

			var postData = {
				fileId:   fileId,
				folderId: this._getFolderIdFromSourceKey(this.$source.data('key')),
				fileName: newName
			};

			var handleRename = function(data, textStatus)
			{
				this.setIndexAvailable();
				this.promptHandler.resetPrompts();

				if (textStatus == 'success')
				{
					if (data.prompt)
					{
						this.promptHandler.addPrompt(data);

						var callback = $.proxy(function(choice)
						{
							choice = choice[0].choice;
							if (choice != 'cancel')
							{
								postData.action = choice;
								Craft.postActionRequest('assets/moveFile', postData, $.proxy(handleRename, this));
							}
						}, this);

						this.promptHandler.showBatchPrompts(callback);
					}

					if (data.success)
					{
						this.updateElements();

						// If assets were just merged we should get the referece tags updated right away
						Craft.cp.runPendingTasks();
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

	_copyRefTag: function(event)
	{
		var message = Craft.t('{ctrl}C to copy.', {
			ctrl: (navigator.appVersion.indexOf('Mac') ? '⌘' : 'Ctrl-')
		});

		prompt(message, '{asset:'+Craft.getElementInfo($(event.currentTarget)).id+'}');
	},

	/**
	 * Delete a file
	 */
	_deleteFile: function(event)
	{
		var $target = $(event.currentTarget);
		var fileId = Craft.getElementInfo($target).id;

		var fileTitle = Craft.getElementInfo($target).label;

		if (confirm(Craft.t('Are you sure you want to delete “{name}”?', { name: fileTitle })))
		{
			if ($target.data('AssetEditor'))
			{
				$target.data('AssetEditor').removeHud();
			}

			this.setIndexBusy();

			Craft.postActionRequest('assets/deleteFile', {fileId: fileId}, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success')
				{
					if (data.error)
					{
						alert(data.error);
					}

					this.updateElements();
				}
			}, this));
		}
	},

	/**
	 * Delete multiple files.
	 */
	_deleteFiles: function()
	{
		if (confirm(Craft.t("Are you sure you want to delete these {number} files?", {number: this.elementSelect.getTotalSelected()})))
		{
			this.setIndexBusy();

			var postData = {};

			for (var i = 0; i < this._selectedFileIds.length; i++)
			{
				postData['fileId['+i+']'] = this._selectedFileIds[i];
			}

			Craft.postActionRequest('assets/deleteFile', postData, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success')
				{
					if (data.error)
					{
						alert(data.error);
					}

					this.updateElements();
				}
			}, this));
		}
	},

	_getDragHelper: function($element)
	{
		var currentView = this.getSelectedSourceState('mode');
		switch (currentView)
		{
			case 'table':
			{
				var $container = $('<div class="assets-listview assets-lv-drag" />'),
					$table = $('<table cellpadding="0" cellspacing="0" border="0" />').appendTo($container),
					$tbody = $('<tbody />').appendTo($table);

				$table.width(this.$table.width());
				$tbody.append($element);

				return $container;
			}
			case 'thumbs':
			{
				return $('<ul class="thumbsview assets-tv-drag" />').append($element.removeClass('sel'));
			}
		}

		return $();
	},

	/**
	 * On Drop Target Change
	 */
	_onDropTargetChange: function($dropTarget)
	{
		clearTimeout(this._expandDropTargetFolderTimeout);

		if ($dropTarget)
		{
			var folderId = this._getFolderIdFromSourceKey($dropTarget.data('key'));

			if (folderId)
			{
				this.dropTargetFolder = this._getSourceByFolderId(folderId);

				if (this._hasSubfolders(this.dropTargetFolder) && ! this._isExpanded(this.dropTargetFolder))
				{
					this._expandDropTargetFolderTimeout = setTimeout($.proxy(this, '_expandFolder'), 500);
				}
			}
			else
			{
				this.dropTargetFolder = null;
			}
		}
	},

	/**
	 * Collapse Extra Expanded Folders
	 */
	_collapseExtraExpandedFolders: function(dropTargetFolderId)
	{
		clearTimeout(this._expandDropTargetFolderTimeout);

		// If a source id is passed in, exclude it's parents
		if (dropTargetFolderId)
		{
			var excluded = this._getSourceByFolderId(dropTargetFolderId).parents('li').find('>a');
		}

		for (var i = this._tempExpandedFolders.length-1; i >= 0; i--)
		{
			var $source = this._tempExpandedFolders[i];

			// check the parent list, if a source id is passed in
			if (! dropTargetFolderId || excluded.filter('[data-key="' + $source.data('key') + '"]').length == 0)
			{
				this._collapseFolder($source);
				this._tempExpandedFolders.splice(i, 1);
			}
		}
	},

	_getSourceByFolderId: function(folderId)
	{
		return this.$sources.filter('[data-key="folder:' + folderId + '"]');
	},

	_hasSubfolders: function($source)
	{
		return $source.siblings('ul').find('li').length;
	},

	_isExpanded: function($source)
	{
		return $source.parent('li').hasClass('expanded');
	},

	_expandFolder: function()
	{
		// collapse any temp-expanded drop targets that aren't parents of this one
		this._collapseExtraExpandedFolders(this._getFolderIdFromSourceKey(this.dropTargetFolder.data('key')));

		this.dropTargetFolder.parent().find('> .toggle').click();

		// keep a record of that
		this._tempExpandedFolders.push(this.dropTargetFolder);
	},

	_collapseFolder: function($source)
	{
		var li = $source.parent();

		if (li.hasClass('expanded'))
		{
			li.find('> .toggle').click();
		}
	},

	_createFolderContextMenu: function($source)
	{
		var menuOptions = [{ label: Craft.t('New subfolder'), onClick: $.proxy(this, '_createSubfolder', $source) }];

		// For all folders that are not top folders
		if (this.settings.context == 'index' && this._getSourceLevel($source) > 1)
		{
			menuOptions.push({ label: Craft.t('Rename folder'), onClick: $.proxy(this, '_renameFolder', $source) });
			menuOptions.push({ label: Craft.t('Delete folder'), onClick: $.proxy(this, '_deleteFolder', $source) });
		}

		new Garnish.ContextMenu($source, menuOptions, {menuClass: 'menu'});
	},

	_createSubfolder: function($parentFolder)
	{
		var subfolderName = prompt(Craft.t('Enter the name of the folder'));

		if (subfolderName)
		{
			var params = {
				parentId:  this._getFolderIdFromSourceKey($parentFolder.data('key')),
				folderName: subfolderName
			};

			this.setIndexBusy();

			Craft.postActionRequest('assets/createFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					this._prepareParentForChildren($parentFolder);

					var $subFolder = $(
						'<li>' +
							'<a data-key="folder:'+data.folderId+'"' +
								(Garnish.hasAttr($parentFolder, 'data-has-thumbs') ? ' data-has-thumbs' : '') +
								' data-upload="'+$parentFolder.attr('data-upload')+'"' +
							'>' +
								data.folderName +
							'</a>' +
						'</li>'
					);

					var $a = $subFolder.children('a:first');
					this._appendSubfolder($parentFolder, $subFolder);
					this.initSource($a);
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}
			}, this));
		}
	},

	_deleteFolder: function($targetFolder)
	{
		if (confirm(Craft.t('Really delete folder “{folder}”?', {folder: $.trim($targetFolder.text())})))
		{
			var params = {
				folderId: this._getFolderIdFromSourceKey($targetFolder.data('key'))
			}

			this.setIndexBusy();

			Craft.postActionRequest('assets/deleteFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					var $parentFolder = this._getParentSource($targetFolder);

					// Remove folder and any trace from its parent, if needed
					this.deinitSource($targetFolder);

					$targetFolder.parent().remove();
					this._cleanUpTree($parentFolder);

					this.$sidebar.trigger('resize');
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}
			}, this));
		}
	},

	/**
	 * Rename
	 */
	_renameFolder: function($targetFolder)
	{
		var oldName = $.trim($targetFolder.text()),
			newName = prompt(Craft.t('Rename folder'), oldName);

		if (newName && newName != oldName)
		{
			var params = {
				folderId: this._getFolderIdFromSourceKey($targetFolder.data('key')),
				newName: newName
			};

			this.setIndexBusy();

			Craft.postActionRequest('assets/renameFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					$targetFolder.text(data.newName);
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}

			}, this), 'json');
		}
	},

	/**
	 * Prepare a source folder for children folder.
	 *
	 * @param $parentFolder
	 * @private
	 */
	_prepareParentForChildren: function($parentFolder)
	{
		if (!this._hasSubfolders($parentFolder))
		{
			$parentFolder.parent().addClass('expanded').append('<div class="toggle"></div><ul></ul>');
			this.initSourceToggle($parentFolder);
		}
	},

	/**
	 * Appends a subfolder to the parent folder at the correct spot.
	 *
	 * @param $parentFolder
	 * @param $subFolder
	 * @private
	 */
	_appendSubfolder: function($parentFolder, $subFolder)
	{
		var $subfolderList = $parentFolder.siblings('ul'),
			$existingChildren = $subfolderList.children('li'),
			subfolderLabel = $.trim($subFolder.children('a:first').text()),
			folderInserted = false;

		for (var i = 0; i < $existingChildren.length; i++)
		{
			var $existingChild = $($existingChildren[i]);

			if ($.trim($existingChild.children('a:first').text()) > subfolderLabel)
			{
				$existingChild.before($subFolder);
				folderInserted = true;
				break;
			}
		};

		if (!folderInserted)
		{
			$parentFolder.siblings('ul').append($subFolder);
		}

		this.$sidebar.trigger('resize');
	},

	_cleanUpTree: function($parentFolder)
	{
		if ($parentFolder !== null && $parentFolder.siblings('ul').children('li').length == 0)
		{
			this.deinitSourceToggle($parentFolder);
			$parentFolder.siblings('ul').remove();
			$parentFolder.siblings('.toggle').remove();
			$parentFolder.parent().removeClass('expanded');
		}
	},

	_positionProgressBar: function()
	{
		var $container = $(),
			offset = 0;

		if (this.settings.context == 'index')
		{
			$container = this.progressBar.$progressBar.closest('#content');
		}
		else
		{
			$container = this.progressBar.$progressBar.closest('.main');
		}

		var containerTop = $container.offset().top;
		var scrollTop = Garnish.$doc.scrollTop();
		var diff = scrollTop - containerTop;
		var windowHeight = Garnish.$win.height();

		if ($container.height() > windowHeight)
		{
			offset = (windowHeight / 2) - 6 + diff;
		}
		else
		{
			offset = ($container.height() / 2) - 6;
		}

		this.progressBar.$progressBar.css({
			top: offset
		});
	}

});

// Register it!
Craft.registerElementIndexClass('Asset', Craft.AssetIndex);
