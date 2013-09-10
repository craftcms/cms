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
						multiSelect: true,
						criteria: { kind: 'image' },
						onSelect: $.proxy(function(assets) {
							if (assets.length)
							{
                                editor.selectionRestore();
								for (var i = 0; i < assets.length; i++)
								{
									var asset = assets[i],
										url   = asset.url+'#asset:'+asset.id;
									editor.insertNode($('<img src="'+url+'" />')[0]);
									editor.sync();
								}

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
						onSelect: function(entries) {
							if (entries.length)
							{
                                editor.selectionRestore();
                                var entry     = entries[0],
                                	url       = entry.url+'#entry:'+entry.id,
                                	selection = editor.getSelectionText(),
                                	title = selection.length > 0 ? selection : entry.label;
                                editor.insertNode($('<a href="'+url+'">'+title+'</a>')[0]);
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
		link_asset:
		{
			title: Craft.t('Link to an asset'),
			callback: function () {

				this.selectionSave();

				var editor = this;
				if (typeof this.assetLinkSelectionModal == 'undefined')
				{
					this.assetLinkSelectionModal = Craft.createElementSelectorModal('Asset', {
						onSelect: function(assets) {
							if (assets.length)
							{
								editor.selectionRestore();
								var asset     = assets[0],
									url       = asset.url+'#asset:'+asset.id,
									selection = editor.getSelectionText(),
									title     = selection.length > 0 ? selection : asset.label;
								editor.insertNode($('<a href="'+url+'">'+title+'</a>')[0]);
								editor.sync();
							}
							editor.dropdownHideAll();
						},
						closeOtherModals: false
					});
				}
				else
				{
					this.assetLinkSelectionModal.shiftModalToEnd();
					this.assetLinkSelectionModal.show();
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
