(function($) {

	function refreshImage($target, response)
	{
		if (typeof response.html != "undefined")
		{
			$html = $(response.html);

			// Switch out the old image uploader and HTML with the new stuff.
			unsetImageUpload($target);
			$target.replaceWith($html);
			initImageUpload($html);
		}
	}

	function initImageUpload($target)
	{
			var imageType = $target.data('type'),
				settings = {
				modalClass: "cp-image-modal",
				uploadAction: 'rebrand/uploadSiteImage',

				deleteMessage: Craft.t('Are you sure you want to delete the uploaded image?'),
				deleteAction: 'rebrand/deleteSiteImage',

				cropAction: 'rebrand/cropSiteImage',

				constraint: 300,

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
			}, $target);

			settings.onImageDelete = $.proxy(function(response)
			{
				refreshImage($(this), response);
			}, $target);

			settings.uploadButton = $target.find('.upload');
			settings.deleteButton = $target.find('.delete');
			settings.postParameters = {type: imageType};

		$target.data('imageUpload', new Craft.ImageUpload(settings));
	}

	function unsetImageUpload($target)
	{
		if (typeof $target.data('imageUpload') == "object") {
			// Destroy the old ImageUpload object
			$target.data('imageUpload').destroy();
			$target.data('imageUpload', null);
		}
	}

	var $images = $('.cp-image');

	for (var i = 0; i < $images.length; i++)
	{
		initImageUpload($images.eq(i));
	}

})(jQuery);
