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

  structureTableSort: null,

  _totalVisiblePostStructureTableDraggee: null,
  _morePendingPostStructureTableDraggee: false,

  _broadcastListener: null,

  getElementContainer: function () {
    // Save a reference to the table
    this.$table = this.$container.find('table:first');
    return this.$table.children('tbody:first');
  },

  afterInit: function () {
    // Set table caption
    this.$tableCaption = this.$table.find('caption');

    this.$statusMessage = this.$table.parent().find('[data-status-message]');

    // Set the sort header
    this.initTableHeaders();

    // Add callback for after elements are updated
    this.elementIndex.on('updateElements', () => {
      this._updateScreenReaderStatus();
    });

    // Create the Structure Table Sorter
    if (
      this.elementIndex.settings.context === 'index' &&
      this.elementIndex.getSelectedSortAttribute() === 'structure' &&
      Garnish.hasAttr(this.$table, 'data-structure-id')
    ) {
      this.structureTableSort = new Craft.StructureTableSorter(
        this,
        this.getAllElements()
      );
    } else {
      this.structureTableSort = null;
    }

    // Handle expand/collapse toggles for Structures
    if (this.elementIndex.getSelectedSortAttribute() === 'structure') {
      this.addListener(this.$elementContainer, 'click', function (ev) {
        var $target = $(ev.target);

        if ($target.hasClass('toggle')) {
          if (this._collapseElement($target) === false) {
            this._expandElement($target);
          }
        }
      });
    }

    // Set up the broadcast listener
    if (Craft.messageReceiver) {
      this._broadcastListener = (ev) => {
        if (ev.data.event === 'saveElement') {
          const $rows = this.$table.find(
            `> tbody > tr[data-id="${ev.data.id}"]`
          );
          if ($rows.length) {
            const data = {
              elementType: this.elementIndex.elementType,
              source: this.elementIndex.sourceKey,
              id: ev.data.id,
              siteId: this.elementIndex.siteId,
            };
            Craft.sendActionRequest(
              'POST',
              'element-indexes/element-table-html',
              {data}
            ).then(({data}) => {
              for (let i = 0; i < $rows.length; i++) {
                const $row = $rows.eq(i);
                $row
                  .find('> th[data-titlecell] .element')
                  .replaceWith(data.elementHtml);
                for (let attribute in data.attributeHtml) {
                  if (data.attributeHtml.hasOwnProperty(attribute)) {
                    $row
                      .find(`> td[data-attr="${attribute}"]`)
                      .html(data.attributeHtml[attribute]);
                  }
                }
              }
              new Craft.ElementThumbLoader().load($rows);
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

  initTableHeaders: function () {
    const selectedSortAttr = this.elementIndex.getSelectedSortAttribute();
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
        const selectedSortDir = this.elementIndex.getSelectedSortDirection();
        sortValue = selectedSortDir === 'asc' ? 'ascending' : 'descending';
        $header.addClass('ordered ' + selectedSortDir);
        this.makeColumnSortable($header, true);
      } else {
        // Is this attribute sortable?
        const $sortAttribute = this.elementIndex.getSortAttributeOption(attr);
        if ($sortAttribute.length) {
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
      params.criteria.positionedAfter =
        this.structureTableSort.$targetItem.data('id');
    }

    return params;
  },

  appendElements: function ($newElements) {
    this.base($newElements);

    if (this.structureTableSort) {
      this.structureTableSort.addItems($newElements);
    }

    Craft.cp.updateResponsiveTables();
  },

  _collapseElement: function ($toggle, force) {
    if (!force && !$toggle.hasClass('expanded')) {
      return false;
    }

    $toggle.removeClass('expanded');

    // Find and remove the descendant rows
    var $row = $toggle.parent().parent(),
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

        if (this.structureTableSort) {
          this.structureTableSort.removeItems($nextRow);
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

    // Remove this element from our list of collapsed elements
    if (this.elementIndex.instanceState.collapsedElementIds) {
      var $row = $toggle.parent().parent(),
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
          .then((response) => {
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

              if (this.structureTableSort) {
                this.structureTableSort.removeItems($nextRows);
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

            if (this.structureTableSort) {
              this.structureTableSort.addItems($newElements);
            }

            Craft.appendHeadHtml(response.data.headHtml);
            Craft.appendBodyHtml(response.data.bodyHtml);
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
      this.structureTableSort &&
      this.structureTableSort.dragging &&
      this.structureTableSort.draggingLastElements
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

    this.elementIndex.setSortDirection(newSortDir);
    this._handleSortHeaderClick(ev, $header);
  },

  _handleUnselectedSortHeaderClick: function (ev) {
    var $header = $(ev.currentTarget).closest('th');

    if ($header.hasClass('loading')) {
      return;
    }

    var attr = $header.attr('data-attribute');

    this.elementIndex.setSortAttribute(attr);
    this._handleSortHeaderClick(ev, $header);
  },

  _handleSortHeaderClick: function (ev, $header) {
    if (this.$selectedSortHeader) {
      this.$selectedSortHeader.removeClass('ordered asc desc');
    }

    $header.addClass('ordered loading');
    this.elementIndex.storeSortAttributeAndDirection();
    this.elementIndex.updateElements();

    // No need for two spinners
    this.elementIndex.setIndexAvailable();
  },

  _updateScreenReaderStatus: function () {
    const attribute = this.elementIndex.getSelectedSortAttribute();
    const direction =
      this.elementIndex.getSelectedSortDirection() === 'asc'
        ? Craft.t('app', 'Ascending')
        : Craft.t('app', 'Descending');
    const label = this.elementIndex.getSortLabel(attribute);

    if (!attribute && !direction && !label) return;

    const message = Craft.t(
      'app',
      'Table {name} sorted by {attribute}, {direction}',
      {
        name: this.$table.attr('data-name'),
        attribute: label,
        direction: direction,
      }
    );

    this.$statusMessage.empty();
    this.$statusMessage.text(message);
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
    if (this._broadcastListener) {
      Craft.messageReceiver.removeEventListener(
        'message',
        this._broadcastListener
      );
      delete this._broadcastListener;
    }

    this.base();
  },
});
