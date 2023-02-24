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

    _uploadTotalFiles: 0,
    _uploadFileProgress: {},
    _currentUploaderSettings: {},

    init: function (elementType, $container, settings) {
      settings = Object.assign({}, Craft.AssetIndex.defaults, settings);
      this.base(elementType, $container, settings);

      if (this.settings.context === 'index') {
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

    afterInit: function () {
      if (!this.settings.foldersOnly) {
        this.initForFiles();
      }

      this.base();
    },

    /**
     * Initialize the uploader.
     *
     * @private
     */
    initForFiles: function () {
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
      this.progressBar = new Craft.ProgressBar(this.$main, false);

      var options = {
        url: Craft.getActionUrl('assets/upload'),
        fileInput: this.$uploadInput,
        dropZone: this.$container,
      };

      options.events = {
        fileuploadstart: this._onUploadStart.bind(this),
        fileuploadprogressall: this._onUploadProgress.bind(this),
        fileuploadalways: this._onUploadAlways.bind(this),
        fileuploaddone: this._onUploadComplete.bind(this),
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
    },

    onSelectSource: function () {
      if (!this.settings.foldersOnly) {
        const folderId = this.$source.data('folder-id');
        if (folderId && Garnish.hasAttr(this.$source, 'data-can-upload')) {
          this.uploader.setParams({
            folderId: this.$source.attr('data-folder-id'),
          });
          this.$uploadButton.removeClass('disabled');
        } else {
          this.$uploadButton.addClass('disabled');
        }
      }

      this.base();
    },

    onSourcePathChange: function () {
      if (!this.settings.foldersOnly && this.sourcePath.length) {
        const currentFolder = this.sourcePath[this.sourcePath.length - 1];
        if (currentFolder.folderId) {
          this.uploader.setParams({
            folderId: currentFolder.folderId,
          });
        }
      }

      this.base();
    },

    startSearching: function () {
      // Does this source have subfolders?
      if (
        !this.settings.hideSidebar &&
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
      const data = Object.assign(this.base(), {
        showFolders: this.settings.showFolders,
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
    _onUploadProgress: function (event, data) {
      var progress = parseInt((data.loaded / data.total) * 100, 10);
      this.progressBar.setProgressPercentage(progress);
    },

    /**
     * On upload complete no matter what
     * @private
     */
    _onUploadAlways: function () {
      // For the last file, display prompts, if any. If not - just update the element view.
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
     * On Upload Complete.
     */
    _onUploadComplete: function (event, data) {
      var response = data.result;
      var filename = data.files[0].name;

      if (response.success || response.conflict) {
        // Add the uploaded file to the selected ones, if appropriate
        this.selectElementAfterUpdate(response.assetId);

        // If there is a prompt, add it to the queue
        if (response.conflict) {
          response.prompt = {
            message: Craft.t('app', response.conflict, {
              file: response.filename,
            }),
            choices: [
              {value: 'keepBoth', title: Craft.t('app', 'Keep both')},
              {value: 'replace', title: Craft.t('app', 'Replace it')},
            ],
          };

          this.promptHandler.addPrompt(response);
        }

        Craft.cp.runQueue();
      } else {
        if (response.error) {
          alert(
            Craft.t('app', 'Upload failed. The error message was: “{error}”', {
              error: response.error,
            })
          );
        } else {
          alert(Craft.t('app', 'Upload failed for {filename}.', {filename}));
        }
      }
    },

    /**
     * On Upload Failure.
     */
    _onUploadFailure: function (event, data) {
      if (data.jqXHR.responseJSON.error) {
        alert(data.jqXHR.responseJSON.error);
        this.progressBar.hideProgressBar();
        this.setIndexAvailable();
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
        this.progressBar.hideProgressBar();
        this.setIndexAvailable();
        this._updateAfterUpload();
      };

      this.progressBar.setItemCount(returnData.length);

      var doFollowup = (parameterArray, parameterIndex, callback) => {
        var postData = {};
        var action = null;

        var followupCallback = (data, textStatus) => {
          if (textStatus === 'success' && data.assetId) {
            this.selectElementAfterUpdate(data.assetId);
          } else if (data.error) {
            alert(data.error);
          }
          parameterIndex++;
          this.progressBar.incrementProcessedItemCount(1);
          this.progressBar.updateProgressBar();

          if (parameterIndex === parameterArray.length) {
            callback();
          } else {
            doFollowup(parameterArray, parameterIndex, callback);
          }
        };

        if (parameterArray[parameterIndex].choice === 'replace') {
          action = 'assets/replace-file';
          postData.sourceAssetId = parameterArray[parameterIndex].assetId;

          if (parameterArray[parameterIndex].conflictingAssetId) {
            postData.assetId =
              parameterArray[parameterIndex].conflictingAssetId;
          } else {
            postData.targetFilename = parameterArray[parameterIndex].filename;
          }
        } else if (parameterArray[parameterIndex].choice === 'cancel') {
          action = 'assets/delete-asset';
          postData.assetId = parameterArray[parameterIndex].assetId;
        }

        if (!action) {
          // We don't really need to do another request, so let's pretend that already happened
          followupCallback(
            {assetId: parameterArray[parameterIndex].assetId},
            'success'
          );
        } else {
          Craft.postActionRequest(action, postData, followupCallback);
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
      this.removeListener(this.$elements, 'keydown');
      this.addListener(this.$elements, 'keydown', this._onKeyDown.bind(this));
      this.view.elementSelect.on('focusItem', this._onElementFocus.bind(this));

      const $folders = $newElements.find('.element[data-is-folder]');
      for (let i = 0; i < $folders.length; i++) {
        const $folder = $folders.eq(i);
        const $label = $folder.find('.label');
        const folderId = parseInt($folder.data('folder-id'));
        const folderName = $label.text();
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
          const $a = $('<a/>', {
            href: Craft.getCpUrl(sourcePath[sourcePath.length - 1].uri),
            text: folderName,
            role: 'button',
            'aria-label': label,
          });
          $label.empty().append($a);
          this.addListener($a, 'activate', (ev) => {
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
     * Load the preview for an asset
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
                this._deleteFolder();
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
        const params = {
          parentId: currentFolder.folderId,
          folderName: subfolderName,
        };

        this.setIndexBusy();

        Craft.postActionRequest(
          'assets/create-folder',
          params,
          (data, textStatus) => {
            this.setIndexAvailable();

            if (textStatus === 'success' && data.success) {
              Craft.cp.displayNotice(Craft.t('app', 'Folder created.'));
              this.updateElements(true);
            }

            if (textStatus === 'success' && data.error) {
              alert(data.error);
            }
          }
        );
      }
    },

    _deleteFolder: function () {
      const currentFolder = this.sourcePath[this.sourcePath.length - 1];

      if (
        confirm(
          Craft.t('app', 'Really delete folder “{folder}”?', {
            folder: currentFolder.label,
          })
        )
      ) {
        const params = {
          folderId: currentFolder.folderId,
        };

        this.setIndexBusy();

        Craft.postActionRequest(
          'assets/delete-folder',
          params,
          (data, textStatus) => {
            this.setIndexAvailable();

            if (textStatus === 'success' && data.success) {
              Craft.cp.displayNotice(Craft.t('app', 'Folder deleted.'));
              this.sourcePath = this.sourcePath.slice(
                0,
                this.sourcePath.length - 1
              );
              this.updateElements();
            }

            if (textStatus === 'success' && data.error) {
              alert(data.error);
            }
          }
        );
      }
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
          if (response.data.success) {
            Craft.cp.displayNotice(Craft.t('app', 'Folder renamed.'));
            const sourcePath = this.sourcePath.slice();
            sourcePath[sourcePath.length - 1].label = response.data.newName;
            sourcePath[sourcePath.length - 1].uri =
              sourcePath[sourcePath.length - 1].uri +
              `/${response.data.newName}`;
            this.sourcePath = sourcePath;
          } else if (response.data.error) {
            alert(response.data.error);
          }
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
