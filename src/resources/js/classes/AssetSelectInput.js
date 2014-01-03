/**
 * Element Select input
 */
window.xxx = {};
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend({

    requestId: 0,
    hud: null,
	fieldId: 0,

	init: function(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey, fieldId)
	{
		this.base(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey);
		this.fieldId = fieldId;
		this._attachHUDEvents();
		this._attachDragEvents();
	},

    selectElements: function (elements)
    {
        this.base(elements);
        this._attachHUDEvents();
    },

	onHide: function ()
	{
		console.log('canceling, sir');
	},

    _attachHUDEvents: function ()
    {
        this.removeListener(this.$elements, 'dlbclick');
        this.addListener(this.$elements, 'dblclick', $.proxy(this, '_editProperties'));
    },

    _editProperties: function (event)
    {
        var $target = $(event.currentTarget);
        if (!$target.data('ElementEditor'))
        {
            var settings = {
                elementId: $target.attr('data-id'),
                $trigger: $target,
                loadContentAction: 'assets/editFileContent',
                saveContentAction: 'assets/saveFileContent'
            };
            $target.data('ElementEditor', new Craft.ElementEditor(settings));
        }

        $target.data('ElementEditor').show();
    },

	_attachDragEvents: function ()
	{
		var elem = this.$container;

		var progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(elem));
		progressBar.$progressBar.css({
			top: Math.round(elem.outerHeight() / 2) - 6
		});

		$(document).bind('drop dragover', function (e) {
			e.preventDefault();
		});

		var options = {
			url: Craft.getActionUrl('assets/expressUpload'),
			dropZone: elem,
			pasteZone: null,
			fileInput: null,
			formData: {
				fieldId: this.fieldId,
				entryId: $('input[name=entryId]').val()
			},
			autoUpload: true
		};

		elem.fileupload(options)
			.bind('fileuploadstart', function (event)
			{
				elem.addClass('uploading');
				progressBar.resetProgressBar();
				progressBar.showProgressBar();
			})
			.bind('fileuploadprogressall', function (event, data)
			{
				var progress = parseInt(data.loaded / data.total * 100, 10);
				progressBar.setProgressPercentage(progress);
			})
			.bind('fileuploaddone', function (event, data)
			{

				// Last file
				if ($(this).fileupload('active') == 1)
				{
					progressBar.hideProgressBar();
					elem.removeClass('uploading');
				}
			});

	}
});
