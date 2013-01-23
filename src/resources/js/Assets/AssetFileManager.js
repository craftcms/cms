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

		this.$upload = $('.buttons .assets-upload', this.$manager);

		this.$search = $('> .search input.text', this.$toolbar);

		this.$spinner = $('.temp-spinner', this.$manager);

		this.$left = $('> .nav', this.$manager);
		this.$right = $('> .asset-content', this.$manager);

		this.$status = $('> .asset-status', this.$right);

		this.$sources = $('> .assets-sources', this.$left);

		this.$folderContainer = $('> .folder-container', this.$right);

		this.$uploadProgress = $('> .assets-fm-uploadprogress', this.$manager);
		this.$uploadProgressBar = $('.assets-fm-pb-bar', this.$uploadProgress);

		this.modal = null;

		this.sort = 'asc';

		this.requestId = 0;

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
	 * Mark a source as active.
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

				if ($modalContainerDiv == null) {
					$modalContainerDiv = $('<div class="modal view-file"></div>').addClass().appendTo(Garnish.$bod);
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

		if (response.success) {
			this._uploadedFiles++;

			this._setUploadStatus();

		}

		// is this the last file?
		if (! this.uploader.getInProgress()) {
			if (this._uploadedFiles) {
				this.reloadFolderView();
			} else {
				// just skip to hiding the progress bar
				this._hideProgressBar();
				this.$spinner.hide();
			}
		}


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
