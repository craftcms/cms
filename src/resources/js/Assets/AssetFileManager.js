(function($) {

// define the Assets global
if (typeof window.Assets == 'undefined') window.Assets = Blocks.Base.extend({


});

// -------------------------------------------
//  File Manager classes
// -------------------------------------------

    /**
     * File Manager
     */
    Assets.FileManager = Blocks.Base.extend({
        init: function($manager, options) {

            this.$manager = $manager;

            this.options = $.extend({}, Assets.FileManager.defaultOptions, options);

            this.$toolbar = $('.toolbar', this.$manager);

            this.$viewAsThumbsBtn = $('a.thumbs', this.$toolbar);
            this.$viewInListBtn   = $('a.list', this.$toolbar);

            this.$upload = $('.buttons .assets-upload', this.$manager);

            this.$search = $('> .search input.text', this.$toolbar);

            this.$spinner = $('.temp-spinner', this.$manager);

            this.$left = $('> .nav', this.$manager);
            this.$right = $('> .asset-content', this.$manager);

            this.$status = $('> .assets-fm-status', this.$rightFooter);

            this.$folderContainer = $('> .folder-container', this.$right);

            this.$uploadProgress = $('> .assets-fm-uploadprogress', this.$manager);
            this.$uploadProgressBar = $('.assets-fm-pb-bar', this.$uploadProgress);


            this.sort = 'asc';

            this.requestId = 0;

            // -------------------------------------------
            // Assets states
            // -------------------------------------------

            this.currentState = {
                view: 'thumbs',
                current_folder: null,
                current_source: null
            };

            this.storageKey = 'Blocks_Assets_' + this.options.namespace;

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
            // -------------------------------------------Lo

            this.uploader = new qqUploader.FileUploader({
                element:      this.$upload[0],
                action:       Blocks.actionUrl + 'assets/uploadFile',
                template:     '<div class="assets-qq-uploader">'
                    +   '<div class="assets-qq-upload-drop-area"></div>'
                    +   '<a href="" class="btn submit assets-qq-upload-button" data-icon="↑" style="position: relative; overflow: hidden; direction: ltr; ">' + Blocks.t('Upload files') + '</a>'
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

            // Load the folder
            this.reloadFolderView();


            this.uploader.setParams({
                folder_id: this.getCurrentFolderId()
            });
        },

        reloadFolderView: function () {
            this.loadFolderView(this.getCurrentFolderId());
        },

        /**
         * Load the folder view
         */
        loadFolderView: function (folderId) {

            this.$spinner.show();

            var params = {
                request_id: ++this.requestId,
                folder_id: folderId,
                view_type: this.currentState.view
            };

            Blocks.postActionRequest('assets/viewFolder', params, $.proxy(function(data, textStatus) {
                if (data.request_id != this.requestId) {
                    return;
                }
                this.$folderContainer.attr('data', folderId);
                this.$folderContainer.html(data.html);

                this._setUploadFolder(folderId);

                this.$spinner.hide();

            }, this));
        },

        /**
         * Gets current folder id - if none is set in the current state, then grabs it from the $folderContainer attribute
         * @return mixed
         */
        getCurrentFolderId: function () {
            // TODO: we should remember the state here, but for that we have to make multiple sources work for navigation first
            //if (this.currentState.current_folder == null || typeof this.currentState.current_folder == "udefined") {
                this.storeState('current_folder', this.$folderContainer.attr('data-folder'));
            //}
            return this.currentState.current_folder;
        },

        /**
         * Set the upload folder parameter for uploader
         * @param folderId
         * @private
         */
        _setUploadFolder: function (folderId) {
            this.uploader.setParams({folder_id: folderId});
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

    });


    Assets.FileManager.defaultOptions = {
        mode:          'full',
        multiSelect:   true,
        kinds:         'any',
        disabledFiles: [],
        namespace:     'panel'
    };

})(jQuery);
