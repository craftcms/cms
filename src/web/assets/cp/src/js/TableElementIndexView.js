/** global: Craft */
/** global: Garnish */
/**
 * Table Element Index View
 */
Craft.TableElementIndexView = Craft.BaseElementIndexView.extend({
  $table: null,
  $tableCaption: null,
  $selectedSortHeader: null,
  $statusMessage: null,
  $editBtn: null,
  $saveBtn: null,
  $cancelBtn: null,

  tableSort: null,

  _totalVisiblePostStructureTableDraggee: null,
  _morePendingPostStructureTableDraggee: false,

  _broadcastListener: null,

  initialSerializedValue: null,

  stickyScrollbar: null,
  stickyScrollbarObserver: null,

  getElementContainer: function () {
    // Save a reference to the table
    this.$table = this.$container.find('table:first');
    return this.$table.children('tbody:first');
  },

  afterInit: function () {
    // Set table caption
    this.$tableCaption = this.$table.find('caption');

    // Set the sort header
    this.initTableHeaders();

    this.createScrollbar();

    // Create the table sorter
    if (
      (this.settings.sortable ||
        (this.elementIndex.isAdministrative &&
          this.elementIndex.viewMode === 'structure' &&
          Garnish.hasAttr(this.$table, 'data-structure-id'))) &&
      !this.elementIndex.inlineEditing
    ) {
      this.tableSort = new Craft.ElementTableSorter(
        this,
        this.getAllElements(),
        {
          structureId: this.$table.data('structure-id'),
          maxLevels: this.$table.attr('data-max-levels'),
          onSortChange: () => {
            this.settings.onSortChange(this.tableSort.$draggee);
          },
        }
      );
    }

    // Handle expand/collapse toggles for Structures
    if (
      this.elementIndex.viewMode === 'structure' &&
      !this.elementIndex.inlineEditing
    ) {
      this.addListener(this.$elementContainer, 'click', function (ev) {
        var $target = $(ev.target);

        if ($target.hasClass('toggle')) {
          if (this._collapseElement($target) === false) {
            this._expandElement($target);
          }
        }
      });
    }

    if (
      this.elementIndex.isAdministrative &&
      !this.elementIndex.settings.static &&
      this.elementIndex.settings.inlineEditable !== false &&
      this.$elementContainer.has('> tr[data-id] > th .element[data-editable]')
    ) {
      this.initForInlineEditing();
    }

    // Set up the broadcast listener
    if (Craft.messageReceiver) {
      this._broadcastListener = (ev) => {
        if (ev.data.event === 'saveElement') {
          const $rows = this.$table.find(
            `> tbody > tr[data-id="${ev.data.id}"]`
          );
          if ($rows.length) {
            const data = Object.assign(this.elementIndex.getViewParams(), {
              id: ev.data.id,
            });
            Craft.sendActionRequest(
              'POST',
              'element-indexes/element-table-html',
              {data}
            ).then(({data}) => {
              for (let i = 0; i < $rows.length; i++) {
                const $row = $rows.eq(i);
                for (let attribute in data.attributeHtml) {
                  if (data.attributeHtml.hasOwnProperty(attribute)) {
                    $row
                      .find(`> td[data-attr="${attribute}"]`)
                      .html(data.attributeHtml[attribute]);
                  }
                }
              }
              Craft.cp.elementThumbLoader.load($rows);
            });
          }
        }
      };

      Craft.messageReceiver.addEventListener(
        'message',
        this._broadcastListener
      );
    }
  },

  initForInlineEditing: function () {
    if (this.elementIndex.inlineEditing) {
      Craft.initUiElements(this.$elementContainer);
      this.initialSerializedValue = this.serializeInputs();

      this.$saveBtn = Craft.ui
        .createSubmitButton({
          label: Craft.t('app', 'Save'),
          spinner: true,
        })
        .insertBefore(this.elementIndex.$exportBtn);
      this.$cancelBtn = Craft.ui
        .createButton({
          label: Craft.t('app', 'Cancel'),
          spinner: true,
        })
        .insertBefore(this.elementIndex.$exportBtn);

      this.addListener(this.$saveBtn, 'activate', () => {
        this.$saveBtn.addClass('loading');
        this.closeDateTimeFields();

        this.saveChanges()
          .then((data) => {
            if (data.errors) {
              for (let elementId in data.errors) {
                if (data.errors.hasOwnProperty(elementId)) {
                  const $row = this.$elementContainer.children(
                    `[data-id="${elementId}"]`
                  );
                  for (let attribute in data.errors[elementId]) {
                    $row
                      .find(`[name*="${attribute}"]`)
                      .closest('td')
                      .addClass('errors');
                  }
                }
              }

              this.elementIndex.setIndexAvailable();
              Craft.cp.displayError(
                Craft.t('app', 'Could not save due to validation errors.')
              );
              return;
            }

            Craft.cp.displaySuccess(Craft.t('app', 'Changes saved.'));
            this.elementIndex.inlineEditing = false;
            this.elementIndex.updateElements(true, false).then(() => {
              this.elementIndex.$elements.removeClass('inline-editing');
            });
          })
          .catch(() => {
            this.elementIndex.setIndexAvailable();
            Craft.cp.displayError();
          })
          .finally(() => {
            this.$saveBtn.removeClass('loading');
          });
      });

      this.addListener(this.$cancelBtn, 'activate', () => {
        if (
          !this.getDeltaInputChanges() ||
          confirm(
            Craft.t('app', 'Are you sure you want to discard your changes?')
          )
        ) {
          this.$cancelBtn.addClass('loading');
          this.elementIndex.inlineEditing = false;
          this.closeDateTimeFields();

          this.elementIndex.updateElements(true, false).then(() => {
            this.elementIndex.$elements.removeClass('inline-editing');
          });
        }
      });

      this.addListener(this.$elementContainer, 'keydown', (event) => {
        if (
          event.keyCode === Garnish.RETURN_KEY &&
          Garnish.isCtrlKeyPressed(event)
        ) {
          this.$saveBtn.trigger('click');
        } else if (
          event.keyCode === Garnish.S_KEY &&
          Garnish.isCtrlKeyPressed(event)
        ) {
          event.stopPropagation();
          event.preventDefault();
          this.$saveBtn.trigger('click');
        }
      });
    } else {
      this.$editBtn = Craft.ui
        .createButton({
          label: Craft.t('app', 'Edit'),
          spinner: true,
        })
        .insertBefore(this.elementIndex.$exportBtn);
      this.addListener(this.$editBtn, 'activate', () => {
        this.$editBtn.addClass('loading');
        this.elementIndex.inlineEditing = true;
        this.elementIndex.updateElements(true, false).then(() => {
          this.elementIndex.$elements.addClass('inline-editing');
        });
      });
    }
  },

  closeDateTimeFields: function () {
    // ensure opened date/time pickers don't linger after activating the Cancel btn
    this.elementIndex.$elements
      .find('.datewrapper input')
      .datepicker('destroy');

    if ($().timepicker) {
      this.elementIndex.$elements
        .find('.timewrapper input')
        .timepicker('remove');
    }
  },

  serializeInputs: function () {
    const data = Garnish.getPostData(this.$elementContainer);
    const serialized = [];
    for (let i in data) {
      serialized.push(encodeURIComponent(`${i}=${data[i]}`));
    }
    return serialized.join('&');
  },

  getDeltaInputChanges: function () {
    const deltaNames = this.$elementContainer
      .children()
      .toArray()
      .map(
        (e) =>
          `${this.elementIndex.nestedInputNamespace}[element-${$(e).data(
            'id'
          )}]`
      );
    return Craft.findDeltaData(
      this.initialSerializedValue,
      this.serializeInputs(),
      deltaNames
    );
  },

  haveInputsChanged: function () {
    return this.serializeInputs() !== this.initialSerializedValue;
  },

  saveChanges: async function () {
    let data = this.getDeltaInputChanges();
    if (!data) {
      return {};
    }

    data +=
      '&' +
      $.param({
        elementType: this.elementIndex.elementType,
        siteId: this.elementIndex.siteId,
        namespace: this.elementIndex.nestedInputNamespace,
      });

    const response = await Craft.sendActionRequest(
      'POST',
      'element-indexes/save-elements',
      {
        data,
      }
    );

    return response.data;
  },

  initTableHeaders: function () {
    if (this.settings.sortable || this.elementIndex.inlineEditing) {
      return;
    }

    let selectedSortAttr, selectedSortDir;
    if (this.elementIndex.viewMode === 'structure') {
      selectedSortAttr = 'structure';
      selectedSortDir = 'asc';
    } else {
      [selectedSortAttr, selectedSortDir] =
        this.elementIndex.getSortAttributeAndDirection();
    }

    const $tableHeaders = this.$table
      .children('thead')
      .children()
      .children('[data-attribute]');

    for (let i = 0; i < $tableHeaders.length; i++) {
      const $header = $tableHeaders.eq(i);
      const attr = $header.attr('data-attribute');
      let sortValue = 'none';

      // Is this the selected sort attribute?
      if (attr === selectedSortAttr) {
        this.$selectedSortHeader = $header;
        sortValue = selectedSortDir === 'asc' ? 'ascending' : 'descending';
        $header.addClass('ordered ' + selectedSortDir);
        this.makeColumnSortable($header, true);
      } else {
        // Is this attribute sortable?
        if (this.elementIndex.getSortOption(attr)) {
          this.makeColumnSortable($header);
        }
      }

      $header.attr('aria-sort', sortValue);
    }
  },

  makeColumnSortable: function ($header, sorted = false) {
    $header.addClass('orderable');

    const headerHtml = $header.html();
    const $instructions = this.$tableCaption.find('[data-sort-instructions]');
    const $headerButton = $('<button/>', {
      id: `${this.elementIndex.idPrefix}-${$header.attr('data-attribute')}`,
      type: 'button',
      'aria-pressed': 'false',
    }).html(headerHtml);

    if ($instructions.length) {
      $headerButton.attr('aria-describedby', $instructions.attr('id'));
    }

    if (sorted) {
      $headerButton.attr('aria-pressed', 'true');
      $headerButton.on('click', this._handleSelectedSortHeaderClick.bind(this));
    } else {
      $headerButton.on(
        'click',
        this._handleUnselectedSortHeaderClick.bind(this)
      );
    }

    $header.empty().append($headerButton);
  },

  isVerticalList: function () {
    return true;
  },

  getTotalVisible: function () {
    if (this._isStructureTableDraggingLastElements()) {
      return this._totalVisiblePostStructureTableDraggee;
    } else {
      return this._totalVisible;
    }
  },

  setTotalVisible: function (totalVisible) {
    if (this._isStructureTableDraggingLastElements()) {
      this._totalVisiblePostStructureTableDraggee = totalVisible;
    } else {
      this._totalVisible = totalVisible;
    }
  },

  getMorePending: function () {
    if (this._isStructureTableDraggingLastElements()) {
      return this._morePendingPostStructureTableDraggee;
    } else {
      return this._morePending;
    }
  },

  setMorePending: function (morePending) {
    if (this._isStructureTableDraggingLastElements()) {
      this._morePendingPostStructureTableDraggee = morePending;
    } else {
      this._morePending = this._morePendingPostStructureTableDraggee =
        morePending;
    }
  },

  getLoadMoreParams: function () {
    var params = this.base();

    // If we are dragging the last elements on the page,
    // tell the controller to only load elements positioned after the draggee.
    if (this._isStructureTableDraggingLastElements()) {
      params.criteria.positionedAfter = this.tableSort.$targetItem.data('id');
    }

    return params;
  },

  appendElements: function ($newElements) {
    this.base($newElements);

    if (this.tableSort) {
      this.tableSort.addItems($newElements);
    }

    Craft.cp.updateResponsiveTables();
  },

  _collapseElement: function ($toggle, force) {
    if (!force && !$toggle.hasClass('expanded')) {
      return false;
    }

    $toggle.removeClass('expanded');
    $toggle.attr('aria-expanded', 'false');

    // Find and remove the descendant rows
    var $row = $toggle.closest('tr'),
      id = $row.data('id'),
      level = $row.data('level'),
      $nextRow = $row.next();

    while ($nextRow.length) {
      if (!Garnish.hasAttr($nextRow, 'data-spinnerrow')) {
        if ($nextRow.data('level') <= level) {
          break;
        }

        if (this.elementSelect) {
          this.elementSelect.removeItems($nextRow);
        }

        if (this.tableSort) {
          this.tableSort.removeItems($nextRow);
        }

        this._totalVisible--;
      }

      var $nextNextRow = $nextRow.next();
      $nextRow.remove();
      $nextRow = $nextNextRow;
    }

    // Remember that this row should be collapsed
    if (!this.elementIndex.instanceState.collapsedElementIds) {
      this.elementIndex.instanceState.collapsedElementIds = [];
    }

    this.elementIndex.instanceState.collapsedElementIds.push(id);
    this.elementIndex.setInstanceState(
      'collapsedElementIds',
      this.elementIndex.instanceState.collapsedElementIds
    );

    // Bottom of the index might be viewable now
    this.maybeLoadMore();
  },

  _expandElement: function ($toggle, force) {
    if (!force && $toggle.hasClass('expanded')) {
      return false;
    }

    $toggle.addClass('expanded');
    $toggle.attr('aria-expanded', 'true');

    // Remove this element from our list of collapsed elements
    if (this.elementIndex.instanceState.collapsedElementIds) {
      var $row = $toggle.closest('tr'),
        id = $row.data('id'),
        index = $.inArray(
          id,
          this.elementIndex.instanceState.collapsedElementIds
        );

      if (index !== -1) {
        this.elementIndex.instanceState.collapsedElementIds.splice(index, 1);
        this.elementIndex.setInstanceState(
          'collapsedElementIds',
          this.elementIndex.instanceState.collapsedElementIds
        );

        // Add a temporary row
        var $spinnerRow = this._createSpinnerRowAfter($row);

        // Load the nested elements
        let data = $.extend(true, {}, this.settings.params);
        data.criteria.descendantOf = id;

        Craft.sendActionRequest('POST', this.settings.loadMoreElementsAction, {
          data,
        })
          .then(async (response) => {
            // Do we even care about this anymore?
            if (!$spinnerRow.parent().length) {
              return;
            }

            let $newElements = $(response.data.html);

            // Are there more descendants we didn't get in this batch?
            let totalVisible = this._totalVisible + $newElements.length;
            let morePending =
              this.settings.batchSize &&
              $newElements.length === this.settings.batchSize;

            if (morePending) {
              // Remove all the elements after it
              let $nextRows = $spinnerRow.nextAll();

              if (this.elementSelect) {
                this.elementSelect.removeItems($nextRows);
              }

              if (this.tableSort) {
                this.tableSort.removeItems($nextRows);
              }

              $nextRows.remove();
              totalVisible -= $nextRows.length;
            } else {
              // Maintain the current 'more' status
              morePending = this._morePending;
            }

            $spinnerRow.replaceWith($newElements);
            this.thumbLoader.load($newElements);

            if (this.elementIndex.actions || this.settings.selectable) {
              this.elementSelect.addItems(
                $newElements.filter(':not(.disabled)')
              );
              this.elementIndex.updateActionTriggers();
            }

            if (this.tableSort) {
              this.tableSort.addItems($newElements);
            }

            await Craft.appendHeadHtml(response.data.headHtml);
            await Craft.appendBodyHtml(response.data.bodyHtml);
            Craft.cp.updateResponsiveTables();

            this.setTotalVisible(totalVisible);
            this.setMorePending(morePending);

            // Is there room to load more right now?
            this.maybeLoadMore();
          })
          .catch((e) => {
            Craft.cp.displayError();
            if (!$spinnerRow.parent().length) {
              return;
            }
          });
      }
    }
  },

  _createSpinnerRowAfter: function ($row) {
    return $(
      '<tr data-spinnerrow>' +
        '<td class="centeralign" colspan="' +
        $row.children().length +
        '">' +
        '<div class="spinner"/>' +
        '</td>' +
        '</tr>'
    ).insertAfter($row);
  },

  _isStructureTableDraggingLastElements: function () {
    return (
      this.tableSort &&
      this.tableSort.dragging &&
      this.tableSort.draggingLastElements
    );
  },

  _handleSelectedSortHeaderClick: function (ev) {
    var $header = $(ev.currentTarget).closest('th');

    if ($header.hasClass('loading')) {
      return;
    }

    // Reverse the sort direction
    var selectedSortDir = this.elementIndex.getSelectedSortDirection(),
      newSortDir = selectedSortDir === 'asc' ? 'desc' : 'asc';

    // In case it's actually the structure view
    this.elementIndex.selectViewMode('table');

    this.elementIndex.setSelectedSortDirection(newSortDir);
    this._handleSortHeaderClick(ev, $header);
  },

  _handleUnselectedSortHeaderClick: function (ev) {
    var $header = $(ev.currentTarget).closest('th');

    if ($header.hasClass('loading')) {
      return;
    }

    var attr = $header.attr('data-attribute');

    // In case it's actually the structure view
    this.elementIndex.selectViewMode('table');

    this.elementIndex.setSelectedSortAttribute(attr);
    this._handleSortHeaderClick(ev, $header);
  },

  _handleSortHeaderClick: function (ev, $header) {
    if (this.$selectedSortHeader) {
      this.$selectedSortHeader.removeClass('ordered asc desc');
    }

    $header.addClass('ordered loading');
    this.elementIndex.updateElements();

    // No need for two spinners
    this.elementIndex.setIndexAvailable();
  },

  _updateTableAttributes: function ($element, tableAttributes) {
    var $tr = $element.closest('tr');

    for (var attr in tableAttributes) {
      if (!tableAttributes.hasOwnProperty(attr)) {
        continue;
      }

      $tr
        .children('[data-attr="' + attr + '"]:first')
        .html(tableAttributes[attr]);
    }
  },

  destroy: function () {
    if (this.$editBtn) {
      this.$editBtn.remove();
    } else if (this.$cancelBtn) {
      this.$saveBtn.remove();
      this.$cancelBtn.remove();
    }

    if (this.stickyScrollbar) {
      this.stickyScrollbar.remove();
    }
    if (this.stickyScrollbarObserver) {
      this.stickyScrollbarObserver.disconnect();
    }

    if (this._broadcastListener) {
      Craft.messageReceiver.removeEventListener(
        'message',
        this._broadcastListener
      );
      delete this._broadcastListener;
    }

    this.base();
  },

  createScrollbar() {
    if (this.elementIndex.settings.context !== 'index') {
      return;
    }

    const footer = document.querySelector('#content > #footer');
    if (!footer) {
      return;
    }

    this.stickyScrollbar = document.createElement('craft-proxy-scrollbar');
    this.stickyScrollbar.setAttribute('scroller', '.tablepane');
    this.stickyScrollbar.setAttribute('content', '.tablepane > table');

    this.stickyScrollbar.style.bottom = `${
      footer.getBoundingClientRect().height + 2
    }px`;

    let $scrollbar = $(this.stickyScrollbar);
    this.stickyScrollbarObserver = new IntersectionObserver(
      ([ev]) => {
        if (ev.intersectionRatio < 1) {
          $scrollbar.insertAfter(this.$container);
        } else {
          $scrollbar.remove();
        }
      },
      {
        rootMargin: '0px 0px -1px 0px',
        threshold: [1],
      }
    );
    this.stickyScrollbarObserver.observe(footer);
  },
});
