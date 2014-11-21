(function($){


/**
 * Rich Text input class
 */
Craft.RichTextInput = Garnish.Base.extend(
{
	id: null,
	sectionSources: null,
	elementLocale: null,
	redactorConfig: null,

	$textarea: null,
	redactor: null,

	init: function(id, sectionSources, elementLocale, redactorConfig, redactorLang)
	{
		this.id = id;
		this.sectionSources = sectionSources;
		this.elementLocale = elementLocale;
		this.redactorConfig = redactorConfig;

		this.redactorConfig.lang = redactorLang;
		this.redactorConfig.direction = Craft.orientation;
		this.redactorConfig.buttonSource = true;
		this.redactorConfig.imageUpload = true;

		var that = this,
			originalInitCallback = redactorConfig.initCallback;

		this.redactorConfig.initCallback = function(ev, data)
		{
			that.redactor = this;
			that.onRedactorInit();

			// Did the config have its own callback?
			if ($.isFunction(originalInitCallback))
			{
				return originalInitCallback.call(this, ev, data);
			}
			else
			{
				return data;
			}
		};

		// Initialize Redactor
		this.$textarea = $('#'+this.id);

		this.initRedactor();

		if (typeof Craft.livePreview != 'undefined')
		{
			// There's a UI glitch if Redactor is in Code view when Live Preview is shown/hidden
			Craft.livePreview.on('beforeEnter beforeExit', $.proxy(function()
			{
				this.redactor.core.destroy();
			}, this));

			Craft.livePreview.on('enter slideOut', $.proxy(function()
			{
				this.initRedactor();
			}, this));
		}
	},

	initRedactor: function()
	{
		this.$textarea.redactor(this.redactorConfig);
		this.redactor = this.$textarea.data('redactor');
	},

	onRedactorInit: function()
	{
		var $imageBtn = this.replaceRedactorButton('image', Craft.t('Insert image')),
			$linkBtn = this.replaceRedactorButton('link', Craft.t('Link'));

		if ($imageBtn)
		{
			this.redactor.button.addCallback($imageBtn, $.proxy(function()
			{
				this.redactor.selection.save();

				if (typeof this.assetSelectionModal == 'undefined')
				{
					this.assetSelectionModal = Craft.createElementSelectorModal('Asset', {
						storageKey: 'RichTextFieldType.ChooseImage',
						multiSelect: true,
						criteria: { locale: this.elementLocale, kind: 'image' },
						onSelect: $.proxy(function(assets, transform)
						{
							if (assets.length)
							{
								this.redactor.selection.restore();
								for (var i = 0; i < assets.length; i++)
								{
									var asset = assets[i],
										url   = asset.url+'#asset:'+asset.id;

									if (transform)
									{
										url += ':'+transform;
									}

									this.redactor.insert.node($('<img src="'+url+'" />')[0]);
									this.redactor.code.sync();
								}
								this.redactor.observe.images();
								this.redactor.dropdown.hideAll();
							}
						}, this),
						closeOtherModals: false,
						canSelectImageTransforms: true
					});
				}
				else
				{
					this.assetSelectionModal.show();
				}
			}, this));
		}

		if ($linkBtn)
		{
			this.redactor.button.addDropdown($linkBtn,
			{
				link_entry:
				{
					title: Craft.t('Link to an entry'),
					func: $.proxy(function()
					{
						this.redactor.selection.save();

						if (typeof this.entrySelectionModal == 'undefined')
						{
							this.entrySelectionModal = Craft.createElementSelectorModal('Entry', {
								storageKey: 'RichTextFieldType.LinkToEntry',
								sources: this.sectionSources,
								criteria: { locale: this.elementLocale },
								onSelect: $.proxy(function(entries)
								{
									if (entries.length)
									{
										this.redactor.selection.restore();
										var entry     = entries[0],
											url       = entry.url+'#entry:'+entry.id,
											selection = this.redactor.selection.getText(),
											title = selection.length > 0 ? selection : entry.label;
										this.redactor.insert.node($('<a href="'+url+'">'+title+'</a>')[0]);
										this.redactor.code.sync();
									}
									this.redactor.dropdown.hideAll();
								}, this),
								closeOtherModals: false
							});
						}
						else
						{
							this.entrySelectionModal.show();
						}
					}, this)
				},
				link_asset:
				{
					title: Craft.t('Link to an asset'),
					func: $.proxy(function()
					{
						this.redactor.selection.save();

						if (typeof this.assetLinkSelectionModal == 'undefined')
						{
							this.assetLinkSelectionModal = Craft.createElementSelectorModal('Asset', {
								storageKey: 'RichTextFieldType.LinkToAsset',
								criteria: { locale: this.elementLocale },
								onSelect: $.proxy(function(assets)
								{
									if (assets.length)
									{
										this.redactor.selection.restore();
										var asset     = assets[0],
											url       = asset.url+'#asset:'+asset.id,
											selection = this.redactor.selection.getText(),
											title     = selection.length > 0 ? selection : asset.label;
										this.redactor.insert.node($('<a href="'+url+'">'+title+'</a>')[0]);
										this.redactor.code.sync();
									}
									this.redactor.dropdown.hideAll();
								}, this),
								closeOtherModals: false,
								canSelectImageTransforms: true
							});
						}
						else
						{
							this.assetLinkSelectionModal.show();
						}
					}, this)
				},
				link:
				{
					title: Craft.t('Insert link'),
					func:  'link.show'
				},
				unlink:
				{
					title: Craft.t('Unlink'),
					exec:  'link.unlink'
				}
			});
		}

		if (typeof this.redactor.fullscreen != 'undefined' && typeof this.redactor.toggleFullscreen == 'function')
		{
			Craft.cp.on('beforeSaveShortcut', $.proxy(function()
			{
				if (this.redactor.fullscreen)
				{
					this.redactor.toggleFullscreen();
				}
			}, this));
		}
	},

	replaceRedactorButton: function(key, title)
	{
		// Ignore if the button isn't in use
		if (!this.redactor.button.get(key).length)
		{
			return;
		}

		// Create a placeholder button
		var placeholderKey = key+'_placeholder';
		this.redactor.button.addAfter(key, placeholderKey);

		// Remove the original
		this.redactor.button.remove(key);

		// Add the new one
		var $btn = this.redactor.button.addAfter(placeholderKey, key, title);

		// Set the dropdown
		//this.redactor.button.addDropdown($btn, dropdown);

		// Remove the placeholder
		this.redactor.button.remove(placeholderKey);

		return $btn;
	}
});


})(jQuery);
