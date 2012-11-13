(function($) {

    var settings = {
        modalClass: "logo-modal",
        uploadButton: $('.logo-controls .upload-logo'),
        uploadAction: 'rebrand/uploadLogo',

        deleteButton: $('.logo-controls .delete-logo'),
        deleteMessage: Blocks.t('Are you sure you want to delete the logo?'),
        deleteAction: 'rebrand/deleteLogo',

        cropAction: 'rebrand/cropLogo',

        areaToolOptions:
        {
            aspectRatio: "",
            initialRectangle: {
                mode: "auto"
            }
        }
    };

    new Blocks.ui.ImageUpload(settings);
})(jQuery);
