/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend({

    uploader: null,

    init: function($element, settings)
    {

        settings = $.extend(this.defaultSettings, settings);
        settings.button = $element[0];
        this.uploader = new qqUploader.FileUploaderBasic(settings);
    },

    /**
     * Set uploader parameters
     * @param paramObject
     */
    setParams: function (paramObject)
    {
        this.uploader.setParams(paramObject);
    },

    /**
     * Get the number of uploads in progress
     * @returns {*}
     */
    getInProgress: function ()
    {
        return this.uploader.getInProgress();
    },

    defaultSettings: {
        action:       Craft.actionUrl + '/assets/uploadFile',
        template:     '<div class="assets-qq-uploader">'
            +   '<a href="javascript:;" class="btn submit assets-qq-upload-button" data-icon="↑" style="position: relative; overflow: hidden; direction: ltr; " role="button">' + Craft.t('Upload files') + '</a>'
            +   '</div>',

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

        onSubmit:     $.noop,
        onProgress:   $.noop,
        onComplete:   $.noop
    }
});
