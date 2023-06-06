import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Select
 */
export default Base.extend(
  {
    $container: null,
    $items: null,
    $selectedItems: null,
    $focusedItem: null,

    mousedownTarget: null,
    mouseUpTimeout: null,
    callbackFrame: null,

    $focusable: null,
    $first: null,
    first: null,
    $last: null,
    last: null,

    multiSelectLimit: null,
    branchLimit: null,

    /**
     * Constructor
     */
    init: function (container, items, settings) {
      this.$container = $(container);

      // Param mapping
      if (typeof items === 'undefined' && $.isPlainObject(container)) {
        // (settings)
        settings = container;
        container = null;
        items = null;
      } else if (typeof settings === 'undefined' && $.isPlainObject(items)) {
        // (container, settings)
        settings = items;
        items = null;
      }

      // Is this already a select?
      if (this.$container.data('select')) {
        console.warn('Double-instantiating a select on an element');
        this.$container.data('select').destroy();
      }

      this.$container.data('select', this);

      this.setSettings(settings, Garnish.Select.defaults);

      this.$items = $();
      this.$selectedItems = $();

      this.addItems(items);

      this.multiSelectLimit = this._getMultiSelectLimit();
      this.branchLimit = this._getBranchLimit();

      // --------------------------------------------------------------------

      if (this.settings.allowEmpty && !this.settings.checkboxMode) {
        this.addListener(this.$container, 'click', function () {
          if (this.ignoreClick) {
            this.ignoreClick = false;
          } else {
            // Deselect all items on container click
            this.deselectAll(true);
          }
        });
      }
    },

    /**
     * Get Item Index
     */
    getItemIndex: function ($item) {
      if ($item[0] == undefined) {
        return this.$items.index($item);
      }

      return this.$items.index($item[0]);
    },

    /**
     * Is Selected?
     */
    isSelected: function (item) {
      if (Garnish.isJquery(item)) {
        if (!item[0]) {
          return false;
        }

        item = item[0];
      }

      return $.inArray(item, this.$selectedItems) !== -1;
    },

    /**
     * Select Item
     */
    selectItem: function ($item, focus, preventScroll) {
      if (!this.settings.multi) {
        this.deselectAll();
      }

      //only allow to select an item if the multiSelectLimit hasn't been reached
      if (this._allowSelectItem($item)) {
        this.$first = this.$last = $item;
        this.first = this.last = this.getItemIndex($item);

        if (focus) {
          this.setFocusableItem($item);
          this.focusItem($item, preventScroll);
        }

        this._selectItems($item);
      }
    },

    /**
     * Is selecting item allowed?
     * @param $item
     * @returns {boolean}
     * @private
     */
    _allowSelectItem: function ($item) {
      let allow = false;

      // not grabbing this in init because it can change from one modal opening to another without page reload
      const selectedCount = this._getSelectedCount();
      const selectedBranchRootElementIds =
        this._getSelectedBranchRootElementIds();

      if (this.multiSelectLimit === null && this.branchLimit === null) {
        allow = true;
      } else {
        const currentlySelectedItems = this.$items.filter((i, el) => {
          return $(el).hasClass('sel');
        });

        if (
          this.multiSelectLimit !== null &&
          selectedCount + currentlySelectedItems.length < this.multiSelectLimit
        ) {
          allow = true;
        }

        if (
          this.branchLimit !== null &&
          !this._isBranchLimitReached(
            $item,
            this.branchLimit,
            selectedBranchRootElementIds,
            currentlySelectedItems
          )
        ) {
          allow = true;
        }
      }

      return allow;
    },

    /**
     * Has branchLimit been reached for selecting (single) item?
     *
     * @param $item
     * @param branchLimit
     * @param selectedBranchRootElementIds
     * @returns {boolean}
     * @private
     */
    _isBranchLimitReached: function (
      $item,
      branchLimit,
      selectedBranchRootElementIds,
      currentlySelectedItems
    ) {
      let limitReached = false;
      const level = $item.data('level');

      // get newly selected branch roots
      const newlySelectedBranchRoots = this._getNewlySelectedBranchRoots(
        currentlySelectedItems
      );

      let currentlySelectedRootsCount = Object.keys(
        newlySelectedBranchRoots
      ).length;

      // are we about to go over the limit?
      if (
        selectedBranchRootElementIds.length + currentlySelectedRootsCount >=
        branchLimit
      ) {
        const $branchRoot = this._getBranchRoot($item);

        // if top-level item is already selected and saved (in selectedBranchRootElementIds)
        // or selected during this modal open (newlySelectedBranchRoots)
        // allow the selection of elements from that branch
        if (
          $branchRoot.length == 1 &&
          (Craft.inArray(
            $branchRoot.data('id'),
            selectedBranchRootElementIds
          ) ||
            Craft.inArray(
              $branchRoot.data('id').toString(),
              Object.keys(newlySelectedBranchRoots)
            ))
        ) {
          limitReached = false;
        } else {
          limitReached = true;
        }
      }

      return limitReached;
    },

    /**
     * Get branch roots of newly selected elements
     *
     * @param currentlySelectedItems
     * @returns {{}}
     * @private
     */
    _getNewlySelectedBranchRoots: function (currentlySelectedItems) {
      let newlySelectedBranchRoots = {};

      currentlySelectedItems.each((i, e) => {
        let parent = this._getBranchRoot($(e));
        if (newlySelectedBranchRoots[parent.data('id')] === undefined) {
          newlySelectedBranchRoots[parent.data('id')] = parent[0];
        }
      });

      return newlySelectedBranchRoots;
    },

    /**
     * Select All
     */
    selectAll: function () {
      if (!this.settings.multi || !this.$items.length) {
        return;
      }

      this.first = 0;
      // get last index for select all
      this.last = this._getLastForSelectAll();

      this.$first = this.$items.eq(this.first);
      this.$last = this.$items.eq(this.last);

      let sliceFrom = this.first;
      let sliceTo = this.last + 1;

      this._selectItems(this.$items.slice(sliceFrom, sliceTo));
    },

    /**
     * Get last allowed index for select all.
     * It should be either the last available index,
     * or the index that matches the limit set for the field
     *
     * @returns {number}
     * @private
     */
    _getLastForSelectAll: function () {
      // not grabbing this in init because it can change from one modal opening to another without page reload
      const selectedCount = this._getSelectedCount();
      const selectedBranchRootElementIds =
        this._getSelectedBranchRootElementIds();
      let last;

      // if multiSelect and branchLimit are not specified - do what we used to do
      if (this.multiSelectLimit === null && this.branchLimit === null) {
        last = this.$items.length - 1;
      } else {
        // if we have a multiSelect limit
        if (this.multiSelectLimit !== null) {
          const limit = this.multiSelectLimit - selectedCount;
          // if the limit minus elements already selected is less or equal to all the items -
          // do what we used to do - truly select all
          if (this.$items.length <= limit) {
            last = this.$items.length - 1;
          } else {
            // select "all" up to the limit
            this.deselectAll();
            last = limit - 1;
          }
        }

        // if we have a branchLimit
        if (this.branchLimit !== null) {
          // get all available top-level items
          let $branchRootItems = this._getBranchRootItems();
          const limit = this.branchLimit - selectedBranchRootElementIds.length;

          // if the limit minus top-level elements already selected is less or equal to all top-level items
          // do what we used to do - truly select all
          if ($branchRootItems.length <= limit) {
            last = this.$items.length - 1;
          } else {
            // select "all" up to the limit;
            // the limit is last top-level item past the limit minus 1
            this.deselectAll();
            last = this.getItemIndex($branchRootItems[limit]) - 1;
          }
        }
      }

      return last;
    },

    /**
     * Select Range
     */
    selectRange: function ($item, preventScroll) {
      if (!this.settings.multi) {
        return this.selectItem($item, true, true);
      }

      this.deselectAll();

      // not grabbing this in init because it can change from one modal opening to another without page reload
      const selectedCount = this._getSelectedCount();
      const selectedBranchRootElementIds =
        this._getSelectedBranchRootElementIds();

      let last = this.getItemIndex($item);
      let rangeItemsCount = Math.abs(this.first - last) + 1;

      if (this.multiSelectLimit !== null || this.branchLimit !== null) {
        // if we have the multiSelect limit, and we're about to go over it
        if (
          this.multiSelectLimit !== null &&
          this.multiSelectLimit - selectedCount < rangeItemsCount
        ) {
          let diff = rangeItemsCount - (this.multiSelectLimit - selectedCount);

          if (this.first < last) {
            last = last - diff;
          } else {
            last = last + diff;
          }
        }

        // if we have the branchLimit
        if (this.branchLimit !== null) {
          // get all available top-level items
          let $branchRootItems = this._getBranchRootItems();
          const remainingLimit =
            this.branchLimit - selectedBranchRootElementIds.length;

          // get top level parent for the first item in the range
          let $firstBranchRoot = this._getBranchRoot(this.$first);

          // get top level parent for the last item in the range
          let $last = this.$items.eq(last);
          let $lastBranchRoot = this._getBranchRoot($last);

          // get rangeItemsCount for the top level items
          rangeItemsCount =
            Math.abs(
              $branchRootItems.index($firstBranchRoot[0]) -
                $branchRootItems.index($lastBranchRoot[0])
            ) + 1;

          // if we're about to go over the limit - calculate what last can be
          if (remainingLimit < rangeItemsCount) {
            // calculate the diff
            let diff = rangeItemsCount - remainingLimit;
            // get the top-level index of the last parent
            let lastBranchRootIndex = $branchRootItems.index(
              $lastBranchRoot[0]
            );

            // get last allowed top-level item index
            if (this.first < last) {
              let lastAllowedBranchRootIndex = lastBranchRootIndex - diff;
              last =
                this.getItemIndex(
                  $branchRootItems[lastAllowedBranchRootIndex + 1]
                ) - 1;
            } else {
              let lastAllowedBranchRootIndex = lastBranchRootIndex + diff;
              last =
                this.getItemIndex(
                  $branchRootItems[lastAllowedBranchRootIndex - 1]
                ) + 1;
            }
          }
        }
      }

      this.last = last;
      this.$last = this.$items.eq(this.last);

      this.setFocusableItem(this.$last);
      this.focusItem(this.$last, preventScroll);

      // prepare params for $.slice()
      let sliceFrom, sliceTo;

      if (this.first < this.last) {
        sliceFrom = this.first;
        sliceTo = this.last + 1;
      } else {
        sliceFrom = this.last;
        sliceTo = this.first + 1;
      }

      this._selectItems(this.$items.slice(sliceFrom, sliceTo));
    },

    /**
     * Deselect Item
     */
    deselectItem: function ($item) {
      var index = this.getItemIndex($item);
      if (this.first === index) {
        this.$first = this.first = null;
      }
      if (this.last === index) {
        this.$last = this.last = null;
      }

      this._deselectItems($item);
    },

    /**
     * Deselect All
     */
    deselectAll: function (clearFirst) {
      if (clearFirst) {
        this.$first = this.first = this.$last = this.last = null;
      }

      this._deselectItems(this.$items);
    },

    /**
     * Deselect Others
     */
    deselectOthers: function ($item) {
      this.deselectAll();
      this.selectItem($item, true, true);
    },

    /**
     * Toggle Item
     */
    toggleItem: function ($item, preventScroll) {
      if (!this.isSelected($item)) {
        this.selectItem($item, true, preventScroll);
      } else {
        if (this._canDeselect($item)) {
          this.deselectItem($item, true);
        }
      }
    },

    clearMouseUpTimeout: function () {
      clearTimeout(this.mouseUpTimeout);
    },

    getFirstItem: function () {
      if (this.$items.length) {
        return this.$items.first();
      }
    },

    getLastItem: function () {
      if (this.$items.length) {
        return this.$items.last();
      }
    },

    isPreviousItem: function (index) {
      return index > 0;
    },

    isNextItem: function (index) {
      return index < this.$items.length - 1;
    },

    getPreviousItem: function (index) {
      if (this.isPreviousItem(index)) {
        return this.$items.eq(index - 1);
      }
    },

    getNextItem: function (index) {
      if (this.isNextItem(index)) {
        return this.$items.eq(index + 1);
      }
    },

    getItemToTheLeft: function (index) {
      var func = Garnish.ltr ? 'Previous' : 'Next';

      if (this['is' + func + 'Item'](index)) {
        if (this.settings.horizontal) {
          return this['get' + func + 'Item'](index);
        }
        if (!this.settings.vertical) {
          return this.getClosestItem(index, Garnish.X_AXIS, '<');
        }
      }
    },

    getItemToTheRight: function (index) {
      var func = Garnish.ltr ? 'Next' : 'Previous';

      if (this['is' + func + 'Item'](index)) {
        if (this.settings.horizontal) {
          return this['get' + func + 'Item'](index);
        } else if (!this.settings.vertical) {
          return this.getClosestItem(index, Garnish.X_AXIS, '>');
        }
      }
    },

    getItemAbove: function (index) {
      if (this.isPreviousItem(index)) {
        if (this.settings.vertical) {
          return this.getPreviousItem(index);
        } else if (!this.settings.horizontal) {
          return this.getClosestItem(index, Garnish.Y_AXIS, '<');
        }
      }
    },

    getItemBelow: function (index) {
      if (this.isNextItem(index)) {
        if (this.settings.vertical) {
          return this.getNextItem(index);
        } else if (!this.settings.horizontal) {
          return this.getClosestItem(index, Garnish.Y_AXIS, '>');
        }
      }
    },

    getClosestItem: function (index, axis, dir) {
      var axisProps = Garnish.Select.closestItemAxisProps[axis],
        dirProps = Garnish.Select.closestItemDirectionProps[dir];

      var $thisItem = this.$items.eq(index),
        thisOffset = $thisItem.offset(),
        thisMidpoint =
          thisOffset[axisProps.midpointOffset] +
          Math.round($thisItem[axisProps.midpointSizeFunc]() / 2),
        otherRowPos = null,
        smallestMidpointDiff = null,
        $closestItem = null;

      // Go the other way if this is the X axis and a RTL page
      var step;

      if (Garnish.rtl && axis === Garnish.X_AXIS) {
        step = dirProps.step * -1;
      } else {
        step = dirProps.step;
      }

      for (
        var i = index + step;
        typeof this.$items[i] !== 'undefined';
        i += step
      ) {
        var $otherItem = this.$items.eq(i),
          otherOffset = $otherItem.offset();

        // Are we on the next row yet?
        if (
          dirProps.isNextRow(
            otherOffset[axisProps.rowOffset],
            thisOffset[axisProps.rowOffset]
          )
        ) {
          // Is this the first time we've seen this row?
          if (otherRowPos === null) {
            otherRowPos = otherOffset[axisProps.rowOffset];
          }
          // Have we gone too far?
          else if (otherOffset[axisProps.rowOffset] !== otherRowPos) {
            break;
          }

          var otherMidpoint =
              otherOffset[axisProps.midpointOffset] +
              Math.round($otherItem[axisProps.midpointSizeFunc]() / 2),
            midpointDiff = Math.abs(thisMidpoint - otherMidpoint);

          // Are we getting warmer?
          if (
            smallestMidpointDiff === null ||
            midpointDiff < smallestMidpointDiff
          ) {
            smallestMidpointDiff = midpointDiff;
            $closestItem = $otherItem;
          }
          // Getting colder?
          else {
            break;
          }
        }
        // Getting colder?
        else if (
          dirProps.isWrongDirection(
            otherOffset[axisProps.rowOffset],
            thisOffset[axisProps.rowOffset]
          )
        ) {
          break;
        }
      }

      return $closestItem;
    },

    getFurthestItemToTheLeft: function (index) {
      return this.getFurthestItem(index, 'ToTheLeft');
    },

    getFurthestItemToTheRight: function (index) {
      return this.getFurthestItem(index, 'ToTheRight');
    },

    getFurthestItemAbove: function (index) {
      return this.getFurthestItem(index, 'Above');
    },

    getFurthestItemBelow: function (index) {
      return this.getFurthestItem(index, 'Below');
    },

    getFurthestItem: function (index, dir) {
      var $item, $testItem;

      while (($testItem = this['getItem' + dir](index))) {
        $item = $testItem;
        index = this.getItemIndex($item);
      }

      return $item;
    },

    /**
     * totalSelected getter
     */
    get totalSelected() {
      return this.getTotalSelected();
    },

    /**
     * Get Total Selected
     */
    getTotalSelected: function () {
      return this.$selectedItems.length;
    },

    /**
     * Add Items
     */
    addItems: function (items) {
      var $items = $(items);

      for (var i = 0; i < $items.length; i++) {
        var item = $items[i];

        // Make sure this element doesn't belong to another selector
        if ($.data(item, 'select')) {
          console.warn('Element was added to more than one selector');
          $.data(item, 'select').removeItems(item);
        }

        // Add the item
        $.data(item, 'select', this);

        // Get the handle
        var $handle;

        if (this.settings.handle) {
          if (typeof this.settings.handle === 'object') {
            $handle = $(this.settings.handle);
          } else if (typeof this.settings.handle === 'string') {
            $handle = $(item).find(this.settings.handle);
          } else if (typeof this.settings.handle === 'function') {
            $handle = $(this.settings.handle(item));
          }
        } else {
          $handle = $(item);
        }

        $.data(item, 'select-handle', $handle);
        $handle.data('select-item', item);

        this.addListener($handle, 'mousedown', 'onMouseDown');
        this.addListener($handle, 'mouseup', 'onMouseUp');
        this.addListener($handle, 'click', function () {
          this.ignoreClick = true;
        });

        this.addListener(item, 'keydown', 'onKeyDown');
      }

      this.$items = this.$items.add($items);
      this.updateIndexes();
    },

    /**
     * Remove Items
     */
    removeItems: function (items) {
      items = $.makeArray(items);

      var itemsChanged = false,
        selectionChanged = false;

      for (var i = 0; i < items.length; i++) {
        var item = items[i];

        // Make sure we actually know about this item
        var index = $.inArray(item, this.$items);
        if (index !== -1) {
          this._deinitItem(item);
          this.$items.splice(index, 1);
          itemsChanged = true;

          var selectedIndex = $.inArray(item, this.$selectedItems);
          if (selectedIndex !== -1) {
            this.$selectedItems.splice(selectedIndex, 1);
            selectionChanged = true;
          }
        }
      }

      if (itemsChanged) {
        this.updateIndexes();

        if (selectionChanged) {
          $(items).removeClass(this.settings.selectedClass);
          this.onSelectionChange();
        }
      }
    },

    /**
     * Remove All Items
     */
    removeAllItems: function () {
      for (var i = 0; i < this.$items.length; i++) {
        this._deinitItem(this.$items[i]);
      }

      this.$items = $();
      this.$selectedItems = $();
      this.updateIndexes();
    },

    /**
     * Update First/Last indexes
     */
    updateIndexes: function () {
      if (this.first !== null) {
        this.first = this.getItemIndex(this.$first);
        this.setFocusableItem(this.$first);
      } else if (this.$items.length) {
        this.setFocusableItem($(this.$items[0]));
      }

      if (this.$focusedItem) {
        this.setFocusableItem(this.$focusedItem);
        this.focusItem(this.$focusedItem, true);
      }

      if (this.last !== null) {
        this.last = this.getItemIndex(this.$last);
      }
    },

    /**
     * Reset Item Order
     */
    resetItemOrder: function () {
      this.$items = $().add(this.$items);
      this.$selectedItems = $().add(this.$selectedItems);
      this.updateIndexes();
    },

    /**
     * Sets the focusable item.
     *
     * We only want to have one focusable item per selection list, so that the user
     * doesn't have to tab through a million items.
     *
     * @param {object} $item
     */
    setFocusableItem: function ($item) {
      if (this.$focusable) {
        this.$focusable.removeAttr('tabindex');
      }

      this.$focusable = $item.attr('tabindex', '0');
    },

    /**
     * Sets the focus on an item.
     */
    focusItem: function ($item, preventScroll) {
      $item[0].focus({preventScroll: !!preventScroll});
      this.$focusedItem = $item;
      this.trigger('focusItem', {item: $item});
    },

    /**
     * Get Selected Items
     */
    getSelectedItems: function () {
      return $(this.$selectedItems.toArray());
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.$container.removeData('select');
      this.removeAllItems();
      this.base();
    },

    // Events
    // ---------------------------------------------------------------------

    /**
     * On Mouse Down
     */
    onMouseDown: function (ev) {
      // ignore right clicks
      if (ev.which !== Garnish.PRIMARY_CLICK) {
        return;
      }

      // Enforce the filter
      if (this.settings.filter && !$(ev.target).is(this.settings.filter)) {
        return;
      }

      this.mousedownTarget = ev.currentTarget;

      var $item = $($.data(ev.currentTarget, 'select-item'));

      if (this.first !== null && ev.shiftKey) {
        // Shift key is consistent for both selection modes
        this.selectRange($item, true);
      } else if (this._actAsCheckbox(ev)) {
        this.toggleItem($item, true);
      }
    },

    /**
     * On Mouse Up
     */
    onMouseUp: function (ev) {
      // ignore right clicks
      if (ev.which !== Garnish.PRIMARY_CLICK) {
        return;
      }

      // Enfore the filter
      if (this.settings.filter && !$(ev.target).is(this.settings.filter)) {
        return;
      }

      var $item = $($.data(ev.currentTarget, 'select-item'));

      // was this a click?
      if (
        !this._actAsCheckbox(ev) &&
        !ev.shiftKey &&
        ev.currentTarget === this.mousedownTarget
      ) {
        // If this is already selected, wait a moment to see if this is a double click before making any rash decisions
        if (this.isSelected($item)) {
          this.clearMouseUpTimeout();

          this.mouseUpTimeout = setTimeout(
            function () {
              this.deselectOthers($item);
            }.bind(this),
            300
          );
        } else {
          this.deselectAll();
          this.selectItem($item, true, true);
        }
      }
    },

    /**
     * On Key Down
     */
    onKeyDown: function (ev) {
      // Ignore if the focus isn't on one of our items
      if (ev.target !== ev.currentTarget) {
        return;
      }

      var ctrlKey = Garnish.isCtrlKeyPressed(ev);
      var shiftKey = ev.shiftKey;

      var anchor, $item;

      if (!this.settings.checkboxMode || !this.$focusable.length) {
        anchor = ev.shiftKey ? this.last : this.first;
      } else {
        anchor = $.inArray(this.$focusable[0], this.$items);

        if (anchor === -1) {
          anchor = 0;
        }
      }

      // Ok, what are we doing here?
      switch (ev.keyCode) {
        case Garnish.LEFT_KEY: {
          ev.preventDefault();

          // Select the last item if none are selected
          if (this.first === null) {
            if (Garnish.ltr) {
              $item = this.getLastItem();
            } else {
              $item = this.getFirstItem();
            }
          } else {
            if (ctrlKey) {
              $item = this.getFurthestItemToTheLeft(anchor);
            } else {
              $item = this.getItemToTheLeft(anchor);
            }
          }

          break;
        }

        case Garnish.RIGHT_KEY: {
          ev.preventDefault();

          // Select the first item if none are selected
          if (this.first === null) {
            if (Garnish.ltr) {
              $item = this.getFirstItem();
            } else {
              $item = this.getLastItem();
            }
          } else {
            if (ctrlKey) {
              $item = this.getFurthestItemToTheRight(anchor);
            } else {
              $item = this.getItemToTheRight(anchor);
            }
          }

          break;
        }

        case Garnish.UP_KEY: {
          ev.preventDefault();

          // Select the last item if none are selected
          if (this.first === null) {
            if (this.$focusable) {
              $item = this.$focusable.prev();
            }

            if (!this.$focusable || !$item.length) {
              $item = this.getLastItem();
            }
          } else {
            if (ctrlKey) {
              $item = this.getFurthestItemAbove(anchor);
            } else {
              $item = this.getItemAbove(anchor);
            }

            if (!$item) {
              $item = this.getFirstItem();
            }
          }

          break;
        }

        case Garnish.DOWN_KEY: {
          ev.preventDefault();

          // Select the first item if none are selected
          if (this.first === null) {
            if (this.$focusable) {
              $item = this.$focusable.next();
            }

            if (!this.$focusable || !$item.length) {
              $item = this.getFirstItem();
            }
          } else {
            if (ctrlKey) {
              $item = this.getFurthestItemBelow(anchor);
            } else {
              $item = this.getItemBelow(anchor);
            }

            if (!$item) {
              $item = this.getLastItem();
            }
          }

          break;
        }

        case Garnish.SPACE_KEY: {
          if (!ctrlKey && !shiftKey) {
            ev.preventDefault();

            if (this.isSelected(this.$focusable)) {
              if (this._canDeselect(this.$focusable)) {
                this.deselectItem(this.$focusable);
              }
            } else {
              this.selectItem(this.$focusable, true, false);
            }
          }

          break;
        }

        case Garnish.A_KEY: {
          if (ctrlKey) {
            ev.preventDefault();
            this.selectAll();
          }

          break;
        }
      }

      // Is there an item queued up for focus/selection?
      if ($item && $item.length) {
        if (!this.settings.checkboxMode) {
          // select it
          if (this.first !== null && ev.shiftKey) {
            this.selectRange($item, false);
          } else {
            this.deselectAll();
            this.selectItem($item, true, false);
          }
        } else {
          // just set the new item to be focusable
          this.setFocusableItem($item);
          $item.focus();
          this.$focusedItem = $item;
          this.trigger('focusItem', {item: $item});
        }
      }
    },

    /**
     * Set Callback Timeout
     */
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

    // Private methods
    // ---------------------------------------------------------------------

    _actAsCheckbox: function (ev) {
      if (Garnish.isCtrlKeyPressed(ev)) {
        return !this.settings.checkboxMode;
      } else {
        return this.settings.checkboxMode;
      }
    },

    _canDeselect: function ($items) {
      return this.settings.allowEmpty || this.totalSelected > $items.length;
    },

    _selectItems: function ($items) {
      $items.addClass(this.settings.selectedClass);
      this.$selectedItems = this.$selectedItems.add($items);
      this.onSelectionChange();
    },

    _deselectItems: function ($items) {
      $items.removeClass(this.settings.selectedClass);
      this.$selectedItems = this.$selectedItems.not($items);
      this.onSelectionChange();
    },

    /**
     * Deinitialize an item.
     */
    _deinitItem: function (item) {
      var $handle = $.data(item, 'select-handle');

      if ($handle) {
        $handle.removeData('select-item');
        this.removeAllListeners($handle);
      }

      $.removeData(item, 'select');
      $.removeData(item, 'select-handle');

      if (this.$focusedItem && this.$focusedItem[0] === item) {
        this.$focusedItem = null;
      }
    },

    /**
     * Get the multiSelect value
     * @returns {?number}
     * @private
     */
    _getMultiSelectLimit: function () {
      let limit = null;

      if (
        this.settings.multiSelectParams !== null &&
        this.settings.multiSelectParams.multiSelectLimit !== undefined
      ) {
        limit = this.settings.multiSelectParams.multiSelectLimit;
      }

      return limit;
    },

    /**
     * Get the branchLimit value
     *
     * @returns {?number}
     * @private
     */
    _getBranchLimit: function () {
      let limit = null;

      if (
        this.settings.multiSelectParams !== null &&
        this.settings.multiSelectParams.branchLimit !== undefined
      ) {
        limit = this.settings.multiSelectParams.branchLimit;
      }

      return limit;
    },

    /**
     * Get the selectedCount value
     *
     * @returns {number}
     * @private
     */
    _getSelectedCount: function () {
      let count = 0;

      if (
        this.settings.multiSelectParams !== null &&
        this.settings.multiSelectParams.selectedCount !== undefined
      ) {
        count = this.settings.multiSelectParams.selectedCount;
      }

      return count;
    },

    /**
     * Get the selectedBranchRootElementIds values
     * @returns {*[]}
     * @private
     */
    _getSelectedBranchRootElementIds: function () {
      let ids = [];

      if (
        this.settings.multiSelectParams !== null &&
        this.settings.multiSelectParams.selectedBranchRootElementIds !==
          undefined
      ) {
        ids = this.settings.multiSelectParams.selectedBranchRootElementIds;
      }

      return ids;
    },

    /**
     * Get the branch root (top-level (level 1) parent) of an $item
     *
     * @param $item
     * @returns {*|jQuery|HTMLElement}
     * @private
     */
    _getBranchRoot: function ($item) {
      let $branchRoot = $item;
      let level = $item.data('level');
      let $prev = $item.prev('tr');

      while (level != 1) {
        level = $prev.data('level');
        if (level == 1) {
          $branchRoot = $prev;
          break;
        }
        $prev = $prev.prev('tr');
      }

      return $branchRoot;
    },

    /**
     * Get all top-level (level 1) items
     *
     * @returns {*|jQuery|HTMLElement}
     * @private
     */
    _getBranchRootItems: function () {
      return this.$items.filter((i, el) => {
        return $(el).data('level') == 1;
      });
    },
  },
  {
    defaults: {
      selectedClass: 'sel',
      multi: false,
      multiSelectParams: null,
      allowEmpty: true,
      vertical: false,
      horizontal: false,
      handle: null,
      filter: null,
      checkboxMode: false,
      onSelectionChange: $.noop,
    },

    closestItemAxisProps: {
      x: {
        midpointOffset: 'top',
        midpointSizeFunc: 'outerHeight',
        rowOffset: 'left',
      },
      y: {
        midpointOffset: 'left',
        midpointSizeFunc: 'outerWidth',
        rowOffset: 'top',
      },
    },

    closestItemDirectionProps: {
      '<': {
        step: -1,
        isNextRow: function (a, b) {
          return a < b;
        },
        isWrongDirection: function (a, b) {
          return a > b;
        },
      },
      '>': {
        step: 1,
        isNextRow: function (a, b) {
          return a > b;
        },
        isWrongDirection: function (a, b) {
          return a < b;
        },
      },
    },
  }
);
