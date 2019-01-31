/** global: Craft */
/** global: Garnish */
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
        sourcesByKey: null,
        $visibleSources: null,

        $customizeSourcesBtn: null,
        customizeSourcesModal: null,

        $toolbar: null,
        $toolbarFlexContainer: null,
        toolbarOffset: null,

        $search: null,
        searching: false,
        searchText: null,
        trashed: false,
        $clearSearchBtn: null,

        $statusMenuBtn: null,
        statusMenu: null,
        status: null,

        $siteMenuBtn: null,
        siteMenu: null,
        siteId: null,

        $sortMenuBtn: null,
        sortMenu: null,
        $sortAttributesList: null,
        $sortDirectionsList: null,
        $scoreSortAttribute: null,
        $structureSortAttribute: null,

        $elements: null,
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
        _$detachedToolbarItems: null,
        _$triggers: null,

        // Public methods
        // =========================================================================

        /**
         * Constructor
         */
        init: function(elementType, $container, settings) {
            this.elementType = elementType;
            this.$container = $container;
            this.setSettings(settings, Craft.BaseElementIndex.defaults);

            // Set the state objects
            // ---------------------------------------------------------------------

            this.instanceState = this.getDefaultInstanceState();

            this.sourceStates = {};

            // Instance states (selected source) are stored by a custom storage key defined in the settings
            if (this.settings.storageKey) {
                $.extend(this.instanceState, Craft.getLocalStorage(this.settings.storageKey), {});
            }

            // Source states (view mode, etc.) are stored by the element type and context
            this.sourceStatesStorageKey = 'BaseElementIndex.' + this.elementType + '.' + this.settings.context;
            $.extend(this.sourceStates, Craft.getLocalStorage(this.sourceStatesStorageKey, {}));

            // Find the DOM elements
            // ---------------------------------------------------------------------

            this.$main = this.$container.find('.main');
            this.$toolbar = this.$container.find('.toolbar:first');
            this.$toolbarFlexContainer = this.$toolbar.children('.flex');
            this.$statusMenuBtn = this.$toolbarFlexContainer.find('.statusmenubtn:first');
            this.$siteMenuBtn = this.$container.find('.sitemenubtn:first');
            this.$sortMenuBtn = this.$toolbarFlexContainer.find('.sortmenubtn:first');
            this.$search = this.$toolbarFlexContainer.find('.search:first input:first');
            this.$clearSearchBtn = this.$toolbarFlexContainer.find('.search:first > .clear');
            this.$mainSpinner = this.$toolbarFlexContainer.find('.spinner:first');
            this.$sidebar = this.$container.find('.sidebar:first');
            this.$customizeSourcesBtn = this.$sidebar.find('.customize-sources');
            this.$elements = this.$container.find('.elements:first');

            // Hide sidebar if needed
            if (this.settings.hideSidebar) {
                this.$sidebar.hide();
                $('.body, .content', this.$container).removeClass('has-sidebar');
            }

            // Keep the toolbar at the top of the window
            if (
                (this.settings.toolbarFixed || (this.settings.toolbarFixed === null && this.settings.context === 'index')) &&
                !Garnish.isMobileBrowser(true)
            ) {
                this.addListener(Garnish.$win, 'resize', 'updateFixedToolbar');
                this.addListener(Garnish.$scrollContainer, 'scroll', 'updateFixedToolbar');
            }

            // Initialize the sources
            // ---------------------------------------------------------------------

            if (!this.initSources()) {
                return;
            }

            // Customize button
            if (this.$customizeSourcesBtn.length) {
                this.addListener(this.$customizeSourcesBtn, 'click', 'createCustomizeSourcesModal');
            }

            // Initialize the status menu
            // ---------------------------------------------------------------------

            if (this.$statusMenuBtn.length) {
                this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
                this.statusMenu.on('optionselect', $.proxy(this, '_handleStatusChange'));
            }

            // Initialize the site menu
            // ---------------------------------------------------------------------

            // Is there a site menu?
            if (this.$siteMenuBtn.length) {
                this.siteMenu = this.$siteMenuBtn.menubtn().data('menubtn').menu;

                // Figure out the initial site
                var $option = this.siteMenu.$options.filter('.sel:first');

                if (!$option.length) {
                    $option = this.siteMenu.$options.first();
                }

                if ($option.length) {
                    this._setSite($option.data('site-id'));
                }
                else {
                    // No site options -- they must not have any site permissions
                    this.settings.criteria = {id: '0'};
                }

                this.siteMenu.on('optionselect', $.proxy(this, '_handleSiteChange'));

                if (this.siteId) {
                    // Do we have a different site stored in localStorage?
                    var storedSiteId = Craft.getLocalStorage('BaseElementIndex.siteId');

                    if (storedSiteId && storedSiteId != this.siteId) {
                        // Is that one available here?
                        var $storedSiteOption = this.siteMenu.$options.filter('[data-site-id="' + storedSiteId + '"]:first');

                        if ($storedSiteOption.length) {
                            // Todo: switch this to siteMenu.selectOption($storedSiteOption) once Menu is updated to support that
                            $storedSiteOption.trigger('click');
                        }
                    }
                }
            }
            else if (this.settings.criteria && this.settings.criteria.siteId) {
                this._setSite(this.settings.criteria.siteId);
            } else {
                this._setSite(Craft.siteId);
            }

            // Initialize the search input
            // ---------------------------------------------------------------------

            // Automatically update the elements after new search text has been sitting for a 1/2 second
            this.addListener(this.$search, 'textchange', $.proxy(function() {
                if (!this.searching && this.$search.val()) {
                    this.startSearching();
                }
                else if (this.searching && !this.$search.val()) {
                    this.stopSearching();
                }

                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }

                this.searchTimeout = setTimeout($.proxy(this, 'updateElementsIfSearchTextChanged'), 500);
            }, this));

            // Update the elements when the Return key is pressed
            this.addListener(this.$search, 'keypress', $.proxy(function(ev) {
                if (ev.keyCode === Garnish.RETURN_KEY) {
                    ev.preventDefault();

                    if (this.searchTimeout) {
                        clearTimeout(this.searchTimeout);
                    }

                    this.updateElementsIfSearchTextChanged();
                }
            }, this));

            // Clear the search when the X button is clicked
            this.addListener(this.$clearSearchBtn, 'click', $.proxy(function() {
                this.$search.val('');

                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }

                if (!Garnish.isMobileBrowser(true)) {
                    this.$search.trigger('focus');
                }

                this.stopSearching();

                this.updateElementsIfSearchTextChanged();

            }, this));

            // Auto-focus the Search box
            if (!Garnish.isMobileBrowser(true)) {
                this.$search.trigger('focus');
            }

            // Initialize the sort menu
            // ---------------------------------------------------------------------

            // Is there a sort menu?
            if (this.$sortMenuBtn.length) {
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

            this.selectDefaultSource();

            // Load the first batch of elements!
            // ---------------------------------------------------------------------

            this.updateElements();
        },

        afterInit: function() {
            this.onAfterInit();
        },

        getSourceContainer: function() {
            return this.$sidebar.find('nav>ul');
        },

        get $sources() {
            if (!this.sourceSelect) {
                return undefined;
            }

            return this.sourceSelect.$items;
        },

        initSources: function() {
            var $sources = this._getSourcesInList(this.getSourceContainer());

            // No source, no party.
            if ($sources.length === 0) {
                return false;
            }

            // The source selector
            if (!this.sourceSelect) {
                this.sourceSelect = new Garnish.Select(this.$sidebar.find('nav'), {
                    multi: false,
                    allowEmpty: false,
                    vertical: true,
                    onSelectionChange: $.proxy(this, '_handleSourceSelectionChange')
                });
            }

            this.sourcesByKey = {};
            this._initSources($sources);

            return true;
        },

        selectDefaultSource: function() {
            var sourceKey = this.getDefaultSourceKey(),
                $source;

            if (sourceKey) {
                $source = this.getSourceByKey(sourceKey);

                // Make sure it's visible
                if (this.$visibleSources.index($source) === -1) {
                    $source = null;
                }
            }

            if (!sourceKey || !$source) {
                // Select the first source by default
                $source = this.$visibleSources.first();
            }

            if ($source.length) {
                this.selectSource($source);
            }
        },

        refreshSources: function() {
            this.sourceSelect.removeAllItems();

            var params = {
                context: this.settings.context,
                elementType: this.elementType
            };

            this.setIndexBusy();

            Craft.postActionRequest(this.settings.refreshSourcesAction, params, $.proxy(function(response, textStatus) {
                this.setIndexAvailable();

                if (textStatus === 'success') {
                    this.getSourceContainer().replaceWith(response.html);
                    this.initSources();
                    this.selectDefaultSource();
                }
                else {
                    Craft.cp.displayError(Craft.t('app', 'An unknown error occurred.'));
                }

            }, this));
        },

        updateFixedToolbar: function(e) {
            this.updateFixedToolbar._scrollTop = Garnish.$scrollContainer.scrollTop();

            if (Garnish.$win.width() > 992 && this.updateFixedToolbar._scrollTop >= 17) {
                if (this.updateFixedToolbar._makingFixed = !this.$toolbar.hasClass('fixed')) {
                    this.$elements.css('padding-top', (this.$toolbar.outerHeight() + 21));
                    this.$toolbar.addClass('fixed');
                }

                if (this.updateFixedToolbar._makingFixed || e.type === 'resize') {
                    this.$toolbar.css({
                        top: Garnish.$scrollContainer.offset().top,
                        width: this.$main.width()
                    });
                }
            }
            else {
                if (this.$toolbar.hasClass('fixed')) {
                    this.$toolbar.removeClass('fixed');
                    this.$toolbar.css('width', '');
                    this.$elements.css('padding-top', '');
                    this.$toolbar.css('top', '0');
                }
            }
        },

        initSource: function($source) {
            this.sourceSelect.addItems($source);
            this.initSourceToggle($source);
            this.sourcesByKey[$source.data('key')] = $source;

            if ($source.data('hasNestedSources') && this.instanceState.expandedSources.indexOf($source.data('key')) !== -1) {
                this._expandSource($source);
            }
        },

        initSourceToggle: function($source) {
            var $toggle = this._getSourceToggle($source);

            if ($toggle.length) {
                // Remove handlers for the same thing. Just in case.
                this.removeListener($toggle, 'click', '_handleSourceToggleClick');

                this.addListener($toggle, 'click', '_handleSourceToggleClick');
                $source.data('hasNestedSources', true);
            } else {
                $source.data('hasNestedSources', false);
            }
        },

        deinitSource: function($source) {
            this.sourceSelect.removeItems($source);
            this.deinitSourceToggle($source);
            delete this.sourcesByKey[$source.data('key')];
        },

        deinitSourceToggle: function($source) {
            if ($source.data('hasNestedSources')) {
                var $toggle = this._getSourceToggle($source);
                this.removeListener($toggle, 'click');
            }

            $source.removeData('hasNestedSources');
        },

        getDefaultInstanceState: function() {
            return {
                selectedSource: null,
                expandedSources: []
            };
        },

        getDefaultSourceKey: function() {
            return this.instanceState.selectedSource;
        },

        getDefaultExpandedSources: function() {
            return this.instanceState.expandedSources;
        },

        startSearching: function() {
            // Show the clear button and add/select the Score sort option
            this.$clearSearchBtn.removeClass('hidden');

            if (!this.$scoreSortAttribute) {
                this.$scoreSortAttribute = $('<li><a data-attr="score">' + Craft.t('app', 'Score') + '</a></li>');
                this.sortMenu.addOptions(this.$scoreSortAttribute.children());
            }

            this.$scoreSortAttribute.prependTo(this.$sortAttributesList);

            this.searching = true;

            this._updateStructureSortOption();
            this.setSortAttribute('score');
        },

        stopSearching: function() {
            // Hide the clear button and Score sort option
            this.$clearSearchBtn.addClass('hidden');

            this.$scoreSortAttribute.detach();

            this.searching = false;

            this._updateStructureSortOption();
        },

        setInstanceState: function(key, value) {
            if (typeof key === 'object') {
                $.extend(this.instanceState, key);
            }
            else {
                this.instanceState[key] = value;
            }

            this.storeInstanceState();
        },

        storeInstanceState: function() {
            if (this.settings.storageKey) {
                Craft.setLocalStorage(this.settings.storageKey, this.instanceState);
            }
        },

        getSourceState: function(source, key, defaultValue) {
            if (typeof this.sourceStates[source] === 'undefined') {
                // Set it now so any modifications to it by whoever's calling this will be stored.
                this.sourceStates[source] = {};
            }

            if (typeof key === 'undefined') {
                return this.sourceStates[source];
            }
            else if (typeof this.sourceStates[source][key] !== 'undefined') {
                return this.sourceStates[source][key];
            }
            else {
                return (typeof defaultValue !== 'undefined' ? defaultValue : null);
            }
        },

        getSelectedSourceState: function(key, defaultValue) {
            return this.getSourceState(this.instanceState.selectedSource, key, defaultValue);
        },

        setSelecetedSourceState: function(key, value) {
            var viewState = this.getSelectedSourceState();

            if (typeof key === 'object') {
                $.extend(viewState, key);
            }
            else {
                viewState[key] = value;
            }

            this.sourceStates[this.instanceState.selectedSource] = viewState;

            // Store it in localStorage too
            Craft.setLocalStorage(this.sourceStatesStorageKey, this.sourceStates);
        },

        storeSortAttributeAndDirection: function() {
            var attr = this.getSelectedSortAttribute();

            if (attr !== 'score') {
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
        getViewParams: function() {
            var criteria = $.extend({
                status: this.status,
                siteId: this.siteId,
                search: this.searchText,
                limit: this.settings.batchSize,
                trashed: this.trashed ? 1 : 0
            }, this.settings.criteria);

            var params = {
                context: this.settings.context,
                elementType: this.elementType,
                source: this.instanceState.selectedSource,
                criteria: criteria,
                disabledElementIds: this.settings.disabledElementIds,
                viewState: $.extend({}, this.getSelectedSourceState())
            };

            // Possible that the order/sort isn't entirely accurate if we're sorting by Score
            params.viewState.order = this.getSelectedSortAttribute();
            params.viewState.sort = this.getSelectedSortDirection();

            if (this.getSelectedSortAttribute() === 'structure') {
                if (typeof this.instanceState.collapsedElementIds === 'undefined') {
                    this.instanceState.collapsedElementIds = [];
                }
                params.collapsedElementIds = this.instanceState.collapsedElementIds;
            }

            return params;
        },

        updateElements: function() {
            // Ignore if we're not fully initialized yet
            if (!this.initialized) {
                return;
            }

            this.setIndexBusy();

            // Kill the old view class
            if (this.view) {
                this.view.destroy();
                delete this.view;
            }

            this.$elements.html('');

            var params = this.getViewParams();

            Craft.postActionRequest(this.settings.updateElementsAction, params, $.proxy(function(response, textStatus) {
                this.setIndexAvailable();

                if (textStatus === 'success') {
                    this._updateView(params, response);
                }
                else {
                    Craft.cp.displayError(Craft.t('app', 'An unknown error occurred.'));
                }

            }, this));
        },

        updateElementsIfSearchTextChanged: function() {
            if (this.searchText !== (this.searchText = this.searching ? this.$search.val() : null)) {
                this.updateElements();
            }
        },

        showActionTriggers: function() {
            // Ignore if they're already shown
            if (this.showingActionTriggers) {
                return;
            }

            // Hard-code the min toolbar height in case it was taller than the actions toolbar
            // (prevents the elements from jumping if this ends up being a double-click)
            this.$toolbar.css('min-height', this.$toolbar.height());

            // Hide any toolbar inputs
            this._$detachedToolbarItems = this.$toolbarFlexContainer.children().not(this.$selectAllContainer).not(this.$mainSpinner);
            this._$detachedToolbarItems.detach();

            if (!this._$triggers) {
                this._createTriggers();
            }
            else {
                this._$triggers.insertAfter(this.$selectAllContainer);
            }

            this.showingActionTriggers = true;
        },

        submitAction: function(actionClass, actionParams) {
            // Make sure something's selected
            var selectedElementIds = this.view.getSelectedElementIds(),
                totalSelected = selectedElementIds.length;

            if (totalSelected === 0) {
                return;
            }

            // Find the action
            var action;

            for (var i = 0; i < this.actions.length; i++) {
                if (this.actions[i].type === actionClass) {
                    action = this.actions[i];
                    break;
                }
            }

            if (!action || (action.confirm && !confirm(action.confirm))) {
                return;
            }

            // Get ready to submit
            var viewParams = this.getViewParams();

            var params = $.extend(viewParams, actionParams, {
                elementAction: actionClass,
                elementIds: selectedElementIds
            });

            // Do it
            this.setIndexBusy();
            this._autoSelectElements = selectedElementIds;

            Craft.postActionRequest(this.settings.submitActionsAction, params, $.proxy(function(response, textStatus) {
                this.setIndexAvailable();

                if (textStatus === 'success') {
                    if (response.success) {
                        this._updateView(viewParams, response);

                        if (response.message) {
                            Craft.cp.displayNotice(response.message);
                        }

                        this.afterAction(action, params);
                    }
                    else {
                        Craft.cp.displayError(response.message);
                    }
                }
            }, this));
        },

        afterAction: function(action, params) {

            // There may be a new background task that needs to be run
            Craft.cp.runQueue();

            this.onAfterAction(action, params);
        },

        hideActionTriggers: function() {
            // Ignore if there aren't any
            if (!this.showingActionTriggers) {
                return;
            }

            this._$detachedToolbarItems.insertBefore(this.$mainSpinner);
            this._$triggers.detach();

            this.$toolbarFlexContainer.children().not(this.$selectAllContainer).removeClass('hidden');

            // Unset the min toolbar height
            this.$toolbar.css('min-height', '');

            this.showingActionTriggers = false;
        },

        updateActionTriggers: function() {
            // Do we have an action UI to update?
            if (this.actions) {
                var totalSelected = this.view.getSelectedElements().length;

                if (totalSelected !== 0) {
                    if (totalSelected === this.view.getEnabledElements().length) {
                        this.$selectAllCheckbox.removeClass('indeterminate');
                        this.$selectAllCheckbox.addClass('checked');
                        this.$selectAllBtn.attr('aria-checked', 'true');
                    }
                    else {
                        this.$selectAllCheckbox.addClass('indeterminate');
                        this.$selectAllCheckbox.removeClass('checked');
                        this.$selectAllBtn.attr('aria-checked', 'mixed');
                    }

                    this.showActionTriggers();
                }
                else {
                    this.$selectAllCheckbox.removeClass('indeterminate checked');
                    this.$selectAllBtn.attr('aria-checked', 'false');
                    this.hideActionTriggers();
                }
            }
        },

        getSelectedElements: function() {
            return this.view ? this.view.getSelectedElements() : $();
        },

        getSelectedElementIds: function() {
            return this.view ? this.view.getSelectedElementIds() : [];
        },

        getSortAttributeOption: function(attr) {
            return this.$sortAttributesList.find('a[data-attr="' + attr + '"]:first');
        },

        getSelectedSortAttribute: function() {
            return this.$sortAttributesList.find('a.sel:first').data('attr');
        },

        setSortAttribute: function(attr) {
            // Find the option (and make sure it actually exists)
            var $option = this.getSortAttributeOption(attr);

            if ($option.length) {
                this.$sortAttributesList.find('a.sel').removeClass('sel');
                $option.addClass('sel');

                var label = $option.text();
                this.$sortMenuBtn.attr('title', Craft.t('app', 'Sort by {attribute}', {attribute: label}));
                this.$sortMenuBtn.text(label);

                this.setSortDirection(attr === 'score' ? 'desc' : 'asc');

                if (attr === 'structure') {
                    this.$sortDirectionsList.find('a').addClass('disabled');
                }
                else {
                    this.$sortDirectionsList.find('a').removeClass('disabled');
                }
            }
        },

        getSortDirectionOption: function(dir) {
            return this.$sortDirectionsList.find('a[data-dir=' + dir + ']:first');
        },

        getSelectedSortDirection: function() {
            return this.$sortDirectionsList.find('a.sel:first').data('dir');
        },

        getSelectedViewMode: function() {
            return this.getSelectedSourceState('mode');
        },

        setSortDirection: function(dir) {
            if (dir !== 'desc') {
                dir = 'asc';
            }

            this.$sortMenuBtn.attr('data-icon', dir);
            this.$sortDirectionsList.find('a.sel').removeClass('sel');
            this.getSortDirectionOption(dir).addClass('sel');
        },

        getSourceByKey: function(key) {
            if (typeof this.sourcesByKey[key] === 'undefined') {
                return null;
            }

            return this.sourcesByKey[key];
        },

        selectSource: function($source) {
            if (!$source || !$source.length) {
                return false;
            }

            if (this.$source && this.$source[0] && this.$source[0] === $source[0] && $source.data('key') === this.sourceKey) {
                return false;
            }

            // Hide action triggers if they're currently being shown
            this.hideActionTriggers();

            this.$source = $source;
            this.sourceKey = $source.data('key');
            this.setInstanceState('selectedSource', this.sourceKey);
            this.sourceSelect.selectItem($source);

            Craft.cp.updateSidebarMenuLabel();

            if (this.searching) {
                // Clear the search value without causing it to update elements
                this.searchText = null;
                this.$search.val('');
                this.stopSearching();
            }

            // Sort menu
            // ----------------------------------------------------------------------

            // Does this source have a structure?
            if (Garnish.hasAttr(this.$source, 'data-has-structure')) {
                if (!this.$structureSortAttribute) {
                    this.$structureSortAttribute = $('<li><a data-attr="structure">' + Craft.t('app', 'Structure') + '</a></li>');
                    this.sortMenu.addOptions(this.$structureSortAttribute.children());
                }

                this.$structureSortAttribute.prependTo(this.$sortAttributesList);
            }
            else if (this.$structureSortAttribute) {
                this.$structureSortAttribute.removeClass('sel').detach();
            }

            this.setStoredSortOptionsForSource();

            // View mode buttons
            // ----------------------------------------------------------------------

            // Clear out any previous view mode data
            if (this.$viewModeBtnContainer) {
                this.$viewModeBtnContainer.remove();
            }

            this.viewModeBtns = {};
            this.viewMode = null;

            // Get the new list of view modes
            this.sourceViewModes = this.getViewModesForSource();

            // Create the buttons if there's more than one mode available to this source
            if (this.sourceViewModes.length > 1) {
                this.$viewModeBtnContainer = $('<div class="btngroup"/>').insertBefore(this.$mainSpinner);

                for (var i = 0; i < this.sourceViewModes.length; i++) {
                    var sourceViewMode = this.sourceViewModes[i];

                    var $viewModeBtn = $('<div data-view="' + sourceViewMode.mode + '" role="button"' +
                        ' class="btn' + (typeof sourceViewMode.className !== 'undefined' ? ' ' + sourceViewMode.className : '') + '"' +
                        ' title="' + sourceViewMode.title + '"' +
                        (typeof sourceViewMode.icon !== 'undefined' ? ' data-icon="' + sourceViewMode.icon + '"' : '') +
                        '/>'
                    ).appendTo(this.$viewModeBtnContainer);

                    this.viewModeBtns[sourceViewMode.mode] = $viewModeBtn;

                    this.addListener($viewModeBtn, 'click', {mode: sourceViewMode.mode}, function(ev) {
                        this.selectViewMode(ev.data.mode);
                        this.updateElements();
                    });
                }
            }

            // Figure out which mode we should start with
            var viewMode = this.getSelectedViewMode();

            if (!viewMode || !this.doesSourceHaveViewMode(viewMode)) {
                // Try to keep using the current view mode
                if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode)) {
                    viewMode = this.viewMode;
                }
                // Just use the first one
                else {
                    viewMode = this.sourceViewModes[0].mode;
                }
            }

            this.selectViewMode(viewMode);

            this.onSelectSource();

            return true;
        },

        selectSourceByKey: function(key) {
            var $source = this.getSourceByKey(key);

            if ($source) {
                return this.selectSource($source);
            }
            else {
                return false;
            }
        },

        setStoredSortOptionsForSource: function() {
            var sortAttr = this.getSelectedSourceState('order'),
                sortDir = this.getSelectedSourceState('sort');

            if (!sortAttr || !sortDir) {
                // Get the default
                sortAttr = this.getDefaultSort();

                if (Garnish.isArray(sortAttr)) {
                    sortDir = sortAttr[1];
                    sortAttr = sortAttr[0];
                }
            }

            if (sortDir !== 'asc' && sortDir !== 'desc') {
                sortDir = 'asc';
            }

            this.setSortAttribute(sortAttr);
            this.setSortDirection(sortDir);
        },

        getDefaultSort: function() {
            // Does the source specify what to do?
            if (this.$source && Garnish.hasAttr(this.$source, 'data-default-sort')) {
                return this.$source.attr('data-default-sort').split(':');
            }
            else {
                // Default to whatever's first
                return [this.$sortAttributesList.find('a:first').data('attr'), 'asc'];
            }
        },

        getViewModesForSource: function() {
            var viewModes = [
                {mode: 'table', title: Craft.t('app', 'Display in a table'), icon: 'list'}
            ];

            if (this.$source && Garnish.hasAttr(this.$source, 'data-has-thumbs')) {
                viewModes.push({mode: 'thumbs', title: Craft.t('app', 'Display as thumbnails'), icon: 'grid'});
            }

            return viewModes;
        },

        doesSourceHaveViewMode: function(viewMode) {
            for (var i = 0; i < this.sourceViewModes.length; i++) {
                if (this.sourceViewModes[i].mode === viewMode) {
                    return true;
                }
            }

            return false;
        },

        selectViewMode: function(viewMode, force) {
            // Make sure that the current source supports it
            if (!force && !this.doesSourceHaveViewMode(viewMode)) {
                viewMode = this.sourceViewModes[0].mode;
            }

            // Has anything changed?
            if (viewMode === this.viewMode) {
                return;
            }

            // Deselect the previous view mode
            if (this.viewMode && typeof this.viewModeBtns[this.viewMode] !== 'undefined') {
                this.viewModeBtns[this.viewMode].removeClass('active');
            }

            this.viewMode = viewMode;
            this.setSelecetedSourceState('mode', this.viewMode);

            if (typeof this.viewModeBtns[this.viewMode] !== 'undefined') {
                this.viewModeBtns[this.viewMode].addClass('active');
            }
        },

        createView: function(mode, settings) {
            var viewClass = this.getViewClass(mode);
            return new viewClass(this, this.$elements, settings);
        },

        getViewClass: function(mode) {
            switch (mode) {
                case 'table':
                    return Craft.TableElementIndexView;
                case 'thumbs':
                    return Craft.ThumbsElementIndexView;
                default:
                    throw 'View mode "' + mode + '" not supported.';
            }
        },

        rememberDisabledElementId: function(id) {
            var index = $.inArray(id, this.settings.disabledElementIds);

            if (index === -1) {
                this.settings.disabledElementIds.push(id);
            }
        },

        forgetDisabledElementId: function(id) {
            var index = $.inArray(id, this.settings.disabledElementIds);

            if (index !== -1) {
                this.settings.disabledElementIds.splice(index, 1);
            }
        },

        enableElements: function($elements) {
            $elements.removeClass('disabled').parents('.disabled').removeClass('disabled');

            for (var i = 0; i < $elements.length; i++) {
                var id = $($elements[i]).data('id');
                this.forgetDisabledElementId(id);
            }

            this.onEnableElements($elements);
        },

        disableElements: function($elements) {
            $elements.removeClass('sel').addClass('disabled');

            for (var i = 0; i < $elements.length; i++) {
                var id = $($elements[i]).data('id');
                this.rememberDisabledElementId(id);
            }

            this.onDisableElements($elements);
        },

        getElementById: function(id) {
            return this.view.getElementById(id);
        },

        enableElementsById: function(ids) {
            ids = $.makeArray(ids);

            for (var i = 0; i < ids.length; i++) {
                var id = ids[i],
                    $element = this.getElementById(id);

                if ($element && $element.length) {
                    this.enableElements($element);
                }
                else {
                    this.forgetDisabledElementId(id);
                }
            }
        },

        disableElementsById: function(ids) {
            ids = $.makeArray(ids);

            for (var i = 0; i < ids.length; i++) {
                var id = ids[i],
                    $element = this.getElementById(id);

                if ($element && $element.length) {
                    this.disableElements($element);
                }
                else {
                    this.rememberDisabledElementId(id);
                }
            }
        },

        selectElementAfterUpdate: function(id) {
            if (this._autoSelectElements === null) {
                this._autoSelectElements = [];
            }

            this._autoSelectElements.push(id);
        },

        addButton: function($button) {
            this.getButtonContainer().append($button);
        },

        isShowingSidebar: function() {
            if (this.showingSidebar === null) {
                this.showingSidebar = (this.$sidebar.length && !this.$sidebar.hasClass('hidden'));
            }

            return this.showingSidebar;
        },

        getButtonContainer: function() {
            // Is there a predesignated place where buttons should go?
            if (this.settings.buttonContainer) {
                return $(this.settings.buttonContainer);
            }
            else {
                var $container = $('#button-container');

                if (!$container.length) {
                    $container = $('<div id="button-container"/>').appendTo(Craft.cp.$header);
                }

                return $container;
            }
        },

        setIndexBusy: function() {
            this.$mainSpinner.removeClass('invisible');
            this.isIndexBusy = true;
        },

        setIndexAvailable: function() {
            this.$mainSpinner.addClass('invisible');
            this.isIndexBusy = false;
        },

        createCustomizeSourcesModal: function() {
            // Recreate it each time
            var modal = new Craft.CustomizeSourcesModal(this, {
                onHide: function() {
                    modal.destroy();
                }
            });

            return modal;
        },

        disable: function() {
            if (this.sourceSelect) {
                this.sourceSelect.disable();
            }

            if (this.view) {
                this.view.disable();
            }

            this.base();
        },

        enable: function() {
            if (this.sourceSelect) {
                this.sourceSelect.enable();
            }

            if (this.view) {
                this.view.enable();
            }

            this.base();
        },

        // Events
        // =========================================================================

        onAfterInit: function() {
            this.settings.onAfterInit();
            this.trigger('afterInit');
        },

        onSelectSource: function() {
            this.settings.onSelectSource(this.sourceKey);
            this.trigger('selectSource', {sourceKey: this.sourceKey});
        },

        onSelectSite: function() {
            this.settings.onSelectSite(this.siteId);
            this.trigger('selectSite', {siteId: this.siteId});
        },

        onUpdateElements: function() {
            this.settings.onUpdateElements();
            this.trigger('updateElements');
        },

        onSelectionChange: function() {
            this.settings.onSelectionChange();
            this.trigger('selectionChange');
        },

        onEnableElements: function($elements) {
            this.settings.onEnableElements($elements);
            this.trigger('enableElements', {elements: $elements});
        },

        onDisableElements: function($elements) {
            this.settings.onDisableElements($elements);
            this.trigger('disableElements', {elements: $elements});
        },

        onAfterAction: function(action, params) {
            this.settings.onAfterAction(action, params);
            this.trigger('afterAction', {action: action, params: params});
        },

        // Private methods
        // =========================================================================

        // UI state handlers
        // -------------------------------------------------------------------------

        _handleSourceSelectionChange: function() {
            // If the selected source was just removed (maybe because its parent was collapsed),
            // there won't be a selected source
            if (!this.sourceSelect.totalSelected) {
                this.sourceSelect.selectItem(this.$visibleSources.first());
                return;
            }

            if (this.selectSource(this.sourceSelect.$selectedItems)) {
                this.updateElements();
            }
        },

        _handleActionTriggerSubmit: function(ev) {
            ev.preventDefault();

            var $form = $(ev.currentTarget);

            // Make sure Craft.ElementActionTrigger isn't overriding this
            if ($form.hasClass('disabled') || $form.data('custom-handler')) {
                return;
            }

            var actionClass = $form.data('action'),
                params = Garnish.getPostData($form);

            this.submitAction(actionClass, params);
        },

        _handleMenuActionTriggerSubmit: function(ev) {
            var $option = $(ev.option);

            // Make sure Craft.ElementActionTrigger isn't overriding this
            if ($option.hasClass('disabled') || $option.data('custom-handler')) {
                return;
            }

            var actionClass = $option.data('action');
            this.submitAction(actionClass);
        },

        _handleStatusChange: function(ev) {
            this.statusMenu.$options.removeClass('sel');
            var $option = $(ev.selectedOption).addClass('sel');
            this.$statusMenuBtn.html($option.html());

            if (Garnish.hasAttr($option, 'data-trashed')) {
                this.trashed = true;
                this.status = null;
            } else {
                this.trashed = false;
                this.status = $option.data('status');
            }

            this._updateStructureSortOption();
            this.updateElements();
        },

        _handleSiteChange: function(ev) {
            this.siteMenu.$options.removeClass('sel');
            var $option = $(ev.selectedOption).addClass('sel');
            this.$siteMenuBtn.html($option.html());
            this._setSite($option.data('site-id'));
            this.onSelectSite();
        },

        _setSite: function(siteId) {
            this.siteId = siteId;
            this.$visibleSources = $();

            // Hide any sources that aren't available for this site
            var $firstVisibleSource;
            var $source;
            var selectNewSource = false;

            for (var i = 0; i < this.$sources.length; i++) {
                $source = this.$sources.eq(i);
                if (typeof $source.data('sites') === 'undefined' || $source.data('sites').toString().split(',').indexOf(siteId.toString()) !== -1) {
                    $source.parent().removeClass('hidden');
                    this.$visibleSources = this.$visibleSources.add($source);
                    if (!$firstVisibleSource) {
                        $firstVisibleSource = $source;
                    }
                } else {
                    $source.parent().addClass('hidden');

                    // Is this the currently selected source?
                    if (this.$source && this.$source.get(0) == $source.get(0)) {
                        selectNewSource = true;
                    }
                }
            }

            if (selectNewSource) {
                this.selectSource($firstVisibleSource);
            }

            // Hide any empty-nester headings
            var $headings = this.getSourceContainer().children('.heading');
            var $heading;

            for (i = 0; i < $headings.length; i++) {
                $heading = $headings.eq(i);
                if ($heading.nextUntil('.heading', ':not(.hidden)').length !== 0) {
                    $heading.removeClass('hidden');
                } else {
                    $heading.addClass('hidden');
                }
            }

            if (this.initialized) {
                // Remember this site for later
                Craft.setLocalStorage('BaseElementIndex.siteId', siteId);

                // Update the elements
                this.updateElements();
            }
        },

        _handleSortChange: function(ev) {
            var $option = $(ev.selectedOption);

            if ($option.hasClass('disabled') || $option.hasClass('sel')) {
                return;
            }

            // Is this an attribute or a direction?
            if ($option.parent().parent().is(this.$sortAttributesList)) {
                this.setSortAttribute($option.data('attr'));
            }
            else {
                this.setSortDirection($option.data('dir'));
            }

            this.storeSortAttributeAndDirection();
            this.updateElements();
        },

        _handleSelectionChange: function() {
            this.updateActionTriggers();
            this.onSelectionChange();
        },

        _handleSourceToggleClick: function(ev) {
            this._toggleSource($(ev.currentTarget).prev('a'));
            ev.stopPropagation();
        },

        _updateStructureSortOption: function() {
            var $option = this.getSortAttributeOption('structure');

            if (!$option.length) {
                return;
            }

            if (this.trashed || this.searching) {
                $option.addClass('disabled');
                if (this.getSelectedSortAttribute() === 'structure') {
                    // Temporarily set the sort to the first option
                    var $firstOption = this.$sortAttributesList.find('a:not(.disabled):first')
                    this.setSortAttribute($firstOption.data('attr'));
                    this.setSortDirection('asc');
                }
            } else {
                $option.removeClass('disabled');
                this.setStoredSortOptionsForSource();
            }
        },

        // Source managemnet
        // -------------------------------------------------------------------------

        _getSourcesInList: function($list) {
            return $list.children('li').children('a');
        },

        _getChildSources: function($source) {
            var $list = $source.siblings('ul');
            return this._getSourcesInList($list);
        },

        _getSourceToggle: function($source) {
            return $source.siblings('.toggle');
        },

        _initSources: function($sources) {
            for (var i = 0; i < $sources.length; i++) {
                this.initSource($($sources[i]));
            }
        },

        _deinitSources: function($sources) {
            for (var i = 0; i < $sources.length; i++) {
                this.deinitSource($($sources[i]));
            }
        },

        _toggleSource: function($source) {
            if ($source.parent('li').hasClass('expanded')) {
                this._collapseSource($source);
            }
            else {
                this._expandSource($source);
            }
        },

        _expandSource: function($source) {
            $source.parent('li').addClass('expanded');

            var $childSources = this._getChildSources($source);
            this._initSources($childSources);

            var key = $source.data('key');
            if (this.instanceState.expandedSources.indexOf(key) === -1) {
                this.instanceState.expandedSources.push(key);
                this.storeInstanceState();
            }
        },

        _collapseSource: function($source) {
            $source.parent('li').removeClass('expanded');

            var $childSources = this._getChildSources($source);
            this._deinitSources($childSources);

            var i = this.instanceState.expandedSources.indexOf($source.data('key'));
            if (i !== -1) {
                this.instanceState.expandedSources.splice(i, 1);
                this.storeInstanceState();
            }
        },

        // View
        // -------------------------------------------------------------------------

        _updateView: function(params, response) {
            // Cleanup
            // -------------------------------------------------------------

            // Get rid of the old action triggers regardless of whether the new batch has actions or not
            if (this.actions) {
                this.hideActionTriggers();
                this.actions = this.actionsHeadHtml = this.actionsFootHtml = this._$triggers = null;
            }

            if (this.$selectAllContainer) {
                // Git rid of the old select all button
                this.$selectAllContainer.detach();
            }

            // Batch actions setup
            // -------------------------------------------------------------

            if (response.actions && response.actions.length) {
                this.actions = response.actions;
                this.actionsHeadHtml = response.actionsHeadHtml;
                this.actionsFootHtml = response.actionsFootHtml;

                // First time?
                if (!this.$selectAllContainer) {
                    // Create the select all button
                    this.$selectAllContainer = $('<div class="selectallcontainer"/>');
                    this.$selectAllBtn = $('<div class="btn"/>').appendTo(this.$selectAllContainer);
                    this.$selectAllCheckbox = $('<div class="checkbox"/>').appendTo(this.$selectAllBtn);

                    this.$selectAllBtn.attr({
                        'role': 'checkbox',
                        'tabindex': '0',
                        'aria-checked': 'false'
                    });

                    this.addListener(this.$selectAllBtn, 'click', function() {
                        if (this.view.getSelectedElements().length === 0) {
                            this.view.selectAllElements();
                        }
                        else {
                            this.view.deselectAllElements();
                        }
                    });

                    this.addListener(this.$selectAllBtn, 'keydown', function(ev) {
                        if (ev.keyCode === Garnish.SPACE_KEY) {
                            ev.preventDefault();

                            $(ev.currentTarget).trigger('click');
                        }
                    });
                }
                else {
                    // Reset the select all button
                    this.$selectAllCheckbox.removeClass('indeterminate checked');

                    this.$selectAllBtn.attr('aria-checked', 'false');
                }

                // Place the select all button at the beginning of the toolbar
                this.$selectAllContainer.prependTo(this.$toolbarFlexContainer);
            }

            // Update the view with the new container + elements HTML
            // -------------------------------------------------------------

            this.$elements.html(response.html);
            Craft.appendHeadHtml(response.headHtml);
            Craft.appendFootHtml(response.footHtml);

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
                checkboxMode: !!this.actions,
                onSelectionChange: $.proxy(this, '_handleSelectionChange')
            });

            // Auto-select elements
            // -------------------------------------------------------------

            if (this._autoSelectElements) {
                if (selectable) {
                    for (var i = 0; i < this._autoSelectElements.length; i++) {
                        this.view.selectElementById(this._autoSelectElements[i]);
                    }
                }

                this._autoSelectElements = null;
            }

            // Trigger the event
            // -------------------------------------------------------------

            this.onUpdateElements();
        },

        _createTriggers: function() {
            var triggers = [],
                safeMenuActions = [],
                destructiveMenuActions = [];

            var i;

            for (i = 0; i < this.actions.length; i++) {
                var action = this.actions[i];

                if (action.trigger) {
                    var $form = $('<form id="' + Craft.formatInputId(action.type) + '-actiontrigger"/>')
                        .data('action', action.type)
                        .append(action.trigger);

                    this.addListener($form, 'submit', '_handleActionTriggerSubmit');
                    triggers.push($form);
                }
                else {
                    if (!action.destructive) {
                        safeMenuActions.push(action);
                    }
                    else {
                        destructiveMenuActions.push(action);
                    }
                }
            }

            var $btn;

            if (safeMenuActions.length || destructiveMenuActions.length) {
                var $menuTrigger = $('<form/>');

                $btn = $('<div class="btn menubtn" data-icon="settings" title="' + Craft.t('app', 'Actions') + '"/>').appendTo($menuTrigger);

                var $menu = $('<ul class="menu"/>').appendTo($menuTrigger),
                    $safeList = this._createMenuTriggerList(safeMenuActions, false),
                    $destructiveList = this._createMenuTriggerList(destructiveMenuActions, true);

                if ($safeList) {
                    $safeList.appendTo($menu);
                }

                if ($safeList && $destructiveList) {
                    $('<hr/>').appendTo($menu);
                }

                if ($destructiveList) {
                    $destructiveList.appendTo($menu);
                }

                triggers.push($menuTrigger);
            }

            this._$triggers = $();

            for (i = 0; i < triggers.length; i++) {
                var $div = $('<div/>').append(triggers[i]);
                this._$triggers = this._$triggers.add($div);
            }

            this._$triggers.insertAfter(this.$selectAllContainer);
            Craft.appendHeadHtml(this.actionsHeadHtml);
            Craft.appendFootHtml(this.actionsFootHtml);

            Craft.initUiElements(this._$triggers);

            if ($btn) {
                $btn.data('menubtn').on('optionSelect', $.proxy(this, '_handleMenuActionTriggerSubmit'));
            }
        },

        _createMenuTriggerList: function(actions, destructive) {
            if (actions && actions.length) {
                var $ul = $('<ul/>');

                for (var i = 0; i < actions.length; i++) {
                    var actionClass = actions[i].type;
                    $('<li/>').append($('<a/>', {
                        id: Craft.formatInputId(actionClass) + '-actiontrigger',
                        'class': (destructive ? 'error' : null),
                        'data-action': actionClass,
                        text: actions[i].name
                    })).appendTo($ul);
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
            hideSidebar: false,
            refreshSourcesAction: 'element-indexes/get-source-tree-html',
            updateElementsAction: 'element-indexes/get-elements',
            submitActionsAction: 'element-indexes/perform-action',
            toolbarFixed: null,

            onAfterInit: $.noop,
            onSelectSource: $.noop,
            onSelectSite: $.noop,
            onUpdateElements: $.noop,
            onSelectionChange: $.noop,
            onEnableElements: $.noop,
            onDisableElements: $.noop,
            onAfterAction: $.noop
        }
    });

