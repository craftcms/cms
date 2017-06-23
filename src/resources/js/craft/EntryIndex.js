/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend(
{
	publishableSections: null,
	$newEntryBtnGroup: null,
	$newEntryBtn: null,

	afterInit: function()
	{
		// Find which of the visible sections the user has permission to create new entries in
		this.publishableSections = [];

		for (var i = 0; i < Craft.publishableSections.length; i++)
		{
			var section = Craft.publishableSections[i];

			if (this.getSourceByKey('section:'+section.id))
			{
				this.publishableSections.push(section);
			}
		}

		this.base();
	},

	getDefaultSourceKey: function()
	{
		// Did they request a specific section in the URL?
		if (this.settings.context == 'index' && typeof defaultSectionHandle != typeof undefined)
		{
			if (defaultSectionHandle == 'singles')
			{
				return 'singles';
			}
			else
			{
				for (var i = 0; i < this.$sources.length; i++)
				{
					var $source = $(this.$sources[i]);

					if ($source.data('handle') == defaultSectionHandle)
					{
						return $source.data('key');
					}
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		var selectedSourceHandle;

		// Get the handle of the selected source
		if (this.$source.data('key') == 'singles')
		{
			selectedSourceHandle = 'singles';
		}
		else
		{
			selectedSourceHandle = this.$source.data('handle');
		}

		// Update the New Entry button
		// ---------------------------------------------------------------------

		if (this.publishableSections.length)
		{
			// Remove the old button, if there is one
			if (this.$newEntryBtnGroup)
			{
				this.$newEntryBtnGroup.remove();
			}

			// Determine if they are viewing a section that they have permission to create entries in
			var selectedSection;

			if (selectedSourceHandle)
			{
				for (var i = 0; i < this.publishableSections.length; i++)
				{
					if (this.publishableSections[i].handle == selectedSourceHandle)
					{
						selectedSection = this.publishableSections[i];
						break;
					}
				}
			}

			this.$newEntryBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
			// Otherwise only show a menu button
			if (selectedSection)
			{
				var href = this._getSectionTriggerHref(selectedSection),
					label = (this.settings.context == 'index' ? Craft.t('New entry') : Craft.t('New {section} entry', {section: selectedSection.name}));
				this.$newEntryBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newEntryBtnGroup);

				if (this.settings.context != 'index')
				{
					this.addListener(this.$newEntryBtn, 'click', function(ev)
					{
						this._openCreateEntryModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.publishableSections.length > 1)
				{
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newEntryBtnGroup);
				}
			}
			else
			{
				this.$newEntryBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New entry')+'</div>').appendTo(this.$newEntryBtnGroup);
			}

			if ($menuBtn)
			{
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.publishableSections.length; i++)
				{
					var section = this.publishableSections[i];

					if (this.settings.context == 'index' || section != selectedSection)
					{
						var href = this._getSectionTriggerHref(section),
							label = (this.settings.context == 'index' ? section.name : Craft.t('New {section} entry', {section: section.name}));
						menuHtml += '<li><a '+href+'">'+Craft.escapeHtml(label)+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newEntryBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index')
				{
					menuBtn.on('optionSelect', $.proxy(function(ev)
					{
						this._openCreateEntryModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newEntryBtnGroup);
		}

		// Update the URL if we're on the Entries index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined)
		{
			var uri = 'entries';

			if (selectedSourceHandle)
			{
				uri += '/'+selectedSourceHandle;
			}

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getSectionTriggerHref: function(section)
	{
		if (this.settings.context == 'index')
		{
			return 'href="'+Craft.getUrl('entries/'+section.handle+'/new')+'"';
		}
		else
		{
			return 'data-id="'+section.id+'"';
		}
	},

	_openCreateEntryModal: function(sectionId)
	{
		if (this.$newEntryBtn.hasClass('loading'))
		{
			return;
		}

		// Find the section
		var section;

		for (var i = 0; i < this.publishableSections.length; i++)
		{
			if (this.publishableSections[i].id == sectionId)
			{
				section = this.publishableSections[i];
				break;
			}
		}

		if (!section)
		{
			return;
		}

		this.$newEntryBtn.addClass('inactive');
		var newEntryBtnText = this.$newEntryBtn.text();
		this.$newEntryBtn.text(Craft.t('New {section} entry', {section: section.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newEntryBtnGroup,
			elementType: 'Entry',
			locale: this.locale,
			attributes: {
				sectionId: sectionId
			},
			onBeginLoading: $.proxy(function()
			{
				this.$newEntryBtn.addClass('loading');
			}, this),
			onEndLoading: $.proxy(function()
			{
				this.$newEntryBtn.removeClass('loading');
			}, this),
			onHideHud: $.proxy(function()
			{
				this.$newEntryBtn.removeClass('inactive').text(newEntryBtnText);
			}, this),
			onSaveElement: $.proxy(function(response)
			{
				// Make sure the right section is selected
				var sectionSourceKey = 'section:'+sectionId;

				if (this.sourceKey != sectionSourceKey)
				{
					this.selectSourceByKey(sectionSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
Craft.registerElementIndexClass('Entry', Craft.EntryIndex);
