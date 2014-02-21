(function($) {

	var ImageUpload = null;

	var settings = {
		modalClass: "logo-modal",

		uploadAction: 'rebrand/uploadLogo',

		deleteMessage: Craft.t('Are you sure you want to delete the logo?'),
		deleteAction: 'rebrand/deleteLogo',

		cropAction: 'rebrand/cropLogo',

		areaToolOptions:
		{
			aspectRatio: "",
			initialRectangle: {
				mode: "auto"
			}
		},

		onImageSave: function(response)
		{
			refreshImage(response);
		},

		onImageDelete: function(response)
		{
			refreshImage(response);
		}

	};

	function refreshImage(response) {
		if (typeof response.html != "undefined") {
			$('.cp-logo').replaceWith(response.html);
			initImageUpload();
		}

	}

	function initImageUpload()
	{
		// These change dynamically after each HTML overwrite, so we can't have them in the initial settings array.
		settings.uploadButton = $('.logo-controls .upload-logo');
		settings.deleteButton = $('.logo-controls .delete-logo');
		ImageUpload = new Craft.ImageUpload(settings);
	}

	initImageUpload();

})(jQuery);
