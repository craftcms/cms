/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend(
  {
    $includeSubfoldersContainer: null,
    $includeSubfoldersCheckbox: null,
    showingIncludeSubfoldersCheckbox: false,

    $uploadButton: null,
    $uploadInput: null,
    $progressBar: null,

    uploader: null,
    promptHandler: null,
    progressBar: null,
    currentFolderId: null,

    $listedFolders: null,
    itemDrag: null,

    _uploadTotalFiles: 0,
    _uploadFileProgress: {},
    _currentUploaderSettings: {},
    _includeSubfolders: null,

    init: function (elementType, $container, settings) {
      settings = Object.assign({}, Craft.AssetIndex.defaults, settings);
      this.setSettings(settings, Craft.BaseElementIndex.defaults);

      if (this.settings.context === 'index') {
        // remember whether includeSubfolders was set in the query string,
        // before the URL is updated
        const queryParams = Craft.getQueryParams();
        if (queryParams.includeSubfolders !== undefined) {
          this._includeSubfolders = !!parseInt(queryParams.includeSubfolders);
        }
      }

      this.base(elementType, $container, this.settings);

      if (this.settings.context === 'index') {
        this.itemDrag = new Garnish.DragDrop({
          activeDropTargetClass: 'sel',
          minMouseDist: 10,
          hideDraggee: false,
          moveHelperToCursor: true,
          activeDropTargetClass: 'active-drop-target',
          handle: (item) => $(item).closest('tr,li'),
          filter: () => {
            const $container = this.itemDrag.$targetItem.closest('tr,li');
            this.view.elementSelect.selectItem($container);
            return this._findDraggableItems(this.view.getSelectedElements());
          },
          helper: ($item, index) =>
            $('<div class="offset-drag-helper"/>')
              .append($item)
              .css({
                opacity: Math.max(0.9 - 0.05 * index, 0),
                width: '',
                height: '',
              }),
          dropTargets: () => {
            // volume sources
            let $dropTargets = $(
              this.$visibleSources
                .toArray()
                .filter(
                  (source) =>
                    Garnish.hasAttr(source, 'data-folder-id') &&
                    Garnish.hasAttr(source, 'data-can-move-peer-files-to')
                )
            );
            if (this.sourcePath.length <= 1) {
              // exclude the current source since we're already at the root of it
              $dropTargets = $dropTargets.not(this.$source);
            } else {
              // parent folders in the source path
              for (let i = 0; i < this.sourcePath.length - 1; i++) {
                const step = this.sourcePath[i];
                if (step.folderId) {
                  $dropTargets = $dropTargets.add(step.$btn);
                }
              }
            }
            // folders in the elements listing
            if (this.$listedFolders) {
              $dropTargets = $dropTargets
                .add(
                  this.$listedFolders
                    .filter('[data-folder-id]')
                    .closest('tr,li')
                )
                .not(this.view.getSelectedElements());
            }
            return $dropTargets;
          },
          onDragStart: () => {
            Garnish.$bod.addClass('dragging');
            this.itemDrag.$draggee.closest('tr,li').addClass('draggee');
          },
          onDragStop: () => {
            Garnish.$bod.removeClass('dragging');

            const $draggee = this.itemDrag.$draggee;
            const targetFolderId = this._targetFolderId(
              this.itemDrag.$activeDropTarget
            );

            if (!targetFolderId) {
              $draggee.closest('tr,li').removeClass('draggee');
              this.itemDrag.returnHelpersToDraggees();
              return;
            }

            this.itemDrag.fadeOutHelpers();

            const $folders = $draggee.filter('[data-is-folder]');
            const $assets = $draggee.not($folders);
            const folderIds = $folders.toArray().map((item) => {
              return parseInt($(item).data('folder-id'));
            });
            const assetIds = $assets.toArray().map((item) => {
              return parseInt($(item).data('id'));
            });

            const mover = new Craft.AssetMover();
            mover
              .moveFolders(folderIds, targetFolderId)
              .then((totalFoldersMoved) => {
                mover
                  .moveAssets(assetIds, targetFolderId)
                  .then((totalAssetsMoved) => {
                    const totalItemsMoved =
                      totalFoldersMoved + totalAssetsMoved;
                    if (totalItemsMoved) {
                      Craft.cp.displayNotice(
                        Craft.t(
                          'app',
                          '{totalItems, plural, =1{Item} other{Items}} moved.',
                          {
                            totalItems: totalItemsMoved,
                          }
                        )
                      );
                      Craft.elementIndex.updateElements(true);
                    } else {
                      $draggee.closest('tr,li').removeClass('draggee');
                    }
                  });
              });
          },
        });

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

      this.addListener(this.$elements, 'keydown', this._onKeyDown.bind(this));
    },

    _findDraggableItems: function ($items) {
      return $(
        $items
          .toArray()
          .map((item) => $(item).find('.element:first')[0])
          .filter((item) => item && Garnish.hasAttr(item, 'data-movable'))
      );
    },

    _targetFolderId: function ($dropTarget) {
      if (!$dropTarget || !$dropTarget.length) {
        return false;
      }

      // source?
      if ($dropTarget.is(this.$visibleSources)) {
        return $dropTarget.data('folder-id');
      }

      // source path step?
      for (let i = 0; i < this.sourcePath.length - 1; i++) {
        const step = this.sourcePath[i];
        if ($dropTarget.is(step.$btn)) {
          return step.folderId;
        }
      }

      // folder in the element listing?
      return $dropTarget.find('.element:first').data('folder-id') || false;
    },

    afterInit: function () {
      if (!this.settings.foldersOnly) {
        this.initForFiles();
      }

      // Double-clicking or double-tapping on folders should open them
      this.addListener(this.$elements, 'doubletap', function (ev, touchData) {
        // Make sure the touch targets are the same
        // (they may be different if Command/Ctrl/Shift-clicking on multiple elements quickly)
        if (touchData.firstTap.target === touchData.secondTap.target) {
          const $element = $(touchData.firstTap.target)
            .closest('tr,ul.thumbsview > li')
            .find('.element:first');
          if (Garnish.hasAttr($element, 'data-is-folder')) {
            $element.find('a').trigger('activate');
          }
        }
      });

      this.base();
    },

    /**
     * Initialize the uploader.
     *
     * @private
     */
    initForFiles: function () {
      this.promptHandler = new Craft.PromptHandler();
      this.progressBar = new Craft.ProgressBar(this.$main, false);
    },

    createUploadInputs: function () {
      this.$uploadButton?.remove();
      this.$uploadInput?.remove();

      this.$uploadButton = $('<button/>', {
        type: 'button',
        class: 'btn submit',
        'data-icon': 'upload',
        style: 'position: relative; overflow: hidden;',
        'aria-label': Craft.t('app', 'Upload files'),
        text: Craft.t('app', 'Upload files'),
      });
      this.addButton(this.$uploadButton);

      this.$uploadInput = $(
        '<input type="file" multiple="multiple" name="assets-upload" />'
      )
        .hide()
        .insertBefore(this.$uploadButton);

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
    },

    onSelectSource: function () {
      if (!this.settings.foldersOnly) {
        this.currentFolderId =
          this.currentFolderId || this.$source.data('folder-id');
        const fsType = this.$source.data('fs-type');

        this.createUploadInputs();

        if (
          this.currentFolderId &&
          Garnish.hasAttr(this.$source, 'data-can-upload')
        ) {
          this.uploader?.destroy();
          this.$uploadButton.removeClass('disabled');

          const options = {
            fileInput: this.$uploadInput,
            dropZone: this.$container,
            events: {
              fileuploadstart: this._onUploadStart.bind(this),
              fileuploadprogressall: this._onUploadProgress.bind(this),
              fileuploaddone: this._onUploadSuccess.bind(this),
              fileuploadalways: this._onUploadAlways.bind(this),
              fileuploadfail: this._onUploadFailure.bind(this),
            },
          };

          if (this.settings?.criteria?.kind) {
            options.allowedKinds = this.settings.criteria.kind;
          }

          this._currentUploaderSettings = options;

          this.uploader = Craft.createUploader(
            fsType,
            this.$uploadButton,
            options
          );
          this.uploader.setParams({
            folderId: this.currentFolderId,
          });
        } else {
          this.$uploadButton.addClass('disabled');
        }
      }

      this.base();
    },

    onSourcePathChange: function () {
      const currentFolder = this.sourcePath.length
        ? this.sourcePath[this.sourcePath.length - 1]
        : null;
      this.currentFolderId = currentFolder?.folderId;

      if (!this.settings.foldersOnly && this.currentFolderId) {
        this.uploader?.setParams({
          folderId: this.currentFolderId,
        });

        // will the user be allowed to move items in this folder?
        const canMoveSubItems =
          this.context === 'index' && !!currentFolder.canMoveSubItems;
        this.settings.selectable = this.settings.selectable || canMoveSubItems;
        this.settings.multiSelect =
          this.settings.multiSelect || canMoveSubItems;
      }

      this.base();
    },

    startSearching: function () {
      // Does this source have subfolders?
      if (
        !this.settings.hideSidebar &&
        this.sourcePath.length &&
        this.sourcePath[this.sourcePath.length - 1].hasChildren
      ) {
        if (this.$includeSubfoldersContainer === null) {
          var id =
            'includeSubfolders-' + Math.floor(Math.random() * 1000000000);

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
          this.$includeSubfoldersContainer
            .velocity('stop')
            .removeClass('hidden');
        }

        let checked;
        if (this._includeSubfolders !== null) {
          checked = this._includeSubfolders;
          this._includeSubfolders = null;
        } else {
          checked = this.getSelectedSourceState('includeSubfolders', false);
        }
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
          {
            duration: 'fast',
            complete: () => {
              this.$includeSubfoldersContainer.addClass('hidden');
            },
          }
        );

        this.showingIncludeSubfoldersCheckbox = false;
      }

      this.base();
    },

    getViewSettings: function () {
      const settings = {};

      if (this.settings.context === 'index') {
        // Allow folders to be selected
        settings.canSelectElement = () => true;
      }

      return settings;
    },

    getViewParams: function () {
      const data = Object.assign(this.base(), {
        showFolders: this.settings.showFolders && !this.trashed,
        foldersOnly: this.settings.foldersOnly,
      });

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
    _onUploadProgress: function (event, data = null) {
      data = event instanceof CustomEvent ? event.detail : data;

      var progress = parseInt(Math.min(data.loaded / data.total, 1) * 100, 10);
      this.progressBar.setProgressPercentage(progress);
    },

    /**
     * On upload success.
     *
     * @param {Object} event
     * @param {Object} data
     * @private
     */
    _onUploadSuccess: function (event, data = null) {
      const result = event instanceof CustomEvent ? event.detail : data.result;

      // Add the uploaded file to the selected ones, if appropriate
      this.selectElementAfterUpdate(result.assetId);

      // If there is a prompt, add it to the queue
      if (result.conflict) {
        result.prompt = {
          message: Craft.t('app', result.conflict, {file: result.filename}),
          choices: [
            {value: 'keepBoth', title: Craft.t('app', 'Keep both')},
            {value: 'replace', title: Craft.t('app', 'Replace it')},
          ],
          modalSettings: {
            hideOnEsc: false,
            hideOnShadeClick: false,
          },
        };

        this.promptHandler.addPrompt(result);
      }

      Craft.cp.runQueue();
    },

    /**
     * On upload complete no matter what (success, fail, or abort).
     */
    _onUploadAlways: function () {
      if (this.uploader.isLastUpload()) {
        this.progressBar.hideProgressBar();
        this.setIndexAvailable();

        if (this.promptHandler.getPromptCount()) {
          this.promptHandler.showBatchPrompts(this._uploadFollowup.bind(this));
        } else {
          this._updateAfterUpload();
        }
      }
    },

    /**
     * On Upload Failure.
     */
    _onUploadFailure: function (event, data = null) {
      const response =
        event instanceof CustomEvent ? event.detail : data?.jqXHR?.responseJSON;

      let {message, filename, errors} = response || {};
      filename = filename || data?.files?.[0].name;
      let errorMessages = errors ? Object.values(errors).flat() : [];

      if (!message) {
        if (errorMessages.length) {
          message = errorMessages.join('\n');
        } else if (filename) {
          message = Craft.t('app', 'Upload failed for “{filename}”.', {
            filename,
          });
        } else {
          message = Craft.t('app', 'Upload failed.');
        }
      }

      Craft.cp.displayError(message);
    },

    /**
     * Update the elements after an upload, setting sort to dateModified descending, if not using index.
     *
     * @private
     */
    _updateAfterUpload: function () {
      if (this.settings.context !== 'index') {
        this.clearSearch();
        this.setSelectedSortAttribute('dateCreated', 'desc');
      }
      this.updateElements();
    },

    /**
     * Follow up to an upload that triggered at least one conflict resolution prompt.
     *
     * @param {Object} returnData
     * @private
     */
    _uploadFollowup: function (returnData) {
      this.setIndexBusy();
      this.progressBar.resetProgressBar();

      this.promptHandler.resetPrompts();

      var finalCallback = () => {
        this.progressBar.hideProgressBar();
        this.setIndexAvailable();
        this._updateAfterUpload();
      };

      this.progressBar.setItemCount(returnData.length);

      var doFollowup = (parameterArray, parameterIndex, callback) => {
        var data = {};
        var action = null;
        const {replaceAction, deleteAction} = this.uploader.settings;

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
          Craft.cp.displayError(data.message);
          followupAlways();
        };

        if (parameterArray[parameterIndex].choice === 'replace') {
          action = replaceAction;
          data.sourceAssetId = parameterArray[parameterIndex].assetId;

          if (parameterArray[parameterIndex].conflictingAssetId) {
            data.assetId = parameterArray[parameterIndex].conflictingAssetId;
          } else {
            data.targetFilename = parameterArray[parameterIndex].filename;
          }
        } else if (parameterArray[parameterIndex].choice === 'cancel') {
          action = deleteAction;
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
      this.$listedFolders = $newElements.find(
        '.element[data-is-folder][data-folder-name]'
      );
      for (let i = 0; i < this.$listedFolders.length; i++) {
        const $folder = this.$listedFolders.eq(i);
        const $label = $folder.find('.label');
        const $link = $label.find('.label-link');
        const folderId = parseInt($folder.data('folder-id'));
        const folderName = $folder.data('folder-name');
        const label = Craft.t('app', '{name} folder', {
          name: folderName,
        });
        if (this.settings.disabledFolderIds.includes(folderId)) {
          $label.attr('aria-label', label);
          $newElements.has($folder).addClass('disabled');
          continue;
        }
        const sourcePath = $folder.data('source-path');
        if (sourcePath) {
          $link.attr({
            href: Craft.getCpUrl(sourcePath[sourcePath.length - 1].uri),
            role: 'button',
            'aria-label': label,
          });
          this.addListener($link, 'activate', (ev) => {
            this.sourcePath = sourcePath;
            this.clearSearch(false);
            this.updateElements().then(() => {
              const firstFocusableEl = this.$elements.find(
                ':focusable:not(.selectallcontainer)'
              )[0];
              if (firstFocusableEl) {
                firstFocusableEl.focus();
              }
            });
          });
        }
      }

      if (this.itemDrag) {
        const currentFolder = this.sourcePath[this.sourcePath.length - 1];
        const canMoveSubItems = !!(
          currentFolder &&
          currentFolder.folderId &&
          currentFolder.canMoveSubItems
        );
        if (!canMoveSubItems || !append) {
          this.itemDrag.removeAllItems();
        }
        if (canMoveSubItems) {
          this.itemDrag.addItems(this._findDraggableItems($newElements));
        }
      }
    },

    /**
     * Handle a keypress
     * @private
     */
    _onKeyDown: function (ev) {
      if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
        if (Craft.PreviewFileModal.openInstance) {
          Craft.PreviewFileModal.openInstance.hide();
        } else if (this.view.elementSelect) {
          let $element = $(ev.target).closest('.element');
          if (!$element.length) {
            $element = $(ev.target).find('.element:first');
          }

          if ($element.length && !Garnish.hasAttr($element, 'data-folder-id')) {
            Craft.PreviewFileModal.showForAsset(
              $element,
              this.view.elementSelect
            );
          }
        }

        ev.stopPropagation();
        return false;
      }
    },

    /**
     * @returns {string}
     */
    getSourcePathLabel: function () {
      return Craft.t('app', 'Volume path');
    },

    /**
     * @returns {string}
     */
    getSourcePathActionLabel: function () {
      return Craft.t('app', 'Folder actions');
    },

    getSourcePathActions: function () {
      const actions = [];
      const currentFolder = this.sourcePath[this.sourcePath.length - 1];

      if (currentFolder.canCreate) {
        actions.push({
          label: Craft.t('app', 'New subfolder'),
          onSelect: () => {
            this._createSubfolder();
          },
        });
      }

      if (this.settings.context === 'index') {
        if (currentFolder.canRename) {
          actions.push({
            label: Craft.t('app', 'Rename folder'),
            onSelect: () => {
              this._renameFolder();
            },
          });

          if (
            currentFolder.canMove &&
            this.getMoveTargetSourceKeys(true).length
          ) {
            actions.push({
              label: Craft.t('app', 'Move folder'),
              onSelect: () => {
                this._moveFolder();
              },
            });
          }

          if (currentFolder.canDelete) {
            actions.push({
              label: Craft.t('app', 'Delete folder'),
              destructive: true,
              onSelect: () => {
                this.deleteCurrentFolder();
              },
            });
          }
        }
      }

      return actions;
    },

    _createSubfolder: function () {
      const currentFolder = this.sourcePath[this.sourcePath.length - 1];
      const subfolderName = prompt(
        Craft.t('app', 'Enter the name of the folder')
      );

      if (subfolderName) {
        const data = {
          parentId: currentFolder.folderId,
          folderName: subfolderName,
        };

        this.setIndexBusy();

        Craft.sendActionRequest('POST', 'assets/create-folder', {data})
          .then((response) => {
            this.setIndexAvailable();
            Craft.cp.displayNotice(Craft.t('app', 'Folder created.'));
            this.updateElements(true);
          })
          .catch(({response}) => {
            this.setIndexAvailable();
            Craft.cp.displayError(response.data.message);
          });
      }
    },

    deleteCurrentFolder: async function () {
      if (
        await this.deleteFolder(this.sourcePath[this.sourcePath.length - 1])
      ) {
        this.sourcePath = this.sourcePath.slice(0, this.sourcePath.length - 1);
        this.updateElements();
      }
    },

    deleteFolder: async function (folder) {
      if (
        !confirm(
          Craft.t('app', 'Really delete folder “{folder}”?', {
            folder: folder.label,
          })
        )
      ) {
        return false;
      }

      this.setIndexBusy();

      try {
        await Craft.sendActionRequest('POST', 'assets/delete-folder', {
          data: {
            folderId: folder.folderId,
          },
        });
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
        return false;
      } finally {
        this.setIndexAvailable();
      }

      Craft.cp.displayNotice(Craft.t('app', 'Folder deleted.'));
      return true;
    },

    /**
     * Rename
     */
    _renameFolder: function () {
      const currentFolder = this.sourcePath[this.sourcePath.length - 1];
      const newName = prompt(
        Craft.t('app', 'Rename folder'),
        currentFolder.label
      );

      if (!newName || newName === currentFolder.label) {
        return;
      }

      this.setIndexBusy();

      Craft.sendActionRequest('POST', 'assets/rename-folder', {
        data: {
          folderId: currentFolder.folderId,
          newName: newName,
        },
      })
        .then((response) => {
          Craft.cp.displayNotice(Craft.t('app', 'Folder renamed.'));
          const sourcePath = this.sourcePath.slice();
          sourcePath[sourcePath.length - 1].label = response.data.newName;
          sourcePath[sourcePath.length - 1].uri =
            sourcePath[sourcePath.length - 2].uri + `/${response.data.newName}`;
          this.sourcePath = sourcePath;
        })
        .catch(({response}) => {
          Craft.cp.displayError(response.data.message);
        })
        .finally(() => {
          this.setIndexAvailable();
        });
    },

    getMoveTargetSourceKeys: function (peerFiles) {
      const attr = peerFiles
        ? 'data-can-move-peer-files-to'
        : 'data-can-move-to';
      return this.$sources
        .toArray()
        .filter((source) => {
          const volumeHandle = $(source).data('volume-handle');
          return (
            volumeHandle &&
            volumeHandle !== 'temp' &&
            Garnish.hasAttr(source, attr)
          );
        })
        .map((source) => $(source).data('key'));
    },

    _moveFolder: function () {
      const currentFolder = this.sourcePath[this.sourcePath.length - 1];
      const parentFolder = this.sourcePath[this.sourcePath.length - 2];

      const disabledFolderIds = [currentFolder.folderId];
      if (parentFolder) {
        disabledFolderIds.push(parentFolder.folderId);
      }

      new Craft.VolumeFolderSelectorModal({
        sources: this.getMoveTargetSourceKeys(true),
        showTitle: true,
        modalTitle: Craft.t('app', 'Move to'),
        selectBtnLabel: Craft.t('app', 'Move'),
        disabledFolderIds: disabledFolderIds,
        indexSettings: {
          defaultSource: this.sourceKey,
          defaultSourcePath: this.sourcePath.slice(
            0,
            this.sourcePath.length - 1
          ),
        },
        onSelect: ([targetFolder]) => {
          this.$sourcePathActionsBtn.focus();
          const mover = new Craft.AssetMover();
          mover
            .moveFolders([currentFolder.folderId], targetFolder.folderId)
            .then((totalFoldersMoved) => {
              if (totalFoldersMoved) {
                Craft.cp.displayNotice(
                  Craft.t(
                    'app',
                    '{totalItems, plural, =1{Item} other{Items}} moved.',
                    {
                      totalItems: totalFoldersMoved,
                    }
                  )
                );
                this.sourcePath = this.sourcePath.slice(
                  0,
                  this.sourcePath.length - 1
                );
                this.clearSearch(false);
                this.updateElements();
              }
            });
        },
      });
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
  },
  {
    defaults: {
      showFolders: true,
      foldersOnly: false,
      disabledFolderIds: [],
    },
  }
);

// Register it!
Craft.registerElementIndexClass('craft\\elements\\Asset', Craft.AssetIndex);
