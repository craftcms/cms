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
      this.onBeforeDragStart();
      this.dragging = true;
      this.setScrollContainer();
      this.onDragStart();

      // Mute activate events
      Garnish.activateEventsMuted = true;
    },

    setScrollContainer: function () {
      this._.$scrollContainer = this.$targetItem.scrollParent();

      while (true) {
        if (
          this._.$scrollContainer[0] === Garnish.$doc[0] ||
          this._.$scrollContainer[0] === Garnish.$bod[0]
        ) {
          this._.$scrollContainer = Garnish.$win;
          break;
        }

        if (
          // if we're able to drag vertically and the container is vertically scrollable
          (this.settings.axis !== Garnish.X_AXIS &&
            this._.$scrollContainer[0].scrollHeight >
              this._.$scrollContainer[0].clientHeight) ||
          // or if we're able to drag horizontally and the container is horizontally scrollable
          (this.settings.axis !== Garnish.Y_AXIS &&
            this._.$scrollContainer[0].scrollWidth >
              this._.$scrollContainer[0].clientWidth)
        ) {
          // ...we found our scroll container
          break;
        }

        this._.$scrollContainer = this._.$scrollContainer.scrollParent();
      }
    },

    isScrollingWindow: function () {
      return this._.$scrollContainer[0] === Garnish.$win[0];
    },

    /**
     * Drag
     */
    drag: function (didMouseMove) {
      if (didMouseMove) {
        // Is the mouse up against one of the window edges?
        this.drag._scrollProperty = null;

        if (this.settings.axis !== Garnish.X_AXIS) {
          if (this.isScrollingWindow()) {
            this.drag._minMouseScrollY = Garnish.$win.scrollTop();
            this.drag._maxMouseScrollY =
              this.drag._minMouseScrollY + Garnish.$win.height();
          } else {
            this.drag._minMouseScrollY = this._.$scrollContainer.offset().top;
            this.drag._maxMouseScrollY =
              this.drag._minMouseScrollY +
              this._.$scrollContainer.outerHeight();
          }

          this.drag._minMouseScrollY += Garnish.BaseDrag.windowScrollTargetSize;
          this.drag._maxMouseScrollY -= Garnish.BaseDrag.windowScrollTargetSize;

          if (this.mouseY < this.drag._minMouseScrollY) {
            // Scrolling up
            this.drag._scrollProperty = 'scrollTop';
            this.drag._scrollAxis = 'Y';
            this.drag._scrollDist = Math.round(
              (this.mouseY - this.drag._minMouseScrollY) / 2
            );
          } else if (this.mouseY > this.drag._maxMouseScrollY) {
            // Scrolling down
            this.drag._scrollProperty = 'scrollTop';
            this.drag._scrollAxis = 'Y';
            this.drag._scrollDist = Math.round(
              (this.mouseY - this.drag._maxMouseScrollY) / 2
            );
          }
        }

        if (
          !this.drag._scrollProperty &&
          this.settings.axis !== Garnish.Y_AXIS
        ) {
          if (this.isScrollingWindow()) {
            this.drag._minMouseScrollX = Garnish.$win.scrollLeft();
            this.drag._maxMouseScrollX =
              this.drag._minMouseScrollX + Garnish.$win.width();
          } else {
            this.drag._minMouseScrollX = this._.$scrollContainer.offset().left;
            this.drag._maxMouseScrollX =
              this.drag._minMouseScrollX + this._.$scrollContainer.outerWidth();
          }

          this.drag._minMouseScrollX += Garnish.BaseDrag.windowScrollTargetSize;
          this.drag._maxMouseScrollX -= Garnish.BaseDrag.windowScrollTargetSize;

          if (this.mouseX < this.drag._minMouseScrollX) {
            // Scrolling left
            this.drag._scrollProperty = 'scrollLeft';
            this.drag._scrollAxis = 'X';
            this.drag._scrollDist = Math.round(
              (this.mouseX - this.drag._minMouseScrollX) / 2
            );
          } else if (this.mouseX > this.drag._maxMouseScrollX) {
            // Scrolling right
            this.drag._scrollProperty = 'scrollLeft';
            this.drag._scrollAxis = 'X';
            this.drag._scrollDist = Math.round(
              (this.mouseX - this.drag._maxMouseScrollX) / 2
            );
          }
        }

        if (this.drag._scrollProperty) {
          // Are we starting to scroll now?
          if (!this.scrollProperty) {
            if (this.scrollFrame) {
              Garnish.cancelAnimationFrame(this.scrollFrame);
              this.scrollFrame = null;
            }

            this.scrollFrame = Garnish.requestAnimationFrame(() => {
              this._scrollWindow();
            });
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

      // Unmute activate events
      Garnish.requestAnimationFrame(() => {
        Garnish.activateEventsMuted = false;
      });
    },

    /**
     * Add Items
     *
     * @param {object} items Elements that should be draggable.
     */
    addItems: function (items) {
      items = $.makeArray(items);

      for (const item of items) {
        // Make sure this element doesn't belong to another dragger
        if ($.data(item, 'drag')) {
          console.warn('Element was added to more than one dragger');
          $.data(item, 'drag').removeItems(item);
        }

        // Add the item
        $.data(item, 'drag', this);

        // Add the listener
        this.addListener(this._getItemHandle(item), 'mousedown', (ev) => {
          this._handleMouseDown(ev, item);
        });
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
     * On Before Drag Start
     */
    onBeforeDragStart: function () {
      this.trigger('beforeDragStart');
      this.settings.onBeforeDragStart();
    },

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
    _handleMouseDown: function (ev, item) {
      // Ignore right/ctrl-clicks
      if (!Garnish.isPrimaryClick(ev)) {
        return;
      }

      // Ignore if we already have a target
      if (this.$targetItem) {
        return;
      }

      // Ignore if they didn't actually click on the handle
      var $target = $(ev.target);

      // Make sure the target isn't a button (unless the button is the handle)
      if (item !== ev.target && this.settings.ignoreHandleSelector) {
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
      this.$targetItem = $(item);

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
          // https://github.com/craftcms/cms/issues/12896
          const selector = this.settings.handle
            .split(',')
            .map((s) => Craft.ensureEndsWith(s.trim(), ':first'))
            .join(',');
          return $(selector, item);
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

        if (
          this._handleMouseMove._mouseDist >=
          (this.settings.minMouseDist ?? Garnish.BaseDrag.minMouseDist)
        ) {
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
      this._.scrollPos = this._.$scrollContainer[this.scrollProperty]();
      this._.scrollTargetPos = this._.scrollPos + this.scrollDist;
      if (this._.scrollTargetPos < 0) {
        this._.scrollTargetPos = 0;
      } else {
        if (this.scrollAxis === 'Y') {
          this._.scrollMax =
            this._.$scrollContainer[0].scrollHeight -
            this._.$scrollContainer.outerHeight();
        } else {
          this._.scrollMax =
            this._.$scrollContainer[0].scrollWidth -
            this._.$scrollContainer.outerWidth();
        }
        if (this._.scrollTargetPos > this._.scrollMax) {
          this._.scrollTargetPos = this._.scrollMax;
        }
      }
      this._.$scrollContainer[this.scrollProperty](this._.scrollTargetPos);

      if (this.isScrollingWindow()) {
        this['mouse' + this.scrollAxis] -=
          this._.scrollPos - Garnish.$win[this.scrollProperty]();
        this['realMouse' + this.scrollAxis] = this['mouse' + this.scrollAxis];
      }

      this.scrollFrame = Garnish.requestAnimationFrame(() => {
        this._scrollWindow();
      });

      this.drag(true);
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
      minMouseDist: null,
      handle: null,
      axis: null,
      ignoreHandleSelector: 'input, textarea, button, select, .btn',

      onBeforeDragStart: $.noop,
      onDragStart: $.noop,
      onDrag: $.noop,
      onDragStop: $.noop,
    },
  }
);
