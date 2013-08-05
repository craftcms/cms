var config = {{config|raw}};
var targetSelector = '.redactor-{{handle}}';

// Replace the image and link dropdowns with slight modifications.
if (typeof config.buttonsCustom == "undefined")
{
  config.buttonsCustom = {};
}

config.buttonsCustom.image = {
	title: Craft.t('Insert image'),
	dropdown:
	{
		from_web: {
			title: Craft.t('Insert URL'),
			callback: function () { this.imageShow();}
		},
		from_assets: {
			title: Craft.t('Choose image'),
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
	title: Craft.t('Link'),
	dropdown: {
		link_entry:
		{
			title: Craft.t('Link to an entry'),
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
		link_asset:
		{
			title: Craft.t('Link to an asset'),
			callback: function () {

				this.selectionSave();

				var editor = this;
				if (typeof this.assetLinkSelectionModal == 'undefined')
				{
					this.assetLinkSelectionModal = Craft.createElementSelectorModal('Asset', {
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
					this.assetLinkSelectionModal.shiftModalToEnd();
					this.assetLinkSelectionModal.show();
				}
			}
		},
		link:
		{
			title: Craft.t('Insert link'),
			callback: function () { this.linkShow();}
		},
		unlink:
		{
			title: Craft.t('Remove link'),
			callback: function () { this.exec('unlink');}
		}
	}
}

config.fullscreenAppend = true;

$(targetSelector).redactor(config);