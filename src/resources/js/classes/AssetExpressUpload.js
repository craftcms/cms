/*
 * Assets express uploader
 */
Craft.AssetExpressUpload = Garnish.Base.extend({

	uploadAction: 'assets/expressUpload',

	init: function (files, fieldId, folderId){
		$.ajax({
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					myXhr.upload.addEventListener('progress',function(ev) {
						console.log('prgoress');
						if (ev.lengthComputable) {
							var percentUploaded = Math.floor(ev.loaded * 100 / ev.total);
							console.log('Uploaded '+percentUploaded+'%');
							// update UI to reflect percentUploaded
						} else {
							console.log('Uploaded '+ev.loaded+' bytes');
							// update UI to reflect bytes uploaded
						}
					}, false);
				}
				return myXhr;
			},
			type: 'POST',
			url: Craft.getActionUrl(this.uploadAction),
			data: {
				fieldId: fieldId,
				folderId: folderId
			},
			success: function(data){
				console.log(data);
			}
		 });
	}

});
