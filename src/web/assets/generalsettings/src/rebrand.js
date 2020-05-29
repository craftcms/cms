(function($) {
    /** global: Craft */
    /** global: Garnish */
    var settings = {
        uploadAction: 'rebrand/upload-site-image',
        deleteAction: 'rebrand/delete-site-image',
        uploadButtonSelector: '.btn.upload',
        deleteButtonSelector: '.btn.delete',
        fileInputSelector: 'input[name=image]',
        uploadParamName: 'image'
    };

    var logoSettings = $.extend({}, settings, {
        postParameters: {type: 'logo'},
        containerSelector: '.cp-image-logo'
    });

    var iconSettings = $.extend({}, settings, {
        postParameters: {type: 'icon'},
        containerSelector: '.cp-image-icon',

        onAfterRefreshImage: function(response) {
            if (typeof response.html !== 'undefined') {
                $('#site-icon').find('> img').attr('src', ($('.cp-image-icon .cp-current-image').data('url')));
            }
        }
    });

    new Craft.ImageUpload(iconSettings);
    new Craft.ImageUpload(logoSettings);
})(jQuery);
