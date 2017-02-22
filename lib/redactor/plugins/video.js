(function($)
{
	$.Redactor.prototype.video = function()
	{
		return {
			reUrlYoutube: /https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com\S*[^\w\-\s])([\w\-]{11})(?=[^\w\-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))[?=&+%\w.-]*/ig,
			reUrlVimeo: /https?:\/\/(www\.)?vimeo.com\/(\d+)($|\/)/,
			langs: {
				en: {
					"video": "Video",
					"video-html-code": "Video Embed Code or Youtube/Vimeo Link"
				}
			},
			getTemplate: function()
			{
				return String()
				+ '<div class="modal-section" id="redactor-modal-video-insert">'
					+ '<section>'
						+ '<label>' + this.lang.get('video-html-code') + '</label>'
						+ '<textarea id="redactor-insert-video-area" style="height: 160px;"></textarea>'
					+ '</section>'
					+ '<section>'
						+ '<button id="redactor-modal-button-action">Insert</button>'
						+ '<button id="redactor-modal-button-cancel">Cancel</button>'
					+ '</section>'
				+ '</div>';
			},
			init: function()
			{
				var button = this.button.addAfter('image', 'video', this.lang.get('video'));
				this.button.setIcon(button, '<i class="re-icon-video"></i>');
				this.button.addCallback(button, this.video.show);
			},
			show: function()
			{
				this.modal.addTemplate('video', this.video.getTemplate());

				this.modal.load('video', this.lang.get('video'), 700);

				// action button
				this.modal.getActionButton().text(this.lang.get('insert')).on('click', this.video.insert);
				this.modal.show();

				// focus
				if (this.detect.isDesktop())
				{
					setTimeout(function()
					{
						$('#redactor-insert-video-area').focus();

					}, 1);
				}


			},
			insert: function()
			{
				var data = $('#redactor-insert-video-area').val();

				if (!data.match(/<iframe|<video/gi))
				{
					data = this.clean.stripTags(data);

					this.opts.videoContainerClass = (typeof this.opts.videoContainerClass === 'undefined') ? 'video-container' : this.opts.videoContainerClass;

					// parse if it is link on youtube & vimeo
					var iframeStart = '<div class="' + this.opts.videoContainerClass + '"><iframe style="width: 500px; height: 281px;" src="',
						iframeEnd = '" frameborder="0" allowfullscreen></iframe></div>';

					if (data.match(this.video.reUrlYoutube))
					{
						data = data.replace(this.video.reUrlYoutube, iframeStart + '//www.youtube.com/embed/$1' + iframeEnd);
					}
					else if (data.match(this.video.reUrlVimeo))
					{
						data = data.replace(this.video.reUrlVimeo, iframeStart + '//player.vimeo.com/video/$2' + iframeEnd);
					}
				}

				this.modal.close();
				this.placeholder.hide();

				// buffer
				this.buffer.set();

				// insert
				this.air.collapsed();
				this.insert.html(data);

			}

		};
	};
})(jQuery);