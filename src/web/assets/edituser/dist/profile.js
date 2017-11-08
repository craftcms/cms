(function($) {
    /** global: Craft */
    /** global: Garnish */
    var settings = {
        postParameters: {userId: $('.user-photo').attr('data-user')},
        containerSelector: '.user-photo',
        uploadAction: 'users/upload-user-photo',
        deleteAction: 'users/delete-user-photo',
        uploadButtonSelector: '.btn.upload-photo',
        deleteButtonSelector: '.btn.delete-photo',
        fileInputSelector: 'input[name=photo]',
        uploadParamName: 'photo',

        onAfterRefreshImage: function(response) {
            if (typeof response.html !== 'undefined') {
                if (typeof changeSidebarPicture !== 'undefined' && changeSidebarPicture) {
                    $('#user-photo').find('> img').replaceWith($('#current-photo').find('> img').clone());
                }
            }
        }
    };

    new Craft.ImageUpload(settings);

    var settings = {
        allowSavingAsNew: false,
        onSave: function() {
            // So not optimal.
            location.reload();
        },
        allowDegreeFractions: Craft.isImagick
    };

    $('#main').on('click', '.btn.edit-photo', function(ev) {
        new Craft.AssetImageEditor($(ev.currentTarget).data('photoid'), settings);
    });
})(jQuery);
