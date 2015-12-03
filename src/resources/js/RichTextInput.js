(function($){


/**
 * Rich Text input class
 */
Craft.RichTextInput = Garnish.Base.extend(
{
	id: null,
	entrySources: null,
	categorySources: null,
	assetSources: null,
	elementLocale: null,
	redactorConfig: null,

	$textarea: null,
	redactor: null,

	init: function(id, entrySources, categorySources, assetSources, elementLocale, direction, redactorConfig, redactorLang)
	{
		this.id = id;
		this.entrySources = entrySources;
		this.categorySources = categorySources;
		this.assetSources = assetSources;
		this.elementLocale = elementLocale;
		this.redactorConfig = redactorConfig;

		if (!this.redactorConfig.lang)
		{
			this.redactorConfig.lang = redactorLang;
		}

		if (!this.redactorConfig.direction)
		{
			this.redactorConfig.direction = direction;
		}

		this.redactorConfig.imageUpload = true;
		this.redactorConfig.fileUpload = true;

		// Prevent a JS error when calling core.destroy() when opts.plugins == false
		if (typeof this.redactorConfig.plugins !== typeof [])
		{
			this.redactorConfig.plugins = [];
		}

		var callbacks = {
			init: Craft.RichTextInput.handleRedactorInit
		};

		if (typeof this.redactorConfig.callbacks == typeof [])
		{
			// Merge them together
			for (var i in callbacks)
			{
				if (typeof this.redactorConfig.callbacks[i] != typeof undefined)
				{
					this.redactorConfig.callbacks[i] = this.mergeCallbacks(callbacks[i], this.redactorConfig.callbacks[i]);
				}
			}
		}
		else
		{
			this.redactorConfig.callbacks = callbacks;
		}

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

	mergeCallbacks: function(callback1, callback2)
	{
		return function() {
			callback1.apply(this, arguments);
			callback2.apply(this, arguments);
		}
	},

	initRedactor: function()
	{
		Craft.RichTextInput.currentInstance = this;
		this.$textarea.redactor(this.redactorConfig);
		delete Craft.RichTextInput.currentInstance;
	},

	onInitRedactor: function(redactor)
	{
		this.redactor = redactor;

		// Only customize the toolbar if there is one,
		// otherwise we get a JS error due to redactor.$toolbar being undefined
		if (this.redactor.opts.toolbar)
		{
			this.customizeToolbar();
		}

		this.leaveFullscreetOnSaveShortcut();

		this.redactor.core.editor()
			.on('focus', $.proxy(this, 'onEditorFocus'))
			.on('blur', $.proxy(this, 'onEditorBlur'));
	},

	customizeToolbar: function()
	{
		// Override the Image and File buttons?
		if (this.assetSources.length)
		{
			var $imageBtn = this.replaceRedactorButton('image', this.redactor.lang.get('image')),
				$fileBtn = this.replaceRedactorButton('file', this.redactor.lang.get('file'));

			if ($imageBtn)
			{
				this.redactor.button.addCallback($imageBtn, $.proxy(this, 'onImageButtonClick'));
			}

			if ($fileBtn)
			{
				this.redactor.button.addCallback($fileBtn, $.proxy(this, 'onFileButtonClick'));
			}
		}
		else
		{
			// Image and File buttons aren't supported
			this.redactor.button.remove('image');
			this.redactor.button.remove('file');
		}

		// Override the Link button?
		if (this.entrySources.length || this.categorySources.length)
		{
			var $linkBtn = this.replaceRedactorButton('link', this.redactor.lang.get('link'));

			if ($linkBtn)
			{
				var dropdownOptions = {};

				if (this.entrySources.length)
				{
					dropdownOptions.link_entry = {
						title: Craft.t('Link to an entry'),
						func: $.proxy(this, 'onLinkToEntryButtonClick')
					};
				};

				if (this.categorySources.length)
				{
					dropdownOptions.link_category = {
						title: Craft.t('Link to a category'),
						func: $.proxy(this, 'onLinkToCategoryButtonClick')
					};
				}

				// Add the default Link options
				$.extend(dropdownOptions, {
					link:
					{
						title: this.redactor.lang.get('link-insert'),
						func: 'link.show',
						observe: {
							element: 'a',
							in: {
								title: this.redactor.lang.get('link-edit'),
							},
							out: {
								title: this.redactor.lang.get('link-insert')
							}
						}
					},
					unlink:
					{
						title: this.redactor.lang.get('unlink'),
						func: 'link.unlink',
						observe: {
							element: 'a',
							out: {
								attr: {
									'class': 'redactor-dropdown-link-inactive',
									'aria-disabled': true
								}
							}
						}
					}
				});

				this.redactor.button.addDropdown($linkBtn, dropdownOptions);
			}
		}
	},

	onImageButtonClick: function()
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
	},

	onFileButtonClick: function()
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
							selection = this.redactor.selection.text(),
							title     = selection.length > 0 ? selection : asset.label;
						this.redactor.insert.node($('<a href="'+url+'">'+title+'</a>')[0]);
						this.redactor.code.sync();
					}
				}, this),
				closeOtherModals: false,
				canSelectImageTransforms: true
			});
		}
		else
		{
			this.assetLinkSelectionModal.show();
		}
	},

	onLinkToEntryButtonClick: function()
	{
		this.redactor.selection.save();

		if (typeof this.entrySelectionModal == 'undefined')
		{
			this.entrySelectionModal = Craft.createElementSelectorModal('Entry', {
				storageKey: 'RichTextFieldType.LinkToEntry',
				sources: this.entrySources,
				criteria: { locale: this.elementLocale },
				onSelect: $.proxy(function(entries)
				{
					if (entries.length)
					{
						this.redactor.selection.restore();
						var entry     = entries[0],
							url       = entry.url+'#entry:'+entry.id,
							selection = this.redactor.selection.text(),
							title = selection.length > 0 ? selection : entry.label;
						this.redactor.insert.node($('<a href="'+url+'">'+title+'</a>')[0]);
						this.redactor.code.sync();
					}
				}, this),
				closeOtherModals: false
			});
		}
		else
		{
			this.entrySelectionModal.show();
		}
	},

	onLinkToCategoryButtonClick: function()
	{
		this.redactor.selection.save();

		if (typeof this.categorySelectionModal == 'undefined')
		{
			this.categorySelectionModal = Craft.createElementSelectorModal('Category', {
				storageKey: 'RichTextFieldType.LinkToCategory',
				sources: this.categorySources,
				criteria: { locale: this.elementLocale },
				onSelect: $.proxy(function(categories)
				{
					if (categories.length)
					{
						this.redactor.selection.restore();
						var category  = categories[0],
							url       = category.url+'#category:'+category.id,
							selection = this.redactor.selection.text(),
							title = selection.length > 0 ? selection : category.label;
						this.redactor.insert.node($('<a href="'+url+'">'+title+'</a>')[0]);
						this.redactor.code.sync();
					}
				}, this),
				closeOtherModals: false
			});
		}
		else
		{
			this.categorySelectionModal.show();
		}
	},

	onEditorFocus: function()
	{
		this.redactor.core.box().addClass('focus');
	},

	onEditorBlur: function()
	{
		this.redactor.core.box().removeClass('focus');
	},

	leaveFullscreetOnSaveShortcut: function()
	{
		if (typeof this.redactor.fullscreen != 'undefined' && typeof this.redactor.fullscreen.disable == 'function')
		{
			Craft.cp.on('beforeSaveShortcut', $.proxy(function()
			{
				if (this.redactor.fullscreen.isOpen)
				{
					this.redactor.fullscreen.disable();
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
},
{
	handleRedactorInit: function()
	{
		// `this` is the current Redactor instance.
		// `Craft.RichTextInput.currentInstance` is the current RichTextInput instance
		Craft.RichTextInput.currentInstance.onInitRedactor(this);
	}
});


})(jQuery);
