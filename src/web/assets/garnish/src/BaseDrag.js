import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Base drag class
 *
 * Does all the grunt work for manipulating elements via click-and-drag,
 * while leaving the actual element manipulation up to a subclass.
 */

export default Base.extend(
  {
    $items: null,

    dragging: false,

    mousedownX: null,
    mousedownY: null,
    realMouseX: null,
    realMouseY: null,
    mouseX: null,
    mouseY: null,
    mouseDistX: null,
    mouseDistY: null,
    mouseOffsetX: null,
    mouseOffsetY: null,

    $targetItem: null,

    scrollProperty: null,
    scrollAxis: null,
    scrollDist: null,
    scrollProxy: null,
    scrollFrame: null,

    _: null,

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

      this.settings = $.extend({}, Garnish.BaseDrag.defaults, settings);

      this.$items = $();
      this._ = {};

      if (items) {
        this.addItems(items);
      }
    },

    /**
     * Returns whether dragging is allowed right now.
     */
    allowDragging: function () {
      return true;
    },

    /**
     * Start Dragging
     */
    startDragging: function () {
      this.dragging = true;
      this.onDragStart();
    },

    /**
     * Drag
     */
    drag: function (didMouseMove) {
      if (didMouseMove) {
        // Is the mouse up against one of the window edges?
        this.drag._scrollProperty = null;

        if (this.settings.axis !== Garnish.X_AXIS) {
          // Scrolling up?
          this.drag._winScrollTop = Garnish.$win.scrollTop();
          this.drag._minMouseScrollY =
            this.drag._winScrollTop + Garnish.BaseDrag.windowScrollTargetSize;

          if (this.mouseY < this.drag._minMouseScrollY) {
            this.drag._scrollProperty = 'scrollTop';
            this.drag._scrollAxis = 'Y';
            this.drag._scrollDist = Math.round(
              (this.mouseY - this.drag._minMouseScrollY) / 2
            );
          } else {
            // Scrolling down?
            this.drag._maxMouseScrollY =
              this.drag._winScrollTop +
              Garnish.$win.height() -
              Garnish.BaseDrag.windowScrollTargetSize;

            if (this.mouseY > this.drag._maxMouseScrollY) {
              this.drag._scrollProperty = 'scrollTop';
              this.drag._scrollAxis = 'Y';
              this.drag._scrollDist = Math.round(
                (this.mouseY - this.drag._maxMouseScrollY) / 2
              );
            }
          }
        }

        if (
          !this.drag._scrollProperty &&
          this.settings.axis !== Garnish.Y_AXIS
        ) {
          // Scrolling left?
          this.drag._winScrollLeft = Garnish.$win.scrollLeft();
          this.drag._minMouseScrollX =
            this.drag._winScrollLeft + Garnish.BaseDrag.windowScrollTargetSize;

          if (this.mouseX < this.drag._minMouseScrollX) {
            this.drag._scrollProperty = 'scrollLeft';
            this.drag._scrollAxis = 'X';
            this.drag._scrollDist = Math.round(
              (this.mouseX - this.drag._minMouseScrollX) / 2
            );
          } else {
            // Scrolling right?
            this.drag._maxMouseScrollX =
              this.drag._winScrollLeft +
              Garnish.$win.width() -
              Garnish.BaseDrag.windowScrollTargetSize;

            if (this.mouseX > this.drag._maxMouseScrollX) {
              this.drag._scrollProperty = 'scrollLeft';
              this.drag._scrollAxis = 'X';
              this.drag._scrollDist = Math.round(
                (this.mouseX - this.drag._maxMouseScrollX) / 2
              );
            }
          }
        }

        if (this.drag._scrollProperty) {
          // Are we starting to scroll now?
          if (!this.scrollProperty) {
            if (!this.scrollProxy) {
              this.scrollProxy = this._scrollWindow.bind(this);
            }

            if (this.scrollFrame) {
              Garnish.cancelAnimationFrame(this.scrollFrame);
              this.scrollFrame = null;
            }

            this.scrollFrame = Garnish.requestAnimationFrame(this.scrollProxy);
          }

          this.scrollProperty = this.drag._scrollProperty;
          this.scrollAxis = this.drag._scrollAxis;
          this.scrollDist = this.drag._scrollDist;
        } else {
          this._cancelWindowScroll();
        }
      }

      this.onDrag();
    },

    /**
     * Stop Dragging
     */
    stopDragging: function () {
      this.dragging = false;
      this.onDragStop();

      // Clear the scroll animation
      this._cancelWindowScroll();
    },

    /**
     * Add Items
     *
     * @param {object} items Elements that should be draggable.
     */
    addItems: function (items) {
      items = $.makeArray(items);

      for (var i = 0; i < items.length; i++) {
        var item = items[i];

        // Make sure this element doesn't belong to another dragger
        if ($.data(item, 'drag')) {
          console.warn('Element was added to more than one dragger');
          $.data(item, 'drag').removeItems(item);
        }

        // Add the item
        $.data(item, 'drag', this);

        // Add the listener
        this.addListener(item, 'mousedown', '_handleMouseDown');
      }

      this.$items = this.$items.add(items);
    },

    /**
     * Remove Items
     *
     * @param {object} items Elements that should no longer be draggable.
     */
    removeItems: function (items) {
      items = $.makeArray(items);

      for (var i = 0; i < items.length; i++) {
        var item = items[i];

        // Make sure we actually know about this item
        var index = $.inArray(item, this.$items);
        if (index !== -1) {
          this._deinitItem(item);
          this.$items.splice(index, 1);
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
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.removeAllItems();
      this.base();
    },

    // Events
    // ---------------------------------------------------------------------

    /**
     * On Drag Start
     */
    onDragStart: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('dragStart');
          this.settings.onDragStart();
        }.bind(this)
      );
    },

    /**
     * On Drag
     */
    onDrag: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('drag');
          this.settings.onDrag();
        }.bind(this)
      );
    },

    /**
     * On Drag Stop
     */
    onDragStop: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('dragStop');
          this.settings.onDragStop();
        }.bind(this)
      );
    },

    // Private methods
    // ---------------------------------------------------------------------

    /**
     * Handle Mouse Down
     */
    _handleMouseDown: function (ev) {
      // Ignore right clicks
      if (ev.which !== Garnish.PRIMARY_CLICK) {
        return;
      }

      // Ignore if we already have a target
      if (this.$targetItem) {
        return;
      }

      // Ignore if they didn't actually click on the handle
      var $target = $(ev.target),
        $handle = this._getItemHandle(ev.currentTarget);

      if (!$target.is($handle) && !$target.closest($handle).length) {
        return;
      }

      // Make sure the target isn't a button (unless the button is the handle)
      if (
        ev.currentTarget !== ev.target &&
        this.settings.ignoreHandleSelector
      ) {
        if (
          $target.is(this.settings.ignoreHandleSelector) ||
          $target.closest(this.settings.ignoreHandleSelector).length
        ) {
          return;
        }
      }

      ev.preventDefault();

      // Make sure that dragging is allowed right now
      if (!this.allowDragging()) {
        return;
      }

      // Capture the target
      this.$targetItem = $(ev.currentTarget);

      // Capture the current mouse position
      this.mousedownX = this.mouseX = ev.pageX;
      this.mousedownY = this.mouseY = ev.pageY;

      // Capture the difference between the mouse position and the target item's offset
      var offset = this.$targetItem.offset();
      this.mouseOffsetX = ev.pageX - offset.left;
      this.mouseOffsetY = ev.pageY - offset.top;

      // Listen for mousemove, mouseup
      this.addListener(Garnish.$doc, 'mousemove', '_handleMouseMove');
      this.addListener(Garnish.$doc, 'mouseup', '_handleMouseUp');
    },

    _getItemHandle: function (item) {
      if (this.settings.handle) {
        if (typeof this.settings.handle === 'object') {
          return $(this.settings.handle);
        }

        if (typeof this.settings.handle === 'string') {
          return $(this.settings.handle, item);
        }

        if (typeof this.settings.handle === 'function') {
          return $(this.settings.handle(item));
        }
      }

      return $(item);
    },

    /**
     * Handle Mouse Move
     */
    _handleMouseMove: function (ev) {
      ev.preventDefault();

      this.realMouseX = ev.pageX;
      this.realMouseY = ev.pageY;

      if (this.settings.axis !== Garnish.Y_AXIS) {
        this.mouseX = ev.pageX;
      }

      if (this.settings.axis !== Garnish.X_AXIS) {
        this.mouseY = ev.pageY;
      }

      this.mouseDistX = this.mouseX - this.mousedownX;
      this.mouseDistY = this.mouseY - this.mousedownY;

      if (!this.dragging) {
        // Has the mouse moved far enough to initiate dragging yet?
        this._handleMouseMove._mouseDist = Garnish.getDist(
          this.mousedownX,
          this.mousedownY,
          this.realMouseX,
          this.realMouseY
        );

        if (this._handleMouseMove._mouseDist >= Garnish.BaseDrag.minMouseDist) {
          this.startDragging();
        }
      }

      if (this.dragging) {
        this.drag(true);
      }
    },

    /**
     * Handle Moues Up
     */
    _handleMouseUp: function (ev) {
      // Unbind the document events
      this.removeAllListeners(Garnish.$doc);

      if (this.dragging) {
        this.stopDragging();
      }

      this.$targetItem = null;
    },

    /**
     * Scroll Window
     */
    _scrollWindow: function () {
      this._.scrollPos = Garnish.$scrollContainer[this.scrollProperty]();
      Garnish.$scrollContainer[this.scrollProperty](
        this._.scrollPos + this.scrollDist
      );

      this['mouse' + this.scrollAxis] -=
        this._.scrollPos - Garnish.$scrollContainer[this.scrollProperty]();
      this['realMouse' + this.scrollAxis] = this['mouse' + this.scrollAxis];

      this.drag();

      this.scrollFrame = Garnish.requestAnimationFrame(this.scrollProxy);
    },

    /**
     * Cancel Window Scroll
     */
    _cancelWindowScroll: function () {
      if (this.scrollFrame) {
        Garnish.cancelAnimationFrame(this.scrollFrame);
        this.scrollFrame = null;
      }

      this.scrollProperty = null;
      this.scrollAxis = null;
      this.scrollDist = null;
    },

    /**
     * Deinitialize an item.
     */
    _deinitItem: function (item) {
      this.removeAllListeners(item);
      $.removeData(item, 'drag');
    },
  },
  {
    minMouseDist: 1,
    windowScrollTargetSize: 25,

    defaults: {
      handle: null,
      axis: null,
      ignoreHandleSelector: 'input, textarea, button, select, .btn',

      onDragStart: $.noop,
      onDrag: $.noop,
      onDragStop: $.noop,
    },
  }
);
