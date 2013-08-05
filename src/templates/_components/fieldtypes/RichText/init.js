(function($) {


var config = {{ config|raw }},
	targetSelector = '.redactor-{{ handle }}',
	lang = $.Redactor.opts.langs['{{ lang }}'];

config.lang = '{{ lang }}';

// Replace the image and link dropdowns with slight modifications.
if (typeof config.buttonsCustom == "undefined")
{
	config.buttonsCustom = {};
}

config.buttonsCustom.image = {
	title: "{{ 'Insert image'|t|e('js') }}",
	dropdown:
	{
		from_web: {
			title: "{{ 'Insert URL'|t|e('js') }}",
			callback: function () { this.imageShow();}
		},
		from_assets: {
			title: "{{ 'Choose image'|t|e('js') }}",
			callback: function () {

				this.selectionSave();
                var editor = this;
				if (typeof this.assetSelectionModal == 'undefined')
				{
					this.assetSelectionModal = Craft.createElementSelectorModal('Asset', {
						criteria: { kind: 'image' },
						onSelect: $.proxy(function(elements) {
							if (elements.length)
							{
                                editor.selectionRestore();

								var element = elements[0].$element;
                                editor.insertNode($('<img src="' + element.attr('data-url') + '" />')[0]);

                                editor.sync();
                                editor.dropdownHideAll();
							}
						}, this),
                        closeOtherModals: false
					});
				}
				else
				{
                    this.assetSelectionModal.shiftModalToEnd();
					this.assetSelectionModal.show();
				}
			}
		}
	}
};

config.buttonsCustom.link = {
	title: "{{ 'Link'|t|e('js') }}",
	dropdown: {
		link_entry:
		{
			title: "{{ 'Link to an entry'|t|e('js') }}",
			callback: function () {

				this.selectionSave();

                var editor = this;
				if (typeof this.entrySelectionModal == 'undefined')
				{
					this.entrySelectionModal = Craft.createElementSelectorModal('Entry', {
						sources: {{sections|raw}},
						onSelect: function(elements) {
							if (elements.length)
							{
                                editor.selectionRestore();
                                var element = elements[0];
                                var selection = editor.getSelectionText();
                                var title = selection.length > 0 ? selection : element.label;
                                editor.insertNode($('<a href="' + element.$element.attr('data-url') + '">' + title + '</a>')[0]);
                                editor.sync();
                            }
                            editor.dropdownHideAll();
						},
                        closeOtherModals: false
					});
				}
				else
				{
                    this.entrySelectionModal.shiftModalToEnd();
					this.entrySelectionModal.show();
				}
			}
		},
		link:
		{
			title: lang.link_insert,
			func:  'linkShow'
		},
		unlink:
		{
			title: lang.unlink,
			exec:  'unlink'
		}
	}
}

config.fullscreenAppend = true;

$(targetSelector).redactor(config);


})(jQuery);
