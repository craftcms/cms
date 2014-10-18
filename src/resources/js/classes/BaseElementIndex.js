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

	$container: null,
	$main: null,
	$scroller: null,
	$toolbar: null,
	$search: null,
	$clearSearchBtn: null,
	$mainSpinner: null,

	$statusMenuBtn: null,
	statusMenu: null,
	status: null,

	$localeMenuBtn: null,
	localeMenu: null,
	locale: null,

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

		this.selectSource($source);

		// Load up the elements!
		this.initialized = true;
		this.updateElements();

		// Status changes
		if (this.$statusMenuBtn.length)
		{
			this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
			this.statusMenu.on('optionselect', $.proxy(this, 'onStatusChange'));
		}

		this.addListener(this.$search, 'textchange', $.proxy(function()
		{
			if (this.$search.val())
			{
				this.$clearSearchBtn.removeClass('hidden');
			}
			else
			{
				this.$clearSearchBtn.addClass('hidden');
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
			this.$clearSearchBtn.addClass('hidden');

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			if (!Garnish.isMobileBrowser(true))
			{
				this.$search.focus();
			}

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
		this.selectSource(this.sourceSelect.$selectedItems);
		this.updateElements();
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
		return {
			context:            this.settings.context,
			elementType:        this.elementType,
			criteria:           $.extend({ status: this.status, locale: this.locale }, this.settings.criteria),
			disabledElementIds: this.settings.disabledElementIds,
			source:             this.instanceState.selectedSource,
			status:             this.status,
			viewState:          this.getSelectedSourceState(),
			search:             (this.$search ? this.$search.val() : null)
		};
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

		// Can't use structure view for search results
		if (this.$search && this.$search.val())
		{
			if (this.getSelectedSourceState('mode') == 'structure')
			{
				this.selectViewMode('table', true);
			}
		}
		else if (this.getSelectedSourceState('mode') == 'table' && !this.doesSourceHaveViewMode('table'))
		{
			this.selectViewMode(this.sourceViewModes[0].mode, true);
		}

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response, textStatus)
		{
			this.$mainSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.setNewElementDataHtml(response, false);
			}

		}, this));
	},

	setNewElementDataHtml: function(response, append)
	{
		if (!append)
		{
			this.$elements.html(response.html);
			this.$scroller.scrollTop(0);

			if (this.getSelectedSourceState('mode') == 'table')
			{
				var $headers = this.$elements.find('thead:first th');
				this.addListener($headers, 'click', 'onSortChange');

				this.$table = this.$elements.find('table:first');
				Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
			}

			this.$elementContainer = this.getElementContainer();
		}
		else
		{
			this.$elementContainer.append(response.html);
		}

		$('head').append(response.headHtml);
		Garnish.$bod.append(response.footHtml);

		// More?
		if (response.more)
		{
			this.totalVisible = response.totalVisible;

			this.addListener(this.$scroller, 'scroll', function()
			{
				if (this.$scroller[0] == Garnish.$win[0])
				{
					var winHeight = Garnish.$win.innerHeight(),
						winScrollTop = Garnish.$win.scrollTop(),
						bodHeight = Garnish.$bod.height();

					var loadMore = (winHeight + winScrollTop >= bodHeight);
				}
				else
				{
					var containerScrollHeight = this.$scroller.prop('scrollHeight'),
						containerScrollTop = this.$scroller.scrollTop(),
						containerHeight = this.$scroller.outerHeight();

					var loadMore = (containerScrollHeight - containerScrollTop <= containerHeight + 15);
				}

				if (loadMore)
				{
					this.$loadingMoreSpinner.removeClass('hidden');
					this.removeListener(this.$scroller, 'scroll');

					var data = this.getControllerData();
					data.offset = this.totalVisible;

					Craft.postActionRequest('elements/getElements', data, $.proxy(function(response, textStatus)
					{
						this.$loadingMoreSpinner.addClass('hidden');

						if (textStatus == 'success')
						{
							this.setNewElementDataHtml(response, true);
						}

					}, this));
				}
			});
		}

		if (this.getSelectedSourceState('mode') == 'table')
		{
			Craft.cp.updateResponsiveTables();
		}

		this.onUpdateElements(append);
	},

	getElementContainer: function()
	{
		if (this.getSelectedSourceState('mode') == 'table')
		{
			return this.$table.find('tbody:first');
		}
		else
		{
			return this.$elements.children('ul');
		}
	},

	onUpdateElements: function(append)
	{
		this.settings.onUpdateElements(append);
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

	onSortChange: function(ev)
	{
		var $th = $(ev.currentTarget),
			attribute = $th.attr('data-attribute');

		if (this.getSelectedSourceState('order') == attribute)
		{
			if (this.getSelectedSourceState('sort') == 'asc')
			{
				this.setSelecetedSourceState('sort', 'desc');
			}
			else
			{
				this.setSelecetedSourceState('sort', 'asc');
			}
		}
		else
		{
			this.setSelecetedSourceState({
				order: attribute,
				sort: 'asc'
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
		if (this.$source == $source)
		{
			return;
		}

		if (this.$source)
		{
			this.$source.removeClass('sel');
		}

		this.sourceKey = $source.data('key');
		this.$source = $source.addClass('sel');
		this.setInstanceState('selectedSource', this.sourceKey);

		if (this.$search)
		{
			// Clear the search value without triggering the textchange event
			this.$search.data('textchangeValue', '');
			this.$search.val('');
		}

		// View mode buttons

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
			// Default to structure view if the source has it
			if (Garnish.hasAttr(this.$source, 'data-has-structure'))
			{
				viewMode = 'structure';
			}
			// Otherwise try to keep using the current view mode
			else if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode))
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
	},

	getViewModesForSource: function()
	{
		var viewModes = [
			{ mode: 'table', title: Craft.t('Display in a table'), icon: 'list' }
		];

		if (Garnish.hasAttr(this.$source, 'data-has-structure'))
		{
			viewModes.push({ mode: 'structure', title: Craft.t('Display hierarchically'), icon: 'structure' });
		}

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

	setElementSelect: function(obj)
	{
		this.elementSelect = obj;
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
		onUpdateElements: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop,
		onSelectSource: $.noop,
		onAfterHtmlInit: $.noop
	}
});
