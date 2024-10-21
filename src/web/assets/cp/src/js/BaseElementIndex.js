/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Element index class
 */
Craft.BaseElementIndex = Garnish.Base.extend(
  {
    initialized: false,
    elementType: null,
    idPrefix: null,

    instanceState: null,
    _sourceStates: null,
    sourceStatesStorageKey: null,

    searchTimeout: null,
    sourceSelect: null,
    sourceNav: null,

    $container: null,
    $main: null,
    isIndexBusy: false,

    $sidebar: null,
    showingSidebar: null,
    hasImplicitSource: false,
    sourceKey: null,
    rootSourceKey: null,
    sourceViewModes: null,
    $source: null,
    $rootSource: null,
    sourcesByKey: null,
    $visibleSources: null,

    $sourceActionsContainer: null,
    $sourceActionsBtn: null,

    $toolbar: null,
    toolbarOffset: null,

    $srStatusContainer: null,
    $searchContainer: null,
    $search: null,
    $filterBtn: null,
    searching: false,
    searchText: null,
    sortByScore: null,
    trashed: false,
    drafts: false,
    $clearSearchBtn: null,

    $statusMenuBtn: null,
    $statusMenuContainer: null,
    statusMenu: null,
    status: null,

    $siteMenuBtn: null,
    siteMenu: null,
    siteId: null,

    sourcePaths: null,
    $sourcePathOuterContainer: null,
    $sourcePathInnerContainer: null,
    $sourcePathOverflowBtnContainer: null,
    $sourcePathActionsBtn: null,

    $elements: null,
    $updateSpinner: null,
    $viewModeBtnContainer: null,
    viewModeBtns: null,
    _viewParams: null,
    _previousViewParams: null,
    _viewMode: null,
    view: null,
    _autoSelectElements: null,
    $countSpinner: null,
    $countContainer: null,
    $actionsContainer: null,
    $actionMenuBtn: null,
    page: 1,
    prevPage: null,
    resultSet: null,
    totalResults: null,
    totalUnfilteredResults: null,
    $exportBtn: null,

    actions: null,
    actionsHeadHtml: null,
    actionsBodyHtml: null,
    $selectAllContainer: null,
    $selectAllCheckbox: null,
    showingActionTriggers: false,
    exporters: null,
    exportersByType: null,
    triggers: null,
    _$triggers: null,

    _cancelToken: null,

    viewMenus: null,
    activeViewMenu: null,
    filterHuds: null,

    _activeElement: null,

    inlineEditing: false,
    nestedInputNamespace: null,

    get viewMode() {
      if (this._viewMode === 'structure' && !this.canSort) {
        // return the default
        return this.validateViewMode(null);
      }

      return this.validateViewMode(this._viewMode);
    },

    set viewMode(viewMode) {
      this._viewMode = viewMode ? this.validateViewMode(viewMode) : null;
    },

    get paginated() {
      return !!(this.isAdministrative && this.viewMode !== 'structure');
    },

    get selectable() {
      return (
        !!(this.actions || this.settings.selectable) && !this.inlineEditing
      );
    },

    get multiSelect() {
      return !!(
        this.actions ||
        (this.settings.selectable && this.settings.multiSelect)
      );
    },

    get sortable() {
      return this.settings.sortable && this.canSort && !this.inlineEditing;
    },

    get canSort() {
      return (
        this.isAdministrative &&
        !this.status &&
        !this.trashed &&
        !this.drafts &&
        !this.searching &&
        !this.hasActiveFilter
      );
    },

    get isAdministrative() {
      return ['index', 'embedded-index'].includes(this.settings.context);
    },

    get hasActiveFilter() {
      return (
        this.filterHuds[this.siteId] &&
        this.filterHuds[this.siteId][this.sourceKey] &&
        this.filterHuds[this.siteId][this.sourceKey].isActive
      );
    },

    get baseCriteria() {
      const criteria = {};
      if (this.$source) {
        Object.assign(criteria, this.$source.data('criteria'));
      }
      if (this.settings.criteria) {
        Object.assign(criteria, this.settings.criteria);
      }
      if (this.sourcePath.length) {
        const currentStep = this.sourcePath[this.sourcePath.length - 1];
        if (currentStep.criteria) {
          Object.assign(criteria, currentStep.criteria);
        }
      }
      return criteria;
    },

    get sourceStates() {
      if (this.hasImplicitSource) {
        return {
          __IMP__: this.instanceState.source || {},
        };
      }

      return this._sourceStates;
    },

    set sourceStates(sourceStates) {
      if (this.hasImplicitSource) {
        this.setInstanceState('source', sourceStates.__IMP__ || {});
      } else {
        this._sourceStates = sourceStates;
        Craft.setLocalStorage(this.sourceStatesStorageKey, this.sourceStates);
      }
    },

    /**
     * Constructor
     */
    init: function (elementType, $container, settings) {
      this.elementType = elementType;
      this.$container = $container;
      this.setSettings(settings, Craft.BaseElementIndex.defaults);

      this.$container.data('elementIndex', this);

      this.nestedInputNamespace = `elementindex-${Math.floor(
        Math.random() * 100000
      )}`;
      this.sourcePaths = {};

      // Define an ID prefix that can be used for dynamically created elements
      // ---------------------------------------------------------------------

      this.idPrefix = Craft.randomString(10);

      // Find the DOM elements
      // ---------------------------------------------------------------------

      this.$main = this.$container.find('.main');
      this.$toolbar = this.$container.find(this.settings.toolbarSelector);
      this.$statusMenuBtn = this.$toolbar.find('.statusmenubtn:first');
      this.$statusMenuContainer = this.$statusMenuBtn.parent();
      this.$siteMenuBtn = this.$container.find('.sitemenubtn:first');

      this.$searchContainer = this.$toolbar.find('.search-container:first');
      this.$search = this.$searchContainer.children('input:first');
      this.$filterBtn = this.$searchContainer.children('.filter-btn:first');
      this.$clearSearchBtn = this.$searchContainer.children('.clear-btn:first');

      this.$sidebar = this.$container.find('.sidebar:first');
      this.$sourceActionsContainer = this.$sidebar.find(
        `#${this.namespaceId('source-actions')}`
      );
      this.$sourceActionsContainer = this.$sidebar.find('#source-actions');
      this.$srStatusContainer = this.$container.find('[data-status-message]');

      this.$elements = this.$container.find('.elements:first');
      this.$updateSpinner = this.$elements.find('.spinner');

      if (!this.$updateSpinner.length) {
        this.$updateSpinner = $('<div/>', {
          class: 'update-spinner spinner spinner-absolute',
        }).appendTo(this.$elements);
      }

      this.$countSpinner = this.$container.find(
        `#${this.namespaceId('count-spinner')}`
      );
      this.$countContainer = this.$container.find(
        `#${this.namespaceId('count-container')}`
      );
      this.$actionsContainer = this.$container.find(
        `#${this.namespaceId('actions-container')}`
      );
      this.$exportBtn = this.$container.find(
        `#${this.namespaceId('export-btn')}`
      );

      // Hide sidebar if needed
      if (this.settings.hideSidebar) {
        this.$sidebar.hide();
        $('.body, .content', this.$container).removeClass('has-sidebar');
      }

      // Find the sources
      // ---------------------------------------------------------------------

      const $sources = this.findSources();

      // Is there just an implicit source?
      if ($sources.length === 1 && $sources.data('key') === '__IMP__') {
        this.hasImplicitSource = true;
      }

      // Set the state objects
      // ---------------------------------------------------------------------

      this.instanceState = this.getDefaultInstanceState();

      // Instance states (selected source) are stored by a custom storage key defined in the settings
      if (this.settings.storageKey) {
        $.extend(
          this.instanceState,
          Craft.getLocalStorage(this.settings.storageKey),
          {}
        );
      }

      // Source states (view mode, etc.) are stored by the element type and context
      if (!this.hasImplicitSource) {
        this._sourceStates = {};

        this.sourceStatesStorageKey =
          'BaseElementIndex.' + this.elementType + '.' + this.settings.context;
        Object.assign(
          this._sourceStates,
          Craft.getLocalStorage(this.sourceStatesStorageKey, {})
        );
      }

      // Initialize the sources
      // ---------------------------------------------------------------------

      if (!this.initSources($sources)) {
        return;
      }

      // Initialize the status menu
      // ---------------------------------------------------------------------

      if (this.$statusMenuBtn.length) {
        this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
        this.statusMenu.on('optionselect', this._handleStatusChange.bind(this));
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
        } else {
          // No site options -- they must not have any site permissions
          this.settings.criteria = {id: '0'};
        }

        this.siteMenu.on('optionselect', this._handleSiteChange.bind(this));

        if (this.siteId) {
          // Should we be using a different default site?
          var defaultSiteId =
            this.settings.defaultSiteId || Craft.cp.getSiteId();

          if (defaultSiteId && defaultSiteId != this.siteId) {
            // Is that one available here?
            var $storedSiteOption = this.siteMenu.$options.filter(
              '[data-site-id="' + defaultSiteId + '"]:first'
            );

            if ($storedSiteOption.length) {
              // Todo: switch this to siteMenu.selectOption($storedSiteOption) once Menu is updated to support that
              $storedSiteOption.trigger('click');
            }
          }
        }
      } else if (
        this.settings.criteria &&
        this.settings.criteria.siteId &&
        this.settings.criteria.siteId !== '*'
      ) {
        this._setSite(this.settings.criteria.siteId);
      } else {
        this._setSite(Craft.siteId);
      }

      // Don't let the criteria override the selected site
      if (this.settings.criteria && this.settings.criteria.siteId) {
        delete this.settings.criteria.siteId;
      }

      // Initialize the search input
      // ---------------------------------------------------------------------

      // Automatically update the elements after new search text has been sitting for a 1/2 second
      this.addListener(this.$search, 'input', () => {
        if (!this.searching && this.$search.val()) {
          this.startSearching();
        } else if (this.searching && !this.$search.val()) {
          this.stopSearching();
        }

        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(
          this.updateElementsIfSearchTextChanged.bind(this),
          500
        );
      });

      // Update the elements when the Return key is pressed
      this.addListener(this.$search, 'keypress', (ev) => {
        if (ev.keyCode === Garnish.RETURN_KEY) {
          ev.preventDefault();

          if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
          }

          this.updateElementsIfSearchTextChanged();
        }
      });

      // Clear the search when the X button is clicked
      this.addListener(this.$clearSearchBtn, 'click', () => {
        this.clearSearch(true);

        if (!Garnish.isMobileBrowser(true)) {
          this.$search.focus();
        }
      });

      // Autofocus the Search box, unless this is an embedded index
      if (
        this.settings.context !== 'embedded-index' &&
        !Garnish.isMobileBrowser(true) &&
        Craft.disableAutofocus === false
      ) {
        this.$search.focus();
      }

      // View menus
      this.viewMenus = {};

      // Filter HUDs
      this.filterHuds = {};
      this.addListener(this.$filterBtn, 'click', 'showFilterHud');

      // Set the default status
      // ---------------------------------------------------------------------

      const queryParams =
        this.settings.context === 'index' ? Craft.getQueryParams() : {};

      if (queryParams.status) {
        let selector;
        switch (queryParams.status) {
          case 'trashed':
            selector = '[data-trashed]';
            break;
          case 'drafts':
            selector = '[data-drafts]';
            break;
          default:
            selector = `[data-status="${queryParams.status}"]`;
        }

        const $option = this.statusMenu.$options.filter(selector);
        if ($option.length) {
          this.statusMenu.selectOption($option[0]);
        } else {
          Craft.setQueryParam('status', null);
        }
      }

      // Initialize the Export button
      // ---------------------------------------------------------------------

      this.addListener(this.$exportBtn, 'click', '_showExportHud');

      // Let everyone know that the UI is initialized
      // ---------------------------------------------------------------------

      this.initialized = true;
      this.afterInit();

      // Select the initial source + source path
      // ---------------------------------------------------------------------

      // Grab the localStorage step key up front, so we don's lose track of it when the default source's default
      // source path is selected
      let stepKey;
      if (this.settings.context === 'index') {
        if (queryParams.sourcePathStep !== undefined) {
          stepKey = queryParams.sourcePathStep;
        } else {
          stepKey = this.getSelectedSourceState('sourcePathStep');
        }
      } else {
        stepKey = this.instanceState.sourcePathStep || null;
      }

      this.selectDefaultSource();

      const sourcePath = this.getDefaultSourcePath();

      // If no default source path was explicitly configured, or it's set to the root of the volume, use the localStorage key
      if ((!sourcePath || sourcePath.length == 1) && stepKey) {
        this.loadSourcePathByKey(stepKey).then((sourcePath) => {
          if (sourcePath) {
            // Filter out any source path steps that are above the source's root
            const lastSourceKey = this.sourceKey.split('/').slice(-1)[0];
            const sourceRootIndex = sourcePath.findIndex(
              (p) => p.key === lastSourceKey
            );
            if (sourceRootIndex !== -1) {
              this.sourcePath = sourcePath.slice(sourceRootIndex);
            }
          }
          this.afterSetInitialSource(queryParams);
        });
      } else {
        if (sourcePath) {
          this.sourcePath = sourcePath;
        }
        this.afterSetInitialSource(queryParams);
      }

      // Set visible source name on small/zoomed screens
      this.updateMainHeading();
    },

    afterInit: function () {
      this.onAfterInit();
    },

    namespaceInputName(name) {
      return Craft.namespaceInputName(name, this.settings.namespace);
    },

    namespaceId(id) {
      return Craft.namespaceId(id, this.settings.namespace);
    },

    loadSourcePathByKey: function (stepKey) {
      return new Promise((resolve, reject) => {
        // If the step key is equal to the current source key, then it represents the root. No source path needed.
        if (stepKey === this.sourceKey) {
          resolve([]);
          return;
        }

        const params = this.getViewParams();
        params.stepKey = stepKey;

        Craft.sendActionRequest('POST', 'element-indexes/source-path', {
          data: params,
        })
          .then(({data}) => {
            resolve(data.sourcePath);
          })
          .catch(reject);
      });
    },

    afterSetInitialSource: function (queryParams) {
      // Resize handler
      // ---------------------------------------------------------------------

      if (this.settings.context === 'index') {
        this.addListener(Garnish.$win, 'resize', 'handleResize');
      }

      this.handleResize();

      // Respect initial search
      // ---------------------------------------------------------------------
      // Has to go after selecting the default source because selecting a source
      // clears out search params

      if (queryParams.search) {
        this.startSearching();
        this.searchText = queryParams.search;
      }

      // Respect the initial filters
      // ---------------------------------------------------------------------

      if (queryParams.filters) {
        this.createFilterHud({
          showOnInit: false,
          serialized: queryParams.filters,
        });
      }

      // Select the default sort attribute/direction
      // ---------------------------------------------------------------------

      if (queryParams.sort) {
        const lastDashPos = queryParams.sort.lastIndexOf('-');
        if (lastDashPos !== -1) {
          const attr = queryParams.sort.substring(0, lastDashPos);
          const dir = queryParams.sort.substring(lastDashPos + 1);
          this.setSelectedSortAttribute(attr, dir);
        }
      }

      // Load the first batch of elements!
      // ---------------------------------------------------------------------

      // Default to whatever page is in the URL
      this.setPage(Craft.pageNum);

      this.updateElements(true);
    },

    handleResize: function () {
      if (this.sourcePath.length && this.settings.showSourcePath) {
        this._updateSourcePathVisibility();
      }
    },

    _createCancelToken: function () {
      this._cancelToken = axios.CancelToken.source();
      return this._cancelToken.token;
    },

    _cancelRequests: function () {
      if (this._cancelToken) {
        this._cancelToken.cancel();
      }
    },

    getSourceContainer: function () {
      return this.$sidebar.find('nav > ul');
    },

    getSourceLabel: function () {
      return this.$source?.data('label');
    },

    getItemLabel: function () {
      return Craft.elementTypeNames[this.elementType]
        ? Craft.elementTypeNames[this.elementType][2]
        : this.settings.elementTypeName.toLowerCase();
    },

    getItemsLabel: function () {
      return Craft.elementTypeNames[this.elementType]
        ? Craft.elementTypeNames[this.elementType][3]
        : this.settings.elementTypePluralName.toLowerCase();
    },

    getFirstItemNumber: function (total) {
      return Math.min(this.settings.batchSize * (this.page - 1) + 1, total);
    },

    getLastItemNumber: function (first, total) {
      return Math.min(first + (this.settings.batchSize - 1), total);
    },

    get $sources() {
      if (!this.sourceNav) {
        return undefined;
      }

      return this.sourceNav.$items;
    },

    getSite: function () {
      if (!this.siteId) {
        return undefined;
      }
      return Craft.sites.find((s) => s.id == this.siteId);
    },

    initSources: function ($sources) {
      if (typeof $sources === 'undefined') {
        $sources = this.findSources();
      }

      // No source, no party.
      if ($sources.length === 0) {
        return false;
      }

      if (!this.sourceNav) {
        this.sourceNav = new SourceNav(this.$sidebar.find('nav'), {
          onSelectionChange: this._handleSourceSelectionChange.bind(this),
        });
      }

      this.sourcesByKey = {};

      for (let i = 0; i < $sources.length; i++) {
        this.initSource($($sources[i]));
      }

      return true;
    },

    findSources: function () {
      return this._getSourcesInList(this.getSourceContainer(), true);
    },

    selectDefaultSource: function () {
      // The `source` query param should always take precedence
      let sourceKey;
      if (this.settings.context === 'index') {
        sourceKey = Craft.getQueryParam('source');
      }

      if (!sourceKey) {
        sourceKey = this.getDefaultSourceKey();
      }

      let $source;

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

      return this.selectSource($source);
    },

    refreshSources: function () {
      this.sourceNav.removeAllItems();

      this.setIndexBusy();

      Craft.sendActionRequest('POST', this.settings.refreshSourcesAction, {
        data: {
          context: this.settings.context,
          elementType: this.elementType,
        },
      })
        .then((response) => {
          this.setIndexAvailable();
          this.getSourceContainer().replaceWith(response.data.html);
          this.initSources();
          this.selectDefaultSource();
        })
        .catch((e) => {
          if (!axios.isCancel(e)) {
            this.setIndexAvailable();
            Craft.cp.displayError(e?.response?.data?.message);
          }
        });
    },

    initSource: function ($source) {
      this.sourceNav.addItems($source);
      this.initSourceToggle($source);
      this.sourcesByKey[$source.data('key')] = $source;

      if (
        $source.data('hasNestedSources') &&
        this.instanceState.expandedSources.indexOf($source.data('key')) !== -1
      ) {
        this._expandSource($source);
      }
    },

    initSourceToggle: function ($source) {
      // Remove handlers for the same thing. Just in case.
      this.deinitSourceToggle($source);

      var $toggle = this._getSourceToggle($source);

      if ($toggle.length) {
        this.addListener($source, 'dblclick', '_handleSourceDblClick');
        this.addListener($toggle, 'click', '_handleSourceToggleClick');
        $source.data('hasNestedSources', true);
      } else {
        $source.data('hasNestedSources', false);
      }
    },

    updateMainHeading: function () {
      let $contentHeading = $('#content-heading');

      if (!$contentHeading.length) {
        $contentHeading = $('<span>', {
          id: 'content-heading',
        });

        $contentHeading.appendTo('.screen-title');
      }

      $contentHeading.text(this.getSourceLabel());
    },

    deinitSource: function ($source) {
      this.sourceNav.removeItems($source);
      this.deinitSourceToggle($source);
      delete this.sourcesByKey[$source.data('key')];
    },

    deinitSourceToggle: function ($source) {
      if ($source.data('hasNestedSources')) {
        this.removeListener($source, 'dblclick');
        this.removeListener(this._getSourceToggle($source), 'click');
      }

      $source.removeData('hasNestedSources');
    },

    getDefaultInstanceState: function () {
      const state = {
        selectedSource: null,
        expandedSources: [],
      };

      if (this.hasImplicitSource) {
        state.source = {};
      }

      return state;
    },

    getDefaultSourceKey: function () {
      if (
        this.settings.preferStoredSource &&
        this.instanceState.selectedSource
      ) {
        // Discard the defaults and go with localStorage
        this.settings.defaultSource = null;
        this.settings.defaultSourcePath = null;
        return this.instanceState.selectedSource;
      }

      let sourceKey = null;

      if (this.settings.defaultSource) {
        let $lastSource = null;
        let refreshSources = false;

        for (const segment of this.settings.defaultSource.split('/')) {
          if ($lastSource) {
            this._expandSource($lastSource);
            refreshSources = true;
          }

          const testSourceKey =
            (sourceKey !== null ? `${sourceKey}/` : '') + segment;
          const $source = this.getSourceByKey(testSourceKey);

          if (!$source) {
            if ($lastSource) {
              this._collapseSource($lastSource);
            }
            break;
          }

          $lastSource = $source;
          sourceKey = testSourceKey;
        }

        if (refreshSources) {
          // Make sure that the modal is aware of the newly expanded sources
          this._setSite(this.siteId);
        }
      }

      if (!sourceKey) {
        // If we couldn't resolve a default source, clear out the defaultSource and defaultSourcePath
        // settings, as defaultSourcePath is expected to be relative to defaultSource
        // (https://github.com/craftcms/cms/issues/13072)
        this.settings.defaultSource = null;
        this.settings.defaultSourcePath = null;
      }

      return sourceKey ?? this.instanceState.selectedSource;
    },

    /**
     * @returns {Object[]|null}
     */
    getDefaultSourcePath: function () {
      // @link https://github.com/craftcms/cms/issues/13006
      if (
        this.settings.defaultSourcePath !== null &&
        this.settings.defaultSourcePath[0] !== undefined &&
        this.settings.defaultSourcePath[0].canView === true
      ) {
        return this.settings.defaultSourcePath;
      } else {
        return null;
      }
    },

    getDefaultExpandedSources: function () {
      return this.instanceState.expandedSources;
    },

    /**
     * @returns {Object[]}
     */
    get sourcePath() {
      return this.sourcePaths[this.sourceKey] || [];
    },

    /**
     * @param {Object[]|null} sourcePath
     */
    set sourcePath(sourcePath) {
      this.sourcePaths[this.sourceKey] =
        sourcePath && sourcePath.length ? sourcePath : null;

      if (this.$sourcePathOuterContainer) {
        this.$sourcePathOuterContainer.remove();
        this.$sourcePathOuterContainer = null;
        this.$sourcePathInnerContainer = null;
        this.$sourcePathOverflowBtnContainer = null;
        this.$sourcePathActionsBtn = null;
      }

      if (this.sourcePaths[this.sourceKey] && this.settings.showSourcePath) {
        const actions = this.getSourcePathActions();

        this.$sourcePathOuterContainer = $('<div/>', {
          class: 'source-path',
        }).insertBefore(this.$elements);
        this.$sourcePathInnerContainer = $('<div/>', {
          class: 'chevron-btns',
        }).appendTo(this.$sourcePathOuterContainer);
        const $nav = $('<nav/>', {
          'aria-label': this.getSourcePathLabel(),
        }).appendTo(this.$sourcePathInnerContainer);
        const $ol = $('<ol/>').appendTo($nav);

        let $overflowBtn, overflowMenuId, $overflowUl;

        if (sourcePath.length > 1) {
          this.$sourcePathOverflowBtnContainer = $('<li/>', {
            class: 'first-step hidden',
          }).appendTo($ol);

          overflowMenuId = 'menu' + Math.floor(Math.random() * 1000000);
          $overflowBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            title: Craft.t('app', 'More items'),
            'aria-label': Craft.t('app', 'More items'),
            'data-disclosure-trigger': true,
            'aria-controls': overflowMenuId,
          })
            .append(
              $('<span/>', {class: 'btn-body'}).append(
                $('<span/>', {class: 'label'}).append(
                  $('<span/>', {
                    'data-icon': 'ellipsis',
                    'aria-hidden': 'true',
                  })
                )
              )
            )
            .append($('<span/>', {class: 'chevron-right'}))
            .appendTo(this.$sourcePathOverflowBtnContainer);

          const $overflowMenu = $('<div/>', {
            id: overflowMenuId,
            class: 'menu menu--disclosure',
          }).appendTo(this.$sourcePathOverflowBtnContainer);
          $overflowUl = $('<ul/>').appendTo($overflowMenu);

          $overflowBtn.disclosureMenu();
        }

        for (let i = 0; i < sourcePath.length; i++) {
          ((i) => {
            const step = sourcePath[i];

            if ($overflowUl && i < sourcePath.length - 1) {
              step.$overflowLi = $('<li/>', {
                class: 'hidden',
              }).appendTo($overflowUl);

              $('<a/>', {
                class: 'flex flex-nowrap',
                href: '#',
                type: 'button',
                role: 'button',
                html: step.icon
                  ? `<span data-icon="${step.icon}" aria-hidden="true"></span><span>${step.label}</span>`
                  : step.label,
              })
                .appendTo(step.$overflowLi)
                .on('click', (ev) => {
                  ev.preventDefault();
                  $overflowBtn.data('trigger').hide();
                  this.selectSourcePathStep(i);
                });
            }

            const isFirst = i === 0;
            const isLast = i === sourcePath.length - 1;

            step.$li = $('<li/>').appendTo($ol);

            if (isFirst) {
              step.$li.addClass('first-step');
            }

            step.$btn = $('<a/>', {
              href: step.uri ? Craft.getCpUrl(step.uri) : '#',
              class: 'btn',
              role: 'button',
            });

            if (step.icon) {
              step.$btn.attr('aria-label', step.label);
            }

            const $btnBody = $('<span/>', {
              class: 'btn-body',
            }).appendTo(step.$btn);

            step.$label = $('<span/>', {
              class: 'label',
              html: step.icon
                ? `<span data-icon="${step.icon}" aria-hidden="true"></span>`
                : step.label,
            }).appendTo($btnBody);

            step.$btn.append($('<span class="chevron-left"/>'));

            if (!isLast || !actions.length) {
              step.$btn.append($('<span class="chevron-right"/>'));
            } else {
              step.$btn.addClass('has-action-menu');
            }

            if (isLast) {
              step.$btn.addClass('current-step').attr('aria-current', 'page');
            }

            step.$btn.appendTo(step.$li);

            this.addListener(step.$btn, 'activate', () => {
              this.selectSourcePathStep(i);
            });
          })(i);
        }

        // Action menu
        if (actions && actions.length) {
          const actionBtnLabel = this.getSourcePathActionLabel();
          const menuId = 'menu' + Math.floor(Math.random() * 1000000);
          this.$sourcePathActionsBtn = $('<button/>', {
            type: 'button',
            class: 'btn current-step',
            title: actionBtnLabel,
            'aria-label': actionBtnLabel,
            'data-disclosure-trigger': true,
            'aria-controls': menuId,
          })
            .append(
              $('<span/>', {class: 'btn-body'}).append(
                $('<span/>', {class: 'label'})
              )
            )
            .append($('<span/>', {class: 'chevron-right'}))
            .appendTo(this.$sourcePathInnerContainer);

          const groupedActions = [
            actions.filter((a) => !a.destructive && !a.administrative),
            actions.filter((a) => a.destructive && !a.administrative),
            actions.filter((a) => a.administrative),
          ].filter((group) => group.length);

          const $menu = $('<div/>', {
            id: menuId,
            class: 'menu menu--disclosure',
          }).appendTo(this.$sourcePathInnerContainer);

          groupedActions.forEach((group, index) => {
            if (index !== 0) {
              $('<hr/>').appendTo($menu);
            }
            this._buildSourcePathActionList(group).appendTo($menu);
          });

          this.$sourcePathActionsBtn.disclosureMenu();
          this._updateSourcePathVisibility();
        }

        // Update the URL if we're on the index page
        if (
          this.settings.context === 'index' &&
          typeof sourcePath[sourcePath.length - 1].uri !== 'undefined' &&
          typeof history != 'undefined'
        ) {
          history.replaceState(
            {},
            '',
            Craft.getCpUrl(sourcePath[sourcePath.length - 1].uri)
          );
        }
      }

      // Store the source path
      const sourcePathStep =
        (this.sourcePaths[this.sourceKey]
          ? this.sourcePaths[this.sourceKey][
              this.sourcePaths[this.sourceKey].length - 1
            ].key
          : null) || null;
      if (this.settings.context === 'index') {
        this.setSelecetedSourceState('sourcePathStep', sourcePathStep);
      } else {
        this.setInstanceState('sourcePathStep', sourcePathStep);
      }

      this.onSourcePathChange();
    },

    /**
     * @returns {string}
     */
    getSourcePathLabel: function () {
      return '';
    },

    /**
     * @returns {Object[]}
     */
    getSourcePathActions: function () {
      return [];
    },

    /**
     * @returns {string}
     */
    getSourcePathActionLabel: function () {
      return '';
    },

    _updateSourcePathVisibility: function () {
      const firstStep = this.sourcePath[0];
      const lastStep = this.sourcePath[this.sourcePath.length - 1];

      // reset the source path styles
      if (this.$sourcePathOverflowBtnContainer) {
        this.$sourcePathOverflowBtnContainer.addClass('hidden');
        firstStep.$li.addClass('first-step');
      }

      for (const step of this.sourcePath) {
        if (step.$overflowLi) {
          step.$overflowLi.addClass('hidden');
        }
        step.$li.removeClass('hidden');
      }

      lastStep.$label.css('width', '');
      lastStep.$btn.removeAttr('title');

      let overage = this._checkSourcePathOverage();
      if (!overage) {
        return;
      }

      // show the overflow menu, if we have one
      if (this.$sourcePathOverflowBtnContainer) {
        this.$sourcePathOverflowBtnContainer.removeClass('hidden');
        firstStep.$li.removeClass('first-step');

        for (let i = 0; i < this.sourcePath.length - 1; i++) {
          const step = this.sourcePath[i];
          step.$overflowLi.removeClass('hidden');
          step.$li.addClass('hidden');

          // are we done yet?
          overage = this._checkSourcePathOverage();
          if (!overage) {
            return;
          }
        }
      }

      // if we're still here, truncation is the only remaining strategy
      if (!lastStep.icon) {
        const width = lastStep.$label[0].getBoundingClientRect().width;
        lastStep.$label.width(Math.floor(width - overage));
        lastStep.$btn.attr('title', lastStep.label);
      }
    },

    _checkSourcePathOverage: function () {
      const outerWidth =
        this.$sourcePathOuterContainer[0].getBoundingClientRect().width;
      const innerWidth =
        this.$sourcePathInnerContainer[0].getBoundingClientRect().width;
      return Math.max(innerWidth - outerWidth, 0);
    },

    _buildSourcePathActionList: function (actions) {
      const $ul = $('<ul/>');

      actions.forEach((action) => {
        const $a = $('<a/>', {
          href: '#',
          type: 'button',
          role: 'button',
          'aria-label': action.label,
          text: action.label,
        }).on('click', (ev) => {
          ev.preventDefault();
          this.$sourcePathActionsBtn.data('trigger').hide();
          if (action.onSelect) {
            action.onSelect();
          }
        });

        if (action.destructive) {
          $a.addClass('error');
        }

        $('<li/>').append($a).appendTo($ul);
      });

      return $ul;
    },

    onSourcePathChange: function () {
      this.settings.onSourcePathChange();
      this.trigger('sourcePathChange');
    },

    selectSourcePathStep: function (num) {
      this.sourcePath = this.sourcePath.slice(0, num + 1);
      this.sourcePath[num].$btn.focus();
      this.clearSearch(false);
      this.updateElements();
    },

    startSearching: function () {
      // Show the clear button
      this.$clearSearchBtn.removeClass('hidden');
      this.searching = true;
      this.sortByScore = true;
    },

    clearSearch: function (updateElements) {
      if (!this.searching) {
        return;
      }

      this.$search.val('');

      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }

      this.stopSearching();

      if (updateElements) {
        this.updateElementsIfSearchTextChanged();
      } else {
        this.searchText = null;
      }
    },

    stopSearching: function () {
      // Hide the clear button
      this.$clearSearchBtn.addClass('hidden');
      this.searching = false;
      this.sortByScore = false;
    },

    setInstanceState: function (key, value) {
      if (typeof key === 'object') {
        $.extend(this.instanceState, key);
      } else {
        this.instanceState[key] = value;
      }

      this.storeInstanceState();
    },

    storeInstanceState: function () {
      if (this.settings.storageKey) {
        Craft.setLocalStorage(this.settings.storageKey, this.instanceState);
      }
    },

    getSourceState: function (sourceKey, key, defaultValue) {
      // account for when all sources are disabled
      if (!sourceKey) {
        return null;
      }

      if (typeof this.sourceStates[sourceKey] === 'undefined') {
        // If this is a nested source key, see if we have a source state for the parent
        const lastSlashPos = sourceKey.lastIndexOf('/');
        if (lastSlashPos !== -1) {
          return this.getSourceState(
            sourceKey.substring(0, lastSlashPos),
            key,
            defaultValue
          );
        }
      }

      const sourceState = this.sourceStates[sourceKey] || {};

      if (typeof key === 'undefined') {
        return Object.assign({}, sourceState);
      }
      if (typeof sourceState[key] !== 'undefined') {
        return sourceState[key];
      }
      return typeof defaultValue !== 'undefined' ? defaultValue : null;
    },

    getSelectedSourceState: function (key, defaultValue) {
      return this.getSourceState(
        this.instanceState.selectedSource,
        key,
        defaultValue
      );
    },

    setSelecetedSourceState: function (key, value) {
      var viewState = this.getSelectedSourceState();

      // account for when all sources are disabled
      if (viewState == null) {
        viewState = [];
      }

      if (typeof key === 'object') {
        for (let k in key) {
          if (key.hasOwnProperty(k)) {
            if (key[k] !== null) {
              viewState[k] = key[k];
            } else {
              delete viewState[k];
            }
          }
        }
      } else if (value !== null) {
        viewState[key] = value;
      } else {
        delete viewState[key];
      }

      // account for when all sources are disabled
      let sourceKey = '*';
      if (this.instanceState.selectedSource != undefined) {
        // otherwise do what we used to do
        sourceKey = this.instanceState.selectedSource;
      }

      const sourceStates = this.sourceStates;
      sourceStates[sourceKey] = viewState;

      this.sourceStates = sourceStates;
    },

    /**
     * @deprecated in 4.3.0.
     */
    storeSortAttributeAndDirection: function () {},

    /**
     * Sets the page number.
     */
    setPage: function (page) {
      if (!this.isAdministrative) {
        return;
      }

      page = Math.max(page, 1);
      this.page = page;

      const url = Craft.getPageUrl(this.page);
      history.replaceState({}, '', url);
    },

    _resetCount: function () {
      this.resultSet = null;
      this.totalResults = null;
      this.totalUnfilteredResults = null;
    },

    updateSourceMenu: function () {
      if (!this.$sourceActionsContainer.length) {
        return;
      }

      if (this.$sourceActionsBtn) {
        this.$sourceActionsBtn.data('trigger').destroy();
        this.$sourceActionsContainer.empty();
        $(`#${this.namespaceId('source-actions-menu')}`).remove();
        this.$sourceActionsBtn = null;
      }

      const actions = this.getSourceActions();
      if (!actions.length) {
        return;
      }

      const groupedActions = [
        actions.filter((a) => !a.destructive && !a.administrative),
        actions.filter((a) => a.destructive && !a.administrative),
        actions.filter((a) => a.administrative),
      ].filter((group) => group.length);

      this.$sourceActionsBtn = $('<button/>', {
        type: 'button',
        class: 'btn action-btn hairline',
        title: Craft.t('app', 'Source settings'),
        'aria-label': Craft.t('app', 'Source settings'),
        'aria-controls': 'source-actions-menu',
      }).appendTo(this.$sourceActionsContainer);

      const $menu = $('<div/>', {
        id: 'source-actions-menu',
        class: 'menu menu--disclosure',
      }).appendTo(this.$sourceActionsContainer);

      groupedActions.forEach((group, index) => {
        if (index !== 0) {
          $('<hr/>').appendTo($menu);
        }

        this._buildActionList(group).appendTo($menu);
      });

      this.$sourceActionsBtn.disclosureMenu();
    },

    _buildActionList: function (actions) {
      const $ul = $('<ul/>');

      actions.forEach((action) => {
        const $button = $('<button/>', {
          type: 'button',
          class: 'menu-item',
          text: action.label,
        }).on('click', () => {
          this.$sourceActionsBtn.data('trigger').hide();
          if (action.onSelect) {
            action.onSelect();
          }
        });

        if (action.destructive) {
          $button.addClass('error');
        }

        $('<li/>').append($button).appendTo($ul);
      });

      return $ul;
    },

    getSourceActions: function () {
      let actions = [];

      if (Craft.userIsAdmin && Craft.allowAdminChanges) {
        actions.push({
          label: Craft.t('app', 'Customize sources'),
          administrative: true,
          onSelect: () => {
            this.createCustomizeSourcesModal();
          },
        });
      }

      return actions;
    },

    updateViewMenu: function () {
      if (
        !this.activeViewMenu ||
        this.activeViewMenu !== this.viewMenus[this.sourceKey]
      ) {
        if (this.activeViewMenu) {
          this.activeViewMenu.hideTrigger();
        }
        if (!this.viewMenus[this.sourceKey]) {
          if (
            !this.getViewModesForSource().find(
              (mode) => mode.mode === 'table'
            ) &&
            this.settings.sortable
          ) {
            return;
          }
          this.viewMenus[this.sourceKey] = new ViewMenu(this, this.$source);
        }
        this.activeViewMenu = this.viewMenus[this.sourceKey];
        this.activeViewMenu.showTrigger();
      }
    },

    /**
     * Returns any additional settings that should be passed to the view instance.
     */
    getViewSettings: function () {
      return {};
    },

    /**
     * Returns the data that should be passed to the elementIndex/getElements controller action
     * when loading elements.
     */
    getViewParams: function () {
      // baseCriteria: the criteria properties determined by the source and element index config
      // criteria: everything the user had some say in

      // baseCriteria will determine the unfilteredTotal count, in case something needs to know
      // the total number of elements the user has access to

      const baseCriteria = Object.assign(
        {
          status: null,
          drafts: this.settings.canHaveDrafts ? null : false,
          draftOf: this.settings.canHaveDrafts && this.drafts ? null : false,
          savedDraftsOnly: true,
        },
        this.baseCriteria,
        {
          siteId: this.siteId,
        }
      );

      const criteria = {
        offset: this.settings.batchSize * (this.page - 1),
        limit: this.settings.batchSize,
        search: this.searching ? this.searchText : null,
      };

      // Only set drafts/draftOf/trashed params when needed, so we don't potentially override a source's criteria
      if (this.settings.canHaveDrafts && this.drafts) {
        criteria.drafts = true;
      }

      if (this.trashed) {
        criteria.trashed = true;
      }

      if (!this.$statusMenuContainer.hasClass('hidden') && this.status) {
        criteria.status = this.status;
      }

      const params = {
        context: this.settings.context,
        elementType: this.elementType,
        canHaveDrafts: this.settings.canHaveDrafts,
        source: this.sourceKey,
        condition: this.settings.condition,
        referenceElementId: this.settings.referenceElementId,
        referenceElementSiteId: this.settings.referenceElementSiteId,
        baseCriteria,
        criteria,
        disabledElementIds: this.settings.disabledElementIds,
        viewState: $.extend({}, this.getSelectedSourceState()),
        paginated: this.paginated,
        selectable: this.selectable,
        sortable: this.sortable,
        prevalidate: this.settings.prevalidate,
      };

      params.viewState.showHeaderColumn = this.settings.showHeaderColumn;
      params.viewState.inlineEditing = this.inlineEditing;
      params.viewState.nestedInputNamespace = this.nestedInputNamespace;
      params.viewState.static = this.settings.static;

      // override viewState.mode in case it's different from what's stored
      params.viewState.mode = this.viewMode;

      if (this.viewMode === 'structure') {
        params.viewState.mode = 'table';
        params.viewState.order = 'structure';
        params.viewState.sort = 'asc';

        if (typeof this.instanceState.collapsedElementIds === 'undefined') {
          this.instanceState.collapsedElementIds = [];
        }
        params.collapsedElementIds = this.instanceState.collapsedElementIds;
      } else if (!this.sortable && !this.inlineEditing) {
        // Possible that the order/sort isn't entirely accurate if we're sorting by Score
        const [sortAttribute, sortDirection] =
          this.getSortAttributeAndDirection();
        params.viewState.order = sortAttribute;
        params.viewState.sort = sortDirection;
      }

      if (
        this.filterHuds[this.siteId] &&
        this.filterHuds[this.siteId][this.sourceKey] &&
        (this.filterHuds[this.siteId][this.sourceKey].conditionConfig ||
          this.filterHuds[this.siteId][this.sourceKey].serialized)
      ) {
        params.filterConfig =
          this.filterHuds[this.siteId][this.sourceKey].conditionConfig;
        params.filters =
          this.filterHuds[this.siteId][this.sourceKey].serialized;
      }

      if (
        this.hasImplicitSource &&
        typeof params.viewState.tableColumns === 'undefined'
      ) {
        params.viewState.tableColumns = this.getDefaultTableColumns();
      }

      // Give plugins a chance to hook in here
      this.trigger('registerViewParams', {
        params: params,
      });

      return params;
    },

    updateElements: function (preservePagination, pageChanged) {
      return new Promise((resolve, reject) => {
        // Ignore if we're not fully initialized yet
        if (!this.initialized) {
          reject('The element index isn’t initialized yet.');
          return;
        }

        this.onBeforeUpdateElements();

        // Cancel any ongoing requests
        this._cancelRequests();

        this.setIndexBusy();

        // Kill the old view class
        if (this.view) {
          this.view.disable();
        }

        if (preservePagination !== true) {
          this.prevPage = null;
          this.setPage(1);
        }

        this._resetCount();

        this._previousViewParams = this._viewParams;
        this._viewParams = this.getViewParams();

        Craft.sendActionRequest('POST', this.settings.updateElementsAction, {
          data: this._viewParams,
          cancelToken: this._createCancelToken(),
        })
          .then((response) => {
            this.setIndexAvailable();

            if (this.settings.context === 'index') {
              if (Craft.cp.fixedHeader) {
                const headerContainerHeight =
                  Craft.cp.$headerContainer.height();
                const maxScrollTop =
                  this.$main.offset().top - headerContainerHeight;
                if (maxScrollTop < Garnish.$scrollContainer.scrollTop()) {
                  Garnish.$scrollContainer.scrollTop(maxScrollTop);
                }
              }
            } else {
              this.$main.scrollTop(0);
            }

            this._updateView(this._viewParams, response.data);

            if (this.criteriaHasChanged() && !this.sourceHasChanged()) {
              const itemLabel = this.getItemLabel();
              const itemsLabel = this.getItemsLabel();

              this._countResults().then((total) => {
                let successMessage;

                if (!this.paginated) {
                  successMessage = Craft.t(
                    'app',
                    'Showing {total, number} {total, plural, =1{{item}} other{{items}}}',
                    {
                      total: total,
                      item: itemLabel,
                      items: itemsLabel,
                    }
                  );
                } else {
                  const first = this.getFirstItemNumber(total);
                  successMessage = Craft.t(
                    'app',
                    'Showing {first, number}-{last, number} of {total, number} {total, plural, =1{{item}} other{{items}}}',
                    {
                      first: first,
                      last: this.getLastItemNumber(first, total),
                      total: total,
                      item: itemLabel,
                      items: itemsLabel,
                    }
                  );
                }

                this.updateLiveRegion(successMessage);
              });
            } else {
              this.updateLiveRegion(this.getSortMessage());
            }

            resolve();
          })
          .catch((e) => {
            if (!axios.isCancel(e)) {
              this.setIndexAvailable();
              Craft.cp.displayError(e?.response?.data?.message);
            }
            reject(e);
          });
      });
    },

    criteriaHasChanged: function () {
      if (!this._previousViewParams) {
        return false;
      }

      return !Craft.compare(
        this._viewParams.criteria,
        this._previousViewParams.criteria
      );
    },

    sourceHasChanged: function () {
      if (!this._previousViewParams) {
        return false;
      }

      return this._viewParams.source !== this._previousViewParams.source;
    },

    sortHasChanged: function () {
      if (!this._previousViewParams) {
        return false;
      }

      return (
        this._viewParams.viewState.order !==
          this._previousViewParams.viewState.order ||
        this._viewParams.viewState.sort !==
          this._previousViewParams.viewState.sort
      );
    },

    updateElementsIfSearchTextChanged: function () {
      if (
        this.searchText !==
        (this.searchText = this.searching ? this.$search.val() : null)
      ) {
        if (this.settings.context === 'index') {
          Craft.setQueryParam('search', this.$search.val());
        }
        this.updateElements();
      }
    },

    getSortMessage: function () {
      const attribute = this.getSelectedSortAttribute();
      const direction =
        this.getSelectedSortDirection() === 'asc'
          ? Craft.t('app', 'Ascending')
          : Craft.t('app', 'Descending');
      const sortLabel = this.getSortLabel(attribute);

      if (!attribute && !direction && !sortLabel) return;

      return Craft.t('app', '{name} sorted by {attribute}, {direction}', {
        name: this.getSourceLabel(),
        attribute: sortLabel,
        direction: direction,
      });
    },

    updateLiveRegion: function (message) {
      if (!message) return;

      this.$srStatusContainer.empty().text(message);

      // Clear message after interval
      setTimeout(() => {
        const currentMessage = this.$srStatusContainer.text();

        // Check that this is the same message and hasn't been updated since
        if (message !== currentMessage) return;

        this.$srStatusContainer.empty();
      }, 5000);
    },

    showActionTriggers: function () {
      // Ignore if they're already shown
      if (this.showingActionTriggers) {
        return;
      }

      if (!this._$triggers) {
        this._createTriggers();
      } else {
        this._$triggers.appendTo(this.$actionsContainer);
      }

      this.showingActionTriggers = true;
    },

    submitAction: async function (action, actionParams, beforeCallback) {
      // Make sure something's selected
      var selectedElementIds = this.view.getSelectedElementIds(),
        totalSelected = selectedElementIds.length;

      if (totalSelected === 0) {
        return false;
      }

      if (typeof action === 'string') {
        action = this._findAction(action);
      }

      if (action.confirm && !confirm(action.confirm)) {
        return false;
      }

      if (beforeCallback) {
        await beforeCallback();
      }

      // Cancel any ongoing requests
      this._cancelRequests();

      // Get ready to submit
      var viewParams = this.getViewParams();

      actionParams = actionParams ? Craft.expandPostArray(actionParams) : {};
      var params = $.extend(viewParams, action.settings || {}, actionParams, {
        elementAction: action.type,
        elementIds: selectedElementIds,
      });

      // Do it
      this.setIndexBusy();
      this._autoSelectElements = selectedElementIds;

      if (action.download) {
        if (Craft.csrfTokenName) {
          params[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }
        try {
          await Craft.downloadFromUrl(
            'POST',
            Craft.getActionUrl(this.settings.submitActionsAction),
            params
          );
        } finally {
          this.setIndexAvailable();
        }
      } else {
        try {
          const response = await Craft.sendActionRequest(
            'POST',
            this.settings.submitActionsAction,
            {
              data: params,
              cancelToken: this._createCancelToken(),
            }
          );

          // Update the count text too
          this._resetCount();
          this._updateView(viewParams, response.data);

          if (typeof response.data.badgeCounts !== 'undefined') {
            this._updateBadgeCounts(response.data.badgeCounts);
          }

          if (response.data.message) {
            Craft.cp.displaySuccess(response.data.message);
          }

          this.afterAction(action, params);
        } catch (e) {
          Craft.cp.displayError(e?.response?.data?.message);
        } finally {
          this.setIndexAvailable();
        }
      }
    },

    _findAction: function (actionClass) {
      for (var i = 0; i < this.actions.length; i++) {
        if (this.actions[i].type === actionClass) {
          return this.actions[i];
        }
      }
      throw `Invalid element action: ${actionClass}`;
    },

    afterAction: function (action, params) {
      // There may be a new background job that needs to be run
      Craft.cp.runQueue();

      this.onAfterAction(action, params);
    },

    hideActionTriggers: function () {
      // Ignore if there aren't any
      if (!this.showingActionTriggers) {
        return;
      }

      this._$triggers.detach();

      this.showingActionTriggers = false;
    },

    updateSelectAllCheckbox: function () {
      if (this.$selectAllCheckbox) {
        const totalSelected = this.view.getSelectedElements().length;

        if (totalSelected !== 0) {
          if (totalSelected === this.view.getEnabledElements().length) {
            this.$selectAllCheckbox.removeClass('indeterminate');
            this.$selectAllCheckbox.addClass('checked');
            this.$selectAllCheckbox.attr('aria-checked', 'true');
          } else {
            this.$selectAllCheckbox.addClass('indeterminate');
            this.$selectAllCheckbox.removeClass('checked');
            this.$selectAllCheckbox.attr('aria-checked', 'mixed');
          }
        } else {
          this.$selectAllCheckbox.removeClass('indeterminate checked');
          this.$selectAllCheckbox.attr('aria-checked', 'false');
        }
      }
    },

    updateActionTriggers: function () {
      // Do we have an action UI to update?
      if (this.actions) {
        const totalSelected = this.view.getSelectedElements().length;

        if (totalSelected !== 0) {
          this.showActionTriggers();
        } else {
          this.hideActionTriggers();
        }
      }
    },

    getSelectedElements: function () {
      return this.view ? this.view.getSelectedElements() : $();
    },

    getSelectedElementIds: function () {
      return this.view ? this.view.getSelectedElementIds() : [];
    },

    setStatus: function (status) {
      // Find the option (and make sure it actually exists)
      var $option = this.statusMenu.$options.filter(
        'a[data-status="' + status + '"]:first'
      );

      if ($option.length) {
        this.statusMenu.selectOption($option[0]);
      }
    },

    /**
     * Returns the selected sort attribute for a source
     * @param {jQuery} [$source]
     * @returns {string}
     */
    getSelectedSortAttribute: function ($source) {
      $source = $source || this.$source;

      if ($source) {
        const attribute = this.getSourceState($source.data('key'), 'order');

        // Make sure it's valid
        if (this.getSortOption(attribute, $source)) {
          return attribute;
        }
      }

      return this.getDefaultSort()[0];
    },

    /**
     * Returns the selected sort direction for a source
     * @param {jQuery} [$source]
     * @returns {string}
     */
    getSelectedSortDirection: function ($source) {
      $source = $source || this.$source;

      if ($source) {
        const direction = this.getSourceState($source.data('key'), 'sort');

        // Make sure it's valid
        if (['asc', 'desc'].includes(direction)) {
          return direction;
        }
      }

      return this.getDefaultSort()[1];
    },

    /**
     * @deprecated in 4.3.0. Use setSelectedSortAttribute() instead.
     */
    setSortAttribute: function (attr) {
      this.setSelectedSortAttribute(attr);
    },

    /**
     * Sets the selected sort attribute and direction.
     *
     * If direction isn’t provided, the attribute’s default direction will be used.
     *
     * @param {string} attr
     * @param {string} [dir]
     */
    setSelectedSortAttribute: function (attr, dir) {
      // If score, keep track of that separately
      if (attr === 'score') {
        this.sortByScore = true;
        return;
      }

      this.sortByScore = false;

      // Make sure it's valid
      const sortOption = this.getSortOption(attr);
      if (!sortOption) {
        console.warn(`Invalid sort option: ${attr}`);
        return;
      }

      if (!dir) {
        dir = sortOption.defaultDir;
      }

      const history = [];

      // Remember the previous choices
      const attributes = [attr];

      // Only include the last attribute if it changed
      const lastAttr = this.getSelectedSourceState('order');
      if (lastAttr && lastAttr !== attr) {
        history.push([lastAttr, this.getSelectedSourceState('sort')]);
        attributes.push(lastAttr);
      }

      const oldHistory = this.getSelectedSourceState('orderHistory', []);
      for (let i = 0; i < oldHistory.length; i++) {
        const [a] = oldHistory[i];
        if (a && !attributes.includes(a)) {
          history.push(oldHistory[i]);
          attributes.push(a);
        } else {
          break;
        }
      }

      this.setSelecetedSourceState({
        order: attr,
        sort: dir,
        orderHistory: history,
      });

      if (this.settings.context === 'index') {
        // Update the query string
        Craft.setQueryParam('sort', `${attr}-${dir}`);
      }
    },

    /**
     * @deprecated in 4.3.0. Use setSelectedSortAttribute() or setSelectedSortDirection() instead.
     */
    setSortDirection: function (dir) {
      this.setSelectedSortDirection(dir);
    },

    /**
     * Sets the selected sort direction, maintaining the current sort attribute.
     * @param {string} dir
     */
    setSelectedSortDirection: function (dir) {
      this.setSelectedSortAttribute(this.getSelectedSortAttribute(), dir);
    },

    /**
     * Returns the actual sort attribute, which may be different from what's selected.
     * @returns {string[]}
     */
    getSortAttributeAndDirection: function () {
      if (this.searching && this.sortByScore) {
        return ['score', 'desc'];
      }

      return [this.getSelectedSortAttribute(), this.getSelectedSortDirection()];
    },

    getSortLabel: function (attr) {
      const sortOption = this.getSortOption(attr);
      return sortOption ? sortOption.label : null;
    },

    getSelectedViewMode: function () {
      return this.validateViewMode(this.getSelectedSourceState('mode') || null);
    },

    /**
     * Returns the nesting level for a given source, where 1 = the root level
     * @param {jQuery} $source
     * @returns {number}
     */
    getSourceLevel: function ($source) {
      return $source.parentsUntil('nav', 'ul.nested').length + 1;
    },

    /**
     * Returns a source’s parent, or null if it’s the root source
     * @param {jQuery} $source
     * @returns {?jQuery}
     */
    getParentSource: function ($source) {
      const $parent = $source.parent().parent().siblings('a');
      return $parent.length ? $parent : null;
    },

    /**
     * Returns the root level source for a given source.
     * @param {jQuery} $source
     * @returns {jQuery}
     */
    getRootSource: function ($source) {
      let $parent;
      while (($parent = this.getParentSource($source))) {
        $source = $parent;
      }
      return $source;
    },

    getSourceByKey: function (key) {
      return this.sourcesByKey[key] || null;
    },

    selectSource: function (source) {
      const $source = $(source);

      // return false if there truly are no sources;
      // don't attempt to check only default/visible sources
      if (!this.sourcesByKey || !Object.keys(this.sourcesByKey).length) {
        return false;
      }

      if (
        this.$source &&
        this.$source[0] &&
        this.$source[0] === $source[0] &&
        $source.data('key') === this.sourceKey
      ) {
        return false;
      }

      // Hide action triggers if they're currently being shown
      this.hideActionTriggers();

      this.$source = $source;
      this.$rootSource = this.getRootSource($source);
      this.sourceKey = $source.data('key');
      this.rootSourceKey = this.$rootSource.data('key');
      this.setInstanceState('selectedSource', this.sourceKey);
      this.sourceNav.selectItem($source);

      this.updateMainHeading();

      if (this.searching) {
        // Clear the search value without causing it to update elements
        this.searchText = null;
        this.$search.val('');
        if (this.settings.context === 'index') {
          Craft.setQueryParam('search', null);
        }
        this.stopSearching();
      }

      // Status menu
      // ----------------------------------------------------------------------

      if (this.$statusMenuBtn.length) {
        if (typeof this.baseCriteria.status !== 'undefined') {
          this.$statusMenuContainer.addClass('hidden');
        } else {
          this.$statusMenuContainer.removeClass('hidden');
        }

        if (this.trashed) {
          // Swap to the initial status
          var $firstOption = this.statusMenu.$options.first();
          this.setStatus($firstOption.data('status'));
        }
      }

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
        this.$viewModeBtnContainer = $(
          '<section class="btngroup btngroup--exclusive"/>'
        )
          .attr('aria-label', Craft.t('app', 'View'))
          .insertAfter(this.$searchContainer);

        for (var i = 0; i < this.sourceViewModes.length; i++) {
          let sourceViewMode = this.sourceViewModes[i];

          let $viewModeBtn = $('<button/>', {
            type: 'button',
            class:
              'btn' +
              (typeof sourceViewMode.className !== 'undefined'
                ? ` ${sourceViewMode.className}`
                : ''),
            'data-view': sourceViewMode.mode,
            'data-icon': sourceViewMode.icon,
            'aria-label': sourceViewMode.title,
            'aria-pressed': 'false',
            title: sourceViewMode.title,
          }).appendTo(this.$viewModeBtnContainer);

          this.viewModeBtns[sourceViewMode.mode] = $viewModeBtn;

          this.addListener(
            $viewModeBtn,
            'click',
            {mode: sourceViewMode.mode},
            function (ev) {
              this.selectViewMode(ev.data.mode);
              this.updateElements();
            }
          );
        }
      }

      // Figure out which mode we should start with
      var viewMode = this.getSelectedSourceState('mode');

      // Maintain the structure view for source states that were saved with an older Craft version
      if (
        viewMode === 'table' &&
        this.getSourceState($source.data('key'), 'order') === 'structure'
      ) {
        viewMode = 'structure';
      }

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

      this.updateSourceMenu();
      this.updateViewMenu();

      if (!this.filterHudExists() && this.$source.data('default-filter')) {
        this.createFilterHud({
          showOnInit: false,
          conditionConfig: this.$source.data('default-filter'),
        });
      } else {
        this.updateFilterBtn();
      }

      this.onSelectSource();

      this.sourcePath =
        this.sourcePaths[this.sourceKey] ||
        this.$source.data('default-source-path');

      if (this.settings.context === 'index') {
        const urlParams = Craft.getQueryParams();
        urlParams.source = this.sourceKey;
        Craft.setUrl(Craft.getUrl(Craft.path, urlParams));
      }

      return true;
    },

    selectSourceByKey: function (key) {
      var $source = this.getSourceByKey(key);

      if ($source) {
        return this.selectSource($source);
      } else {
        return false;
      }
    },

    getSourceData($source, key) {
      $source ||= this.$source;
      if (!$source) {
        return undefined;
      }
      const data = $source.data(key);
      if (data === undefined) {
        const $parentSource = this.getParentSource($source);
        if ($parentSource) {
          return this.getSourceData($parentSource, key);
        }
      }
      return data;
    },

    /**
     * Returns the available sort attributes for a source (or the selected root source)
     * @param {jQuery} [$source]
     * @returns {Object[]}
     */
    getSortOptions: function ($source) {
      const sortOptions = this.getSourceData($source, 'sort-opts') || [];

      // Make sure there's at least one attribute
      if (!sortOptions.length) {
        sortOptions.push({
          label: Craft.t('app', 'Title'),
          attr: 'title',
          defaultDir: 'asc',
        });
      }

      return sortOptions;
    },

    /**
     * Returns info about a sort attribute.
     * @param {string} attribute
     * @param {jQuery} [$source]
     * @returns {?Object}
     */
    getSortOption: function (attribute, $source) {
      return (
        this.getSortOptions($source).find((o) => o.attr === attribute) || null
      );
    },

    /**
     * Returns the default sort attribute and direction for a source.
     * @param {jQuery} [$source]
     * @returns {string[]}
     */
    getDefaultSort: function ($source) {
      let defaultSort = this.getSourceData($source, 'default-sort');
      if (defaultSort) {
        if (typeof defaultSort === 'string') {
          defaultSort = [defaultSort];
        }

        // Make sure it's valid
        const sortOption = this.getSortOption(defaultSort[0], $source);
        if (sortOption) {
          // Fill in the default direction if it's not specified
          if (!defaultSort[1]) {
            defaultSort[1] = sortOption.defaultDir;
          }

          return defaultSort;
        }
      }

      // Default to the first sort option
      const sortOptions = this.getSortOptions($source);
      return [sortOptions[0].attr, sortOptions[0].defaultDir];
    },

    /**
     * Returns the available table columns for a source (or the selected root source)
     * @param {jQuery} [$source]
     * @returns {Object[]}
     */
    getTableColumnOptions: function ($source) {
      return this.getSourceData($source, 'table-col-opts') || [];
    },

    /**
     * Returns info about a table column.
     * @param {string} attribute
     * @param {jQuery} [$source]
     * @returns {?Object}
     */
    getTableColumnOption: function (attribute, $source) {
      return (
        this.getTableColumnOptions($source).find((o) => o.attr === attribute) ||
        null
      );
    },

    /**
     * Returns the default table columns for a source (or the selected root source)
     * @param {jQuery} [$source]
     * @returns {string[]}
     */
    getDefaultTableColumns: function ($source) {
      return this.getSourceData($source, 'default-table-cols') || [];
    },

    /**
     * Returns the selected sort attribute for a source
     * @param {jQuery} [$source]
     * @returns {string[]}
     */
    getSelectedTableColumns: function ($source) {
      $source ||= this.$source;
      if ($source) {
        const attributes = this.getSourceState(
          $source.data('key'),
          'tableColumns'
        );

        if (attributes) {
          // Only return the valid ones
          return attributes.filter(
            (a) => !!this.getTableColumnOption(a, $source)
          );
        }
      }

      return this.getDefaultTableColumns($source);
    },

    setSelectedTableColumns: function (attributes) {
      this.setSelecetedSourceState({
        tableColumns: attributes,
      });

      // Update the view menu
      if (this.activeViewMenu) {
        this.activeViewMenu.updateTableColumnField();
      }
    },

    getViewModesForSource: function () {
      const viewModes = [];

      if (!Garnish.isMobileBrowser(true)) {
        if (Garnish.hasAttr(this.$source, 'data-has-structure')) {
          viewModes.push({
            mode: 'structure',
            title: Craft.t('app', 'Display in a structured table'),
            icon: Craft.orientation === 'rtl' ? 'structurertl' : 'structure',
          });
        }

        viewModes.push({
          mode: 'table',
          title: Craft.t('app', 'Display in a table'),
          icon: 'list',
        });
      }

      if (this.$source && Garnish.hasAttr(this.$source, 'data-has-thumbs')) {
        viewModes.push({
          mode: 'thumbs',
          title: Craft.t('app', 'Display as thumbnails'),
          icon: 'grid',
        });
      }

      viewModes.push({
        mode: 'cards',
        title: Craft.t('app', 'Display as cards'),
        icon: 'element-cards',
      });

      if (this.settings.allowedViewModes) {
        return viewModes.filter((mode) =>
          this.settings.allowedViewModes.includes(mode.mode)
        );
      }

      return viewModes;
    },

    doesSourceHaveViewMode: function (viewMode) {
      if (!this.sourceViewModes) {
        return false;
      }

      for (var i = 0; i < this.sourceViewModes.length; i++) {
        if (this.sourceViewModes[i].mode === viewMode) {
          return true;
        }
      }

      return false;
    },

    validateViewMode: function (viewMode) {
      if (viewMode && this.doesSourceHaveViewMode(viewMode)) {
        return viewMode;
      }

      if (this.sourceViewModes && this.sourceViewModes.length) {
        return this.sourceViewModes[0].mode;
      }

      return this.doesSourceHaveViewMode('table') ? 'table' : 'cards';
    },

    selectViewMode: function (viewMode, force) {
      // Make sure that the current source supports it
      if (!force && !this.doesSourceHaveViewMode(viewMode)) {
        viewMode = this.sourceViewModes[0].mode;
      }

      // Has anything changed?
      if (viewMode === this._viewMode) {
        return;
      }

      // Deselect the previous view mode
      if (
        this._viewMode &&
        typeof this.viewModeBtns[this._viewMode] !== 'undefined'
      ) {
        this.viewModeBtns[this._viewMode]
          .removeClass('active')
          .attr('aria-pressed', 'false');
      }

      this._viewMode = viewMode;
      this.setSelecetedSourceState('mode', this._viewMode);

      if (typeof this.viewModeBtns[this._viewMode] !== 'undefined') {
        this.viewModeBtns[this._viewMode]
          .addClass('active')
          .attr('aria-pressed', 'true');
      }
    },

    createView: function (mode, settings) {
      var viewClass = this.getViewClass(mode);
      return new viewClass(this, this.$elements, settings);
    },

    getViewClass: function (mode) {
      switch (mode) {
        case 'table':
        case 'structure':
          return Craft.TableElementIndexView;
        case 'cards':
          return Craft.CardsElementIndexView;
        case 'thumbs':
          return Craft.ThumbsElementIndexView;
        default:
          throw `View mode "${mode}" not supported.`;
      }
    },

    rememberDisabledElementId: function (id) {
      var index = $.inArray(id, this.settings.disabledElementIds);

      if (index === -1) {
        this.settings.disabledElementIds.push(id);
      }
    },

    forgetDisabledElementId: function (id) {
      var index = $.inArray(id, this.settings.disabledElementIds);

      if (index !== -1) {
        this.settings.disabledElementIds.splice(index, 1);
      }
    },

    enableElements: function ($elements) {
      $elements
        .removeClass('disabled')
        .parents('.disabled')
        .removeClass('disabled');

      for (var i = 0; i < $elements.length; i++) {
        var id = $($elements[i]).data('id');
        this.forgetDisabledElementId(id);
      }

      this.onEnableElements($elements);
    },

    disableElements: function ($elements) {
      $elements.removeClass('sel').addClass('disabled');

      for (var i = 0; i < $elements.length; i++) {
        var id = $($elements[i]).data('id');
        this.rememberDisabledElementId(id);
      }

      this.onDisableElements($elements);
    },

    getElementById: function (id) {
      return this.view.getElementById(id);
    },

    enableElementsById: function (ids) {
      ids = $.makeArray(ids);

      for (var i = 0; i < ids.length; i++) {
        var id = ids[i],
          $element = this.getElementById(id);

        if ($element && $element.length) {
          this.enableElements($element);
        } else {
          this.forgetDisabledElementId(id);
        }
      }
    },

    disableElementsById: function (ids) {
      ids = $.makeArray(ids);

      for (var i = 0; i < ids.length; i++) {
        var id = ids[i],
          $element = this.getElementById(id);

        if ($element && $element.length) {
          this.disableElements($element);
        } else {
          this.rememberDisabledElementId(id);
        }
      }
    },

    selectElementAfterUpdate: function (id) {
      if (this._autoSelectElements === null) {
        this._autoSelectElements = [];
      }

      this._autoSelectElements.push(id);
    },

    addButton: function ($button) {
      this.getButtonContainer().append($button);
    },

    isShowingSidebar: function () {
      if (this.showingSidebar === null) {
        this.showingSidebar =
          this.$sidebar.length && !this.$sidebar.hasClass('hidden');
      }

      return this.showingSidebar;
    },

    getButtonContainer: function () {
      // Is there a predesignated place where buttons should go?
      if (this.settings.buttonContainer) {
        return $(this.settings.buttonContainer);
      } else {
        var $container = $(`#${this.namespaceId('action-buttons')}`);

        if (!$container.length) {
          $container = $(`<div/>`, {
            id: this.namespaceId('action-buttons'),
          }).appendTo($(`#${this.namespaceId('header')}`));
        }

        return $container;
      }
    },

    setIndexBusy: function () {
      this.$elements.addClass('busy');
      this.$updateSpinner.appendTo(this.$elements);
      this.isIndexBusy = true;

      // Blur the active element, if it's within the element listing pane
      if (
        document.activeElement &&
        this.$elements[0].contains(document.activeElement)
      ) {
        this._activeElement = document.activeElement;
        document.activeElement.blur();
      }

      let elementsHeight = this.$elements.height();
      let windowHeight = window.innerHeight;
      let scrollTop = $(document).scrollTop();

      if (this.settings.context == 'modal') {
        windowHeight = this.$elements.parents('.modal').height();
        scrollTop = this.$elements.scrollParent().scrollTop();
      }

      if (elementsHeight > windowHeight) {
        let positionTop = Math.floor(scrollTop + windowHeight / 2) - 100;
        positionTop = Math.floor((positionTop / elementsHeight) * 100);

        document.documentElement.style.setProperty(
          '--elements-busy-top-position',
          positionTop + '%'
        );
      }
      this.updateLiveRegion(Craft.t('app', 'Loading'));
    },

    setIndexAvailable: function () {
      this.$elements.removeClass('busy');
      this.$updateSpinner.remove();
      this.isIndexBusy = false;

      // Refocus the previously-focused element
      if (this._activeElement) {
        if (
          !document.activeElement ||
          document.activeElement === document.body
        ) {
          if (document.body.contains(this._activeElement)) {
            this._activeElement.focus();
          } else if (this._activeElement.id) {
            $(`#${this._activeElement.id}`).focus();
          }
        }
        this._activeElement = null;
      }
    },

    createCustomizeSourcesModal: function () {
      // Recreate it each time
      var modal = new Craft.CustomizeSourcesModal(this, {
        hideOnEsc: false,
        hideOnShadeClick: false,
        onHide: function () {
          modal.destroy();
        },
      });

      return modal;
    },

    disable: function () {
      if (this.sourceNav) {
        this.sourceNav.disable();
      }

      if (this.view) {
        this.view.disable();
      }

      this.base();
    },

    enable: function () {
      if (this.sourceNav) {
        this.sourceNav.enable();
      }

      if (this.view) {
        this.view.enable();
      }

      this.base();
    },

    onAfterInit: function () {
      this.settings.onAfterInit();
      this.trigger('afterInit');
    },

    onSelectSource: function () {
      this.settings.onSelectSource(this.sourceKey);
      this.trigger('selectSource', {sourceKey: this.sourceKey});
    },

    onSelectSite: function () {
      this.settings.onSelectSite(this.siteId);
      this.trigger('selectSite', {siteId: this.siteId});
    },

    onBeforeUpdateElements: function () {
      this.settings.onBeforeUpdateElements();
      this.trigger('beforeUpdateElements');
    },

    onUpdateElements: function () {
      this.settings.onUpdateElements();
      this.trigger('updateElements');
    },

    onCountResults: function () {
      this.settings.onCountResults(
        this.totalResults,
        this.totalUnfilteredResults
      );
      this.trigger('countResults', {
        total: this.totalResults,
        totalUnfiltered: this.totalUnfilteredResults,
      });
    },

    onSelectionChange: function () {
      this.settings.onSelectionChange();
      this.trigger('selectionChange');
    },

    onEnableElements: function ($elements) {
      this.settings.onEnableElements($elements);
      this.trigger('enableElements', {elements: $elements});
    },

    onDisableElements: function ($elements) {
      this.settings.onDisableElements($elements);
      this.trigger('disableElements', {elements: $elements});
    },

    onAfterAction: function (action, params) {
      this.settings.onAfterAction(action, params);
      this.trigger('afterAction', {action: action, params: params});
    },

    // UI state handlers
    // -------------------------------------------------------------------------

    _handleSourceSelectionChange: function (event) {
      // If the selected source was just removed (maybe because its parent was collapsed),
      // there won't be a selected source

      if (!this.sourceNav.$selectedItem) {
        this.sourceNav.selectItem(this.$visibleSources.first());
        return;
      }

      if (this.selectSource(this.sourceNav.$selectedItem)) {
        this.updateElements();
      }
    },

    _handleActionTriggerSubmit: function (ev) {
      ev.preventDefault();

      const $form = $(ev.currentTarget);

      // Make sure Craft.ElementActionTrigger isn't overriding this
      if ($form.hasClass('disabled') || $form.data('custom-handler')) {
        return;
      }

      this._submitActionInternal(
        $form.data('action'),
        $form.data('trigger') && $form.data('trigger').data('trigger'),
        Garnish.getPostData($form)
      );
    },

    _handleMenuActionTriggerSubmit: function (ev) {
      const $option = $(ev.option);

      // Make sure Craft.ElementActionTrigger isn't overriding this
      if ($option.hasClass('disabled') || $option.data('custom-handler')) {
        return;
      }

      this._submitActionInternal(
        $option.data('action'),
        $option.data('trigger')
      );
    },

    _submitActionInternal: async function (action, trigger, actionParams) {
      const $selectedElements = this.getSelectedElements();

      await this.submitAction(action, actionParams, async () => {
        if (trigger) {
          await trigger.settings.beforeActivate($selectedElements, this);
        }
      });

      if (trigger) {
        await trigger.settings.afterActivate($selectedElements, this);
      }
    },

    _handleStatusChange: function (ev) {
      this.statusMenu.$options.removeClass('sel');
      var $option = $(ev.selectedOption).addClass('sel');
      this.$statusMenuBtn.html($option.html());

      this.trashed = false;
      this.drafts = false;
      this.status = null;
      let queryParam = null;

      if (Garnish.hasAttr($option, 'data-trashed')) {
        this.trashed = true;
        queryParam = 'trashed';
      } else if (Garnish.hasAttr($option, 'data-drafts')) {
        this.drafts = true;
        queryParam = 'drafts';
      } else {
        this.status = queryParam = $option.data('status') || null;
      }

      if (this.settings.context === 'index') {
        Craft.setQueryParam('status', queryParam);
      }

      this.updateElements();
    },

    _handleSiteChange: function (ev) {
      this.siteMenu.$options.removeClass('sel');
      var $option = $(ev.selectedOption).addClass('sel');
      this.$siteMenuBtn.html($option.html());
      this._setSite($option.data('site-id'));
      if (this.initialized) {
        this.updateElements();
      }
      this.onSelectSite();
    },

    _setSite: function (siteId) {
      let firstSite = this.siteId === null;
      this.siteId = siteId;

      this.updateSourceVisibility();

      if (
        this.initialized &&
        !firstSite &&
        (!this.$source || !this.$source.length) &&
        this.$visibleSources.length
      ) {
        this.selectSource(this.$visibleSources[0]);
      }

      // Hide any empty-nester headings
      var $headings = this.getSourceContainer().children('.heading');
      var $heading;

      for (let i = 0; i < $headings.length; i++) {
        $heading = $headings.eq(i);
        if ($heading.has('> ul > li:not(.hidden)').length !== 0) {
          $heading.removeClass('hidden');
        } else {
          $heading.addClass('hidden');
        }
      }

      if (this.initialized) {
        if (this.settings.context === 'index') {
          // Remember this site for later
          Craft.cp.setSiteId(siteId);
        }

        this.updateFilterBtn();
      }
    },

    updateSourceVisibility: function () {
      this.$visibleSources = $();

      for (let i = 0; i < this.$sources.length; i++) {
        const $source = this.$sources.eq(i);

        if (
          !Garnish.hasAttr($source, 'data-disabled') &&
          (typeof $source.data('sites') === 'undefined' ||
            $source
              .data('sites')
              .toString()
              .split(',')
              .some((siteId) => siteId == this.siteId))
        ) {
          $source.parent().removeClass('hidden');
          this.$visibleSources = this.$visibleSources.add($source);
        } else {
          $source.parent().addClass('hidden');

          // Is this the currently selected source?
          if (this.$source && this.$source.get(0) === $source.get(0)) {
            this.$source = null;
            this.$rootSource = null;
            this.sourceKey = null;
            this.rootSourceKey = null;
          }
        }
      }
    },

    _handleSelectionChange: function () {
      this.updateSelectAllCheckbox();
      this.updateActionTriggers();
      this.onSelectionChange();
    },

    _handleSourceDblClick: function (ev) {
      this._toggleSource($(ev.currentTarget));
      ev.stopPropagation();
    },

    _handleSourceToggleClick: function (ev) {
      this._toggleSource($(ev.currentTarget).prev('a'));
      ev.stopPropagation();
    },

    // Source management
    // -------------------------------------------------------------------------

    _getSourcesInList: function ($list, topLevel) {
      let $sources = $list.find('> li:not(.heading) > a');
      if (topLevel) {
        $sources = $sources.add($list.find('> li.heading > ul > li > a'));
      }
      return $sources;
    },

    _getChildSources: function ($source) {
      var $list = $source.siblings('ul');
      return this._getSourcesInList($list);
    },

    _getSourceToggle: function ($source) {
      return $source.siblings('.toggle');
    },

    _toggleSource: function ($source) {
      if ($source.parent('li').hasClass('expanded')) {
        this._collapseSource($source);
      } else {
        this._expandSource($source);
      }
    },

    _expandSource: function ($source) {
      $source.next('.toggle').attr({
        'aria-expanded': 'true',
      });
      $source.parent('li').addClass('expanded');

      var $childSources = this._getChildSources($source);
      for (let i = 0; i < $childSources.length; i++) {
        this.initSource($($childSources[i]));
        if (this.$visibleSources) {
          this.$visibleSources = this.$visibleSources.add($childSources[i]);
        }
      }

      var key = $source.data('key');
      if (this.instanceState.expandedSources.indexOf(key) === -1) {
        this.instanceState.expandedSources.push(key);
        this.storeInstanceState();
      }
    },

    _collapseSource: function ($source) {
      $source.next('.toggle').attr({
        'aria-expanded': 'false',
        'aria-label': Craft.t('app', 'Show nested sources'),
      });
      $source.parent('li').removeClass('expanded');

      var $childSources = this._getChildSources($source);
      for (let i = 0; i < $childSources.length; i++) {
        this.deinitSource($($childSources[i]));
        this.$visibleSources = this.$visibleSources.not($childSources[i]);
      }

      var i = this.instanceState.expandedSources.indexOf($source.data('key'));
      if (i !== -1) {
        this.instanceState.expandedSources.splice(i, 1);
        this.storeInstanceState();
      }
    },

    // View
    // -------------------------------------------------------------------------

    async _updateView(params, response) {
      // Cleanup
      // -------------------------------------------------------------

      // Get rid of the old action triggers regardless of whether the new batch has actions or not
      if (this.actions) {
        this.hideActionTriggers();

        if (this.triggers) {
          for (let trigger of this.triggers) {
            trigger.destroy();
          }
        }

        this.actions =
          this.actionsHeadHtml =
          this.actionsBodyHtml =
          this.triggers =
          this._$triggers =
          this.$actionMenuBtn =
            null;
      }

      // Update the count text
      // -------------------------------------------------------------

      if (this.$countContainer.length) {
        this.$countSpinner.removeClass('hidden');
        this.$countContainer.html('');

        this._countResults()
          .then((total) => {
            this.$countSpinner.addClass('hidden');
            const itemLabel = this.getItemLabel();
            const itemsLabel = this.getItemsLabel();

            if (!this.paginated) {
              let countLabel = Craft.t(
                'app',
                '{total, number} {total, plural, =1{{item}} other{{items}}}',
                {
                  total: total,
                  item: itemLabel,
                  items: itemsLabel,
                }
              );
              this.$countContainer.text(countLabel);
            } else {
              const first = this.getFirstItemNumber(total);
              const last = this.getLastItemNumber(first, total);
              let countLabel = Craft.t(
                'app',
                '{first, number}-{last, number} of {total, number} {total, plural, =1{{item}} other{{items}}}',
                {
                  first: first,
                  last: last,
                  total: total,
                  item: itemLabel,
                  items: itemsLabel,
                }
              );

              let $paginationContainer = $(
                '<div class="flex pagination"/>'
              ).appendTo(this.$countContainer);
              let totalPages = Math.max(
                Math.ceil(total / this.settings.batchSize),
                1
              );

              const $paginationNav = $('<nav/>', {
                class: 'flex',
                'aria-label': Craft.t('app', '{element} pagination', {
                  element: itemLabel,
                }),
              }).appendTo($paginationContainer);

              let $prevBtn = $('<button/>', {
                type: 'button',
                role: 'button',
                class:
                  'page-link prev-page' + (this.page > 1 ? '' : ' disabled'),
                disabled: this.page === 1,
                title: Craft.t('app', 'Previous Page'),
              }).appendTo($paginationNav);
              let $nextBtn = $('<button/>', {
                type: 'button',
                role: 'button',
                class:
                  'page-link next-page' +
                  (this.page < totalPages ? '' : ' disabled'),
                disabled: this.page === totalPages,
                title: Craft.t('app', 'Next Page'),
              }).appendTo($paginationNav);

              // Choose pagination button to focus based on user behavior
              if (this.prevPage) {
                let $btnToFocus =
                  this.prevPage > this.page ? $prevBtn : $nextBtn;

                if ($btnToFocus.attr('disabled') === 'disabled') {
                  $btnToFocus = Garnish.firstFocusableElement($paginationNav);
                }

                $btnToFocus.focus();
              }

              $('<div/>', {
                class: 'page-info',
                text: countLabel,
              }).appendTo($paginationContainer);

              if (this.page > 1) {
                this.addListener($prevBtn, 'click', function () {
                  this.removeListener($prevBtn, 'click');
                  this.removeListener($nextBtn, 'click');
                  this.prevPage = this.page;
                  this.setPage(this.page - 1);
                  this.updateElements(true);
                });
              }

              if (this.page < totalPages) {
                this.addListener($nextBtn, 'click', function () {
                  this.removeListener($prevBtn, 'click');
                  this.removeListener($nextBtn, 'click');
                  this.prevPage = this.page;
                  this.setPage(this.page + 1);
                  this.updateElements(true);
                });
              }
            }
          })
          .catch(() => {
            this.$countSpinner.addClass('hidden');
          });
      }

      // Update the view with the new container + elements HTML
      // -------------------------------------------------------------

      this.$elements.html(response.html);
      await Craft.appendHeadHtml(response.headHtml);
      await Craft.appendBodyHtml(response.bodyHtml);

      // Batch actions setup
      // -------------------------------------------------------------

      this.$selectAllContainer = this.$elements.find(
        '.selectallcontainer:first'
      );

      if (this.multiSelect || (response.actions && response.actions.length)) {
        if (this.$selectAllContainer.length) {
          if (response.actions && response.actions.length) {
            this.actions = response.actions;
            this.actionsHeadHtml = response.actionsHeadHtml;
            this.actionsBodyHtml = response.actionsBodyHtml;
          }

          if (this.settings.actions && this.settings.actions.length) {
            if (!this.actions) {
              this.actions = [];
            }

            for (let action of this.settings.actions) {
              // clone it so bodyHtml doesn't make it into this.actions
              action = Object.assign({}, action);
              if (action.bodyHtml) {
                this.actionsBodyHtml += action.bodyHtml;
                delete action.bodyHtml;
              }
              this.actions.push(action);
            }
          }

          // Create the select all checkbox
          this.$selectAllCheckbox = $('<div class="checkbox"/>')
            .prependTo(this.$selectAllContainer)
            .attr({
              role: 'checkbox',
              tabindex: '0',
              'aria-checked': 'false',
              'aria-label': Craft.t('app', 'Select all'),
            });

          this.addListener(this.$selectAllContainer, 'click', function () {
            if (this.view.getSelectedElements().length === 0) {
              this.view.selectAllElements();
            } else {
              this.view.deselectAllElements();
            }
          });

          this.addListener(this.$selectAllCheckbox, 'keydown', function (ev) {
            if (ev.keyCode === Garnish.SPACE_KEY) {
              ev.preventDefault();

              $(ev.currentTarget).trigger('click');
            }
          });
        }
      } else if (this.$selectAllContainer.prop('nodeName') !== 'TH') {
        if (!this.$selectAllContainer.siblings().length) {
          this.$selectAllContainer.parent('.header').remove();
        }
        this.$selectAllContainer.remove();
      }

      if (this.selectable) {
        const role = this.multiSelect ? 'checkbox' : 'radio';
        this.$elements.find('.checkbox').attr('role', role);
        if (!this.multiSelect) {
          this.$elements.attr('role', 'radiogroup');
        }
      } else {
        this.$elements.removeAttr('role');
      }

      // Exporters setup
      // -------------------------------------------------------------

      this.exporters = response.exporters;
      this.exportersByType = Craft.index(this.exporters || [], (e) => e.type);

      if (this.exporters && this.exporters.length) {
        this.$exportBtn.removeClass('hidden');
      } else {
        this.$exportBtn.addClass('hidden');
      }

      // Create the view
      // -------------------------------------------------------------

      const settings = Object.assign(
        {
          context: this.settings.context,
          batchSize:
            this.isAdministrative || this.viewMode === 'structure'
              ? this.settings.batchSize
              : null,
          params: params,
          selectable: this.selectable,
          multiSelect: this.multiSelect,
          canSelectElement: this.settings.canSelectElement,
          checkboxMode: this.selectable,
          waitForDoubleClicks: this.settings.waitForDoubleClicks,
          sortable: this.sortable,
          onSelectionChange: this._handleSelectionChange.bind(this),
          onSortChange: this.settings.onSortChange,
        },
        this.getViewSettings(),
        this.settings.viewSettings()
      );

      // Kill the old view class
      if (this.view) {
        this.view.destroy();
        delete this.view;
      }

      this.view = this.createView(this.getSelectedViewMode(), settings);

      // Auto-select elements
      // -------------------------------------------------------------

      if (this._autoSelectElements) {
        if (this.selectable) {
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

    _updateBadgeCounts: function (badgeCounts) {
      for (let sourceKey in badgeCounts) {
        if (badgeCounts.hasOwnProperty(sourceKey)) {
          const $source = this.getSourceByKey(sourceKey);
          if ($source) {
            let $badge = $source.children('.badge');
            if (badgeCounts[sourceKey] !== null) {
              if (!$badge.length) {
                $badge = $('<span class="badge"/>').appendTo($source);
              }
              $badge.text(badgeCounts[sourceKey]);
            } else if ($badge) {
              $badge.remove();
            }
          }
        }
      }
    },

    _countResults: function () {
      return new Promise((resolve, reject) => {
        if (this.totalResults !== null) {
          resolve(this.totalResults, this.totalUnfilteredResults);
          this.onCountResults();
        } else {
          var params = this.getViewParams();
          delete params.baseCriteria.offset;
          delete params.baseCriteria.limit;

          // Make sure we've got an active result set ID
          if (this.resultSet === null) {
            this.resultSet = Math.floor(Math.random() * 100000000);
          }
          params.resultSet = this.resultSet;

          Craft.sendActionRequest('POST', this.settings.countElementsAction, {
            data: params,
            cancelToken: this._createCancelToken(),
          })
            .then((response) => {
              if (response.data.resultSet == this.resultSet) {
                this.totalResults = response.data.total;
                this.totalUnfilteredResults = response.data.unfilteredTotal;
                resolve(this.totalResults, this.totalUnfilteredResults);
                this.onCountResults();
              } else {
                reject();
              }
            })
            .catch(reject);
        }
      });
    },

    _createTriggers: async function () {
      this.triggers = [];
      this._$triggers = $();
      const safeMenuActions = [];
      const destructiveMenuActions = [];

      for (let i = 0; i < this.actions.length; i++) {
        const action = this.actions[i];

        if (action.trigger) {
          const $trigger = $('<div/>', {
            id: `${this.namespaceId(action.type)}-actiontrigger`,
          }).append(action.trigger);
          $trigger.find('.btn').addClass('secondary');

          // Add any active inputs to action.settings,
          // and remove their input names so they don't muck up the parent form
          if (!action.settings) {
            action.settings = {};
          }
          $.extend(
            action.settings,
            Craft.expandPostArray(Garnish.getPostData($trigger))
          );
          $trigger.find('[name]').removeAttr('name');

          // create a new form at the end of the DOM, in case the element index is within another form
          const formId = `element-action-form-${Math.floor(
            Math.random() * 1000000
          )}`;
          const $form = $('<form/>', {
            id: formId,
            method: 'post',
            action: '',
            class: 'hidden',
            data: {action},
          }).appendTo(Garnish.$bod);

          // redirect all submits to the remote form
          $trigger
            .find('button[type=submit],input[type=submit],.formsubmit')
            .addClass('formsubmit')
            .attr('data-form', formId);

          $form.data('trigger', $trigger);

          this.addListener($form, 'submit', '_handleActionTriggerSubmit');
          this._$triggers = this._$triggers.add($trigger);
        } else {
          if (!action.destructive) {
            safeMenuActions.push(action);
          } else {
            destructiveMenuActions.push(action);
          }
        }
      }

      if (safeMenuActions.length || destructiveMenuActions.length) {
        const $menuTrigger = $('<form/>');

        this.$actionMenuBtn = $('<button/>', {
          type: 'button',
          class: 'btn secondary menubtn',
          'data-icon': 'settings',
          title: Craft.t('app', 'Actions'),
        }).appendTo($menuTrigger);

        const $menu = $('<ul class="menu"/>').appendTo($menuTrigger),
          $safeList = this._createMenuTriggerList(safeMenuActions, false),
          $destructiveList = this._createMenuTriggerList(
            destructiveMenuActions,
            true
          );

        if ($safeList) {
          $safeList.appendTo($menu);
        }

        if ($safeList && $destructiveList) {
          $('<hr/>').appendTo($menu);
        }

        if ($destructiveList) {
          $destructiveList.appendTo($menu);
        }

        this._$triggers = this._$triggers.add($menuTrigger);
      }

      this._$triggers.appendTo(this.$actionsContainer);

      if (this.isAdministrative) {
        // set Craft.currentElementIndex for actions
        Craft.currentElementIndex = this;
      }

      await Craft.appendHeadHtml(this.actionsHeadHtml);
      await Craft.appendBodyHtml(this.actionsBodyHtml);

      Craft.initUiElements(this._$triggers);

      if (this.$actionMenuBtn) {
        this.$actionMenuBtn
          .data('menubtn')
          .on('optionSelect', this._handleMenuActionTriggerSubmit.bind(this));
      }
    },

    _showExportHud: function () {
      this.$exportBtn.addClass('active');
      this.$exportBtn.attr('aria-expanded', 'true');

      var $form = $('<form/>', {
        class: 'export-form',
      });

      var typeOptions = [];
      for (var i = 0; i < this.exporters.length; i++) {
        typeOptions.push({
          label: this.exporters[i].name,
          value: this.exporters[i].type,
        });
      }
      var $typeField = Craft.ui
        .createSelectField({
          label: Craft.t('app', 'Export Type'),
          options: typeOptions,
          class: 'fullwidth',
        })
        .appendTo($form);

      var $formatField = Craft.ui
        .createSelectField({
          label: Craft.t('app', 'Format'),
          options: [
            {label: 'CSV', value: 'csv'},
            {label: 'JSON', value: 'json'},
            {label: 'XML', value: 'xml'},
          ],
          class: 'fullwidth',
        })
        .appendTo($form);

      let $typeSelect = $typeField.find('select');
      this.addListener($typeSelect, 'change', () => {
        let type = $typeSelect.val();
        if (this.exportersByType[type].formattable) {
          $formatField.removeClass('hidden');
        } else {
          $formatField.addClass('hidden');
        }
      });
      $typeSelect.trigger('change');

      // Only show the Limit field if there aren't any selected elements
      var selectedElementIds = this.view.getSelectedElementIds();

      if (!selectedElementIds.length) {
        var $limitField = Craft.ui
          .createTextField({
            label: Craft.t('app', 'Limit'),
            placeholder: Craft.t('app', 'No limit'),
            type: 'number',
            min: 1,
          })
          .appendTo($form);
      }

      const $submitBtn = Craft.ui
        .createSubmitButton({
          class: 'fullwidth',
          label: Craft.t('app', 'Export'),
          spinner: true,
        })
        .appendTo($form);

      const $exportSubmit = new Garnish.MultiFunctionBtn($submitBtn);

      var hud = new Garnish.HUD(this.$exportBtn, $form);

      hud.on('hide', () => {
        this.$exportBtn.removeClass('active');
        this.$exportBtn.attr('aria-expanded', 'false');
      });

      var submitting = false;

      this.addListener($form, 'submit', function (ev) {
        ev.preventDefault();
        if (submitting) {
          return;
        }

        submitting = true;
        $exportSubmit.busyEvent();

        var params = this.getViewParams();
        delete params.baseCriteria.offset;
        delete params.baseCriteria.limit;
        delete params.collapsedElementIds;

        params.type = $typeField.find('select').val();
        params.format = $formatField.find('select').val();

        if (selectedElementIds.length) {
          params.criteria.id = selectedElementIds;
        } else {
          var limit = parseInt($limitField.find('input').val());
          if (limit && !isNaN(limit)) {
            params.criteria.limit = limit;
          } else {
            // don't set the default limit of 100
            delete params.criteria.limit;
          }
        }

        if (Craft.csrfTokenValue) {
          params[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }

        Craft.downloadFromUrl(
          'POST',
          Craft.getActionUrl('element-indexes/export'),
          params
        )
          .catch((e) => {
            if (!axios.isCancel(e)) {
              Craft.cp.displayError(e?.response?.data?.message);
            }
          })
          .finally(() => {
            submitting = false;
            $exportSubmit.successEvent();
          });
      });
    },

    _createMenuTriggerList: function (actions, destructive) {
      if (actions && actions.length) {
        const $ul = $('<ul/>');

        for (let action of actions) {
          $('<li/>')
            .append(
              $('<a/>', {
                id: `${this.namespaceId(action.type)}-actiontrigger`,
                class: destructive ? 'error' : null,
                data: {action},
                text: action.name,
              })
            )
            .appendTo($ul);
        }

        return $ul;
      }
    },

    filterHudExists: function () {
      return (
        this.filterHuds[this.siteId] &&
        this.filterHuds[this.siteId][this.sourceKey]
      );
    },

    showFilterHud: function () {
      if (!this.filterHudExists()) {
        this.createFilterHud();
      } else {
        this.filterHuds[this.siteId][this.sourceKey].show();
      }
    },

    createFilterHud: function (settings) {
      if (!this.filterHuds[this.siteId]) {
        this.filterHuds[this.siteId] = {};
      }

      this.filterHuds[this.siteId][this.sourceKey] = new FilterHud(
        this,
        this.sourceKey,
        this.siteId,
        settings
      );

      this.updateFilterBtn();
    },

    updateFilterBtn: function () {
      this.$filterBtn.removeClass('active');

      if (this.settings.context === 'index') {
        Craft.setQueryParam('filters', null);
      }

      if (
        this.filterHuds[this.siteId] &&
        this.filterHuds[this.siteId][this.sourceKey]
      ) {
        this.$filterBtn
          .attr(
            'aria-controls',
            this.filterHuds[this.siteId][this.sourceKey].id
          )
          .attr(
            'aria-expanded',
            this.filterHuds[this.siteId][this.sourceKey].showing
              ? 'true'
              : 'false'
          );

        if (this.hasActiveFilter) {
          this.$filterBtn.addClass('active');

          if (this.settings.context === 'index') {
            Craft.setQueryParam(
              'filters',
              this.filterHuds[this.siteId][this.sourceKey].serialized
            );
          }
        }
      } else {
        this.$filterBtn.attr('aria-controls', null);
      }
    },
  },
  {
    defaults: {
      context: 'index',
      namespace: null,
      modal: null,
      storageKey: null,
      condition: null,
      referenceElementId: null,
      referenceElementSiteId: null,
      allowedViewModes: null,
      showHeaderColumn: true,
      static: false,
      criteria: null,
      batchSize: 100,
      disabledElementIds: [],
      selectable: false,
      multiSelect: false,
      waitForDoubleClicks: false,
      canSelectElement: null,
      canDuplicateElements: (selectedItems) => true,
      onBeforeDuplicateElements: async (selectedItems) => {},
      onDuplicateElements: async (selectedItems) => {},
      canDeleteElements: (selectedItems) => true,
      onBeforeDeleteElements: async (selectedItems) => {},
      onDeleteElements: async (selectedItems) => {},
      sortable: false,
      prevalidate: false,
      inlineEditable: null,
      actions: null,
      buttonContainer: null,
      hideSidebar: false,
      toolbarSelector: '.toolbar:first',
      refreshSourcesAction: 'element-indexes/get-source-tree-html',
      updateElementsAction: 'element-indexes/get-elements',
      countElementsAction: 'element-indexes/count-elements',
      submitActionsAction: 'element-indexes/perform-action',
      defaultSiteId: null,
      defaultSource: null,
      defaultSourcePath: null,
      preferStoredSource: false,
      showSourcePath: true,
      canHaveDrafts: false,
      viewSettings: $.noop,

      elementTypeName: Craft.t('app', 'Element'),
      elementTypePluralName: Craft.t('app', 'Elements'),

      onAfterInit: $.noop,
      onSelectSource: $.noop,
      onSelectSite: $.noop,
      onBeforeUpdateElements: $.noop,
      onUpdateElements: $.noop,
      onCountResults: $.noop,
      onSelectionChange: $.noop,
      onSourcePathChange: $.noop,
      onEnableElements: $.noop,
      onDisableElements: $.noop,
      onAfterAction: $.noop,
      onSortChange: $.noop,
    },
  }
);

const SourceNav = Garnish.Base.extend(
  {
    $container: null,
    $items: null,
    $selectedItem: null,

    init: function (container, settings) {
      this.$container = $(container);

      const items = this.$container.find('[data-source-item]');

      this.setSettings(settings, SourceNav.defaults);

      this.$items = $();
      this.addItems(items);
    },

    addItems: function (items) {
      const $items = $(items);

      for (var i = 0; i < $items.length; i++) {
        const item = $items[i];

        this.addListener(item, 'click', this.handleClick.bind(this));
        this.addListener(item, 'keydown', this.handleKeypress.bind(this));
      }

      this.$items = this.$items.add($items);
    },

    /**
     * Remove All Items
     */
    removeAllItems: function () {
      for (var i = 0; i < this.$items.length; i++) {
        this._deinitItem(this.$items[i]);
      }

      this.$items = $();
      this.$selectedItem = $();
    },

    handleKeypress: function (event) {
      const {keyCode} = event;

      if (
        (keyCode === Garnish.RETURN_KEY || keyCode === Garnish.SPACE_KEY) &&
        !event.shiftKey &&
        !Garnish.isCtrlKeyPressed(event)
      ) {
        event.preventDefault();
        this.selectItem(event.target);
      }
    },

    handleClick: function (event) {
      const $item = this.getClosestItem(event.target);

      this.selectItem($item);
    },

    getClosestItem: function (element) {
      return $(element).closest('[data-source-item]');
    },

    selectItem: function (item) {
      if (item.length === 0) {
        return;
      }

      const $item = $(item);
      this.deselectAll();

      this.$selectedItem = $item
        .attr('aria-current', 'true')
        .addClass(this.settings.selectedClass);

      this.onSelectionChange();
    },

    deselectAll: function () {
      this.$items
        .attr('aria-current', 'false')
        .removeClass(this.settings.selectedClass);

      this.onSelectionChange();
    },

    removeItems: function (items) {
      let selectedItem;

      items = $.makeArray(items);

      let itemsChanged = false,
        selectionChanged = false;

      for (let i = 0; i < items.length; i++) {
        const item = items[i];

        // Is this item in the list of navigation items?
        const index = $.inArray(item, this.$items);

        // Yes! Then deinitialize the item and remove it from the list
        if (index !== -1) {
          this._deinitItem(item);
          this.$items.splice(index, 1);
          itemsChanged = true;

          // One of the items is currently selected
          if ($(item).is(this.$selectedItem)) {
            selectionChanged = true;
            this.$selectedItem = null;
          }
        }
      }

      if (itemsChanged) {
        if (selectionChanged) {
          $(items).removeClass(this.settings.selectedClass);
          this.onSelectionChange();
        }
      }
    },

    onSelectionChange: function () {
      if (this.callbackFrame) {
        Garnish.cancelAnimationFrame(this.callbackFrame);
        this.callbackFrame = null;
      }

      this.callbackFrame = Garnish.requestAnimationFrame(
        function () {
          this.callbackFrame = null;
          this.trigger('selectionChange');
          this.settings.onSelectionChange();
        }.bind(this)
      );
    },

    _deinitItem: function (item) {
      const $item = $(item);
      this.removeAllListeners($item);
    },
  },
  {
    defaults: {
      selectedClass: 'sel',
      onSelectionChange: $.noop,
    },
  }
);

const ViewMenu = Garnish.Base.extend({
  elementIndex: null,
  $source: null,
  sourceKey: null,
  menu: null,
  id: null,

  $trigger: null,
  $container: null,
  $sortField: null,
  $sortAttributeSelect: null,
  $sortDirectionPicker: null,
  sortDirectionListbox: null,
  $tableColumnsField: null,
  $tableColumnsContainer: null,
  $revertContainer: null,
  $revertBtn: null,
  $closeBtn: null,

  init: function (elementIndex, $source) {
    this.elementIndex = elementIndex;
    this.$source = $source;
    this.sourceKey = $source.data('key');
    this.id = `view-menu-${Math.floor(Math.random() * 1000000000)}`;

    this.$trigger = $('<button/>', {
      type: 'button',
      class: 'btn menubtn hidden',
      text: Craft.t('app', 'View'),
      'aria-label': Craft.t('app', 'View settings'),
      'aria-controls': this.id,
      'data-icon': 'sliders',
    }).appendTo(this.elementIndex.$toolbar);

    this.$container = $('<div/>', {
      id: this.id,
      class: 'menu menu--disclosure element-index-view-menu',
      'data-align': 'right',
    }).appendTo(Garnish.$bod);

    this._buildMenu();

    this.addListener(this.$container, 'mousedown', (ev) => {
      ev.stopPropagation();
    });

    this.menu = new Garnish.DisclosureMenu(this.$trigger);

    this.menu.on('show', () => {
      this.$trigger.addClass('active');
      this.updateSortField();
      this.updateTableFieldVisibility();
    });

    this.menu.on('hide', () => {
      this.$trigger.removeClass('active');

      // Move all checked table column checkboxes to the top once it's fully faded out
      setTimeout(() => {
        this.tidyTableColumnField();
      }, Garnish.FX_DURATION);
    });
  },

  showTrigger: function () {
    this.$trigger.removeClass('hidden');
  },

  hideTrigger: function () {
    this.$trigger.data('trigger').hide();
    this.$trigger.addClass('hidden');
    this.menu.hide();
  },

  updateTableFieldVisibility: function () {
    // we only want to show the "Table Columns" checkboxes and "Use defaults" btn in table and structure views
    if (
      this.elementIndex.viewMode !== 'table' &&
      this.elementIndex.viewMode !== 'structure'
    ) {
      if (this.$tableColumnsContainer) {
        this.$tableColumnsContainer
          .closest('.table-columns-field')
          .addClass('hidden');
      }
      if (this.$revertBtn) {
        this.$revertBtn.addClass('hidden');
      }
    } else {
      if (this.$tableColumnsContainer) {
        this.$tableColumnsContainer
          .closest('.table-columns-field')
          .removeClass('hidden');
      }
      if (this.$revertBtn) {
        this.$revertBtn.removeClass('hidden');
      }
    }
  },

  updateSortField: function () {
    if (this.elementIndex.settings.sortable) {
      return;
    }

    if (this.$sortField) {
      if (this.elementIndex.viewMode === 'structure') {
        this.$sortField.addClass('hidden');
        if (this.$tableColumnsField) {
          this.$tableColumnsField.addClass('first-child');
        }
      } else {
        this.$sortField.removeClass('hidden');
        if (this.$tableColumnsField) {
          this.$tableColumnsField.removeClass('first-child');
        }
      }
    }

    let [attribute, direction] =
      this.elementIndex.getSortAttributeAndDirection();

    // Add/remove a score option
    const $scoreOption = this.$sortAttributeSelect.children(
      'option[value="score"]'
    );

    // If searching by score, just keep showing the actual selection
    if (this.elementIndex.searching) {
      if (!$scoreOption.length) {
        this.$sortAttributeSelect.prepend(
          $('<option/>', {
            value: 'score',
            text: Craft.t('app', 'Score'),
          })
        );
      }
    } else if ($scoreOption.length) {
      $scoreOption.remove();
    }

    this.$sortAttributeSelect.val(attribute);
    this.sortDirectionListbox.select(direction === 'asc' ? 0 : 1);

    if (attribute === 'score') {
      this.sortDirectionListbox.disable();
      this.$sortDirectionPicker.addClass('disabled');
    } else {
      this.sortDirectionListbox.enable();
      this.$sortDirectionPicker.removeClass('disabled');
    }
  },

  updateTableColumnField: function () {
    if (!this.$tableColumnsContainer) {
      return;
    }

    const attributes = this.elementIndex.getSelectedTableColumns();
    let $lastContainer, lastIndex;

    attributes.forEach((attribute) => {
      const $checkbox = this.$tableColumnsContainer.find(
        `input[value="${attribute}"]`
      );
      if (!$checkbox.prop('checked')) {
        $checkbox.prop('checked', true);
      }
      const $container = $checkbox.parent();

      // Do we need to move it up?
      if ($lastContainer && $container.index() < lastIndex) {
        $container.insertAfter($lastContainer);
      }

      $lastContainer = $container;
      lastIndex = $container.index();
    });

    // See if we need to uncheck any checkboxes
    const $checkboxes = this._getTableColumnCheckboxes();
    for (let i = 0; i < $checkboxes.length; i++) {
      const $checkbox = $checkboxes.eq(i);
      if ($checkbox.prop('checked') && !attributes.includes($checkbox.val())) {
        $checkbox.prop('checked', false);
      }
    }
  },

  tidyTableColumnField: function () {
    if (!this.$tableColumnsContainer) {
      return;
    }

    const defaultOrder = this.elementIndex
      .getTableColumnOptions(this.$source)
      .map((column) => column.attr)
      .reduce((obj, attr, index) => {
        return {...obj, [attr]: index};
      }, {});

    this.$tableColumnsContainer
      .children()
      .sort((a, b) => {
        const checkboxA = $(a).children('input[type="checkbox"]')[0];
        const checkboxB = $(b).children('input[type="checkbox"]')[0];
        if (checkboxA.checked && checkboxB.checked) {
          return 0;
        }
        if (checkboxA.checked || checkboxB.checked) {
          return checkboxA.checked ? -1 : 1;
        }
        return defaultOrder[checkboxA.value] < defaultOrder[checkboxB.value]
          ? -1
          : 1;
      })
      .appendTo(this.$tableColumnsContainer);
  },

  revert: function () {
    this.elementIndex.setSelecetedSourceState({
      order: null,
      sort: null,
      tableColumns: null,
    });

    this.updateSortField();
    this.updateTableColumnField();
    this.tidyTableColumnField();

    this.$revertBtn.remove();
    this.$revertBtn = null;

    this.$closeBtn.focus();
    this.elementIndex.updateElements();
  },

  _buildMenu: function () {
    const $metaContainer = $('<div class="meta"/>').appendTo(this.$container);

    if (!this.elementIndex.settings.sortable) {
      this.$sortField = this._createSortField().appendTo($metaContainer);
    }

    if (!Garnish.isMobileBrowser(true)) {
      this.$tableColumnsField =
        this._createTableColumnsField().appendTo($metaContainer);
    }

    this.updateSortField();

    if (this.$sortAttributeSelect) {
      this.$sortAttributeSelect.focus();
    }

    const $footerContainer = $('<div/>', {
      class: 'flex menu-footer',
    }).appendTo(this.$container);

    this.$revertContainer = $('<div/>', {
      class: 'flex-grow',
    }).appendTo($footerContainer);

    // Only create the revert button if there's a custom view state
    if (
      this.elementIndex.getSelectedSourceState('order') ||
      this.elementIndex.getSelectedSourceState('sort') ||
      this.elementIndex.getSelectedSourceState('tableColumns')
    ) {
      this._createRevertBtn();
    }

    // we only want to show the "Use defaults" btn in table and structure views
    if (
      this.elementIndex.viewMode !== 'table' &&
      this.elementIndex.viewMode !== 'structure'
    ) {
      if (this.$revertBtn) {
        this.$revertBtn.addClass('hidden');
      }
    }

    this.$closeBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Close'),
    })
      .appendTo($footerContainer)
      .on('click', () => {
        this.menu.hide();
      });
  },

  _createSortField: function () {
    const $container = $('<div class="flex"/>');

    const options = this.elementIndex
      .getSortOptions(this.$source)
      .sort((a, b) => {
        return a.label === b.label ? 0 : a.label < b.label ? -1 : 1;
      });
    const groups = options.reduce(
      (groups, o) => {
        const index = o.attr.startsWith('field:') ? 1 : 0;
        groups[index].push(o);
        return groups;
      },
      [[], []]
    );
    if (groups[1].length) {
      groups[1].unshift({
        optgroup: Craft.t('app', 'Fields'),
      });
    }

    const $sortAttributeSelectContainer = Craft.ui
      .createSelect({
        options: groups.flat().map((o) => {
          return o.optgroup
            ? o
            : {
                label: Craft.escapeHtml(o.label),
                value: o.attr,
              };
        }),
      })
      .addClass('fullwidth')
      .appendTo($('<div class="flex-grow"/>').appendTo($container));

    this.$sortAttributeSelect = $sortAttributeSelectContainer
      .children('select')
      .attr({
        'aria-label': Craft.t('app', 'Sort attribute'),
      });

    this.$sortDirectionPicker = $('<section/>', {
      class: 'btngroup btngroup--exclusive',
      'aria-label': Craft.t('app', 'Sort direction'),
    })
      .append(
        $('<button/>', {
          type: 'button',
          class: 'btn',
          title: Craft.t('app', 'Sort ascending'),
          'aria-label': Craft.t('app', 'Sort ascending'),
          'aria-pressed': 'false',
          'data-icon': 'asc',
          'data-dir': 'asc',
        })
      )
      .append(
        $('<button/>', {
          type: 'button',
          class: 'btn',
          title: Craft.t('app', 'Sort descending'),
          'aria-label': Craft.t('app', 'Sort descending'),
          'aria-pressed': 'false',
          'data-icon': 'desc',
          'data-dir': 'desc',
        })
      )
      .appendTo($container);

    this.sortDirectionListbox = new Craft.Listbox(this.$sortDirectionPicker, {
      onChange: ($selectedOption) => {
        const direction = $selectedOption.data('dir');
        if (direction !== this.elementIndex.getSelectedSortDirection()) {
          this.elementIndex.setSelectedSortAttribute(
            this.$sortAttributeSelect.val(),
            $selectedOption.data('dir')
          );

          if (!this.elementIndex.sortByScore) {
            // In case it's actually the structure view
            this.elementIndex.selectViewMode(this.elementIndex.viewMode);
          }

          this.elementIndex.updateElements();
          this._createRevertBtn();
        }
      },
    });

    this.$sortAttributeSelect.on('change', () => {
      this.elementIndex.setSelectedSortAttribute(
        this.$sortAttributeSelect.val(),
        null,
        false
      );

      // In case it's actually the structure view
      this.elementIndex.selectViewMode(this.elementIndex.viewMode);

      this.elementIndex.updateElements();
      this._createRevertBtn();
    });

    const $field = Craft.ui.createField($container, {
      label: Craft.t('app', 'Sort by'),
      fieldset: true,
    });
    $field.addClass('sort-field');
    return $field;
  },

  _getTableColumnCheckboxes: function () {
    if (!this.$tableColumnsContainer) {
      return $();
    }

    return this.$tableColumnsContainer.find('input[type="checkbox"]');
  },

  _createTableColumnsField: function () {
    const columns = this.elementIndex
      .getTableColumnOptions(this.$source)
      .sort((a, b) => {
        return a.label === b.label ? 0 : a.label < b.label ? -1 : 1;
      });

    if (!columns.length) {
      return $();
    }

    this.$tableColumnsContainer = Craft.ui.createCheckboxSelect({
      options: columns.map((c) => ({
        label: c.label,
        value: c.attr,
      })),
      sortable: true,
    });

    this.updateTableColumnField();
    this.tidyTableColumnField();

    this.$tableColumnsContainer.data('dragSort').on('sortChange', () => {
      this._onTableColumnChange();
    });

    this._getTableColumnCheckboxes().on('change', (ev) => {
      this._onTableColumnChange();
    });

    const $field = Craft.ui.createField(this.$tableColumnsContainer, {
      label: Craft.t('app', 'Table Columns'),
      fieldset: true,
    });
    $field.addClass('table-columns-field');

    // we only want to show the "Table Columns" checkboxes in table and structure views
    if (
      this.elementIndex.viewMode !== 'table' &&
      this.elementIndex.viewMode !== 'structure'
    ) {
      $field.addClass('hidden');
    }

    return $field;
  },

  _onTableColumnChange: function () {
    const columns = [];
    const $selectedCheckboxes =
      this._getTableColumnCheckboxes().filter(':checked');
    for (let i = 0; i < $selectedCheckboxes.length; i++) {
      columns.push($selectedCheckboxes.eq(i).val());
    }

    // Only commit the change if it's different from the current column selections
    // (maybe an unchecked column was dragged, etc.)
    if (
      Craft.compare(
        columns,
        this.elementIndex.getSelectedTableColumns(this.$source)
      )
    ) {
      return;
    }

    this.elementIndex.setSelectedTableColumns(columns, false);
    this.elementIndex.updateElements();
    this._createRevertBtn();
  },

  _createRevertBtn: function () {
    if (this.$revertBtn) {
      return;
    }

    this.$revertBtn = $('<button/>', {
      type: 'button',
      class: 'light',
      text: Craft.t('app', 'Use defaults'),
    })
      .appendTo(this.$revertContainer)
      .on('click', () => {
        this.revert();
      });
  },

  destroy: function () {
    this.menu.destroy();
    delete this.menu;
    this.base();
  },
});

const FilterHud = Garnish.HUD.extend({
  elementIndex: null,
  sourceKey: null,
  siteId: null,
  id: null,
  loading: true,
  conditionConfig: null,
  serialized: null,
  $clearBtn: null,
  cleared: false,

  get isActive() {
    return this.showing || this.conditionConfig || this.serialized;
  },

  init: function (elementIndex, sourceKey, siteId, settings) {
    this.elementIndex = elementIndex;
    this.sourceKey = sourceKey;
    this.siteId = siteId;
    this.id = `filter-${Math.floor(Math.random() * 1000000000)}`;

    if (settings) {
      if (settings.conditionConfig) {
        this.conditionConfig = settings.conditionConfig;
        delete settings.conditionConfig;
      } else if (settings.serialized) {
        this.serialized = settings.serialized;
        delete settings.serialized;
      }
    }

    const $loadingContent = $('<div/>')
      .append(
        $('<div/>', {
          class: 'spinner',
        })
      )
      .append(
        $('<div/>', {
          text: Craft.t('app', 'Loading'),
          class: 'visually-hidden',
          'aria-role': 'alert',
        })
      );

    this.base(
      this.elementIndex.$filterBtn,
      $loadingContent,
      Object.assign(
        {
          hudClass: 'hud element-filter-hud loading',
        },
        settings
      )
    );

    this.$hud.attr({
      id: this.id,
      'aria-live': 'polite',
      'aria-busy': 'false',
    });
    this.$tip.remove();
    this.$tip = null;

    this.$body.on('submit', (ev) => {
      ev.preventDefault();
      this.hide();
    });

    Craft.sendActionRequest('POST', 'element-indexes/filter-hud', {
      data: {
        elementType: this.elementIndex.elementType,
        source: this.sourceKey,
        condition: this.elementIndex.settings.condition,
        conditionConfig: this.conditionConfig,
        serialized: this.serialized,
        id: `${this.id}-filters`,
      },
    })
      .then(async (response) => {
        this.loading = false;
        this.$hud.removeClass('loading');
        $loadingContent.remove();

        this.$main.append(response.data.hudHtml);
        await Craft.appendHeadHtml(response.data.headHtml);
        await Craft.appendBodyHtml(response.data.bodyHtml);

        const $btnContainer = $('<div/>', {
          class: 'flex flex-nowrap',
        }).appendTo(this.$main);
        $('<div/>', {
          class: 'flex-grow',
        }).appendTo($btnContainer);
        this.$clearBtn = $('<button/>', {
          type: 'button',
          class: 'btn',
          text: Craft.t('app', 'Cancel'),
        }).appendTo($btnContainer);
        $('<button/>', {
          type: 'submit',
          class: 'btn secondary',
          text: Craft.t('app', 'Apply'),
        }).appendTo($btnContainer);
        this.$clearBtn.on('click', () => {
          this.clear();
        });

        this.$hud.find('.condition-container').on('htmx:beforeRequest', () => {
          this.setBusy();
        });

        this.$hud.find('.condition-container').on('htmx:load', () => {
          this.setReady();
          this.updateSizeAndPosition(true);
        });
        this.setFocus();

        if (this.conditionConfig) {
          // conditionConfig => serialized
          this.conditionConfig = null;
          this.serialized = this.serialize();
        }
      })
      .catch((e) => {
        Craft.cp.displayError(e?.response?.data?.message);
      });

    this.$hud.css('position', 'fixed');

    this.addListener(Garnish.$win, 'scroll,resize', () => {
      this.updateSizeAndPosition(true);
    });
  },

  addListener: function (elem, events, data, func) {
    if (elem === this.$main && events === 'resize') {
      return;
    }
    this.base(elem, events, data, func);
  },

  setBusy: function () {
    this.$hud.attr('aria-busy', 'true');

    $('<div/>', {
      class: 'visually-hidden',
      text: Craft.t('app', 'Loading'),
    }).insertAfter(this.$main.find('.htmx-indicator'));
  },

  setReady: function () {
    this.$hud.attr('aria-busy', 'false');
  },

  setFocus: function () {
    Garnish.setFocusWithin(this.$main);
  },

  clear: function () {
    this.cleared = true;
    this.hide();
  },

  updateSizeAndPositionInternal: function () {
    const searchOffset =
      this.elementIndex.$searchContainer[0].getBoundingClientRect();

    // Ensure HUD is scrollable if content falls off-screen
    const windowHeight = Garnish.$win.height();
    let hudHeight;
    const availableSpace = windowHeight - searchOffset.bottom;

    if (this.$body.height() > availableSpace) {
      hudHeight = windowHeight - searchOffset.bottom - 10;
    }

    this.$hud.css({
      width: this.elementIndex.$searchContainer.outerWidth() - 2,
      top: searchOffset.top + this.elementIndex.$searchContainer.outerHeight(),
      left: searchOffset.left + 1,
      height: hudHeight ? `${hudHeight}px` : 'unset',
      overflowY: hudHeight ? 'scroll' : 'unset',
    });
  },

  onShow: function () {
    this.base();

    // Cancel => Clear
    if (this.$clearBtn) {
      this.$clearBtn.text(Craft.t('app', 'Clear'));
    }

    this.elementIndex.updateFilterBtn();
    this.setFocus();
  },

  onHide: function () {
    this.base();

    // If something changed, update the elements
    if (this.serialized !== (this.serialized = this.serialize())) {
      this.elementIndex.updateElements();
    }

    if (this.cleared) {
      this.destroy();
    } else {
      this.$hud.detach();
      this.$shade.detach();
    }

    this.elementIndex.updateFilterBtn();
    this.elementIndex.$filterBtn.focus();
  },

  hasRules: function () {
    return this.$main.has('.condition-rule').length !== 0;
  },

  serialize: function () {
    return !this.cleared && this.hasRules() ? this.$body.serialize() : null;
  },

  destroy: function () {
    this.base();
    delete this.elementIndex.filterHuds[this.siteId][this.sourceKey];
  },
});
