(function($) {

	function refreshImage($target, response) {
		if (typeof response.html != "undefined") {
			// Destroy the old ImageUpload object
			$target.parent().data('imageUpload').destroy();
			$target.parent().data('imageUpload', null);

			// Update the input HTML
			$target.replaceWith(response.html);

			// Initialize a new ImageUpload object
			initImageUpload();
		}
	};

	function initImageUpload()
	{
		var $images = $('.cp-image');

		for (var i = 0; i < $images.length; i++)
		{
			var $element = $images.eq(i),
				imageType = $element.data('type'),
				$htmlTarget = $element.find('.cp-image-wrapper');

			var settings = {
				modalClass: "cp-image-modal",

				uploadAction: 'rebrand/uploadSiteImage',

				deleteMessage: Craft.t('Are you sure you want to delete the uploaded image?'),
				deleteAction: 'rebrand/deleteSiteImage',

				cropAction: 'rebrand/cropSiteImage',

				areaToolOptions: {
					aspectRatio: "",
					initialRectangle: {
						mode: "auto"
					}
				}
			};

			settings.onImageSave = $.proxy(function(response)
			{
				refreshImage($(this), response);
			}, $htmlTarget);

			settings.onImageDelete = $.proxy(function(response, reference)
			{
				refreshImage($(this), response);
			}, $htmlTarget);

			settings.uploadButton = $element.find('.upload');
			settings.deleteButton = $element.find('.delete');
			settings.postParameters = {type: imageType};

			$element.data('imageUpload', new Craft.ImageUpload(settings));
		}
	}

	initImageUpload();

})(jQuery);
