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

	$container: null,
	$main: null,
	$scroller: null,
	$toolbar: null,
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
		this.$statusMenuBtn = this.$toolbar.find('.statusmenubtn:first');
		this.$localeMenuBtn = this.$toolbar.find('.localemenubtn:first');
		this.$sortMenuBtn = this.$toolbar.find('.sortmenubtn:first');
		this.$search = this.$toolbar.find('.search:first input:first');
		this.$clearSearchBtn = this.$toolbar.find('.search:first > .clear');
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

		this.$viewModeBtnTd = this.$toolbar.find('.viewbtns:first');
		this.$viewModeBtnContainer = $('<div class="btngroup"/>').appendTo(this.$viewModeBtnTd);

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

		this.$mainSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		if (this.getSelectedSourceState('mode') == 'table' && this.$table)
		{
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		var data = this.getControllerData();

		Craft.postActionRequest('elementIndex/getElements', data, $.proxy(function(response, textStatus)
		{
			this.$mainSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.setNewElementDataHtml(response, false);
			}

		}, this));
	},

	/**
	 * Updates the element container with new element HTML.
	 */
	setNewElementDataHtml: function(response, append)
	{
		// Is this a brand new set of elements?
		if (!append)
		{
			this.$elements.html(response.html);
			this.$scroller.scrollTop(0);

			if (this.getSelectedSourceState('mode') == 'table')
			{
				this.$table = this.$elements.find('table:first');
				Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
			}

			this.$elementContainer = this.getElementContainer();
			var $newElements = this.$elementContainer.children();

			if (this.settings.selectable)
			{
				// Reset the element select
				if (this.elementSelect)
				{
					this.elementSelect.destroy();
					delete this.elementSelect;
				}

				this.elementSelect = this.createElementSelect($newElements);
			}

			// Should we initialize a StructureTableSorter?
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
		}
		else
		{
			var $newElements = $(response.html).appendTo(this.$elementContainer);

			if (this.settings.selectable)
			{
				this.elementSelect.addItems($newElements);
			}

			if (this.structureTableSort)
			{
				this.structureTableSort.addItems($newElements);
			}
		}

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
				this.setNewElementDataHtml(response, true);
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

	createElementSelect: function($elements)
	{
		$elements = $elements.filter(':not(.disabled)');

		return new Garnish.Select(this.$elementContainer, $elements, {
			multi:             this.settings.multiSelect,
			vertical:          (this.getSelectedSourceState('mode') != 'thumbs'),
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});
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
			$('<td class="thin"/>').prependTo(this.$toolbar.find('tr:first')).append($button);
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
		this.$elements.fadeTo('fast', 0.5);
	},

	setIndexAvailable: function()
	{
		this.$mainSpinner.addClass('hidden');
		this.isIndexBusy = false;
		this.$elements.fadeTo('fast', 1);
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

	_isStructureTableDraggingLastElements: function()
	{
		return (this.structureTableSort && this.structureTableSort.dragging && this.structureTableSort.draggingLastElements);
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
