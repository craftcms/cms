(function($){


/**
 * Rich Text input class
 */
Craft.RichTextInput = Garnish.Base.extend({

	id: null,

	init: function(id, lang, sectionSources, config)
	{
		this.id = id;

		config.lang = lang;

		// Replace the image and link menu with slight modifications.
		if (typeof config.buttonsCustom == 'undefined')
		{
			config.buttonsCustom = {};
		}

		config.buttonsCustom.image =
		{
			title: Craft.t('Insert image'),
			dropdown:
			{
				from_web:
				{
					title: Craft.t('Insert URL'),
					callback: function()
					{
						this.imageShow();
					}
				},
				from_assets:
				{
					title: Craft.t('Choose image'),
					callback: function()
					{
						this.selectionSave();
		                var editor = this;
						if (typeof this.assetSelectionModal == 'undefined')
						{
							this.assetSelectionModal = Craft.createElementSelectorModal('Asset', {
								storageKey: 'RichTextFieldType.ChooseImage',
								multiSelect: true,
								criteria: { kind: 'image' },
								onSelect: $.proxy(function(assets, transform)
								{
									if (assets.length)
									{
		                                editor.selectionRestore();
										for (var i = 0; i < assets.length; i++)
										{
											var asset = assets[i],
												url   = asset.url+'#asset:'+asset.id;

											if (transform)
											{
												url += ':'+transform;
											}

											editor.insertNode($('<img src="'+url+'" />')[0]);
											editor.sync();
										}
										this.observeImages();
										editor.dropdownHideAll();
									}
								}, this),
								closeOtherModals: false,
								canSelectImageTransforms: true
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

		config.buttonsCustom.link =
		{
			title: Craft.t('Link'),
			dropdown:
			{
				link_entry:
				{
					title: Craft.t('Link to an entry'),
					callback: function()
					{
						this.selectionSave();

		                var editor = this;
						if (typeof this.entrySelectionModal == 'undefined')
						{
							this.entrySelectionModal = Craft.createElementSelectorModal('Entry', {
								storageKey: 'RichTextFieldType.LinkToEntry',
								sources: sectionSources,
								onSelect: function(entries)
								{
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
					callback: function()
					{
						this.selectionSave();

						var editor = this;
						if (typeof this.assetLinkSelectionModal == 'undefined')
						{
							this.assetLinkSelectionModal = Craft.createElementSelectorModal('Asset', {
								storageKey: 'RichTextFieldType.LinkToAsset',
								onSelect: function(assets)
								{
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
								closeOtherModals: false,
								canSelectImageTransforms: true
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
					func:  'linkShow'
				},
				unlink:
				{
					title: Craft.t('Unlink'),
					exec:  'unlink'
				}
			}
		}

		// Initialize Redactor
		var $textarea = $('#'+this.id);
		$textarea.redactor(config);
		var redactor = $textarea.data('redactor');

		if (typeof redactor.fullscreen != 'undefined' && typeof redactor.toggleFullscreen == 'function')
		{
			Craft.cp.on('beforeSaveShortcut', function()
			{
				if (redactor.fullscreen)
				{
					redactor.toggleFullscreen();
				}
			});
		}
	}
});


})(jQuery);
