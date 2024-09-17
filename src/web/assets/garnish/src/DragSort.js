import Garnish from './Garnish.js';
import Drag from './Drag.js';
import $ from 'jquery';

/**
 * Drag-to-sort class
 *
 * Builds on the Drag class by allowing you to sort the elements amongst themselves.
 */
export default Drag.extend(
  {
    $heightedContainer: null,
    $insertion: null,
    insertionVisible: false,
    oldDraggeeIndexes: null,
    newDraggeeIndexes: null,
    closestItem: null,

    _midpointVersion: 0,
    _$prevItem: null,

    /**
     * Constructor
     *
     * @param {object} items    Elements that should be draggable right away. (Can be skipped.)
     * @param {object} settings Any settings that should override the defaults.
     */
    init: function (items, settings) {
      // Param mapping
      if (typeof settings === 'undefined' && $.isPlainObject(items)) {
        // (settings)
        settings = items;
        items = null;
      }

      settings = $.extend({}, Garnish.DragSort.defaults, settings);
      this.base(items, settings);
    },

    /**
     * Creates the insertion element.
     */
    createInsertion: function () {
      if (this.settings.insertion) {
        return typeof this.settings.insertion === 'function'
          ? $(this.settings.insertion(this.$draggee))
          : $(this.settings.insertion);
      }
    },

    /**
     * Returns the helper’s target X position
     */
    getHelperTargetX: function () {
      if (this.settings.magnetStrength !== 1) {
        const draggeeOffsetX = this.$draggee.offset().left;

        return (
          draggeeOffsetX +
          (this.mouseX - this.mouseOffsetX - draggeeOffsetX) /
            this.settings.magnetStrength
        );
      } else {
        return this.base();
      }
    },

    /**
     * Returns the helper’s target Y position
     */
    getHelperTargetY: function () {
      if (this.settings.magnetStrength !== 1) {
        const draggeeOffsetY = this.$draggee.offset().top;

        return (
          draggeeOffsetY +
          (this.mouseY - this.mouseOffsetY - draggeeOffsetY) /
            this.settings.magnetStrength
        );
      } else {
        return this.base();
      }
    },

    /**
     * Returns whether the draggee can be inserted before a given item.
     */
    canInsertBefore: function ($item) {
      return true;
    },

    /**
     * Returns whether the draggee can be inserted after a given item.
     */
    canInsertAfter: function ($item) {
      return true;
    },

    // Events
    // ---------------------------------------------------------------------

    /**
     * On Drag Start
     */
    onDragStart: function () {
      this.oldDraggeeIndexes = this._getDraggeeIndexes();

      // Are we supposed to be moving the target item to the front, and is it not already there?
      if (
        this.settings.moveTargetItemToFront &&
        this.$draggee.length > 1 &&
        this._getItemIndex(this.$draggee[0]) >
          this._getItemIndex(this.$draggee[1])
      ) {
        // Reposition the target item before the other draggee items in the DOM
        this.$draggee.first().insertBefore(this.$draggee[1]);
      }

      // Create the insertion
      this.$insertion = this.createInsertion();
      this._placeInsertionWithDraggee();

      this.closestItem = null;
      this._clearMidpoints();

      //  Get the closest container that has a height
      if (this.settings.container) {
        this.$heightedContainer = $(this.settings.container);

        while (!this.$heightedContainer.height()) {
          this.$heightedContainer = this.$heightedContainer.parent();
        }
      }

      this.base();
    },

    /**
     * On Drag
     */
    onDrag: function () {
      // If there's a container set, make sure that we're hovering over it
      if (
        this.$heightedContainer &&
        !Garnish.hitTest(this.mouseX, this.mouseY, this.$heightedContainer)
      ) {
        if (this.closestItem) {
          this.closestItem = null;
          this._removeInsertion();
        }
      } else {
        // Is there a new closest item?
        const newClosestItem = this._getClosestItem();

        if (this.closestItem !== newClosestItem && newClosestItem !== null) {
          this.closestItem = newClosestItem;
          this._updateInsertion();
        }
      }

      this.base();
    },

    /**
     * On Drag Stop
     */
    onDragStop: function () {
      this._removeInsertion();

      // Should we keep the target item where it was?
      if (
        !this.settings.moveTargetItemToFront &&
        this.targetItemPositionInDraggee !== 0
      ) {
        this.$targetItem.insertAfter(
          this.$draggee.eq(this.targetItemPositionInDraggee)
        );
      }

      // Return the helpers to the draggees
      this.returnHelpersToDraggees();

      this.base();

      // Has the item actually moved?
      this.$items = $().add(this.$items);
      this.newDraggeeIndexes = this._getDraggeeIndexes();

      if (
        this.newDraggeeIndexes.join(',') !== this.oldDraggeeIndexes.join(',')
      ) {
        this.onSortChange();
      }
    },

    /**
     * On Insertion Point Change event
     */
    onInsertionPointChange: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('insertionPointChange');
          this.settings.onInsertionPointChange();
        }.bind(this)
      );
    },

    /**
     * On Sort Change event
     */
    onSortChange: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('sortChange');
          this.settings.onSortChange();
        }.bind(this)
      );
    },

    // Private methods
    // ---------------------------------------------------------------------

    _getItemIndex: function (item) {
      return $.inArray(item, this.$items);
    },

    _getDraggeeIndexes: function () {
      return this.$draggee.map((_, el) => this._getItemIndex(el)).get();
    },

    /**
     * Returns the closest item to the cursor.
     */
    _getClosestItem() {
      let closestItem = null;
      let closestItemMouseDistX = null;
      let closestItemMouseDistY = null;

      const testForClosestItem = (item) => {
        const midpoint = this._getItemMidpoint(item);
        const mouseDistX = Math.abs(midpoint.x - this.draggeeVirtualMidpointX);
        const mouseDistY = Math.abs(midpoint.y - this.draggeeVirtualMidpointY);

        if (
          closestItem === null ||
          mouseDistY < closestItemMouseDistY ||
          (mouseDistY === closestItemMouseDistY &&
            mouseDistX <= closestItemMouseDistX)
        ) {
          closestItem = item;
          closestItemMouseDistX = mouseDistX;
          closestItemMouseDistY = mouseDistY;
        }
      };

      if (!this.settings.removeDraggee) {
        testForClosestItem(this.$draggee[0]);
      } else if (this.insertionVisible) {
        testForClosestItem(this.$insertion[0]);
      }

      const checkItems = ($items, direction) => {
        $items.each((_, item) => {
          const midpoint = this._getItemMidpoint(item);
          const mouseDistX = Math.abs(
            midpoint.x - this.draggeeVirtualMidpointX
          );
          const mouseDistY = Math.abs(
            midpoint.y - this.draggeeVirtualMidpointY
          );

          if (
            (this.settings.axis === Garnish.Y_AXIS ||
              (closestItemMouseDistX !== null &&
                mouseDistX > closestItemMouseDistX)) &&
            (this.settings.axis === Garnish.X_AXIS ||
              (closestItemMouseDistY !== null &&
                mouseDistY > closestItemMouseDistY))
          ) {
            return false;
          }

          if (this.canInsertBefore($(item))) {
            testForClosestItem(item);
          }
        });
      };

      checkItems(this.$draggee.first().prevAll(), 'prev');
      checkItems(this.$draggee.last().nextAll(), 'next');

      return closestItem !== this.$draggee[0] &&
        (!this.insertionVisible || closestItem !== this.$insertion[0])
        ? closestItem
        : null;
    },

    _clearMidpoints: function () {
      this._midpointVersion++;
      this._$prevItem = null;
    },

    _getItemMidpoint: function (item) {
      if ($.data(item, 'midpointVersion') !== this._midpointVersion) {
        const $item = $(item);
        const offset = $item.offset();

        $.data(item, 'midpoint', {
          x: offset.left + $item.outerWidth() / 2,
          y: offset.top + $item.outerHeight() / 2,
        });

        $.data(item, 'midpointVersion', this._midpointVersion);
      }

      return $.data(item, 'midpoint');
    },

    /**
     * Updates the position of the insertion point.
     */
    _updateInsertion: function () {
      if (this.closestItem) {
        this._moveDraggeeToItem(this.closestItem);
      }

      // Now that things have shifted around, invalidate the midpoints
      this._clearMidpoints();

      this.onInsertionPointChange();
    },

    _moveDraggeeToItem: function (item) {
      const $item = $(item);
      // Going down?
      if (this.$draggee.index() < $item.index()) {
        this.$draggee.insertAfter($item);
      } else {
        this.$draggee.insertBefore($item);
      }

      this._placeInsertionWithDraggee();
    },

    _placeInsertionWithDraggee: function () {
      if (this.$insertion) {
        this.$insertion.insertBefore(this.$draggee.first());
        this.insertionVisible = true;
      }
    },

    /**
     * Removes the insertion, if it's visible.
     */
    _removeInsertion: function () {
      if (this.insertionVisible) {
        this.$insertion.remove();
        this.insertionVisible = false;
      }
    },
  },
  {
    defaults: {
      container: null,
      insertion: null,
      moveTargetItemToFront: false,
      magnetStrength: 1,
      onInsertionPointChange: $.noop,
      onSortChange: $.noop,
    },
  }
);
