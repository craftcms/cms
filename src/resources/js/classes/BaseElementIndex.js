/**
 * Element index class
 */
Craft.BaseElementIndex = Garnish.Base.extend({

	elementType: null,

	instanceState: null,
	instanceStateStorageId: null,
	sourceStates: null,
	sourceStatesStorageId: null,

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

		// Set the state objects
		this.instanceState = {
			selectedSource: null
		};

		this.sourceStates = {};

		if (typeof Storage !== 'undefined')
		{
			// Instance states (selected source) are stored by a custom storage key defined in the settings
			if (this.settings.storageKey)
			{
				this.instanceStateStorageId = 'Craft-'+Craft.siteUid+'.'+this.settings.storageKey;

				if (typeof localStorage[this.instanceStateStorageId] != 'undefined')
				{
					$.extend(this.instanceState, JSON.parse(localStorage[this.instanceStateStorageId]));
				}
			}

			// Source states (view mode, etc.) are stored by the element type and context
			this.sourceStatesStorageId = 'Craft-'+Craft.siteUid+'.BaseElementIndex.'+this.elementType+'.'+this.settings.context;

			if (typeof localStorage[this.sourceStatesStorageId] != 'undefined')
			{
				$.extend(this.sourceStates, JSON.parse(localStorage[this.sourceStatesStorageId]));
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
		var source = this.instanceState.selectedSource;

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
			onSelectionChange: $.proxy(this, 'onSourceSelectionChange')
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

	onSourceSelectionChange: function()
	{
		var sourceElement = this.$sources.filter('.sel');
		if (sourceElement.length == 0)
		{
			sourceElement = this.$sources.filter(':first');
		}

		this.selectSource(sourceElement);
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
		if (this.instanceStateStorageId)
		{
			localStorage[this.instanceStateStorageId] = JSON.stringify(this.instanceState);
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

		// Store it in localStorage too?
		if (this.sourceStatesStorageId)
		{
			localStorage[this.sourceStatesStorageId] = JSON.stringify(this.sourceStates);
		}
	},

	getControllerData: function()
	{
		return {
			context:            this.settings.context,
			elementType:        this.elementType,
			criteria:           this.settings.criteria,
			disabledElementIds: this.settings.disabledElementIds,
			source:             this.instanceState.selectedSource,
			viewState:          this.getSelectedSourceState(),
			search:             (this.$search ? this.$search.val() : null)
		};
	},

	updateElements: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		if (this.getSelectedSourceState('mode') == 'table' && this.$table)
		{
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		// Can't use structure view for search results
		if (this.getSelectedSourceState('mode') == 'structure' && this.$search && this.$search.val())
		{
			this.selectViewMode('table');
		}

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response, textStatus) {

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

			if (this.getSelectedSourceState('mode') == 'table')
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
		}
		else
		{
			this.$elementContainer.append(response.html);
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

		switch (this.getSelectedSourceState('mode'))
		{
			case 'table':
			{
				Craft.cp.updateResponsiveTables();
				break;
			}
			case 'structure':
			{
				var $parents = this.$elementContainer.find('ul').prev('.row'),
					collapsedElementIds = this.getSelectedSourceState('collapsedElementIds', []);

				for (var i = 0; i < $parents.length; i++)
				{
					var $row = $($parents[i]),
						$li = $row.parent(),
						$toggle = $('<div class="toggle" title="'+Craft.t('Show/hide children')+'"/>').prependTo($row);

					if ($.inArray($row.data('id'), collapsedElementIds) != -1)
					{
						$li.addClass('collapsed');
					}

					this.initToggle($toggle);
				}

				if (this.settings.context == 'index')
				{
					if (this.$source.data('sortable'))
					{
						this.$elementContainer.find('.add').click($.proxy(function(ev) {

							var $btn = $(ev.currentTarget);

							if (!$btn.data('menubtn'))
							{
								var elementId = $btn.parent().data('id'),
									newChildUrl = Craft.getUrl(this.$source.data('new-child-url'), 'parentId='+elementId),
									$menu = $('<div class="menu"><ul><li><a href="'+newChildUrl+'">'+Craft.t('New child')+'</a></li></ul></div>').insertAfter($btn);

								var menuBtn = new Garnish.MenuBtn($btn);
								menuBtn.showMenu();
							}

						}, this))

						this.structureDrag = new Craft.StructureDrag(this,
							this.$source.data('move-action'),
							this.$source.data('max-depth')
						);
					}
				}
			}
		}

		this.onUpdateElements(append);
	},

	initToggle: function($toggle)
	{
		$toggle.click($.proxy(function(ev) {

			var $li = $(ev.currentTarget).closest('li'),
				elementId = $li.children('.row').data('id'),
				collapsedElementIds = this.getSelectedSourceState('collapsedElementIds', []),
				viewStateKey = $.inArray(elementId, collapsedElementIds);

			if ($li.hasClass('collapsed'))
			{
				$li.removeClass('collapsed');

				if (viewStateKey != -1)
				{
					collapsedElementIds.splice(viewStateKey, 1);
				}
			}
			else
			{
				$li.addClass('collapsed');

				if (viewStateKey == -1)
				{
					collapsedElementIds.push(elementId);
				}
			}

			this.setSelecetedSourceState('collapsedElementIds', collapsedElementIds);

		}, this));
	},

	onUpdateElements: function(append)
	{
		this.settings.onUpdateElements(append);
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
		this.setInstanceState('selectedSource', this.sourceKey);

		if (this.$search)
		{
			// Clear the search value without triggering the textchange event
			this.$search.data('textchangeValue', '');
			this.$search.val('');
		}

		this.setViewModeForNewSource();
		this.onSelectSource();
	},

	setViewModeForNewSource: function()
	{
		// Have they already visited this source?
		var viewMode = this.getSelectedSourceState('mode');

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
		// Make sure that the current source supports it
		if (!this.doesSourceHaveViewMode(viewMode))
		{
			viewMode = 'table';
		}

		if (this.viewMode)
		{
			this.viewModeBtns[this.viewMode].removeClass('active');
		}

		this.viewMode = viewMode;
		this.viewModeBtns[this.viewMode].addClass('active');
		this.setSelecetedSourceState('mode', this.viewMode);
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
