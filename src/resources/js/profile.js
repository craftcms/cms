(function($) {
	var settings = {
		postParameters: {userId: $('.user-photo').attr('data-user')},
		containerSelector: '.user-photo',
		uploadAction: 'users/upload-user-photo',
		deleteAction: 'users/delete-user-photo',
		uploadButtonSelector: '.btn.upload-photo',
		deleteButtonSelector: '.btn.delete-photo',
		fileInputSelector: 'input[name=photo]',

		onAfterRefreshImage: function (response) {
			if (typeof response.html != "undefined") {
				if (typeof changeSidebarPicture != "undefined" && changeSidebarPicture) {
					$('#user-photo').find('> img').replaceWith($('#current-photo').find('> img').clone());
				}
			}

		}
	};

	new Craft.ImageUpload(settings);
})(jQuery);
