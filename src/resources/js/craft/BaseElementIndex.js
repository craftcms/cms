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
	sourceSelect: null,

	$container: null,
	$main: null,
	$mainSpinner: null,
	isIndexBusy: false,

	$sidebar: null,
	showingSidebar: null,
	sourceKey: null,
	sourceViewModes: null,
	$source: null,

	$customizeSourcesBtn: null,
	customizeSourcesModal: null,

	$toolbar: null,
	$toolbarTableRow: null,
	toolbarOffset: null,

	$search: null,
	searching: false,
	searchText: null,
	$clearSearchBtn: null,

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

	$elements: null,
	$viewModeBtnTd: null,
	$viewModeBtnContainer: null,
	viewModeBtns: null,
	viewMode: null,
	view: null,
	_autoSelectElements: null,

	actions: null,
	actionsHeadHtml: null,
	actionsFootHtml: null,
	$selectAllContainer: null,
	$selectAllCheckbox: null,
	showingActionTriggers: false,
	_$triggers: null,

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
		// ---------------------------------------------------------------------

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
		// ---------------------------------------------------------------------

		this.$main = this.$container.find('.main');
		this.$toolbar = this.$container.find('.toolbar:first');
		this.$toolbarTableRow = this.$toolbar.children('table').children('tbody').children('tr');
		this.$statusMenuBtn = this.$toolbarTableRow.find('.statusmenubtn:first');
		this.$localeMenuBtn = this.$toolbarTableRow.find('.localemenubtn:first');
		this.$sortMenuBtn = this.$toolbarTableRow.find('.sortmenubtn:first');
		this.$search = this.$toolbarTableRow.find('.search:first input:first');
		this.$clearSearchBtn = this.$toolbarTableRow.find('.search:first > .clear');
		this.$mainSpinner = this.$toolbar.find('.spinner:first');
		this.$sidebar = this.$container.find('.sidebar:first');
		this.$customizeSourcesBtn = this.$sidebar.children('.customize-sources');
		this.$elements = this.$container.find('.elements:first');
		this.$viewModeBtnTd = this.$toolbarTableRow.find('.viewbtns:first');
		this.$viewModeBtnContainer = $('<div class="btngroup fullwidth"/>').appendTo(this.$viewModeBtnTd);

		// Keep the toolbar at the top of the window
		if (this.settings.context == 'index' && !Garnish.isMobileBrowser(true))
		{
			this.addListener(Garnish.$win, 'resize,scroll', 'updateFixedToolbar');
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
			onSelectionChange: $.proxy(this, '_handleSourceSelectionChange')
		});

		this._initSources($sources);

		// Customize button
		if (this.$customizeSourcesBtn.length)
		{
			this.addListener(this.$customizeSourcesBtn, 'click', 'createCustomizeSourcesModal');
		}

		// Initialize the status menu
		// ---------------------------------------------------------------------

		if (this.$statusMenuBtn.length)
		{
			this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
			this.statusMenu.on('optionselect', $.proxy(this, '_handleStatusChange'));
		}

		// Initialize the locale menu
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

			this.localeMenu.on('optionselect', $.proxy(this, '_handleLocaleChange'));

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
		else if (this.settings.criteria && this.settings.criteria.locale)
		{
			this.locale = this.settings.criteria.locale;
		}

		// Initialize the search input
		// ---------------------------------------------------------------------

		// Automatically update the elements after new search text has been sitting for a 1/2 second
		this.addListener(this.$search, 'textchange', $.proxy(function()
		{
			if (!this.searching && this.$search.val())
			{
				this.startSearching();
			}
			else if (this.searching && !this.$search.val())
			{
				this.stopSearching();
			}

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'updateElementsIfSearchTextChanged'), 500);
		}, this));

		// Update the elements when the Return key is pressed
		this.addListener(this.$search, 'keypress', $.proxy(function(ev)
		{
			if (ev.keyCode == Garnish.RETURN_KEY)
			{
				ev.preventDefault();

				if (this.searchTimeout)
				{
					clearTimeout(this.searchTimeout);
				}

				this.updateElementsIfSearchTextChanged();
			}
		}, this));

		// Clear the search when the X button is clicked
		this.addListener(this.$clearSearchBtn, 'click', $.proxy(function()
		{
			this.$search.val('');

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			if (!Garnish.isMobileBrowser(true))
			{
				this.$search.trigger('focus');
			}

			this.stopSearching();

			this.updateElementsIfSearchTextChanged();

		}, this));

		// Auto-focus the Search box
		if (!Garnish.isMobileBrowser(true))
		{
			this.$search.trigger('focus');
		}

		// Initialize the sort menu
		// ---------------------------------------------------------------------

		// Is there a sort menu?
		if (this.$sortMenuBtn.length)
		{
			this.sortMenu = this.$sortMenuBtn.menubtn().data('menubtn').menu;
			this.$sortAttributesList = this.sortMenu.$container.children('.sort-attributes');
			this.$sortDirectionsList = this.sortMenu.$container.children('.sort-directions');

			this.sortMenu.on('optionselect', $.proxy(this, '_handleSortChange'));
		}

		// Let everyone know that the UI is initialized
		// ---------------------------------------------------------------------

		this.initialized = true;
		this.afterInit();

		// Select the initial source
		// ---------------------------------------------------------------------

		var sourceKey = this.getDefaultSourceKey(),
			$source;

		if (sourceKey)
		{
			$source = this.getSourceByKey(sourceKey);

			if ($source)
			{
				// Expand any parent sources
				var $parentSources = $source.parentsUntil('.sidebar', 'li');
				$parentSources.not(':first').addClass('expanded');
			}
		}

		if (!sourceKey || !$source)
		{
			// Select the first source by default
			$source = this.$sources.first();
		}

		if ($source.length)
		{
			this.selectSource($source);
		}

		// Load the first batch of elements!
		// ---------------------------------------------------------------------

		this.updateElements();
	},

	afterInit: function()
	{
		this.onAfterInit();
	},

	get $sources()
	{
		if (!this.sourceSelect)
		{
			return undefined;
		}

		return this.sourceSelect.$items;
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

		if (Garnish.$win.width() > 992 && this.updateFixedToolbar._scrollTop > this.toolbarOffset - 7)
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
			this.addListener($toggle, 'click', '_handleSourceToggleClick');
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

	startSearching: function()
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

	stopSearching: function()
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

	storeSortAttributeAndDirection: function()
	{
		var attr = this.getSelectedSortAttribute();

		if (attr != 'score')
		{
			this.setSelecetedSourceState({
				order: attr,
				sort: this.getSelectedSortDirection()
			});
		}
	},

	/**
	 * Returns the data that should be passed to the elementIndex/getElements controller action
	 * when loading elements.
	 */
	getViewParams: function()
	{
		var criteria = $.extend({
			status: this.status,
			locale: this.locale,
			search: this.searchText,
			limit: this.settings.batchSize
		}, this.settings.criteria);

		var params = {
			context:             this.settings.context,
			elementType:         this.elementType,
			source:              this.instanceState.selectedSource,
			criteria:            criteria,
			disabledElementIds:  this.settings.disabledElementIds,
			viewState:           this.getSelectedSourceState()
		};

		// Possible that the order/sort isn't entirely accurate if we're sorting by Score
		params.viewState.order = this.getSelectedSortAttribute();
		params.viewState.sort = this.getSelectedSortDirection();

		if (this.getSelectedSortAttribute() == 'structure')
		{
			if (typeof this.instanceState.collapsedElementIds === 'undefined')
			{
				this.instanceState.collapsedElementIds = [];
			}

			params.collapsedElementIds = this.instanceState.collapsedElementIds;
		}

		return params;
	},

	updateElements: function()
	{
		// Ignore if we're not fully initialized yet
		if (!this.initialized)
		{
			return;
		}

		this.setIndexBusy();

		var params = this.getViewParams();

		Craft.postActionRequest('elementIndex/getElements', params, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				this._updateView(params, response);
			}
			else
			{
				Craft.cp.displayError(Craft.t('An unknown error occurred.'));
			}

		}, this));
	},

	updateElementsIfSearchTextChanged: function()
	{
		if (this.searchText !== (this.searchText = this.searching ? this.$search.val() : null))
		{
			this.updateElements();
		}
	},

	showActionTriggers: function()
	{
		// Ignore if they're already shown
		if (this.showingActionTriggers)
		{
			return;
		}

		// Hard-code the min toolbar height in case it was taller than the actions toolbar
		// (prevents the elements from jumping if this ends up being a double-click)
		this.$toolbar.css('min-height', this.$toolbar.height());

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

	submitAction: function(actionHandle, actionParams)
	{
		// Make sure something's selected
		var selectedElementIds = this.view.getSelectedElementIds(),
			totalSelected = selectedElementIds.length,
			totalItems = this.view.getEnabledElements.length,
			action;

		if (totalSelected == 0)
		{
			return;
		}

		// Find the action
		for (var i = 0; i < this.actions.length; i++)
		{
			if (this.actions[i].handle == actionHandle)
			{
				action = this.actions[i];
				break;
			}
		}

		if (!action || (action.confirm && !confirm(action.confirm)))
		{
			return;
		}

		// Get ready to submit
		var viewParams = this.getViewParams();

		var params = $.extend(viewParams, actionParams, {
			elementAction: actionHandle,
			elementIds: selectedElementIds
		});

		// Do it
		this.setIndexBusy();
		this._autoSelectElements = selectedElementIds;

		Craft.postActionRequest('elementIndex/performAction', params, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this._updateView(viewParams, response);

					if (response.message)
					{
						Craft.cp.displayNotice(response.message);
					}

					// There may be a new background task that needs to be run
					Craft.cp.runPendingTasks();
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

		// Unset the min toolbar height
		this.$toolbar.css('min-height', '');

		this.showingActionTriggers = false;
	},

	updateActionTriggers: function()
	{
		// Do we have an action UI to update?
		if (this.actions)
		{
			var totalSelected = this.view.getSelectedElements().length;

			if (totalSelected != 0)
			{
				if (totalSelected == this.view.getEnabledElements().length)
				{
					this.$selectAllCheckbox.removeClass('indeterminate');
					this.$selectAllCheckbox.addClass('checked');
					this.$selectAllBtn.attr('aria-checked', 'true');
				}
				else
				{
					this.$selectAllCheckbox.addClass('indeterminate');
					this.$selectAllCheckbox.removeClass('checked');
					this.$selectAllBtn.attr('aria-checked', 'mixed');
				}

				this.showActionTriggers();
			}
			else
			{
				this.$selectAllCheckbox.removeClass('indeterminate checked');
				this.$selectAllBtn.attr('aria-checked', 'false');
				this.hideActionTriggers();
			}
		}
	},

	getSelectedElements: function()
	{
		return this.view ? this.view.getSelectedElements() : $();
	},

	getSelectedElementIds: function()
	{
		return this.view ? this.view.getSelectedElementIds() : [];
	},

	getSortAttributeOption: function(attr)
	{
		return this.$sortAttributesList.find('a[data-attr="'+attr+'"]:first');
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

	getSelectedViewMode: function()
	{
		return this.getSelectedSourceState('mode');
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
		if (!$source || !$source.length)
		{
			return false;
		}

		if (this.$source && this.$source[0] && this.$source[0] == $source[0] && $source.data('key') == this.sourceKey)
		{
			return false;
		}

		this.$source = $source;
		this.sourceKey = $source.data('key');
		this.setInstanceState('selectedSource', this.sourceKey);
		this.sourceSelect.selectItem($source);

		Craft.cp.updateSidebarMenuLabel();

		if (this.searching)
		{
			// Clear the search value without causing it to update elements
			this.searchText = null;
			this.$search.val('');
			this.stopSearching();
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
		var viewMode = this.getSelectedViewMode();

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

	selectSourceByKey: function(key)
	{
		var $source = this.getSourceByKey(key);

		if ($source)
		{
			return this.selectSource($source);
		}
		else
		{
			return false;
		}
	},

	setStoredSortOptionsForSource: function()
	{
		// Default to whatever's first
		this.setSortAttribute();
		this.setSortDirection('asc');

		var sortAttr = this.getSelectedSourceState('order'),
			sortDir = this.getSelectedSourceState('sort');

		if (!sortAttr)
		{
			// Get the default
			sortAttr = this.getDefaultSort();

			if (Garnish.isArray(sortAttr))
			{
				sortDir = sortAttr[1];
				sortAttr = sortAttr[0];
			}
		}

		if (sortDir != 'asc' && sortDir != 'desc')
		{
			sortDir = 'asc';
		}

		this.setSortAttribute(sortAttr);
		this.setSortDirection(sortDir);
	},

	getDefaultSort: function()
	{
		// Does the source specify what to do?
		if (this.$source && Garnish.hasAttr(this.$source, 'data-default-sort'))
		{
			return this.$source.attr('data-default-sort').split(':');
		}
		else
		{
			// Default to whatever's first
			return [this.$sortAttributesList.find('a:first').data('attr'), 'asc'];
		}
	},

	getViewModesForSource: function()
	{
		var viewModes = [
			{ mode: 'table', title: Craft.t('Display in a table'), icon: 'list' }
		];

		if (this.$source && Garnish.hasAttr(this.$source, 'data-has-thumbs'))
		{
			viewModes.push({ mode: 'thumbs', title: Craft.t('Display as thumbnails'), icon: 'grid' });
		}

		return viewModes;
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

	createView: function(mode, settings)
	{
		var viewClass = this.getViewClass(mode);
		return new viewClass(this, this.$elements, settings);
	},

	getViewClass: function(mode)
	{
		switch (mode)
		{
			case 'table':
				return Craft.TableElementIndexView;
			case 'thumbs':
				return Craft.ThumbsElementIndexView;
			default:
				throw 'View mode "'+mode+'" not supported.';
		}
	},

	rememberDisabledElementId: function(id)
	{
		var index = $.inArray(id, this.settings.disabledElementIds);

		if (index == -1)
		{
			this.settings.disabledElementIds.push(id);
		}
	},

	forgetDisabledElementId: function(id)
	{
		var index = $.inArray(id, this.settings.disabledElementIds);

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
			var id = $($elements[i]).data('id');
			this.forgetDisabledElementId(id);
		}

		this.onEnableElements($elements);
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var id = $($elements[i]).data('id');
			this.rememberDisabledElementId(id);
		}

		this.onDisableElements($elements);
	},

	getElementById: function(id)
	{
		return this.view.getElementById(id);
	},

	enableElementsById: function(ids)
	{
		ids = $.makeArray(ids);

		for (var i = 0; i < ids.length; i++)
		{
			var id = ids[i],
				$element = this.getElementById(id);

			if ($element && $element.length)
			{
				this.enableElements($element);
			}
			else
			{
				this.forgetDisabledElementId(id);
			}
		}
	},

	disableElementsById: function(ids)
	{
		ids = $.makeArray(ids);

		for (var i = 0; i < ids.length; i++)
		{
			var id = ids[i],
				$element = this.getElementById(id);

			if ($element && $element.length)
			{
				this.disableElements($element);
			}
			else
			{
				this.rememberDisabledElementId(id);
			}
		}
	},

	selectElementAfterUpdate: function(id)
	{
		if (this._autoSelectElements === null)
		{
			this._autoSelectElements = [];
		}

		this._autoSelectElements.push(id);
	},

	addButton: function($button)
	{
		this.getButtonContainer().append($button);
	},

	isShowingSidebar: function()
	{
		if (this.showingSidebar === null)
		{
			this.showingSidebar = (this.$sidebar.length && !this.$sidebar.hasClass('hidden'));
		}

		return this.showingSidebar;
	},

	getButtonContainer: function()
	{
		// Is there a predesignated place where buttons should go?
		if (this.settings.buttonContainer)
		{
			return $(this.settings.buttonContainer);
		}
		else
		{
			// Add it to the page header
			var $container = $('#extra-headers > .buttons:first');

			if (!$container.length)
			{
				var $extraHeadersContainer = $('#extra-headers');

				if (!$extraHeadersContainer.length)
				{
					$extraHeadersContainer = $('<div id="extra-headers"/>').appendTo($('#page-header'));
				}

				$container = $('<div class="buttons right"/>').appendTo($extraHeadersContainer);
			}

			return $container;
		}
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

	createCustomizeSourcesModal: function()
	{
		// Recreate it each time
		var modal = new Craft.CustomizeSourcesModal(this, {
			onHide: function() {
				modal.destroy();
			}
		});

		return modal;
	},

	disable: function()
	{
		if (this.sourceSelect)
		{
			this.sourceSelect.disable();
		}

		if (this.view)
		{
			this.view.disable();
		}

		this.base();
	},

	enable: function()
	{
		if (this.sourceSelect)
		{
			this.sourceSelect.enable();
		}

		if (this.view)
		{
			this.view.enable();
		}

		this.base();
	},

	// Events
	// =========================================================================

	onAfterInit: function()
	{
		this.settings.onAfterInit();
		this.trigger('afterInit');
	},

	onSelectSource: function()
	{
		this.settings.onSelectSource(this.sourceKey);
		this.trigger('selectSource', {sourceKey: this.sourceKey});
	},

	onUpdateElements: function()
	{
		this.settings.onUpdateElements();
		this.trigger('updateElements');
	},

	onSelectionChange: function()
	{
		this.settings.onSelectionChange();
		this.trigger('selectionChange');
	},

	onEnableElements: function($elements)
	{
		this.settings.onEnableElements($elements);
		this.trigger('enableElements', {elements: $elements});
	},

	onDisableElements: function($elements)
	{
		this.settings.onDisableElements($elements);
		this.trigger('disableElements', {elements: $elements});
	},

	// Private methods
	// =========================================================================

	// UI state handlers
	// -------------------------------------------------------------------------

	_handleSourceSelectionChange: function()
	{
		// If the selected source was just removed (maybe because its parent was collapsed),
		// there won't be a selected source
		if (!this.sourceSelect.totalSelected)
		{
			this.sourceSelect.selectItem(this.$sources.first());
			return;
		}

		if (this.selectSource(this.sourceSelect.$selectedItems))
		{
			this.updateElements();
		}
	},

	_handleActionTriggerSubmit: function(ev)
	{
		ev.preventDefault();

		var $form = $(ev.currentTarget);

		// Make sure Craft.ElementActionTrigger isn't overriding this
		if ($form.hasClass('disabled') || $form.data('custom-handler'))
		{
			return;
		}

		var actionHandle = $form.data('action'),
			params = Garnish.getPostData($form);

		this.submitAction(actionHandle, params);
	},

	_handleMenuActionTriggerSubmit: function(ev)
	{
		var $option = $(ev.option);

		// Make sure Craft.ElementActionTrigger isn't overriding this
		if ($option.hasClass('disabled') || $option.data('custom-handler'))
		{
			return;
		}

		var actionHandle = $option.data('action');
		this.submitAction(actionHandle);
	},

	_handleStatusChange: function(ev)
	{
		this.statusMenu.$options.removeClass('sel');
		var $option = $(ev.selectedOption).addClass('sel');
		this.$statusMenuBtn.html($option.html());

		this.status = $option.data('status');
		this.updateElements();
	},

	_handleLocaleChange: function(ev)
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

	_handleSortChange: function(ev)
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

		this.storeSortAttributeAndDirection();
		this.updateElements();
	},

	_handleSelectionChange: function()
	{
		this.updateActionTriggers();
		this.onSelectionChange();
	},

	_handleSourceToggleClick: function(ev)
	{
		this._toggleSource($(ev.currentTarget).prev('a'));
		ev.stopPropagation();
	},

	// Source managemnet
	// -------------------------------------------------------------------------

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

		var $childSources = this._getChildSources($source);
		this._initSources($childSources);
	},

	_collapseSource: function($source)
	{
		$source.parent('li').removeClass('expanded');

		var $childSources = this._getChildSources($source);
		this._deinitSources($childSources);
	},

	// View
	// -------------------------------------------------------------------------

	_updateView: function(params, response)
	{
		// Cleanup
		// -------------------------------------------------------------

		// Kill the old view class
		if (this.view)
		{
			this.view.destroy();
			delete this.view;
		}

		// Get rid of the old action triggers regardless of whether the new batch has actions or not
		if (this.actions)
		{
			this.hideActionTriggers();
			this.actions = this.actionsHeadHtml = this.actionsFootHtml = this._$triggers = null;
		}

		if (this.$selectAllContainer)
		{
			// Git rid of the old select all button
			this.$selectAllContainer.detach();
		}

		// Batch actions setup
		// -------------------------------------------------------------

		if (this.settings.context == 'index' && response.actions && response.actions.length)
		{
			this.actions = response.actions;
			this.actionsHeadHtml = response.actionsHeadHtml;
			this.actionsFootHtml = response.actionsFootHtml;

			// First time?
			if (!this.$selectAllContainer)
			{
				// Create the select all button
				this.$selectAllContainer = $('<td class="selectallcontainer thin"/>');
				this.$selectAllBtn = $('<div class="btn" />').appendTo(this.$selectAllContainer);
				this.$selectAllCheckbox = $('<div class="checkbox"/>').appendTo(this.$selectAllBtn);

				this.$selectAllBtn.attr({
					'role': 'checkbox',
					'tabindex': '0',
					'aria-checked': 'false',
				});

				this.addListener(this.$selectAllBtn, 'click', function()
				{
					if (this.view.getSelectedElements().length == 0)
					{
						this.view.selectAllElements();
					}
					else
					{
						this.view.deselectAllElements();
					}
				});

				this.addListener(this.$selectAllBtn, 'keydown', function(ev)
				{
					if(ev.keyCode == Garnish.SPACE_KEY)
					{
						ev.preventDefault();

						$(ev.currentTarget).trigger('click');
					}
				});
			}
			else
			{
				// Reset the select all button
				this.$selectAllCheckbox.removeClass('indeterminate checked');

				this.$selectAllBtn.attr('aria-checked', 'false');
			}

			// Place the select all button at the beginning of the toolbar
			this.$selectAllContainer.prependTo(this.$toolbarTableRow);
		}

		// Update the view with the new container + elements HTML
		// -------------------------------------------------------------

		this.$elements.html(response.html);
		Craft.appendHeadHtml(response.headHtml);
		Craft.appendFootHtml(response.footHtml);
		picturefill();

		// Create the view
		// -------------------------------------------------------------

		// Should we make the view selectable?
		var selectable = (this.actions || this.settings.selectable);

		this.view = this.createView(this.getSelectedViewMode(), {
			context: this.settings.context,
			batchSize: this.settings.batchSize,
			params: params,
			selectable: selectable,
			multiSelect: (this.actions || this.settings.multiSelect),
			checkboxMode: (this.settings.context == 'index' && this.actions),
			onSelectionChange: $.proxy(this, '_handleSelectionChange')
		});

		// Auto-select elements
		// -------------------------------------------------------------

		if (this._autoSelectElements)
		{
			if (selectable)
			{
				for (var i = 0; i < this._autoSelectElements.length; i++)
				{
					this.view.selectElementById(this._autoSelectElements[i]);
				}
			}

			this._autoSelectElements = null;
		}

		// Trigger the event
		// -------------------------------------------------------------

		this.onUpdateElements();
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

				this.addListener($form, 'submit', '_handleActionTriggerSubmit');
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

		var $btn;

		if (safeMenuActions.length || destructiveMenuActions.length)
		{
			var $menuTrigger = $('<form/>');
			$btn = $('<div class="btn menubtn" data-icon="settings" title="'+Craft.t('Actions')+'"/>').appendTo($menuTrigger);
			var $menu = $('<ul class="menu"/>').appendTo($menuTrigger),
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
		Craft.appendHeadHtml(this.actionsHeadHtml);
		Craft.appendFootHtml(this.actionsFootHtml);

		Craft.initUiElements(this._$triggers);

		if ($btn)
		{
			$btn.data('menubtn').on('optionSelect', $.proxy(this, '_handleMenuActionTriggerSubmit'));
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
		modal: null,
		storageKey: null,
		criteria: null,
		batchSize: 50,
		disabledElementIds: [],
		selectable: false,
		multiSelect: false,
		buttonContainer: null,

		onAfterInit: $.noop,
		onSelectSource: $.noop,
		onUpdateElements: $.noop,
		onSelectionChange: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop
	}
});

