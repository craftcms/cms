/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend(
{
	editableGroups: null,
	$newCategoryBtnGroup: null,
	$newCategoryBtn: null,

	afterInit: function()
	{
		// Find which of the visible groups the user has permission to create new categories in
		this.editableGroups = [];

		for (var i = 0; i < Craft.editableCategoryGroups.length; i++)
		{
			var group = Craft.editableCategoryGroups[i];

			if (this.getSourceByKey('group:'+group.id))
			{
				this.editableGroups.push(group);
			}
		}

		this.base();
	},

	getDefaultSourceKey: function()
	{
		// Did they request a specific category group in the URL?
		if (this.settings.context == 'index' && typeof defaultGroupHandle != typeof undefined)
		{
			for (var i = 0; i < this.$sources.length; i++)
			{
				var $source = $(this.$sources[i]);

				if ($source.data('handle') == defaultGroupHandle)
				{
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		// Get the handle of the selected source
		var selectedSourceHandle = this.$source.data('handle');

		// Update the New Category button
		// ---------------------------------------------------------------------

		if (this.editableGroups.length)
		{
			// Remove the old button, if there is one
			if (this.$newCategoryBtnGroup)
			{
				this.$newCategoryBtnGroup.remove();
			}

			// Determine if they are viewing a group that they have permission to create categories in
			var selectedGroup;

			if (selectedSourceHandle)
			{
				for (var i = 0; i < this.editableGroups.length; i++)
				{
					if (this.editableGroups[i].handle == selectedSourceHandle)
					{
						selectedGroup = this.editableGroups[i];
						break;
					}
				}
			}

			this.$newCategoryBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
			// Otherwise only show a menu button
			if (selectedGroup)
			{
				var href = this._getGroupTriggerHref(selectedGroup),
					label = (this.settings.context == 'index' ? Craft.t('New category') : Craft.t('New {group} category', {group: selectedGroup.name}));
				this.$newCategoryBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newCategoryBtnGroup);

				if (this.settings.context != 'index')
				{
					this.addListener(this.$newCategoryBtn, 'click', function(ev)
					{
						this._openCreateCategoryModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.editableGroups.length > 1)
				{
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newCategoryBtnGroup);
				}
			}
			else
			{
				this.$newCategoryBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New category')+'</div>').appendTo(this.$newCategoryBtnGroup);
			}

			if ($menuBtn)
			{
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.editableGroups.length; i++)
				{
					var group = this.editableGroups[i];

					if (this.settings.context == 'index' || group != selectedGroup)
					{
						var href = this._getGroupTriggerHref(group),
							label = (this.settings.context == 'index' ? group.name : Craft.t('New {group} category', {group: group.name}));
						menuHtml += '<li><a '+href+'">'+label+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newCategoryBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index')
				{
					menuBtn.on('optionSelect', $.proxy(function(ev)
					{
						this._openCreateCategoryModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newCategoryBtnGroup);
		}

		// Update the URL if we're on the Categories index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined)
		{
			var uri = 'categories';

			if (selectedSourceHandle)
			{
				uri += '/'+selectedSourceHandle;
			}

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getGroupTriggerHref: function(group)
	{
		if (this.settings.context == 'index')
		{
			return 'href="'+Craft.getUrl('categories/'+group.handle+'/new')+'"';
		}
		else
		{
			return 'data-id="'+group.id+'"';
		}
	},

	_openCreateCategoryModal: function(groupId)
	{
		if (this.$newCategoryBtn.hasClass('loading'))
		{
			return;
		}

		// Find the group
		var group;

		for (var i = 0; i < this.editableGroups.length; i++)
		{
			if (this.editableGroups[i].id == groupId)
			{
				group = this.editableGroups[i];
				break;
			}
		}

		if (!group)
		{
			return;
		}

		this.$newCategoryBtn.addClass('inactive');
		var newCategoryBtnText = this.$newCategoryBtn.text();
		this.$newCategoryBtn.text(Craft.t('New {group} category', {group: group.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newCategoryBtnGroup,
			elementType: 'Category',
			locale: this.locale,
			attributes: {
				groupId: groupId
			},
			onBeginLoading: $.proxy(function()
			{
				this.$newCategoryBtn.addClass('loading');
			}, this),
			onEndLoading: $.proxy(function()
			{
				this.$newCategoryBtn.removeClass('loading');
			}, this),
			onHideHud: $.proxy(function()
			{
				this.$newCategoryBtn.removeClass('inactive').text(newCategoryBtnText);
			}, this),
			onSaveElement: $.proxy(function(response)
			{
				// Make sure the right group is selected
				var groupSourceKey = 'group:'+groupId;

				if (this.sourceKey != groupSourceKey)
				{
					this.selectSourceByKey(groupSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
Craft.registerElementIndexClass('Category', Craft.CategoryIndex);
