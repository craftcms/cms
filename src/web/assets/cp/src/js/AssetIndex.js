/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend({
  $includeSubfoldersContainer: null,
  $includeSubfoldersCheckbox: null,
  showingIncludeSubfoldersCheckbox: false,

  $uploadButton: null,
  $uploadInput: null,
  $progressBar: null,
  $folders: null,

  uploader: null,
  promptHandler: null,
  progressBar: null,

  _uploadTotalFiles: 0,
  _uploadFileProgress: {},
  _currentUploaderSettings: {},

  _assetDrag: null,
  _folderDrag: null,
  _expandDropTargetFolderTimeout: null,
  _tempExpandedFolders: [],

  _fileConflictTemplate: {
    choices: [
      {value: 'keepBoth', title: Craft.t('app', 'Keep both')},
      {value: 'replace', title: Craft.t('app', 'Replace it')},
    ],
  },
  _folderConflictTemplate: {
    choices: [
      {
        value: 'replace',
        title: Craft.t(
          'app',
          'Replace the folder (all existing files will be deleted)'
        ),
      },
      {
        value: 'merge',
        title: Craft.t(
          'app',
          'Merge the folder (any conflicting files will be replaced)'
        ),
      },
    ],
  },

  init: function (elementType, $container, settings) {
    this.base(elementType, $container, settings);

    if (this.settings.context === 'index') {
      if (!this._folderDrag) {
        this._initIndexPageMode();
      }

      this.addListener(Garnish.$win, 'resize,scroll', '_positionProgressBar');
    } else {
      this.addListener(this.$main, 'scroll', '_positionProgressBar');

      if (this.settings.modal) {
        this.settings.modal.on(
          'updateSizeAndPosition',
          this._positionProgressBar.bind(this)
        );
      }
    }
  },

  initSources: function () {
    if (this.settings.context === 'index' && !this._folderDrag) {
      this._initIndexPageMode();
    }

    return this.base();
  },

  initSource: function ($source) {
    this.base($source);

    this._createFolderContextMenu($source);

    if (this.settings.context === 'index') {
      if (this._folderDrag && this._getSourceLevel($source) > 1) {
        if ($source.data('folder-id')) {
          this._folderDrag.addItems($source.parent());
        }
      }

      if (this._assetDrag) {
        this._assetDrag.updateDropTargets();
      }
    }
  },

  deinitSource: function ($source) {
    this.base($source);

    // Does this source have a context menu?
    var contextMenu = $source.data('contextmenu');

    if (contextMenu) {
      contextMenu.destroy();
    }

    if (this.settings.context === 'index') {
      if (this._folderDrag && this._getSourceLevel($source) > 1) {
        this._folderDrag.removeItems($source.parent());
      }

      if (this._assetDrag) {
        this._assetDrag.updateDropTargets();
      }
    }
  },

  _getSourceLevel: function ($source) {
    return $source.parentsUntil('nav', 'ul').length;
  },

  /**
   * Initialize the index page-specific features
   */
  _initIndexPageMode: function () {
    if (this._folderDrag) {
      return;
    }

    // Make the elements selectable
    this.settings.selectable = true;
    this.settings.multiSelect = true;

    // Asset dragging
    // ---------------------------------------------------------------------

    this._assetDrag = new Garnish.DragDrop({
      activeDropTargetClass: 'sel',
      helperOpacity: 0.75,
      filter: () =>
        this.view.getSelectedElements().has('div.element[data-movable]'),
      helper: ($file) => this._getFileDragHelper($file),
      dropTargets: () => {
        // Which data attribute should we be checking?
        var attr;
        if (
          this._assetDrag.$draggee &&
          this._assetDrag.$draggee.has('.element[data-peer-file]').length
        ) {
          attr = 'data-can-move-peer-files-to';
        } else {
          attr = 'data-can-move-to';
        }

        var targets = [];

        for (var i = 0; i < this.$sources.length; i++) {
          // Make sure it's a volume folder
          var $source = this.$sources.eq(i);
          if (Garnish.hasAttr($source, attr)) {
            targets.push($source);
          }
        }

        return targets;
      },

      onDragStart: this._onDragStart.bind(this),
      onDropTargetChange: this._onDropTargetChange.bind(this),
      onDragStop: this._onFileDragStop.bind(this),
      helperBaseZindex: 800,
    });

    // Folder dragging
    // ---------------------------------------------------------------------

    this._folderDrag = new Garnish.DragDrop({
      activeDropTargetClass: 'sel',
      helperOpacity: 0.75,

      helper: ($draggeeHelper) => {
        var $helperSidebar = $('<div class="sidebar drag-helper"/>'),
          $helperNav = $('<nav/>').appendTo($helperSidebar),
          $helperUl = $('<ul/>').appendTo($helperNav);

        $draggeeHelper.appendTo($helperUl).removeClass('expanded');
        $draggeeHelper.children('a').addClass('sel');

        // Match the style
        $draggeeHelper.css({
          'padding-top': this._folderDrag.$draggee.css('padding-top'),
          'padding-right': this._folderDrag.$draggee.css('padding-right'),
          'padding-bottom': this._folderDrag.$draggee.css('padding-bottom'),
          'padding-left': this._folderDrag.$draggee.css('padding-left'),
        });

        return $helperSidebar;
      },

      dropTargets: () => {
        var targets = [];

        // Tag the dragged folder and it's subfolders
        var draggedSourceIds = [];
        this._folderDrag.$draggee.find('a[data-key]').each(function () {
          draggedSourceIds.push($(this).data('key'));
        });

        for (var i = 0; i < this.$sources.length; i++) {
          // Make sure it's a volume folder and not one of the dragged folders
          var $source = this.$sources.eq(i),
            key = $source.data('key');

          if (!this._getVolumeOrFolderUidFromSourceKey(key)) {
            continue;
          }

          if (!Craft.inArray(key, draggedSourceIds)) {
            targets.push($source);
          }
        }

        return targets;
      },

      onDragStart: this._onDragStart.bind(this),
      onDropTargetChange: this._onDropTargetChange.bind(this),
      onDragStop: this._onFolderDragStop.bind(this),
    });
  },

  /**
   * On file drag stop
   */
  _onFileDragStop: function () {
    if (
      this._assetDrag.$activeDropTarget &&
      this._assetDrag.$activeDropTarget[0] !== this.$source[0]
    ) {
      // Keep it selected
      var originatingSource = this.$source;

      var targetFolderId = this._assetDrag.$activeDropTarget.data('folder-id'),
        originalAssetIds = [];

      // For each file, prepare array data.
      for (var i = 0; i < this._assetDrag.$draggee.length; i++) {
        var originalAssetId = Craft.getElementInfo(
          this._assetDrag.$draggee[i]
        ).id;

        originalAssetIds.push(originalAssetId);
      }

      // Are any files actually getting moved?
      if (originalAssetIds.length) {
        this.setIndexBusy();

        this._positionProgressBar();
        this.progressBar.resetProgressBar();
        this.progressBar.setItemCount(originalAssetIds.length);
        this.progressBar.showProgressBar();

        // For each file to move a separate request
        var parameterArray = [];
        for (i = 0; i < originalAssetIds.length; i++) {
          parameterArray.push({
            action: 'assets/move-asset',
            params: {
              assetId: originalAssetIds[i],
              folderId: targetFolderId,
            },
          });
        }

        // Define the callback for when all file moves are complete
        var onMoveFinish = (responseArray) => {
          this.promptHandler.resetPrompts();

          // Loop trough all the responses
          for (var i = 0; i < responseArray.length; i++) {
            var response = responseArray[i];

            // Push prompt into prompt array
            if (response.conflict) {
              this.promptHandler.addPrompt({
                assetId: response.assetId,
                suggestedFilename: response.suggestedFilename,
                prompt: {
                  message: response.conflict,
                  choices: this._fileConflictTemplate.choices,
                },
              });
            }

            if (response.error) {
              alert(response.error);
            }
          }

          this.setIndexAvailable();
          this.progressBar.hideProgressBar();
          var reloadIndex = false;

          var performAfterMoveActions = function () {
            // Select original source
            this.sourceSelect.selectItem(originatingSource);

            // Make sure we use the correct offset when fetching the next page
            this._totalVisible -= this._assetDrag.$draggee.length;

            // And remove the elements that have been moved away
            for (var i = 0; i < originalAssetIds.length; i++) {
              $('[data-id=' + originalAssetIds[i] + ']').remove();
            }

            this.view.deselectAllElements();
            this._collapseExtraExpandedFolders(targetFolderId);

            if (reloadIndex) {
              this.updateElements();
            }
          };

          if (this.promptHandler.getPromptCount()) {
            // Define callback for completing all prompts
            var promptCallback = (returnData) => {
              var newParameterArray = [];

              // Loop trough all returned data and prepare a new request array
              for (var i = 0; i < returnData.length; i++) {
                if (returnData[i].choice === 'cancel') {
                  reloadIndex = true;
                  continue;
                }

                if (returnData[i].choice === 'keepBoth') {
                  newParameterArray.push({
                    action: 'assets/move-asset',
                    params: {
                      folderId: targetFolderId,
                      assetId: returnData[i].assetId,
                      filename: returnData[i].suggestedFilename,
                    },
                  });
                }

                if (returnData[i].choice === 'replace') {
                  newParameterArray.push({
                    action: 'assets/move-asset',
                    params: {
                      folderId: targetFolderId,
                      assetId: returnData[i].assetId,
                      force: true,
                    },
                  });
                }
              }

              // Nothing to do, carry on
              if (newParameterArray.length === 0) {
                performAfterMoveActions.apply(this);
              } else {
                // Start working
                this.setIndexBusy();
                this.progressBar.resetProgressBar();
                this.progressBar.setItemCount(
                  this.promptHandler.getPromptCount()
                );
                this.progressBar.showProgressBar();

                // Move conflicting files again with resolutions now
                this._performBatchRequests(newParameterArray, onMoveFinish);
              }
            };

            this._assetDrag.fadeOutHelpers();
            this.promptHandler.showBatchPrompts(promptCallback);
          } else {
            performAfterMoveActions.apply(this);
            this._assetDrag.fadeOutHelpers();
          }
        };

        // Initiate the file move with the built array, index of 0 and callback to use when done
        this._performBatchRequests(parameterArray, onMoveFinish);

        // Skip returning dragees
        return;
      }
    } else {
      // Add the .sel class back on the selected source
      this.$source.addClass('sel');

      this._collapseExtraExpandedFolders();
    }

    this._assetDrag.returnHelpersToDraggees();
  },

  /**
   * On folder drag stop
   */
  _onFolderDragStop: function () {
    // Only move if we have a valid target and we're not trying to move into our direct parent
    if (
      this._folderDrag.$activeDropTarget &&
      this._folderDrag.$activeDropTarget
        .siblings('ul')
        .children('li')
        .filter(this._folderDrag.$draggee).length === 0
    ) {
      var targetFolderId = this._folderDrag.$activeDropTarget.data('folder-id');

      this._collapseExtraExpandedFolders(targetFolderId);

      // Get the old folder IDs, and sort them so that we're moving the most-nested folders first
      var folderIds = [];

      for (var i = 0; i < this._folderDrag.$draggee.length; i++) {
        var $a = this._folderDrag.$draggee.eq(i).children('a'),
          folderId = $a.data('folder-id');

        // Make sure it’s not already in the target folder and use this single folder Id.
        if (folderId != targetFolderId) {
          folderIds.push(folderId);
          break;
        }
      }

      if (folderIds.length) {
        folderIds.sort();
        folderIds.reverse();

        this.setIndexBusy();
        this._positionProgressBar();
        this.progressBar.resetProgressBar();
        this.progressBar.setItemCount(folderIds.length);
        this.progressBar.showProgressBar();

        var parameterArray = [];

        for (i = 0; i < folderIds.length; i++) {
          parameterArray.push({
            action: 'assets/move-folder',
            params: {
              folderId: folderIds[i],
              parentId: targetFolderId,
            },
          });
        }

        // Increment, so to avoid displaying folder files that are being moved
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

        // This will hold the final list of files to move
        var fileMoveList = [];

        var newSourceKey = '';

        var onMoveFinish = (responseArray) => {
          this.promptHandler.resetPrompts();

          // Loop trough all the responses
          for (var i = 0; i < responseArray.length; i++) {
            var data = responseArray[i];

            // If successful and have data, then update
            if (data.success) {
              if (data.transferList) {
                fileMoveList = data.transferList;
              }

              if (data.newFolderId) {
                newSourceKey =
                  this._folderDrag.$activeDropTarget.data('key') +
                  '/folder:' +
                  data.newFolderUid;
              }
            }

            // Push prompt into prompt array
            if (data.conflict) {
              data.prompt = {
                message: data.conflict,
                choices: this._folderConflictTemplate.choices,
              };

              this.promptHandler.addPrompt(data);
            }

            if (data.error) {
              alert(data.error);
            }
          }

          if (this.promptHandler.getPromptCount()) {
            // Define callback for completing all prompts
            var promptCallback = (returnData) => {
              this.promptHandler.resetPrompts();

              var newParameterArray = [];

              var params = {};
              // Loop trough all returned data and prepare a new request array
              for (var i = 0; i < returnData.length; i++) {
                if (returnData[i].choice === 'cancel') {
                  continue;
                }

                if (returnData[i].choice === 'replace') {
                  params.force = true;
                }

                if (returnData[i].choice === 'merge') {
                  params.merge = true;
                }

                params.folderId = data.folderId;
                params.parentId = data.parentId;

                newParameterArray.push({
                  action: 'assets/move-folder',
                  params: params,
                });
              }

              // Start working on them lists, baby
              if (newParameterArray.length === 0) {
                this._performActualFolderMove(
                  fileMoveList,
                  folderIds,
                  newSourceKey
                );
              } else {
                // Start working
                this.setIndexBusy();
                this.progressBar.resetProgressBar();
                this.progressBar.setItemCount(
                  this.promptHandler.getPromptCount()
                );
                this.progressBar.showProgressBar();

                this._performBatchRequests(newParameterArray, onMoveFinish);
              }
            };

            this.promptHandler.showBatchPrompts(promptCallback);

            this.setIndexAvailable();
            this.progressBar.hideProgressBar();
          } else {
            this._performActualFolderMove(
              fileMoveList,
              folderIds,
              newSourceKey
            );
          }
        };

        // Initiate the folder move with the built array, index of 0 and callback to use when done
        this._performBatchRequests(parameterArray, onMoveFinish);

        // Skip returning dragees until we get the Ajax response
        return;
      }
    } else {
      // Add the .sel class back on the selected source
      this.$source.addClass('sel');

      this._collapseExtraExpandedFolders();
    }

    this._folderDrag.returnHelpersToDraggees();
  },

  /**
   * Really move the folder. Like really. For real.
   */
  _performActualFolderMove: function (
    fileMoveList,
    folderDeleteList,
    newSourceKey
  ) {
    this.setIndexBusy();
    this.progressBar.resetProgressBar();
    this.progressBar.setItemCount(1);
    this.progressBar.showProgressBar();

    var moveCallback = (folderDeleteList) => {
      // Delete the old folders
      var counter = 0;
      var limit = folderDeleteList.length;
      for (var i = 0; i < folderDeleteList.length; i++) {
        let data = {folderId: folderDeleteList[i]};

        // When all folders are deleted, reload the sources.
        Craft.sendActionRequest('POST', 'assets/delete-folder', {data}).then(
          (response) => {
            if (++counter === limit) {
              this.setIndexAvailable();
              this.progressBar.hideProgressBar();
              this._folderDrag.returnHelpersToDraggees();
              this.setInstanceState('selectedSource', newSourceKey);
              this.refreshSources();
            }
          }
        );
      }
    };

    if (fileMoveList.length > 0) {
      var parameterArray = [];

      for (var i = 0; i < fileMoveList.length; i++) {
        parameterArray.push({
          action: 'assets/move-asset',
          params: fileMoveList[i],
        });
      }
      this._performBatchRequests(parameterArray, function () {
        moveCallback(folderDeleteList);
      });
    } else {
      moveCallback(folderDeleteList);
    }
  },

  /**
   * Returns the root level source for a source.
   *
   * @param $source
   * @returns {*}
   * @private
   */
  _getRootSource: function ($source) {
    var $parent;
    while (($parent = this._getParentSource($source)) && $parent.length) {
      $source = $parent;
    }
    return $source;
  },

  /**
   * Get parent source for a source.
   *
   * @param $source
   * @returns {*}
   * @private
   */
  _getParentSource: function ($source) {
    if (this._getSourceLevel($source) > 1) {
      return $source.parent().parent().siblings('a');
    }
  },

  _selectSourceByFolderId: function (targetFolderId) {
    var $targetSource = this._getSourceByKey(targetFolderId);

    // Make sure that all the parent sources are expanded and this source is visible.
    var $parentSources = $targetSource.parent().parents('li');

    for (var i = 0; i < $parentSources.length; i++) {
      var $parentSource = $($parentSources[i]);

      if (!$parentSource.hasClass('expanded')) {
        $parentSource.children('.toggle').trigger('click');
      }
    }

    this.selectSource($targetSource);
    this.updateElements();
  },

  /**
   * Initialize the uploader.
   *
   * @private
   */
  afterInit: function () {
    if (!this.$uploadButton) {
      this.$uploadButton = $('<button/>', {
        type: 'button',
        class: 'btn submit',
        'data-icon': 'upload',
        style: 'position: relative; overflow: hidden;',
        text: Craft.t('app', 'Upload files'),
      });
      this.addButton(this.$uploadButton);

      this.$uploadInput = $(
        '<input type="file" multiple="multiple" name="assets-upload" />'
      )
        .hide()
        .insertBefore(this.$uploadButton);
    }

    this.promptHandler = new Craft.PromptHandler();
    this.progressBar = new Craft.ProgressBar(this.$main, true);

    var options = {
      url: Craft.getActionUrl('assets/upload'),
      fileInput: this.$uploadInput,
      dropZone: this.$container,
    };

    options.events = {
      fileuploadstart: this._onUploadStart.bind(this),
      fileuploadprogressall: this._onUploadProgress.bind(this),
      fileuploadalways: this._onUploadComplete.bind(this),
      fileuploadfail: this._onUploadFailure.bind(this),
    };

    if (
      this.settings.criteria &&
      typeof this.settings.criteria.kind !== 'undefined'
    ) {
      options.allowedKinds = this.settings.criteria.kind;
    }

    this._currentUploaderSettings = options;

    this.uploader = new Craft.Uploader(this.$uploadButton, options);

    this.$uploadButton.on('click', () => {
      if (this.$uploadButton.hasClass('disabled')) {
        return;
      }
      if (!this.isIndexBusy) {
        this.$uploadButton
          .parent()
          .find('input[name=assets-upload]')
          .trigger('click');
      }
    });

    this.base();
  },

  getDefaultSourceKey: function () {
    // Did they request a specific volume in the URL?
    if (
      this.settings.context === 'index' &&
      typeof window.defaultSource !== 'undefined'
    ) {
      let defaultSourceParts = window.defaultSource.split('/');
      let volumeSource = this.$sources.toArray().find((s) => {
        return $(s).data('volume-handle') === defaultSourceParts[0];
      });
      if (volumeSource) {
        let $source = $(volumeSource);

        for (let i = 1; i < defaultSourceParts.length; i++) {
          // does $source have a subfolder with this path name?
          let subfolderSource = this._getChildSources($source)
            .toArray()
            .find((s) => {
              return $('> .label', s).text() === defaultSourceParts[i];
            });
          if (!subfolderSource) {
            break;
          }
          this._expandSource($source);
          $source = $(subfolderSource);
        }

        return $source.data('key');
      }
    }

    return this.base();
  },

  onSelectSource: function () {
    var $source = this._getSourceByKey(this.sourceKey);
    var folderId = $source.data('folder-id');

    if (folderId && Garnish.hasAttr(this.$source, 'data-can-upload')) {
      this.uploader.setParams({
        folderId: this.$source.attr('data-folder-id'),
      });
      this.$uploadButton.removeClass('disabled');
    } else {
      this.$uploadButton.addClass('disabled');
    }

    // Update the URL if we're on the Assets index
    if ($source.length && this.settings.context === 'index') {
      this._updateUrl($source);
    }

    this.base();
  },

  _updateUrl: function ($source) {
    // Find all the subfolder sources. At the end, $thisSource will be the root volume source
    let nestedSources = [];
    let $thisSource = $source;
    let $parent;
    while (($parent = this._getParentSource($thisSource)) && $parent.length) {
      nestedSources.unshift($thisSource);
      $thisSource = $parent;
    }

    let uri = 'assets';
    if ($thisSource.data('volume-handle')) {
      uri += '/' + $thisSource.data('volume-handle');
      nestedSources.forEach(($s) => {
        uri += '/' + $s.children('.label').text();
      });
    }

    Craft.setPath(uri);
  },

  _getVolumeOrFolderUidFromSourceKey: function (sourceKey) {
    var m = sourceKey.match(/\b(?:folder|volume):([0-9a-f\-]+)$/);

    return m ? m[1] : null;
  },

  startSearching: function () {
    // Does this source have subfolders?
    if (!this.settings.hideSidebar && this.$source.siblings('ul').length) {
      if (this.$includeSubfoldersContainer === null) {
        var id = 'includeSubfolders-' + Math.floor(Math.random() * 1000000000);

        this.$includeSubfoldersContainer = $(
          '<div style="margin-bottom: -25px; opacity: 0;"/>'
        ).insertAfter(this.$search);
        var $subContainer = $('<div style="padding-top: 5px;"/>').appendTo(
          this.$includeSubfoldersContainer
        );
        this.$includeSubfoldersCheckbox = $(
          '<input type="checkbox" id="' + id + '" class="checkbox"/>'
        ).appendTo($subContainer);
        $('<label class="light smalltext" for="' + id + '"/>')
          .text(' ' + Craft.t('app', 'Search in subfolders'))
          .appendTo($subContainer);

        this.addListener(
          this.$includeSubfoldersCheckbox,
          'change',
          function () {
            this.setSelecetedSourceState(
              'includeSubfolders',
              this.$includeSubfoldersCheckbox.prop('checked')
            );
            this.updateElements();
          }
        );
      } else {
        this.$includeSubfoldersContainer.velocity('stop');
      }

      var checked = this.getSelectedSourceState('includeSubfolders', false);
      this.$includeSubfoldersCheckbox.prop('checked', checked);

      this.$includeSubfoldersContainer.velocity(
        {
          marginBottom: 0,
          opacity: 1,
        },
        'fast'
      );

      this.showingIncludeSubfoldersCheckbox = true;
    }

    this.base();
  },

  stopSearching: function () {
    if (this.showingIncludeSubfoldersCheckbox) {
      this.$includeSubfoldersContainer.velocity('stop');

      this.$includeSubfoldersContainer.velocity(
        {
          marginBottom: -25,
          opacity: 0,
        },
        'fast'
      );

      this.showingIncludeSubfoldersCheckbox = false;
    }

    this.base();
  },

  getViewParams: function () {
    var data = this.base();

    if (
      this.showingIncludeSubfoldersCheckbox &&
      this.$includeSubfoldersCheckbox.prop('checked')
    ) {
      data.criteria.includeSubfolders = true;
    }

    return data;
  },

  /**
   * React on upload submit.
   *
   * @private
   */
  _onUploadStart: function () {
    this.setIndexBusy();

    // Initial values
    this._positionProgressBar();
    this.progressBar.resetProgressBar();
    this.progressBar.showProgressBar();

    this.promptHandler.resetPrompts();
  },

  /**
   * Update uploaded byte count.
   */
  _onUploadProgress: function (event, data) {
    var progress = parseInt((data.loaded / data.total) * 100, 10);
    this.progressBar.setProgressPercentage(progress);
  },

  /**
   * On Upload Failure.
   */
  _onUploadFailure: function (event, data) {
    const response = data.response();
    let {message, filename} = response?.jqXHR?.responseJSON || {};

    if (!message) {
      message = filename
        ? Craft.t('app', 'Upload failed for “{filename}”.', {filename})
        : Craft.t('app', 'Upload failed.');
    }

    alert(message);
  },
  /**
   * On Upload Complete.
   */
  _onUploadComplete: function (event, data) {
    const {result} = data || {};
    const assetId = result?.assetId;

    // Add the uploaded file to the selected ones, if appropriate
    if (assetId) {
      this.selectElementAfterUpdate(assetId);
    }

    // If there is a prompt, add it to the queue
    if (result?.conflict) {
      result.prompt = {
        message: Craft.t('app', result.conflict, {file: result.filename}),
        choices: this._fileConflictTemplate.choices,
      };

      this.promptHandler.addPrompt(result);
    }

    Craft.cp.runQueue();

    // For the last file, display prompts, if any. If not - just update the element view.
    if (this.uploader.isLastUpload()) {
      this.setIndexAvailable();
      this.progressBar.hideProgressBar();

      if (this.promptHandler.getPromptCount()) {
        this.promptHandler.showBatchPrompts(this._uploadFollowup.bind(this));
      } else if (assetId) {
        this._updateAfterUpload();
      }
    }
  },

  /**
   * Update the elements after an upload, setting sort to dateModified descending, if not using index.
   *
   * @private
   */
  _updateAfterUpload: function () {
    if (this.settings.context !== 'index') {
      this.clearSearch();
      this.setSortAttribute('dateCreated');
      this.setSortDirection('desc');
    }
    this.updateElements();
  },

  /**
   * Follow up to an upload that triggered at least one conflict resolution prompt.
   *
   * @param returnData
   * @private
   */
  _uploadFollowup: function (returnData) {
    this.setIndexBusy();
    this.progressBar.resetProgressBar();

    this.promptHandler.resetPrompts();

    var finalCallback = () => {
      this.setIndexAvailable();
      this.progressBar.hideProgressBar();
      this._updateAfterUpload();
    };

    this.progressBar.setItemCount(returnData.length);

    var doFollowup = (parameterArray, parameterIndex, callback) => {
      var data = {};
      var action = null;

      const followupAlways = () => {
        parameterIndex++;
        this.progressBar.incrementProcessedItemCount(1);
        this.progressBar.updateProgressBar();

        if (parameterIndex === parameterArray.length) {
          callback();
        } else {
          doFollowup(parameterArray, parameterIndex, callback);
        }
      };
      const followupSuccess = (data) => {
        if (data.assetId) {
          this.selectElementAfterUpdate(data.assetId);
        }

        followupAlways();
      };
      const followupFailure = (data) => {
        alert(data.message);
        followupAlways();
      };

      if (parameterArray[parameterIndex].choice === 'replace') {
        action = 'assets/replace-file';
        data.sourceAssetId = parameterArray[parameterIndex].assetId;

        if (parameterArray[parameterIndex].conflictingAssetId) {
          data.assetId = parameterArray[parameterIndex].conflictingAssetId;
        } else {
          data.targetFilename = parameterArray[parameterIndex].filename;
        }
      } else if (parameterArray[parameterIndex].choice === 'cancel') {
        action = 'assets/delete-asset';
        data.assetId = parameterArray[parameterIndex].assetId;
      }

      if (!action) {
        // We don't really need to do another request, so let's pretend that already happened
        followupSuccess({
          assetId: parameterArray[parameterIndex].assetId,
        });
      } else {
        Craft.sendActionRequest('POST', action, {data})
          .then((response) => followupSuccess(response.data))
          .catch(({response}) => followupFailure(response.data));
      }
    };

    this.progressBar.showProgressBar();
    doFollowup(returnData, 0, finalCallback);
  },

  /**
   * Perform actions after updating elements
   * @private
   */
  onUpdateElements: function () {
    this._onUpdateElements(false, this.view.getAllElements());
    this.view.on('appendElements', (ev) => {
      this._onUpdateElements(true, ev.newElements);
    });

    this.base();
  },

  /**
   * Do the after-update initializations
   * @private
   */
  _onUpdateElements: function (append, $newElements) {
    if (this.settings.context === 'index') {
      if (!append) {
        this._assetDrag.removeAllItems();
      }

      this._assetDrag.addItems($newElements.has('div.element[data-movable]'));
    }

    this.base(append, $newElements);

    this.removeListener(this.$elements, 'keydown');
    this.addListener(this.$elements, 'keydown', this._onKeyDown.bind(this));
    this.view.elementSelect.on('focusItem', this._onElementFocus.bind(this));
  },

  /**
   * Handle a keypress
   * @private
   */
  _onKeyDown: function (ev) {
    if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
      if (Craft.PreviewFileModal.openInstance) {
        Craft.PreviewFileModal.openInstance.selfDestruct();
      } else {
        var $element = this.view.elementSelect.$focusedItem.find('.element');

        if ($element.length) {
          this._loadPreview($element);
        }
      }

      ev.stopPropagation();
      return false;
    }
  },

  /**
   * Handle element being focused
   * @private
   */
  _onElementFocus: function (ev) {
    var $element = $(ev.item).find('.element');

    if (Craft.PreviewFileModal.openInstance && $element.length) {
      this._loadPreview($element);
    }
  },

  /**
   * Load the preview for an Asset element
   * @private
   */
  _loadPreview: function ($element) {
    var settings = {};

    if ($element.data('image-width')) {
      settings.startingWidth = $element.data('image-width');
      settings.startingHeight = $element.data('image-height');
    }

    new Craft.PreviewFileModal(
      $element.data('id'),
      this.view.elementSelect,
      settings
    );
  },

  /**
   * On Drag Start
   */
  _onDragStart: function () {
    this._tempExpandedFolders = [];
  },

  /**
   * Get File Drag Helper
   */
  _getFileDragHelper: function ($element) {
    var currentView = this.getSelectedSourceState('mode');
    var $outerContainer;
    var $innerContainer;

    switch (currentView) {
      case 'table': {
        $outerContainer = $(
          '<div class="elements datatablesorthelper"/>'
        ).appendTo(Garnish.$bod);
        $innerContainer = $('<div class="tableview"/>').appendTo(
          $outerContainer
        );
        var $table = $('<table class="data"/>').appendTo($innerContainer);
        var $tbody = $('<tbody/>').appendTo($table);

        $element.appendTo($tbody);

        // Copy the column widths
        this._$firstRowCells = this.view.$table
          .children('tbody')
          .children('tr:first')
          .children();
        var $helperCells = $element.children();

        for (var i = 0; i < $helperCells.length; i++) {
          // Hard-set the cell widths
          var $helperCell = $($helperCells[i]);

          // Skip the checkbox cell
          if ($helperCell.hasClass('checkbox-cell')) {
            $helperCell.remove();
            $outerContainer.css('margin-' + Craft.left, 19); // 26 - 7
            continue;
          }

          var $firstRowCell = $(this._$firstRowCells[i]),
            width = $firstRowCell.width();

          $firstRowCell.width(width);
          $helperCell.width(width);
        }

        return $outerContainer;
      }
      case 'thumbs': {
        $outerContainer = $('<div class="elements thumbviewhelper"/>').appendTo(
          Garnish.$bod
        );
        $innerContainer = $('<ul class="thumbsview"/>').appendTo(
          $outerContainer
        );

        $element.appendTo($innerContainer);

        return $outerContainer;
      }
    }

    return $();
  },

  /**
   * On Drop Target Change
   */
  _onDropTargetChange: function ($dropTarget) {
    clearTimeout(this._expandDropTargetFolderTimeout);

    if ($dropTarget) {
      var folderId = $dropTarget.data('folder-id');

      if (folderId) {
        this.dropTargetFolder = this._getSourceByKey(folderId);

        if (
          this._hasSubfolders(this.dropTargetFolder) &&
          !this._isExpanded(this.dropTargetFolder)
        ) {
          this._expandDropTargetFolderTimeout = setTimeout(
            this._expandFolder.bind(this),
            500
          );
        }
      } else {
        this.dropTargetFolder = null;
      }
    }

    if ($dropTarget && $dropTarget[0] !== this.$source[0]) {
      // Temporarily remove the .sel class on the active source
      this.$source.removeClass('sel');
    } else {
      this.$source.addClass('sel');
    }
  },

  /**
   * Collapse Extra Expanded Folders
   */
  _collapseExtraExpandedFolders: function (dropTargetFolderId) {
    clearTimeout(this._expandDropTargetFolderTimeout);

    // If a source ID is passed in, exclude its parents
    var $excludedSources;

    if (dropTargetFolderId) {
      $excludedSources = this._getSourceByKey(dropTargetFolderId)
        .parents('li')
        .children('a');
    }

    for (var i = this._tempExpandedFolders.length - 1; i >= 0; i--) {
      var $source = this._tempExpandedFolders[i];

      // Check the parent list, if a source id is passed in
      if (
        typeof $excludedSources === 'undefined' ||
        $excludedSources.filter('[data-key="' + $source.data('key') + '"]')
          .length === 0
      ) {
        this._collapseFolder($source);
        this._tempExpandedFolders.splice(i, 1);
      }
    }
  },

  _getSourceByKey: function (key) {
    return this.$sources.filter('[data-key$="' + key + '"]');
  },

  _hasSubfolders: function ($source) {
    return $source.siblings('ul').find('li').length;
  },

  _isExpanded: function ($source) {
    return $source.parent('li').hasClass('expanded');
  },

  _expandFolder: function () {
    // Collapse any temp-expanded drop targets that aren't parents of this one
    this._collapseExtraExpandedFolders(this.dropTargetFolder.data('folder-id'));

    this.dropTargetFolder.siblings('.toggle').trigger('click');

    // Keep a record of that
    this._tempExpandedFolders.push(this.dropTargetFolder);
  },

  _collapseFolder: function ($source) {
    if ($source.parent().hasClass('expanded')) {
      $source.siblings('.toggle').trigger('click');
    }
  },

  _createFolderContextMenu: function ($source) {
    // Make sure it's a volume folder
    if (!this._getVolumeOrFolderUidFromSourceKey($source.data('key'))) {
      return;
    }

    var menuOptions = [
      {
        label: Craft.t('app', 'New subfolder'),
        onClick: () => {
          this._createSubfolder($source);
        },
      },
    ];

    // For all folders that are not top folders
    if (
      this.settings.context === 'index' &&
      this._getSourceLevel($source) > 1
    ) {
      menuOptions.push({
        label: Craft.t('app', 'Rename folder'),
        onClick: () => {
          this._renameFolder($source);
        },
      });
      menuOptions.push({
        label: Craft.t('app', 'Delete folder'),
        onClick: () => {
          this._deleteFolder($source);
        },
      });
    }

    new Garnish.ContextMenu($source, menuOptions, {menuClass: 'menu'});
  },

  _createSubfolder: function ($parentFolder) {
    var subfolderName = prompt(Craft.t('app', 'Enter the name of the folder'));

    if (subfolderName) {
      var data = {
        parentId: $parentFolder.data('folder-id'),
        folderName: subfolderName,
      };

      this.setIndexBusy();

      Craft.sendActionRequest('POST', 'assets/create-folder', {data})
        .then((response) => {
          const data = response.data;
          this.setIndexAvailable();
          this._prepareParentForChildren($parentFolder);
          var $subfolder = $(
            '<li>' +
              '<a data-key="' +
              $parentFolder.data('key') +
              '/folder:' +
              data.folderUid +
              '"' +
              (Garnish.hasAttr($parentFolder, 'data-has-thumbs')
                ? ' data-has-thumbs'
                : '') +
              ' data-folder-id="' +
              data.folderId +
              '"' +
              (Garnish.hasAttr($parentFolder, 'data-can-upload')
                ? ' data-can-upload'
                : '') +
              (Garnish.hasAttr($parentFolder, 'data-can-move-to')
                ? ' data-can-move-to'
                : '') +
              (Garnish.hasAttr($parentFolder, 'data-can-move-peer-files-to')
                ? ' data-can-move-peer-files-to'
                : '') +
              '>' +
              data.folderName +
              '</a>' +
              '</li>'
          );

          var $a = $subfolder.children('a:first');
          this._appendSubfolder($parentFolder, $subfolder);
          this.initSource($a);
        })
        .catch(({response}) => {
          this.setIndexAvailable();
          alert(response.data.message);
        });
    }
  },

  _deleteFolder: function ($targetFolder) {
    if (
      confirm(
        Craft.t('app', 'Really delete folder “{folder}”?', {
          folder: $.trim($targetFolder.text()),
        })
      )
    ) {
      var data = {
        folderId: $targetFolder.data('folder-id'),
      };

      this.setIndexBusy();

      Craft.sendActionRequest('POST', 'assets/delete-folder', {data})
        .then((response) => {
          this.setIndexAvailable();
          var $parentFolder = this._getParentSource($targetFolder);

          // Remove folder and any trace from its parent, if needed
          this.deinitSource($targetFolder);

          $targetFolder.parent().remove();
          this._cleanUpTree($parentFolder);
        })
        .catch(({response}) => {
          this.setIndexAvailable();
          alert(response.data.message);
        });
    }
  },

  /**
   * Rename
   */
  _renameFolder: function ($source) {
    const $label = $source.children('.label');
    const oldName = Craft.trim($label.text());
    const newName = prompt(Craft.t('app', 'Rename folder'), oldName);

    if (!newName || newName === oldName) {
      return;
    }

    this.setIndexBusy();

    Craft.sendActionRequest('POST', 'assets/rename-folder', {
      data: {
        folderId: $source.data('folder-id'),
        newName: newName,
      },
    })
      .then((response) => {
        $label.text(response.data.newName);

        // Is this the selected source?
        if ($source.data('key') === this.$source.data('key')) {
          this.updateElements();

          // Update the URL if we're on the Assets index
          if (this.settings.context === 'index') {
            this._updateUrl($source);
          }
        }
      })
      .catch(({response}) => {
        this.setIndexAvailable();
        alert(response.data.message);
      });
  },

  /**
   * Prepare a source folder for children folder.
   *
   * @param $parentFolder
   * @private
   */
  _prepareParentForChildren: function ($parentFolder) {
    if (!this._hasSubfolders($parentFolder)) {
      $parentFolder
        .parent()
        .addClass('expanded')
        .append('<div class="toggle"></div><ul></ul>');
      this.initSourceToggle($parentFolder);
    }
  },

  /**
   * Appends a subfolder to the parent folder at the correct spot.
   *
   * @param $parentFolder
   * @param $subfolder
   * @private
   */
  _appendSubfolder: function ($parentFolder, $subfolder) {
    var $subfolderList = $parentFolder.siblings('ul'),
      $existingChildren = $subfolderList.children('li'),
      subfolderLabel = $.trim($subfolder.children('a:first').text()),
      folderInserted = false;

    for (var i = 0; i < $existingChildren.length; i++) {
      var $existingChild = $($existingChildren[i]);

      if ($.trim($existingChild.children('a:first').text()) > subfolderLabel) {
        $existingChild.before($subfolder);
        folderInserted = true;
        break;
      }
    }

    if (!folderInserted) {
      $parentFolder.siblings('ul').append($subfolder);
    }
  },

  _cleanUpTree: function ($parentFolder) {
    if (
      $parentFolder !== null &&
      $parentFolder.siblings('ul').children('li').length === 0
    ) {
      this.deinitSourceToggle($parentFolder);
      $parentFolder.siblings('ul').remove();
      $parentFolder.siblings('.toggle').remove();
      $parentFolder.parent().removeClass('expanded');
    }
  },

  _positionProgressBar: function () {
    if (!this.progressBar) {
      this.progressBar = new Craft.ProgressBar(this.$main, true);
    }

    var $container = $(),
      scrollTop = 0,
      offset = 0;

    if (this.settings.context === 'index') {
      $container = this.progressBar.$progressBar.closest('#content');
      scrollTop = Garnish.$win.scrollTop();
    } else {
      $container = this.progressBar.$progressBar.closest('.main');
      scrollTop = this.$main.scrollTop();
    }

    var containerTop = $container.offset().top;
    var diff = scrollTop - containerTop;
    var windowHeight = Garnish.$win.height();

    if ($container.height() > windowHeight) {
      offset = windowHeight / 2 - 6 + diff;
    } else {
      offset = $container.height() / 2 - 6;
    }

    if (this.settings.context !== 'index') {
      offset = scrollTop + ($container.height() / 2 - 6);
    }

    this.progressBar.$progressBar.css({
      top: offset,
    });
  },

  _performBatchRequests: function (parameterArray, finalCallback) {
    const responseArray = [];
    let activeRequests = parameterArray.length;

    while (parameterArray.length) {
      const parameters = parameterArray.shift();
      Craft.sendActionRequest('POST', parameters.action, {
        data: parameters.params,
      })
        .then((response) => {
          responseArray.push(response.data);
        })
        .finally(() => {
          this.progressBar.incrementProcessedItemCount(1);
          this.progressBar.updateProgressBar();

          // Was that the last one?
          if (--activeRequests === 0) {
            // If assets were just merged we should get the reference tags updated right away
            Craft.cp.runQueue();
            finalCallback(responseArray);
          }
        });
    }
  },
});

// Register it!
Craft.registerElementIndexClass('craft\\elements\\Asset', Craft.AssetIndex);
