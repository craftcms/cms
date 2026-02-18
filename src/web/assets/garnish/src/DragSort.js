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
        if (typeof this.settings.insertion === 'function') {
          return $(this.settings.insertion(this.$draggee));
        } else {
          return $(this.settings.insertion);
        }
      }
    },

    /**
     * Returns the helper’s target X position
     */
    getHelperTargetX: function () {
      if (this.settings.magnetStrength !== 1) {
        this.getHelperTargetX._draggeeOffsetX = this.$draggee.offset().left;
        return (
          this.getHelperTargetX._draggeeOffsetX +
          (this.mouseX -
            this.mouseOffsetX -
            this.getHelperTargetX._draggeeOffsetX) /
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
        this.getHelperTargetY._draggeeOffsetY = this.$draggee.offset().top;
        return (
          this.getHelperTargetY._draggeeOffsetY +
          (this.mouseY -
            this.mouseOffsetY -
            this.getHelperTargetY._draggeeOffsetY) /
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
      return this.settings.canInsertBefore($item);
    },

    /**
     * Returns whether the draggee can be inserted after a given item.
     */
    canInsertAfter: function ($item) {
      return this.settings.canInsertAfter($item);
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

      // PERFORMANCE: Pre-calculate all midpoints in a single read pass
      // This eliminates layout thrashing during drag operations
      this._precalculateMidpoints();

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
        if (
          this.closestItem !== (this.closestItem = this._getClosestItem()) &&
          this.closestItem !== null
        ) {
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

    /**
     * Pre-calculates all item midpoints in a single read pass.
     * This prevents layout thrashing during drag operations.
     */
    _precalculateMidpoints: function () {
      // Store midpoints in a Map for O(1) lookup
      this._allMidpoints = new Map();

      // Store current scroll position for coordinate adjustment
      const scrollX = window.pageXOffset;
      const scrollY = window.pageYOffset;

      // Batch ALL DOM reads together (no writes interleaved)
      this.$items.each((i, item) => {
        const rect = item.getBoundingClientRect();
        // Convert viewport coords to document coords by adding scroll offset
        this._allMidpoints.set(item, {
          x: rect.left + scrollX + rect.width / 2,
          y: rect.top + scrollY + rect.height / 2,
          width: rect.width,
          height: rect.height,
          top: rect.top + scrollY,
          bottom: rect.bottom + scrollY,
        });
      });
    },

    /**
     * Gets items that are currently visible or near the viewport.
     * Returns array of items to check for closest match.
     */
    _getVisibleItems: function () {
      // Get viewport bounds
      const viewportTop = window.pageYOffset;
      const viewportBottom = viewportTop + window.innerHeight;
      const buffer = 300; // Check items 300px outside viewport

      const visibleItems = [];

      this.$items.each((i, item) => {
        // Skip draggee items
        if (this.$draggee && $.contains(this.$draggee[0], item)) {
          return;
        }

        const midpoint = this._allMidpoints.get(item);
        if (!midpoint) return;

        // Check if item is in or near viewport
        if (
          midpoint.bottom >= viewportTop - buffer &&
          midpoint.top <= viewportBottom + buffer
        ) {
          visibleItems.push(item);
        }
      });

      return visibleItems;
    },

    _getItemIndex: function (item) {
      return this.$items.index(item);
    },

    _getDraggeeIndexes: function () {
      var indexes = [];

      for (var i = 0; i < this.$draggee.length; i++) {
        indexes.push(this._getItemIndex(this.$draggee[i]));
      }

      return indexes;
    },

    /**
     * Returns the closest item to the cursor.
     */
    _getClosestItem: function () {
      this._getClosestItem._closestItem = null;

      // PERFORMANCE: For large datasets, only check items in viewport
      const visibleItems =
        this._allMidpoints && this.$items.length > 200
          ? this._getVisibleItems()
          : null;

      // Start by checking the draggee/insertion, if either are visible
      if (!this.settings.removeDraggee) {
        this._testForClosestItem(this.$draggee[0]);
      } else if (this.insertionVisible) {
        this._testForClosestItem(this.$insertion[0]);
      }

      // Check items before the draggee
      if (this._getClosestItem._closestItem) {
        this._getClosestItem._midpoint = this._getItemMidpoint(
          this._getClosestItem._closestItem
        );
      }
      if (this.settings.axis !== Garnish.Y_AXIS) {
        this._getClosestItem._startXDist = this._getClosestItem._lastXDist =
          this._getClosestItem._closestItem
            ? Math.abs(
                this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX
              )
            : null;
      }
      if (this.settings.axis !== Garnish.X_AXIS) {
        this._getClosestItem._startYDist = this._getClosestItem._lastYDist =
          this._getClosestItem._closestItem
            ? Math.abs(
                this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY
              )
            : null;
      }

      // PERFORMANCE: Use viewport filtering for large datasets
      if (visibleItems) {
        // Fast path: Only check visible items
        for (let i = 0; i < visibleItems.length; i++) {
          const item = visibleItems[i];
          const $item = $(item);

          // Give the extending class a chance to allow/disallow this item
          if (this.canInsertBefore($item) || this.canInsertAfter($item)) {
            this._testForClosestItem(item);
          }
        }
      } else {
        // Original path: Check all items (for small datasets)
        this._getClosestItem._$otherItem = this.getPrevItem(
          this.$draggee.first()
        );

        while (this._getClosestItem._$otherItem) {
          // See if we're just getting further away
          this._getClosestItem._midpoint = this._getItemMidpoint(
            this._getClosestItem._$otherItem[0]
          );
          if (this.settings.axis !== Garnish.Y_AXIS) {
            this._getClosestItem._xDist = Math.abs(
              this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX
            );
          }
          if (this.settings.axis !== Garnish.X_AXIS) {
            this._getClosestItem._yDist = Math.abs(
              this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY
            );
          }

          if (
            !(
              (this.settings.axis === Garnish.Y_AXIS ||
                (this._getClosestItem._lastXDist !== null &&
                  this._getClosestItem._xDist >
                    this._getClosestItem._lastXDist)) &&
              (this.settings.axis === Garnish.X_AXIS ||
                (this._getClosestItem._lastYDist !== null &&
                  this._getClosestItem._yDist >
                    this._getClosestItem._lastYDist))
            )
          ) {
            if (this.settings.axis !== Garnish.Y_AXIS) {
              this._getClosestItem._lastXDist = this._getClosestItem._xDist;
            }
            if (this.settings.axis !== Garnish.X_AXIS) {
              this._getClosestItem._lastYDist = this._getClosestItem._yDist;
            }

            // Give the extending class a chance to allow/disallow this item
            if (
              this.canInsertBefore(this._getClosestItem._$otherItem) ||
              this.canInsertAfter(this._getClosestItem._$otherItem)
            ) {
              this._testForClosestItem(this._getClosestItem._$otherItem[0]);
            }
          }

          // Prep the next item
          this._getClosestItem._$otherItem = this.getPrevItem(
            this._getClosestItem._$otherItem
          );
        }

        // Check items after the draggee
        if (this.settings.axis !== Garnish.Y_AXIS) {
          this._getClosestItem._lastXDist = this._getClosestItem._startXDist;
        }
        if (this.settings.axis !== Garnish.X_AXIS) {
          this._getClosestItem._lastYDist = this._getClosestItem._startYDist;
        }

        this._getClosestItem._$otherItem = this.getNextItem(
          this.$draggee.last()
        );

        while (this._getClosestItem._$otherItem) {
          // See if we're just getting further away
          this._getClosestItem._midpoint = this._getItemMidpoint(
            this._getClosestItem._$otherItem[0]
          );
          if (this.settings.axis !== Garnish.Y_AXIS) {
            this._getClosestItem._xDist = Math.abs(
              this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX
            );
          }
          if (this.settings.axis !== Garnish.X_AXIS) {
            this._getClosestItem._yDist = Math.abs(
              this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY
            );
          }

          if (
            !(
              (this.settings.axis === Garnish.Y_AXIS ||
                (this._getClosestItem._lastXDist !== null &&
                  this._getClosestItem._xDist >
                    this._getClosestItem._lastXDist)) &&
              (this.settings.axis === Garnish.X_AXIS ||
                (this._getClosestItem._lastYDist !== null &&
                  this._getClosestItem._yDist >
                    this._getClosestItem._lastYDist))
            )
          ) {
            if (this.settings.axis !== Garnish.Y_AXIS) {
              this._getClosestItem._lastXDist = this._getClosestItem._xDist;
            }
            if (this.settings.axis !== Garnish.X_AXIS) {
              this._getClosestItem._lastYDist = this._getClosestItem._yDist;
            }

            // Give the extending class a chance to allow/disallow this item
            if (
              this.canInsertBefore(this._getClosestItem._$otherItem) ||
              this.canInsertAfter(this._getClosestItem._$otherItem)
            ) {
              this._testForClosestItem(this._getClosestItem._$otherItem[0]);
            }
          }

          // Prep the next item
          this._getClosestItem._$otherItem = this.getNextItem(
            this._getClosestItem._$otherItem
          );
        }
      }

      // Return the result

      // Ignore if it's the draggee or insertion
      if (
        this._getClosestItem._closestItem !== this.$draggee[0] &&
        (!this.insertionVisible ||
          this._getClosestItem._closestItem !== this.$insertion[0])
      ) {
        return this._getClosestItem._closestItem;
      } else {
        return null;
      }
    },

    _clearMidpoints: function () {
      this._midpointVersion++;
      this._$prevItem = null;
    },

    _getItemMidpoint: function (item) {
      // PERFORMANCE: Use pre-calculated midpoints from Map
      // Falls back to old behavior if midpoints not pre-calculated
      if (this._allMidpoints && this._allMidpoints.has(item)) {
        return this._allMidpoints.get(item);
      }

      // Fallback to original logic (for backward compatibility)
      if ($.data(item, 'midpointVersion') !== this._midpointVersion) {
        // If this isn't the draggee, temporarily move the draggee to this item
        this._getItemMidpoint._repositionDraggee =
          !this.settings.axis &&
          (!this.settings.removeDraggee || this.insertionVisible) &&
          item !== this.$draggee[0] &&
          (!this.$insertion || item !== this.$insertion.get(0));

        if (this._getItemMidpoint._repositionDraggee) {
          // Is this the first time we've had to temporarily reposition the draggee since the last midpoint clearing?
          if (!this._$prevItem) {
            this._$prevItem = (
              this.insertionVisible ? this.$insertion : this.$draggee
            )
              .first()
              .prev();
            this._$draggeeParent = (
              this.insertionVisible ? this.$insertion : this.$draggee
            )
              .first()
              .parent();
          }

          this._moveDraggeeToItem(item);

          // Now figure out which element we're actually getting the midpoint of
          if (!this.settings.removeDraggee) {
            this._getItemMidpoint._$item = this.$draggee;
          } else {
            this._getItemMidpoint._$item = this.$insertion;
          }
        } else {
          // We're actually getting the midpoint of this item
          this._getItemMidpoint._$item = $(item);
        }

        this._getItemMidpoint._offset = this._getItemMidpoint._$item.offset();

        $.data(item, 'midpoint', {
          x:
            this._getItemMidpoint._offset.left +
            this._getItemMidpoint._$item.outerWidth() / 2,
          y:
            this._getItemMidpoint._offset.top +
            this._getItemMidpoint._$item.outerHeight() / 2,
        });

        $.data(item, 'midpointVersion', this._midpointVersion);

        delete this._getItemMidpoint._$item;
        delete this._getItemMidpoint._offset;

        if (this._getItemMidpoint._repositionDraggee) {
          // Move the draggee back
          if (this._$prevItem.length) {
            this.$draggee.insertAfter(this._$prevItem);
          } else {
            this.$draggee.prependTo(this._$draggeeParent);
          }

          this._placeInsertionWithDraggee();
        }
      }

      return $.data(item, 'midpoint');
    },

    _testForClosestItem: function (item) {
      this._testForClosestItem._midpoint = this._getItemMidpoint(item);
      this._testForClosestItem._mouseDistX = Math.abs(
        this._testForClosestItem._midpoint.x - this.draggeeVirtualMidpointX
      );
      this._testForClosestItem._mouseDistY = Math.abs(
        this._testForClosestItem._midpoint.y - this.draggeeVirtualMidpointY
      );

      switch (this.settings.axis) {
        case Garnish.X_AXIS:
          this._testForClosestItem._mouseDist =
            this._testForClosestItem._mouseDistX;
          break;
        case Garnish.Y_AXIS:
          this._testForClosestItem._mouseDist =
            this._testForClosestItem._mouseDistY;
          break;
        default:
          this._testForClosestItem._mouseDist = Math.sqrt(
            this._testForClosestItem._mouseDistX ** 2 +
              this._testForClosestItem._mouseDistY ** 2
          );
      }

      if (
        this._getClosestItem._closestItem === null ||
        this._testForClosestItem._mouseDist <
          this._getClosestItem._closestItemMouseDist
      ) {
        this._getClosestItem._closestItem = item;
        this._getClosestItem._closestItemMouseDist =
          this._testForClosestItem._mouseDist;
      }
    },

    /**
     * Updates the position of the insertion point.
     */
    _updateInsertion: function () {
      if (this.closestItem) {
        this._moveDraggeeToItem(this.closestItem);
      }

      // PERFORMANCE: Only recalculate affected midpoints instead of all
      // For pre-calculated midpoints, we recalculate the moved item and neighbors
      if (this._allMidpoints && this.closestItem) {
        // Recalculate midpoints for the items that moved
        const itemsToUpdate = [this.closestItem];

        // Add previous and next items
        const $prev = this.getPrevItem($(this.closestItem));
        const $next = this.getNextItem($(this.closestItem));
        if ($prev && $prev.length) itemsToUpdate.push($prev[0]);
        if ($next && $next.length) itemsToUpdate.push($next[0]);

        // Update only these items
        const scrollX = window.pageXOffset;
        const scrollY = window.pageYOffset;

        itemsToUpdate.forEach((item) => {
          const rect = item.getBoundingClientRect();
          // Convert viewport coords to document coords
          this._allMidpoints.set(item, {
            x: rect.left + scrollX + rect.width / 2,
            y: rect.top + scrollY + rect.height / 2,
            width: rect.width,
            height: rect.height,
            top: rect.top + scrollY,
            bottom: rect.bottom + scrollY,
          });
        });
      } else {
        // Fallback: invalidate all midpoints (original behavior)
        this._clearMidpoints();
      }

      this.onInsertionPointChange();
    },

    _moveDraggeeToItem: function (item) {
      // Going down?
      if (
        this.canInsertAfter($(item)) &&
        this.$draggee.parent()[0] === $(item).parent()[0] &&
        this.$draggee.index() < $(item).index()
      ) {
        this.$draggee.insertAfter(item);
      } else {
        this.$draggee.insertBefore(item);
      }

      this._placeInsertionWithDraggee();
      this.$items = $().add(this.$items);
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
      canInsertBefore: () => true,
      canInsertAfter: () => true,
    },
  }
);
