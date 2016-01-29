(function($) {

	var ImageUpload = null;

	var settings = {
		postParameters: {userId: $('.user-photo').attr('data-user')},

		modalClass: "profile-image-modal",
		uploadAction: 'users/uploadUserPhoto',

		deleteMessage: Craft.t('Are you sure you want to delete this photo?'),
		deleteAction: 'users/deleteUserPhoto',

		cropAction: 'users/cropUserPhoto',

		areaToolOptions:
		{
			aspectRatio: "1",
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
			$('.user-photo').replaceWith(response.html);
			if (typeof changeSidebarPicture != "undefined" && changeSidebarPicture)
			{
				$('#user-photo > img').replaceWith($('#current-photo > img').clone());
			}
			initImageUpload();
		}

	}

	function initImageUpload()
	{
		// These change dynamically after each HTML overwrite, so we can't have them in the initial settings array.
		settings.uploadButton = $('.user-photo-controls .upload-photo');
		settings.deleteButton = $('.user-photo-controls .delete-photo');
		ImageUpload = new Craft.ImageUpload(settings);
	}

	// Only init for existing users.
	if ($('input[name=userId]').val())
	{
		initImageUpload();
	}

})(jQuery);
