/**
 * Element index class
 */
Craft.BaseElementIndex = Garnish.Base.extend(
{
	// Properties
	// =========================================================================

	initialized: false,
	elementType: null,

	instanceState: null,
	sourceStates: null,
	sourceStatesStorageKey: null,

	searchTimeout: null,
	elementSelect: null,
	sourceSelect: null,
	structureTableSort: null,

	isIndexBusy: false,

	selectable: false,
	multiSelect: false,
	actions: null,
	actionsHeadHtml: null,
	actionsFootHtml: null,
	showingActionTriggers: false,
	_$triggers: null,

	$container: null,
	$main: null,
	$scroller: null,
	$toolbar: null,
	$toolbarTableRow: null,
	toolbarOffset: null,
	$selectAllContainer: null,
	$selectAllCheckbox: null,
	$search: null,
	searching: false,
	$clearSearchBtn: null,
	$mainSpinner: null,

	$statusMenuBtn: null,
	statusMenu: null,
	status: null,

	$localeMenuBtn: null,
	localeMenu: null,
	locale: null,

	$sortMenuBtn: null,
	sortMenu: null,
	$sortAttributesList: null,
	$sortDirectionsList: null,
	$scoreSortAttribute: null,
	$structureSortAttribute: null,

	$viewModeBtnTd: null,
	$viewModeBtnContainer: null,
	viewModeBtns: null,
	viewMode: null,

	$loadingMoreSpinner: null,
	$sidebar: null,
	$sidebarButtonContainer: null,
	showingSidebar: null,
	sourceKey: null,
	sourceViewModes: null,
	$source: null,
	$elements: null,
	$table: null,
	$elementContainer: null,
	$checkboxes: null,

	_totalVisible: null,
	_morePending: false,
	_totalVisiblePostStructureTableDraggee: null,
	_morePendingPostStructureTableDraggee: false,
	loadingMore: false,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 */
	init: function(elementType, $container, settings)
	{
		this.elementType = elementType;
		this.$container = $container;
		this.setSettings(settings, Craft.BaseElementIndex.defaults);

		// Set the state objects
		this.instanceState = {
			selectedSource: null
		};

		this.sourceStates = {};

		// Instance states (selected source) are stored by a custom storage key defined in the settings
		if (this.settings.storageKey)
		{
			$.extend(this.instanceState, Craft.getLocalStorage(this.settings.storageKey), {});
		}

		// Source states (view mode, etc.) are stored by the element type and context
		this.sourceStatesStorageKey = 'BaseElementIndex.'+this.elementType+'.'+this.settings.context;
		$.extend(this.sourceStates, Craft.getLocalStorage(this.sourceStatesStorageKey, {}));

		// Find the DOM elements
		this.$main = this.$container.find('.main');
		this.$toolbar = this.$container.find('.toolbar:first');
		this.$toolbarTableRow = this.$toolbar.children('table').children('tbody').children('tr');
		this.$statusMenuBtn = this.$toolbarTableRow.find('.statusmenubtn:first');
		this.$localeMenuBtn = this.$toolbarTableRow.find('.localemenubtn:first');
		this.$sortMenuBtn = this.$toolbarTableRow.find('.sortmenubtn:first');
		this.$search = this.$toolbarTableRow.find('.search:first input:first');
		this.$clearSearchBtn = this.$toolbarTableRow.find('.search:first > .clear');
		this.$mainSpinner = this.$toolbar.find('.spinner:first');
		this.$loadingMoreSpinner = this.$container.find('.spinner.loadingmore')
		this.$sidebar = this.$container.find('.sidebar:first');
		this.$sidebarButtonContainer = this.$sidebar.children('.buttons');
		this.$elements = this.$container.find('.elements:first');

		if (!this.$sidebarButtonContainer.length)
		{
			this.$sidebarButtonContainer = $('<div class="buttons"/>').prependTo(this.$sidebar);
		}

		this.showingSidebar = (this.$sidebar.length && !this.$sidebar.hasClass('hidden'));

		this.$viewModeBtnTd = this.$toolbarTableRow.find('.viewbtns:first');
		this.$viewModeBtnContainer = $('<div class="btngroup fullwidth"/>').appendTo(this.$viewModeBtnTd);

		if (this.settings.context == 'index' && !Garnish.isMobileBrowser(true))
		{
			this.addListener(Garnish.$win, 'scroll resize', 'updateFixedToolbar');
		}

		// Initialize the sources
		// ---------------------------------------------------------------------

		var $sources = this._getSourcesInList(this.$sidebar.children('nav').children('ul'));

		// No source, no party.
		if ($sources.length == 0)
		{
			return;
		}

		// The source selector
		this.sourceSelect = new Garnish.Select(this.$sidebar.find('nav'), {
			multi:             false,
			allowEmpty:        false,
			vertical:          true,
			onSelectionChange: $.proxy(this, 'onSourceSelectionChange')
		});

		this._initSources($sources);

		// Initialize the locale menu button
		// ---------------------------------------------------------------------

		// Is there a locale menu?
		if (this.$localeMenuBtn.length)
		{
			this.localeMenu = this.$localeMenuBtn.menubtn().data('menubtn').menu;

			// Figure out the initial locale
			var $option = this.localeMenu.$options.filter('.sel:first');

			if (!$option.length)
			{
				$option = this.localeMenu.$options.first();
			}

			if ($option.length)
			{
				this.locale = $option.data('locale');
			}
			else
			{
				// No locale options -- they must not have any locale permissions
				this.settings.criteria = { id: '0' };
			}

			this.localeMenu.on('optionselect', $.proxy(this, 'onLocaleChange'));

			if (this.locale)
			{
				// Do we have a different locale stored in localStorage?
				var storedLocale = Craft.getLocalStorage('BaseElementIndex.locale');

				if (storedLocale && storedLocale != this.locale)
				{
					// Is that one available here?
					var $storedLocaleOption = this.localeMenu.$options.filter('[data-locale="'+storedLocale+'"]:first');

					if ($storedLocaleOption.length)
					{
						// Todo: switch this to localeMenu.selectOption($storedLocaleOption) once Menu is updated to support that
						$storedLocaleOption.trigger('click');
					}
				}
			}
		}

		// Is there a sort menu?
		if (this.$sortMenuBtn.length)
		{
			this.sortMenu = this.$sortMenuBtn.menubtn().data('menubtn').menu;
			this.$sortAttributesList = this.sortMenu.$container.children('.sort-attributes');
			this.$sortDirectionsList = this.sortMenu.$container.children('.sort-directions');

			this.sortMenu.on('optionselect', $.proxy(this, 'onSortChange'));
		}

		this.onAfterHtmlInit();

		if (this.settings.context == 'index')
		{
			this.$scroller = Garnish.$win;
		}
		else
		{
			this.$scroller = this.$main;
		}

		// Select the initial source
		var source = this.getDefaultSourceKey();

		if (source)
		{
			var $source = this.getSourceByKey(source);

			if ($source)
			{
				// Expand any parent sources
				var $parentSources = $source.parentsUntil('.sidebar', 'li');
				$parentSources.not(':first').addClass('expanded');
			}
		}

		if (!source || !$source)
		{
			// Select the first source by default
			var $source = this.$sources.first();
		}

		// Load up the elements!
		this.initialized = true;
		this.sourceSelect.selectItem($source);

		// Status changes
		if (this.$statusMenuBtn.length)
		{
			this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
			this.statusMenu.on('optionselect', $.proxy(this, 'onStatusChange'));
		}

		this.addListener(this.$search, 'textchange', $.proxy(function()
		{
			if (!this.searching && this.$search.val())
			{
				this.onStartSearching();
			}
			else if (this.searching && !this.$search.val())
			{
				this.onStopSearching();
			}

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'updateElements'), 500);
		}, this));

		this.addListener(this.$clearSearchBtn, 'click', $.proxy(function()
		{
			this.$search.val('');

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			if (!Garnish.isMobileBrowser(true))
			{
				this.$search.focus();
			}

			this.onStopSearching();

			this.updateElements();

		}, this))

		// Auto-focus the Search box
		if (!Garnish.isMobileBrowser(true))
		{
			this.$search.focus();
		}
	},

	get $sources()
	{
		if (!this.sourceSelect)
		{
			return undefined;
		}

		return this.sourceSelect.$items;
	},

	get totalVisible()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._totalVisiblePostStructureTableDraggee;
		}
		else
		{
			return this._totalVisible;
		}
	},

	get morePending()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._morePendingPostStructureTableDraggee;
		}
		else
		{
			return this._morePending;
		}
	},

	updateFixedToolbar: function()
	{
		if (!this.toolbarOffset)
		{
			this.toolbarOffset = this.$toolbar.offset().top;

			if (!this.toolbarOffset)
			{
				return;
			}
		}

		this.updateFixedToolbar._scrollTop = Garnish.$win.scrollTop();

		if (this.updateFixedToolbar._scrollTop > this.toolbarOffset - 7)
		{
			if (!this.$toolbar.hasClass('fixed'))
			{
				this.$elements.css('padding-top', (this.$toolbar.outerHeight() + 24));
				this.$toolbar.addClass('fixed');
			}

			this.$toolbar.css('width', this.$main.width());
		}
		else
		{
			if (this.$toolbar.hasClass('fixed'))
			{
				this.$toolbar.removeClass('fixed');
				this.$toolbar.css('width', '');
				this.$elements.css('padding-top', '');
			}
		}
	},

	initSource: function($source)
	{
		this.sourceSelect.addItems($source);
		this.initSourceToggle($source);
	},

	initSourceToggle: function($source)
	{
		var $toggle = this._getSourceToggle($source);

		if ($toggle.length)
		{
			this.addListener($toggle, 'click', '_onToggleClick');
		}
	},

	deinitSource: function($source)
	{
		this.sourceSelect.removeItems($source);
		this.deinitSourceToggle($source);
	},

	deinitSourceToggle: function($source)
	{
		var $toggle = this._getSourceToggle($source);

		if ($toggle.length)
		{
			this.removeListener($toggle, 'click');
		}
	},

	getDefaultSourceKey: function()
	{
		return this.instanceState.selectedSource;
	},

	onSourceSelectionChange: function()
	{
		if (this.selectSource(this.sourceSelect.$selectedItems))
		{
			this.updateElements();
		}
	},

	onStartSearching: function()
	{
		// Show the clear button and add/select the Score sort option
		this.$clearSearchBtn.removeClass('hidden');

		if (!this.$scoreSortAttribute)
		{
			this.$scoreSortAttribute = $('<li><a data-attr="score">'+Craft.t('Score')+'</a></li>');
			this.sortMenu.addOptions(this.$scoreSortAttribute.children());
		}

		this.$scoreSortAttribute.prependTo(this.$sortAttributesList);
		this.setSortAttribute('score');
		this.getSortAttributeOption('structure').addClass('disabled');

		this.searching = true;
	},

	onStopSearching: function()
	{
		// Hide the clear button and Score sort option
		this.$clearSearchBtn.addClass('hidden');

		this.$scoreSortAttribute.detach();
		this.getSortAttributeOption('structure').removeClass('disabled');
		this.setStoredSortOptionsForSource();

		this.searching = false;
	},

	setInstanceState: function(key, value)
	{
		if (typeof key == 'object')
		{
			$.extend(this.instanceState, key);
		}
		else
		{
			this.instanceState[key] = value;
		}

		// Store it in localStorage too?
		if (this.settings.storageKey)
		{
			Craft.setLocalStorage(this.settings.storageKey, this.instanceState);
		}
	},

	getSourceState: function(source, key, defaultValue)
	{
		if (typeof this.sourceStates[source] == 'undefined')
		{
			// Set it now so any modifications to it by whoever's calling this will be stored.
			this.sourceStates[source] = {};
		}

		if (typeof key == 'undefined')
		{
			return this.sourceStates[source];
		}
		else if (typeof this.sourceStates[source][key] != 'undefined')
		{
			return this.sourceStates[source][key];
		}
		else
		{
			return (typeof defaultValue != 'undefined' ? defaultValue : null);
		}
	},

	getSelectedSourceState: function(key, defaultValue)
	{
		return this.getSourceState(this.instanceState.selectedSource, key, defaultValue);
	},

	setSelecetedSourceState: function(key, value)
	{
		var viewState = this.getSelectedSourceState();

		if (typeof key == 'object')
		{
			$.extend(viewState, key);
		}
		else
		{
			viewState[key] = value;
		}

		this.sourceStates[this.instanceState.selectedSource] = viewState;

		// Store it in localStorage too
		Craft.setLocalStorage(this.sourceStatesStorageKey, this.sourceStates);
	},

	/**
	 * Returns the data that should be passed to the elementIndex/getElements controller action
	 * when loading the first batch of elements.
	 */
	getControllerData: function()
	{
		var data = {
			context:            this.settings.context,
			elementType:        this.elementType,
			criteria:           $.extend({ status: this.status, locale: this.locale }, this.settings.criteria),
			disabledElementIds: this.settings.disabledElementIds,
			source:             this.instanceState.selectedSource,
			status:             this.status,
			viewState:          this.getSelectedSourceState(),
			search:             (this.$search ? this.$search.val() : null)
		};

		// Possible that the order/sort isn't entirely accurate if we're sorting by Score
		data.viewState.order = this.getSelectedSortAttribute();
		data.viewState.sort = this.getSelectedSortDirection();

		return data;
	},

	updateElements: function()
	{
		// Ignore if we're not fully initialized yet
		if (!this.initialized)
		{
			return;
		}

		// Prep the UI
		// -------------------------------------------------------------

		this.setIndexBusy();
		this.removeListener(this.$scroller, 'scroll');

		if (this.getSelectedSourceState('mode') == 'table' && this.$table)
		{
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		// Fetch the elements
		// -------------------------------------------------------------

		var data = this.getControllerData();

		Craft.postActionRequest('elementIndex/getElements', data, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				// Cleanup
				// -------------------------------------------------------------

				this._prepForNewElements();

				// Selectable setup
				// -------------------------------------------------------------

				if (this.settings.context == 'index' && response.actions && response.actions.length)
				{
					this.actions = response.actions;
					this.actionsHeadHtml = response.actionsHeadHtml;
					this.actionsFootHtml = response.actionsFootHtml;
				}
				else
				{
					this.actions = this.actionsHeadHtml = this.actionsFootHtml = null;
				}

				this.selectable = (this.actions || this.settings.selectable);

				// Update the view with the new container + elements HTML
				// -------------------------------------------------------------

				this.$elements.html(response.html);
				this.$scroller.scrollTop(0);

				if (this.getSelectedSourceState('mode') == 'table')
				{
					this.$table = this.$elements.find('table:first');
					Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
				}

				// Find the new container
				this.$elementContainer = this.getElementContainer();

				// Get the new elements
				var $newElements = this.$elementContainer.children();

				// Initialize the selector stuff and the structure table sorter
				this._setupNewElements($newElements);

				this._onUpdateElements(response, false, $newElements);
			}

		}, this));
	},

	showActionTriggers: function()
	{
		// Ignore if they're already shown
		if (this.showingActionTriggers)
		{
			return;
		}

		// Hide any toolbar inputs
		this.$toolbarTableRow.children().not(this.$selectAllContainer).addClass('hidden');

		if (!this._$triggers)
		{
			this._createTriggers();
		}
		else
		{
			this._$triggers.insertAfter(this.$selectAllContainer);
		}

		this.showingActionTriggers = true;
	},

	handleActionTriggerSubmit: function(ev)
	{
		ev.preventDefault();

		var $form = $(ev.currentTarget),
			actionHandle = $form.data('action'),
			params = Garnish.getPostData($form);

		this.submitAction(actionHandle, params);
	},

	handleMenuActionTriggerSubmit: function(ev)
	{
		var $option = $(ev.option);

		// Maybe it's a link
		if (!$option.attr('href'))
		{
			var actionHandle = $option.data('action');
			this.submitAction(actionHandle);
		}
	},

	submitAction: function(actionHandle, params)
	{
		// Make sure something's selected
		var totalSelected = this.elementSelect.totalSelected,
			totalItems = this.elementSelect.$items.length;

		if (totalSelected == 0)
		{
			return;
		}

		// Find the action
		for (var i = 0; i < this.actions.length; i++)
		{
			if (this.actions[i].handle == actionHandle)
			{
				var action = this.actions[i];
				break;
			}
		}

		if (!action || (action.confirm && !confirm(action.confirm)))
		{
			return;
		}

		// Get ready to submit
		var data = $.extend(this.getControllerData(), params, {
			elementAction: actionHandle,
			elementIds:    this.getSelectedElementIds()
		});

		// Do it
		this.setIndexBusy();

		Craft.postActionRequest('elementIndex/performAction', data, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this._prepForNewElements();
					this.$elementContainer.html('');
					this.elementSelect = this.createElementSelect();

					var $newElements = $(response.html).appendTo(this.$elementContainer);

					// Initialize the selector stuff and the structure table sorter
					this._setupNewElements($newElements);

					// There may be less elements now if some had been lazy-loaded before. If that's the case and all of
					// the elements were selected, we don't want to give the user the impression that all of the same
					// elements are still selected.
					if (totalItems <= 50 || totalSelected < totalItems)
					{
						for (var i = 0; i < data.elementIds.length; i++)
						{
							var $element = this.getElementById(data.elementIds[i]);

							if ($element)
							{
								this.elementSelect.selectItem($element);
							}
						}
					}

					this._onUpdateElements(response, false, $newElements);

					if (response.message)
					{
						Craft.cp.displayNotice(response.message);
					}
				}
				else
				{
					Craft.cp.displayError(response.message);
				}
			}
		}, this));
	},

	hideActionTriggers: function()
	{
		// Ignore if there aren't any
		if (!this.showingActionTriggers)
		{
			return;
		}

		this._$triggers.detach();

		this.$toolbarTableRow.children().not(this.$selectAllContainer).removeClass('hidden');

		this.showingActionTriggers = false;
	},

	updateActionTriggers: function()
	{
		// Do we have an action UI to update?
		if (this.actions)
		{
			var totalSelected = this.elementSelect.totalSelected;

			if (totalSelected != 0)
			{
				if (totalSelected == this.elementSelect.$items.length)
				{
					this.$selectAllCheckbox.removeClass('indeterminate');
					this.$selectAllCheckbox.addClass('checked');
				}
				else
				{
					this.$selectAllCheckbox.addClass('indeterminate');
					this.$selectAllCheckbox.removeClass('checked');
				}

				this.showActionTriggers();
			}
			else
			{
				this.$selectAllCheckbox.removeClass('indeterminate checked');
				this.hideActionTriggers();
			}
		}
	},

	/**
	 * Checks if the user has reached the bottom of the scroll area, and if so, loads the next batch of elemets.
	 */
	maybeLoadMore: function()
	{
		if (this.canLoadMore())
		{
			this.loadMore();
		}
	},

	/**
	 * Returns whether the user has reached the bottom of the scroll area.
	 */
	canLoadMore: function()
	{
		if (!this.morePending)
		{
			return false;
		}

		// Check if the user has reached the bottom of the scroll area
		if (this.$scroller[0] == Garnish.$win[0])
		{
			var winHeight = Garnish.$win.innerHeight(),
				winScrollTop = Garnish.$win.scrollTop(),
				bodHeight = Garnish.$bod.height();

			return (winHeight + winScrollTop >= bodHeight);
		}
		else
		{
			var containerScrollHeight = this.$scroller.prop('scrollHeight'),
				containerScrollTop = this.$scroller.scrollTop(),
				containerHeight = this.$scroller.outerHeight();

			return (containerScrollHeight - containerScrollTop <= containerHeight + 15);
		}
	},

	/**
	 * Loads the next batch of elements.
	 */
	loadMore: function()
	{
		if (!this.morePending || this.loadingMore)
		{
			return;
		}

		this.loadingMore = true;
		this.$loadingMoreSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		var data = this.getLoadMoreData();

		Craft.postActionRequest('elementIndex/getMoreElements', data, $.proxy(function(response, textStatus)
		{
			this.loadingMore = false;
			this.$loadingMoreSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				var $newElements = $(response.html).appendTo(this.$elementContainer);

				if (this.actions || this.settings.selectable)
				{
					this.elementSelect.addItems($newElements.filter(':not(.disabled)'));
					this.updateActionTriggers();
				}

				if (this.structureTableSort)
				{
					this.structureTableSort.addItems($newElements);
				}

				this._onUpdateElements(response, true, $newElements);
			}

		}, this));
	},

	/**
	 * Returns the data that should be passed to the elementIndex/getMoreElements controller action
	 * when loading a subsequent batch of elements.
	 */
	getLoadMoreData: function()
	{
		var data = this.getControllerData();
		data.offset = this.totalVisible;

		// If we are dragging the last elements on the page,
		// tell the controller to only load elements positioned after the draggee.
		if (this._isStructureTableDraggingLastElements())
		{
			data.criteria.positionedAfter = this.structureTableSort.$targetItem.data('id');
		}

		return data;
	},

	/**
	 * Returns the element container.
	 */
	getElementContainer: function()
	{
		if (this.getSelectedSourceState('mode') == 'table')
		{
			return this.$table.children('tbody:first');
		}
		else
		{
			return this.$elements.children('ul');
		}
	},

	createElementSelect: function()
	{
		return new Garnish.Select(this.$elementContainer, {
			multi:             (this.actions || this.settings.multiSelect),
			vertical:          (this.getSelectedSourceState('mode') != 'thumbs'),
			handle:            (this.settings.context == 'index' ? '.checkbox, .element' : null),
			filter:            ':not(a)',
			checkboxMode:      (this.settings.context == 'index' && this.actions),
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});
	},

	getSelectedElementIds: function()
	{
		var $selectedItems = this.elementSelect.$selectedItems,
			ids = [];

		for (var i = 0; i < $selectedItems.length; i++)
		{
			ids.push($selectedItems.eq(i).data('id'));
		}

		return ids;
	},

	onUpdateElements: function(append, $newElements)
	{
		this.settings.onUpdateElements(append, $newElements);
	},

	onStatusChange: function(ev)
	{
		this.statusMenu.$options.removeClass('sel');
		var $option = $(ev.selectedOption).addClass('sel');
		this.$statusMenuBtn.html($option.html());

		this.status = $option.data('status');
		this.updateElements();
	},

	onLocaleChange: function(ev)
	{
		this.localeMenu.$options.removeClass('sel');
		var $option = $(ev.selectedOption).addClass('sel');
		this.$localeMenuBtn.html($option.html());

		this.locale = $option.data('locale');

		if (this.initialized)
		{
			// Remember this locale for later
			Craft.setLocalStorage('BaseElementIndex.locale', this.locale);

			// Update the elements
			this.updateElements();
		}
	},

	getSortAttributeOption: function(attr)
	{
		return this.$sortAttributesList.find('a[data-attr='+attr+']:first');
	},

	getSelectedSortAttribute: function()
	{
		return this.$sortAttributesList.find('a.sel:first').data('attr');
	},

	setSortAttribute: function(attr)
	{
		// Find the option (and make sure it actually exists)
		var $option = this.getSortAttributeOption(attr);

		if ($option.length)
		{
			this.$sortAttributesList.find('a.sel').removeClass('sel');
			$option.addClass('sel');

			var label = $option.text();
			this.$sortMenuBtn.attr('title', Craft.t('Sort by {attribute}', { attribute: label }));
			this.$sortMenuBtn.text(label);

			this.setSortDirection('asc');

			if (attr == 'score' || attr == 'structure')
			{
				this.$sortDirectionsList.find('a').addClass('disabled');
			}
			else
			{
				this.$sortDirectionsList.find('a').removeClass('disabled');
			}
		}
	},

	getSortDirectionOption: function(dir)
	{
		return this.$sortDirectionsList.find('a[data-dir='+dir+']:first');
	},

	getSelectedSortDirection: function()
	{
		return this.$sortDirectionsList.find('a.sel:first').data('dir');
	},

	setSortDirection: function(dir)
	{
		if (dir != 'desc')
		{
			dir = 'asc';
		}

		this.$sortMenuBtn.attr('data-icon', dir);
		this.$sortDirectionsList.find('a.sel').removeClass('sel');
		this.getSortDirectionOption(dir).addClass('sel');
	},

	onSortChange: function(ev)
	{
		var $option = $(ev.selectedOption);

		if ($option.hasClass('disabled') || $option.hasClass('sel'))
		{
			return;
		}

		// Is this an attribute or a direction?
		if ($option.parent().parent().is(this.$sortAttributesList))
		{
			this.setSortAttribute($option.data('attr'));
		}
		else
		{
			this.setSortDirection($option.data('dir'));
		}

		// Save it to localStorage (unless we're sorting by score)
		var attr = this.getSelectedSortAttribute();

		if (attr != 'score')
		{
			this.setSelecetedSourceState({
				order: attr,
				sort: this.getSelectedSortDirection()
			});
		}

		this.updateElements();
	},

	getSourceByKey: function(key)
	{
		if (this.$sources)
		{
			var $source = this.$sources.filter('[data-key="'+key+'"]:first');

			if ($source.length)
			{
				return $source;
			}
		}
	},

	selectSource: function($source)
	{
		if (this.$source && this.$source[0] && this.$source[0] == $source[0])
		{
			return false;
		}

		if ($source[0] != this.sourceSelect.$selectedItems[0])
		{
			this.sourceSelect.selectItem($source);
		}

		this.$source = $source;
		this.sourceKey = $source.data('key');
		this.setInstanceState('selectedSource', this.sourceKey);

		if (this.searching)
		{
			// Clear the search value without triggering the textchange event
			this.$search.data('textchangeValue', '');
			this.$search.val('');
			this.onStopSearching();
		}

		// Sort menu
		// ----------------------------------------------------------------------

		// Does this source have a structure?
		if (Garnish.hasAttr(this.$source, 'data-has-structure'))
		{
			if (!this.$structureSortAttribute)
			{
				this.$structureSortAttribute = $('<li><a data-attr="structure">'+Craft.t('Structure')+'</a></li>');
				this.sortMenu.addOptions(this.$structureSortAttribute.children());
			}

			this.$structureSortAttribute.prependTo(this.$sortAttributesList);
		}
		else if (this.$structureSortAttribute)
		{
			this.$structureSortAttribute.removeClass('sel').detach();
		}

		this.setStoredSortOptionsForSource();

		// View mode buttons
		// ----------------------------------------------------------------------

		// Clear out any previous view mode data
		this.$viewModeBtnContainer.empty();
		this.viewModeBtns = {};
		this.viewMode = null;

		// Get the new list of view modes
		this.sourceViewModes = this.getViewModesForSource();

		// Create the buttons if there's more than one mode available to this source
		if (this.sourceViewModes.length > 1)
		{
			this.$viewModeBtnTd.removeClass('hidden');

			for (var i = 0; i < this.sourceViewModes.length; i++)
			{
				var viewMode = this.sourceViewModes[i];

				var $viewModeBtn = $('<div data-view="'+viewMode.mode+'" role="button"' +
					' class="btn'+(typeof viewMode.className != 'undefined' ? ' '+viewMode.className : '')+'"' +
					' title="'+viewMode.title+'"' +
					(typeof viewMode.icon != 'undefined' ? ' data-icon="'+viewMode.icon+'"' : '') +
					'/>'
				).appendTo(this.$viewModeBtnContainer);

				this.viewModeBtns[viewMode.mode] = $viewModeBtn;

				this.addListener($viewModeBtn, 'click', { mode: viewMode.mode }, function(ev) {
					this.selectViewMode(ev.data.mode);
					this.updateElements();
				});
			}
		}
		else
		{
			this.$viewModeBtnTd.addClass('hidden');
		}

		// Figure out which mode we should start with
		var viewMode = this.getSelectedSourceState('mode');

		if (!viewMode || !this.doesSourceHaveViewMode(viewMode))
		{
			// Try to keep using the current view mode
			if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode))
			{
				viewMode = this.viewMode;
			}
			// Just use the first one
			else
			{
				viewMode = this.sourceViewModes[0].mode;
			}
		}

		this.selectViewMode(viewMode);

		this.onSelectSource();

		return true;
	},

	setStoredSortOptionsForSource: function()
	{
		// Default to whatever's first
		this.setSortAttribute(this.$sortAttributesList.find('a:first').data('attr'));
		this.setSortDirection('asc');

		var storedSortAttr = this.getSelectedSourceState('order'),
			storedSortDir = this.getSelectedSourceState('sort');

		if (storedSortAttr)
		{
			this.setSortAttribute(storedSortAttr);
		}

		if (storedSortDir)
		{
			this.setSortDirection(storedSortDir);
		}
	},

	getViewModesForSource: function()
	{
		var viewModes = [
			{ mode: 'table', title: Craft.t('Display in a table'), icon: 'list' }
		];

		if (Garnish.hasAttr(this.$source, 'data-has-thumbs'))
		{
			viewModes.push({ mode: 'thumbs', title: Craft.t('Display as thumbnails'), icon: 'grid' });
		}

		return viewModes;
	},

	onSelectSource: function()
	{
		this.settings.onSelectSource(this.sourceKey);
	},

	onAfterHtmlInit: function()
	{
		this.settings.onAfterHtmlInit()
	},

	onSelectionChange: function()
	{
		this.updateActionTriggers();
		this.settings.onSelectionChange();
	},

	doesSourceHaveViewMode: function(viewMode)
	{
		for (var i = 0; i < this.sourceViewModes.length; i++)
		{
			if (this.sourceViewModes[i].mode == viewMode)
			{
				return true;
			}
		}

		return false;
	},

	selectViewMode: function(viewMode, force)
	{
		// Make sure that the current source supports it
		if (!force && !this.doesSourceHaveViewMode(viewMode))
		{
			viewMode = this.sourceViewModes[0].mode;
		}

		// Has anything changed?
		if (viewMode == this.viewMode)
		{
			return;
		}

		// Deselect the previous view mode
		if (this.viewMode && typeof this.viewModeBtns[this.viewMode] != 'undefined')
		{
			this.viewModeBtns[this.viewMode].removeClass('active');
		}

		this.viewMode = viewMode;
		this.setSelecetedSourceState('mode', this.viewMode);

		if (typeof this.viewModeBtns[this.viewMode] != 'undefined')
		{
			this.viewModeBtns[this.viewMode].addClass('active');
		}
	},

	rememberDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index == -1)
		{
			this.settings.disabledElementIds.push(elementId);
		}
	},

	forgetDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index != -1)
		{
			this.settings.disabledElementIds.splice(index, 1);
		}
	},

	enableElements: function($elements)
	{
		$elements.removeClass('disabled').parents('.disabled').removeClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.forgetDisabledElementId(elementId);
		}

		this.settings.onEnableElements($elements);
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.rememberDisabledElementId(elementId);
		}

		this.settings.onDisableElements($elements);
	},

	getElementById: function(elementId)
	{
		return this.$elementContainer.find('[data-id='+elementId+']:first');
	},

	enableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.enableElements($element);
			}
			else
			{
				this.forgetDisabledElementId(elementId);
			}
		}
	},

	disableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.disableElements($element);
			}
			else
			{
				this.rememberDisabledElementId(elementId);
			}
		}
	},

	addButton: function($button)
	{
		if (this.showingSidebar)
		{
			this.$sidebarButtonContainer.append($button);
		}
		else
		{
			$('<td class="thin"/>').prependTo(this.$toolbarTableRow.find('tr:first')).append($button);
		}
	},

	addCallback: function(currentCallback, newCallback)
	{
		return $.proxy(function() {
			if (typeof currentCallback == 'function')
			{
				currentCallback.apply(this, arguments);
			}
			newCallback.apply(this, arguments);
		}, this);
	},

	setIndexBusy: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.isIndexBusy = true;
	},

	setIndexAvailable: function()
	{
		this.$mainSpinner.addClass('hidden');
		this.isIndexBusy = false;
	},

	disable: function()
	{
		this.sourceSelect.disable();

		if (this.elementSelect)
		{
			this.elementSelect.disable();
		}

		this.base();
	},

	enable: function()
	{
		this.sourceSelect.enable();

		if (this.elementSelect)
		{
			this.elementSelect.enable();
		}

		this.base();
	},

	// Private methods
	// =========================================================================

	_getSourcesInList: function($list)
	{
		return $list.children('li').children('a');
	},

	_getChildSources: function($source)
	{
		var $list = $source.siblings('ul');
		return this._getSourcesInList($list);
	},

	_getSourceToggle: function($source)
	{
		return $source.siblings('.toggle');
	},

	_initSources: function($sources)
	{
		for (var i = 0; i < $sources.length; i++)
		{
			this.initSource($($sources[i]));
		}
	},

	_deinitSources: function($sources)
	{
		for (var i = 0; i < $sources.length; i++)
		{
			this.deinitSource($($sources[i]));
		}
	},

	_onToggleClick: function(ev)
	{
		this._toggleSource($(ev.currentTarget).prev('a'));
		ev.stopPropagation();
	},

	_toggleSource: function($source)
	{
		if ($source.parent('li').hasClass('expanded'))
		{
			this._collapseSource($source);
		}
		else
		{
			this._expandSource($source);
		}
	},

	_expandSource: function($source)
	{
		$source.parent('li').addClass('expanded');

		this.$sidebar.trigger('resize');

		var $childSources = this._getChildSources($source);
		this._initSources($childSources);
	},

	_collapseSource: function($source)
	{
		$source.parent('li').removeClass('expanded');

		this.$sidebar.trigger('resize');

		var $childSources = this._getChildSources($source);
		this._deinitSources($childSources);
	},

	_prepForNewElements: function()
	{
		if (this.actions)
		{
			// Get rid of the old action triggers regardless of whether the new batch has actions or not
			this.hideActionTriggers();
			this._$triggers = null;
		}

		// Reset the element select
		if (this.elementSelect)
		{
			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		if (this.$selectAllContainer)
		{
			// Git rid of the old select all button
			this.$selectAllContainer.detach();
		}
	},

	_setupNewElements: function($newElements)
	{
		if (this.selectable)
		{
			// Initialize the element selector
			this.elementSelect = this.createElementSelect();
			this.elementSelect.addItems($newElements.filter(':not(.disabled)'));

			if (this.actions)
			{
				// First time?
				if (!this.$selectAllContainer)
				{
					// Create the select all button
					this.$selectAllContainer = $('<td class="selectallcontainer thin"/>');
					this.$selectAllBtn = $('<div class="btn"/>').appendTo(this.$selectAllContainer);
					this.$selectAllCheckbox = $('<div class="checkbox"/>').appendTo(this.$selectAllBtn);

					this.addListener(this.$selectAllBtn, 'click', function()
					{
						if (this.elementSelect.totalSelected == 0)
						{
							this.elementSelect.selectAll();
						}
						else
						{
							this.elementSelect.deselectAll();
						}
					});
				}
				else
				{
					// Reset the select all button
					this.$selectAllCheckbox.removeClass('indeterminate checked');
				}

				// Place the select all button at the beginning of the toolbar
				this.$selectAllContainer.prependTo(this.$toolbarTableRow);
			}
		}

		// StructureTableSorter setup
		// -------------------------------------------------------------

		if (
			this.settings.context == 'index' &&
			this.getSelectedSourceState('mode') == 'table' &&
			this.getSelectedSortAttribute() == 'structure' &&
			Garnish.hasAttr(this.$table, 'data-structure-id')
		)
		{
			// Create the sorter
			this.structureTableSort = new Craft.StructureTableSorter(this, $newElements, {
				onSortChange: $.proxy(this, '_onStructureTableSortChange')
			});
		}
		else
		{
			this.structureTableSort = null;
		}
	},

	_onUpdateElements: function(response, append, $newElements)
	{
		$('head').append(response.headHtml);
		Garnish.$bod.append(response.footHtml);

		if (this._isStructureTableDraggingLastElements())
		{
			this._totalVisiblePostStructureTableDraggee = response.totalVisible;
			this._morePendingPostStructureTableDraggee = response.more;
		}
		else
		{
			this._totalVisible = response.totalVisible;
			this._morePending = this._morePendingPostStructureTableDraggee = response.more;
		}

		if (this.morePending)
		{
			// Is there room to load more right now?
			if (this.canLoadMore())
			{
				this.loadMore();
			}
			else
			{
				this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
			}
		}

		if (this.getSelectedSourceState('mode') == 'table')
		{
			Craft.cp.updateResponsiveTables();
		}

		this.onUpdateElements(append, $newElements);
	},

	_isStructureTableDraggingLastElements: function()
	{
		return (this.structureTableSort && this.structureTableSort.dragging && this.structureTableSort.draggingLastElements);
	},

	_createTriggers: function()
	{
		var triggers = [],
			safeMenuActions = [],
			destructiveMenuActions = [];

		for (var i = 0; i < this.actions.length; i++)
		{
			var action = this.actions[i];

			if (action.trigger)
			{
				var $form = $('<form id="'+action.handle+'-actiontrigger"/>')
					.data('action', action.handle)
					.append(action.trigger);

				this.addListener($form, 'submit', 'handleActionTriggerSubmit');
				triggers.push($form);
			}
			else
			{
				if (!action.destructive)
				{
					safeMenuActions.push(action);
				}
				else
				{
					destructiveMenuActions.push(action);
				}
			}
		}

		if (safeMenuActions.length || destructiveMenuActions.length)
		{
			var $menuTrigger = $('<form/>'),
				$btn = $('<div class="btn menubtn" data-icon="settings" title="'+Craft.t('Actions')+'"/>').appendTo($menuTrigger),
				$menu = $('<ul class="menu"/>').appendTo($menuTrigger),
				$safeList = this._createMenuTriggerList(safeMenuActions),
				$destructiveList = this._createMenuTriggerList(destructiveMenuActions);

			if ($safeList)
			{
				$safeList.appendTo($menu);
			}

			if ($safeList && $destructiveList)
			{
				$('<hr/>').appendTo($menu);
			}

			if ($destructiveList)
			{
				$destructiveList.appendTo($menu);
			}

			triggers.push($menuTrigger);
		}

		// Add a filler TD
		triggers.push('');

		this._$triggers = $();

		for (var i = 0; i < triggers.length; i++)
		{
			var $td = $('<td class="'+(i < triggers.length - 1 ? 'thin' : '')+'"/>').append(triggers[i]);
			this._$triggers = this._$triggers.add($td);
		}

		this._$triggers.insertAfter(this.$selectAllContainer);
		$('head').append(this.actionsHeadHtml);
		Garnish.$bod.append(this.actionsFootHtml);

		Craft.initUiElements(this._$triggers);

		if ($btn)
		{
			$btn.data('menubtn').on('optionSelect', $.proxy(this, 'handleMenuActionTriggerSubmit'));
		}
	},

	_createMenuTriggerList: function(actions)
	{
		if (actions && actions.length)
		{
			var $ul = $('<ul/>');

			for (var i = 0; i < actions.length; i++)
			{
				var handle = actions[i].handle;
				$('<li><a id="'+handle+'-actiontrigger" data-action="'+handle+'">'+actions[i].name+'</a></li>').appendTo($ul);
			}

			return $ul;
		}
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		context: 'index',
		storageKey: null,
		criteria: null,
		disabledElementIds: [],
		selectable: false,
		multiSelect: false,
		onUpdateElements: $.noop,
		onSelectionChange: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop,
		onSelectSource: $.noop,
		onAfterHtmlInit: $.noop
	}
});
