(function($) {

    var settings = {
        postParameters: {userId: $('.user-photo').attr('data-user')},

        modalClass: "profile-image-modal",
        uploadButton: $('.user-photo-controls .upload-photo'),
        uploadAction: 'users/uploadUserPhoto',

        deleteButton: $('.user-photo-controls .delete-photo'),
        deleteMessage: Blocks.t('Are you sure you want to delete this photo?'),
        deleteAction: 'users/deleteUserPhoto',

        cropAction: 'users/cropUserPhoto',

        areaToolOptions:
        {
            aspectRatio: "1:1",
            initialRectangle: {
                mode: "auto"
            }
        }
    };

    new Blocks.ui.ImageUpload(settings);
})(jQuery);
