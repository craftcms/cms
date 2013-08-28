/**
 * Element index class
 */
Craft.BaseElementIndex = Garnish.Base.extend({

	elementType: null,
	state: null,
	stateStorageId: null,
	searchTimeout: null,
	elementSelect: null,
	sourceSelect: null,

	$container: null,
	$main: null,
	$scroller: null,
	$toolbar: null,
	$search: null,
	$viewModeBtnTd: null,
	$viewModeBtnContainer: null,
	viewModeBtns: null,
	viewMode: null,
	$mainSpinner: null,
	$loadingMoreSpinner: null,
	$sidebar: null,
	$sources: null,
	sourceKey: null,
	$source: null,
	$sourceToggles: null,
	$elements: null,
	$table: null,
	$elementContainer: null,

	init: function(elementType, $container, settings)
	{
		this.elementType = elementType;
		this.$container = $container;
		this.setSettings(settings, Craft.BaseElementIndex.defaults);

		// Set the state object
		this.state = {
			source: null,
			viewStates: {}
		};

		if (typeof Storage !== 'undefined' && this.settings.id)
		{
			this.stateStorageId = 'Craft.BaseElementIndex.'+this.settings.id;

			if (typeof localStorage[this.stateStorageId] != 'undefined')
			{
				$.extend(this.state, JSON.parse(localStorage[this.stateStorageId]));
			}
		}

		// Find the DOM elements
		this.$main = this.$container.find('.main');
		this.$toolbar = this.$container.find('.toolbar:first');
		this.$search = this.$toolbar.find('.search:first input:first');
		this.$mainSpinner = this.$toolbar.find('.spinner:first');
		this.$loadingMoreSpinner = this.$container.find('.spinner.loadingmore')
		this.$sidebar = this.$container.find('.sidebar:first');
		this.$sources = this.$sidebar.find('nav a');
		this.$sourceToggles = this.$sidebar.find('.toggle');
		this.$elements = this.$container.find('.elements:first');

		// View Mode buttons
		this.viewModeBtns = {};
		this.$viewModeBtnTd = this.$toolbar.find('.viewbtns:first');
		this.$viewModeBtnContainer = $('<div class="btngroup"/>').appendTo(this.$viewModeBtnTd);

		var viewModes = [
			{ mode: 'table',     title: Craft.t('Display in a table'),     icon: 'list' },
			{ mode: 'structure', title: Craft.t('Display hierarchically'), icon: 'structure' },
			{ mode: 'thumbs',    title: Craft.t('Display as thumbnails'),  icon: 'grid' }
		];

		for (var i = 0; i < viewModes.length; i++)
		{
			var viewMode = viewModes[i],
				$viewModeBtn = $('<div class="btn" title="'+viewMode.title+'" data-icon="'+viewMode.icon+'" data-view="'+viewMode.mode+'" role="button"/>')

			this.viewModeBtns[viewMode.mode] = $viewModeBtn;

			this.addListener($viewModeBtn, 'click', { mode: viewMode.mode }, function(ev) {
				this.selectViewMode(ev.data.mode);
				this.updateElements();
			});
		}

		this.viewModeBtns.table.appendTo(this.$viewModeBtnContainer);

		// No source, no party.
		if (this.$sources.length == 0)
		{
			return;
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
		var source = this.getState('source');

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
		this.updateElements();

		// Add some listeners
		this.addListener(this.$sourceToggles, 'click', function(ev)
		{
			$(ev.currentTarget).parent().toggleClass('expanded');
			ev.stopPropagation();
		});

		// The source selector
		this.sourceSelect = new Garnish.Select(this.$sidebar.find('nav'), this.$sources, {
			selectedClass:     'sel',
			multi:             false,
			vertical:          true,
			onSelectionChange: $.proxy(this, 'onSourceChange')
		});

		this.addListener(this.$search, 'textchange', $.proxy(function()
		{
			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'updateElements'), 500);
		}, this));

		// Auto-focus the Search box
		if (!Garnish.isMobileBrowser(true))
		{
			this.$search.focus();
		}
	},

	onSourceChange: function()
	{
		var sourceElement = this.$sources.filter('.sel');
		if (sourceElement.length == 0)
		{
			sourceElement = this.$sources.filter(':first');
		}

		this.selectSource(sourceElement);
		this.updateElements();
	},

	getState: function(key)
	{
		if (typeof this.state[key] != 'undefined')
		{
			return this.state[key];
		}
		else
		{
			return null;
		}
	},

	setState: function(key, value)
	{
		if (typeof key == 'object')
		{
			$.extend(this.state, key);
		}
		else
		{
			this.state[key] = value;
		}

		this.storeState();
	},

	getViewStateForSource: function(source)
	{
		if (typeof this.state.viewStates[source] == 'undefined')
		{
			// Set it now so any modifications to it by whoever's calling this will be stored.
			this.state.viewStates[source] = {};
		}

		return this.state.viewStates[source];
	},

	getViewState: function(key)
	{
		var viewState = this.getViewStateForSource(this.getState('source'));

		if (typeof viewState[key] != 'undefined')
		{
			return viewState[key];
		}
		else
		{
			return null;
		}
	},

	setViewState: function(key, value)
	{
		var viewState = this.getViewStateForSource(this.getState('source'));

		if (typeof key == 'object')
		{
			$.extend(viewState, key);
		}
		else
		{
			viewState[key] = value;
		}

		this.state.viewStates[this.getState('source')] = viewState;
		this.storeState();
	},

	storeState: function()
	{
		if (this.stateStorageId)
		{
			// Recreate the object to prevent old, unwanted values from getting stored
			localStorage[this.stateStorageId] = JSON.stringify({
				source:     this.state.source,
				viewStates: this.state.viewStates
			});
		}
	},

	getControllerData: function()
	{
		return {
			context:            this.settings.context,
			elementType:        this.elementType,
			criteria:           this.settings.criteria,
			disabledElementIds: this.settings.disabledElementIds,
			source:             this.getState('source'),
			viewState:          this.getViewStateForSource(this.getState('source')),
			search:             (this.$search ? this.$search.val() : null)
		};
	},

	updateElements: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		if (this.getViewState('mode') == 'table' && this.$table)
		{
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response, textStatus) {

			this.$mainSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.$elements.html(response.elementContainerHtml);

				if (this.getViewState('mode') == 'table')
				{
					var $headers = this.$elements.find('thead:first th');
					this.addListener($headers, 'click', 'onSortChange');

					this.$table = this.$elements.find('table:first');
					this.$elementContainer = this.$table.find('tbody:first');

					Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
				}
				else
				{
					this.$elementContainer = this.$elements.children('ul');
				}

				this.setNewElementDataHtml(response);
			}

		}, this));
	},

	setNewElementDataHtml: function(response, append)
	{
		if (append)
		{
			this.$elementContainer.append(response.elementDataHtml);
		}
		else
		{
			this.$elementContainer.html(response.elementDataHtml);
		}

		$('head').append(response.headHtml);

		Craft.cp.setMaxSidebarHeight();

		// More?
		if (response.more)
		{
			this.totalVisible = response.totalVisible;

			this.addListener(this.$scroller, 'scroll', function()
			{
				if (
					(this.$scroller[0] == Garnish.$win[0] && ( Garnish.$win.innerHeight() + Garnish.$bod.scrollTop() >= Garnish.$bod.height() )) ||
					(this.$scroller.prop('scrollHeight') - this.$scroller.scrollTop() == this.$scroller.outerHeight())
				)
				{
					this.$loadingMoreSpinner.removeClass('hidden');
					this.removeListener(this.$scroller, 'scroll');

					var data = this.getControllerData();
					data.offset = this.totalVisible;

					Craft.postActionRequest('elements/getElements', data, $.proxy(function(response, textStatus) {

						this.$loadingMoreSpinner.addClass('hidden');

						if (textStatus == 'success')
						{
							this.setNewElementDataHtml(response, true);
						}

					}, this));
				}
			});
		}

		Craft.cp.updateResponsiveTables();

		this.onUpdateElements(append);
	},

	onUpdateElements: function(append)
	{
		this.settings.onUpdateElements(append);
	},

	onSortChange: function(ev)
	{
		var $th = $(ev.currentTarget),
			attribute = $th.attr('data-attribute');

		if (this.getViewState('order') == attribute)
		{
			if (this.getViewState('sort') == 'asc')
			{
				this.setViewState('sort', 'desc');
			}
			else
			{
				this.setViewState('sort', 'asc');
			}
		}
		else
		{
			this.setViewState({
				order: attribute,
				sort: 'asc'
			});
		}

		this.updateElements();
	},

	getSourceByKey: function(key)
	{
		for (var i = 0; i < this.$sources.length; i++)
		{
			var $source = $(this.$sources[i]);

			if ($source.data('key') == key)
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
		this.setState('source', this.sourceKey);

		this.setViewModeForNewSource();
		this.onSelectSource();
	},

	setViewModeForNewSource: function()
	{
		// Have they already visited this source?
		var viewMode = this.getViewState('mode');

		if (!viewMode || !this.doesSourceHaveViewMode(viewMode))
		{
			// Default to structure view if the source has it
			if (this.doesSourceHaveViewMode('structure'))
			{
				viewMode = 'structure';
			}
			// Otherwise try to keep using the current view mode
			else if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode))
			{
				viewMode = this.viewMode;
			}
			// Fine, use table view
			else
			{
				viewMode = 'table';
			}
		}

		this.selectViewMode(viewMode);

		// Should we be showing the buttons?
		var showViewModeBtns = false;

		for (var viewMode in this.viewModeBtns)
		{
			if (viewMode == 'table')
			{
				continue;
			}

			if (this.doesSourceHaveViewMode(viewMode))
			{
				this.viewModeBtns[viewMode].appendTo(this.$viewModeBtnContainer);
				showViewModeBtns = true;
			}
			else
			{
				this.viewModeBtns[viewMode].detach();
			}
		}

		if (showViewModeBtns)
		{
			this.$viewModeBtnTd.removeClass('hidden');
		}
		else
		{
			this.$viewModeBtnTd.addClass('hidden');
		}
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
		return (viewMode == 'table' || this.$source.data('has-'+viewMode));
	},

	selectViewMode: function(viewMode)
	{
		// Make sure it's not already selected, and that the current source supports it
		if (this.viewMode == viewMode)
		{
			return;
		}

		if (!this.doesSourceHaveViewMode(viewMode))
		{
			return;
		}

		if (this.viewMode)
		{
			this.viewModeBtns[this.viewMode].removeClass('active');
		}

		this.viewMode = viewMode;
		this.viewModeBtns[this.viewMode].addClass('active');
		this.setViewState('mode', this.viewMode);
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
		$elements.removeClass('disabled');

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
		return this.$elementContainer.children('[data-id='+elementId+']:first');
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

	setIndexBusy: function() {
		this.$mainSpinner.removeClass('hidden');
		this.isIndexBusy = true;
	},

	setIndexAvailable: function() {
		this.$mainSpinner.addClass('hidden');
		this.isIndexBusy = false;
	}
},
{
	defaults: {
		context: 'index',
		id: null,
		criteria: null,
		disabledElementIds: [],
		onUpdateElements: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop,
		onSelectSource: $.noop,
		onAfterHtmlInit: $.noop
	}
});
