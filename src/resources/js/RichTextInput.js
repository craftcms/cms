(function($){


/**
 * Rich Text input class
 */
Craft.RichTextInput = Garnish.Base.extend(
{
	id: null,
	redactor: null,

	init: function(id, sectionSources, elementLocale, redactorConfig, redactorLang)
	{
		this.id = id;

		redactorConfig.lang = redactorLang;
		redactorConfig.direction = Craft.orientation;

		// Initialize Redactor
		var $textarea = $('#'+this.id);
		$textarea.redactor(redactorConfig);
		this.redactor = $textarea.data('redactor');

		this.replaceRedactorButton('image', Craft.t('Insert image'), null,
		{
			from_web:
			{
				title: Craft.t('Insert URL'),
				func: 'imageShow'
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
							criteria: { locale: elementLocale, kind: 'image' },
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
		});

		this.replaceRedactorButton('link', Craft.t('Link'), null,
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
							criteria: { locale: elementLocale },
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
							criteria: { locale: elementLocale },
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
		});

		if (typeof this.redactor.fullscreen != 'undefined' && typeof this.redactor.toggleFullscreen == 'function')
		{
			Craft.cp.on('beforeSaveShortcut', function()
			{
				if (this.redactor.fullscreen)
				{
					this.redactor.toggleFullscreen();
				}
			});
		}

		if (typeof Craft.livePreview != 'undefined')
		{
			// There's a UI glitch if Redactor is in Code view when Live Preview is shown/hidden
			Craft.livePreview.on('beforeShowPreviewMode beforeHidePreviewMode', function()
			{
				if (!this.redactor.opts.visual)
				{
					this.redactor.toggleVisual();
				}
			})
		}
	},

	replaceRedactorButton: function(key, title, callback, dropdown)
	{
		// Ignore if the button isn't in use
		if (!this.redactor.buttonGet(key).length)
		{
			return;
		}

		// Create a placeholder button
		var placeholderKey = key+'_placeholder';
		this.redactor.buttonAddAfter(key, placeholderKey);

		// Remove the original
		this.redactor.buttonRemove(key);

		// Add the new one
		this.redactor.buttonAddAfter(placeholderKey, key, title, callback, dropdown);

		// Remove the placeholder
		this.redactor.buttonRemove(placeholderKey);
	}
});


})(jQuery);
