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
	$viewBtns: null,
	$viewBtn: null,
	$mainSpinner: null,
	$loadingMoreSpinner: null,
	$sidebar: null,
	$sources: null,
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
		this.state = {};

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
		this.$viewBtns = this.$toolbar.find('.btngroup .btn');
		this.$mainSpinner = this.$toolbar.find('.spinner:first');
		this.$loadingMoreSpinner = this.$container.find('.spinner.loadingmore')
		this.$sidebar = this.$container.find('.sidebar:first');
		this.$sources = this.$sidebar.find('nav a');
		this.$sourceToggles = this.$sidebar.find('.toggle');
		this.$elements = this.$container.find('.elements:first');

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

		// Select the initial view mode
		var view = this.getState('view');

		if (view)
		{
			var $viewBtn = this.$viewBtns.filter('[data-view='+view+']:first');
		}

		if (!view || !$viewBtn.length)
		{
			var $viewBtn = this.$viewBtns.filter('[data-view=table]:first');
		}

		if ($viewBtn.length)
		{
			this.selectView($viewBtn);
		}
		else
		{
			this.setState('view', 'table');
		}

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
			waitForDblClick:   false,
			vertical:          true,
			onSelectionChange: $.proxy(this, '_onSourceChange')
		});

		this.addListener(this.$viewBtns, 'click', function(ev)
		{
			this.selectView($(ev.currentTarget));
			this.updateElements();
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

	_onSourceChange: function ()
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

		if (this.stateStorageId)
		{
			localStorage[this.stateStorageId] = JSON.stringify(this.state);
		}
	},

	getControllerData: function()
	{
		return {
			context:            this.settings.context,
			elementType:        this.elementType,
			criteria:           this.settings.criteria,
			disabledElementIds: this.settings.disabledElementIds,
			state:              this.state,
			search:             (this.$search ? this.$search.val() : null)
		};
	},

	updateElements: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		if (this.getState('view') == 'table' && this.$table)
		{
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response)
		{
			this.$mainSpinner.addClass('hidden');

			this.$elements.html(response.elementContainerHtml);

			if (this.getState('view') == 'table')
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

					Craft.postActionRequest('elements/getElements', data, $.proxy(function(response)
					{
						this.$loadingMoreSpinner.addClass('hidden');

						this.setNewElementDataHtml(response, true);
					}, this));
				}
			});
		}

		Craft.cp.updateResponsiveTables();

		this.onUpdateElements(append);
	},

	onUpdateElements: function (append)
	{
		this.settings.onUpdateElements(append);
	},

	onSortChange: function(ev)
	{
		var $th = $(ev.currentTarget),
			attribute = $th.attr('data-attribute');

		if (this.getState('order') == attribute)
		{
			if (this.getState('sort') == 'asc')
			{
				this.setState('sort', 'desc');
			}
			else
			{
				this.setState('sort', 'asc');
			}
		}
		else
		{
			this.setState({
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
		if (this.$source)
		{
			this.$source.removeClass('sel');
		}

		var sourceKey = $source.data('key');
		this.$source = $source.addClass('sel');
		this.setState('source', sourceKey);

		this.onSelectSource(sourceKey);
	},

	onSelectSource: function(sourceKey)
	{
		this.settings.onSelectSource(sourceKey);
	},

	onAfterHtmlInit: function ()
	{
		this.settings.onAfterHtmlInit()
	},

	selectView: function($viewBtn)
	{
		if (this.$viewBtn)
		{
			this.$viewBtn.removeClass('active');
		}

		this.$viewBtn = $viewBtn.addClass('active');
		this.setState('view', $viewBtn.data('view'));
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

	setElementSelect: function (obj)
	{
		this.elementSelect = obj;
	},

	addCallback: function (currentCallback, newCallback)
	{
		return $.proxy(function () {
			if (typeof currentCallback == 'function')
			{
				currentCallback.apply(this, arguments);
			}
			newCallback.apply(this, arguments);
		}, this);
	},

	setIndexBusy: function () {
		this.$mainSpinner.removeClass('hidden');
		this.isIndexBusy = true;
	},

	setIndexAvailable: function () {
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
