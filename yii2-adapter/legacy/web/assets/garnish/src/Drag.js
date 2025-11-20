import Garnish from './Garnish.js';
import BaseDrag from './BaseDrag.js';
import $ from 'jquery';

/**
 * Drag class
 *
 * Builds on the BaseDrag class by "picking up" the selceted element(s),
 * without worrying about what to do when an element is being dragged.
 */
export default BaseDrag.extend(
  {
    targetItemWidth: null,
    targetItemHeight: null,
    targetItemPositionInDraggee: null,

    $draggee: null,

    otherItems: null,
    totalOtherItems: null,

    helpers: null,
    helperTargets: null,
    helperPositions: null,
    helperLagIncrement: null,
    updateHelperPosProxy: null,
    updateHelperPosFrame: null,

    lastMouseX: null,
    lastMouseY: null,

    _returningHelpersToDraggees: false,

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

      settings = $.extend({}, Garnish.Drag.defaults, settings);
      this.base(items, settings);
    },

    /**
     * Returns whether dragging is allowed right now.
     */
    allowDragging: function () {
      // Don't allow dragging if we're in the middle of animating the helpers back to the draggees
      return !this._returningHelpersToDraggees;
    },

    /**
     * Start Dragging
     */
    startDragging: function () {
      this.onBeforeDragStart();

      // Reset some things
      this.helpers = [];
      this.helperTargets = [];
      this.helperPositions = [];
      this.lastMouseX = this.lastMouseY = null;

      // Capture the target item's width/height
      this.targetItemWidth = this.$targetItem.outerWidth();
      this.targetItemHeight = this.$targetItem.outerHeight();

      // Save the draggee's display style (block/table-row) so we can re-apply it later
      this.draggeeDisplay = this.$targetItem.css('display');

      // Set the $draggee
      this.setDraggee(this.findDraggee());

      // Create an array of all the other items
      this.otherItems = [];

      for (var i = 0; i < this.$items.length; i++) {
        var item = this.$items[i];

        if ($.inArray(item, this.$draggee) === -1) {
          this.otherItems.push(item);
        }
      }

      this.totalOtherItems = this.otherItems.length;

      // Keep the helpers following the cursor, with a little lag to smooth it out
      if (!this.updateHelperPosProxy) {
        this.updateHelperPosProxy = this._updateHelperPos.bind(this);
      }

      this.helperLagIncrement =
        this.helpers.length === 1
          ? 0
          : this.settings.helperLagIncrementDividend /
            (this.helpers.length - 1);
      this.updateHelperPosFrame = Garnish.requestAnimationFrame(
        this.updateHelperPosProxy
      );

      this.dragging = true;
      this.setScrollContainer();
      this.onDragStart();

      // Mute activate events
      Garnish.activateEventsMuted = true;
    },

    /**
     * Sets the draggee.
     */
    setDraggee: function ($draggee) {
      // Record the target item's position in the draggee
      this.targetItemPositionInDraggee = $.inArray(
        this.$targetItem[0],
        $draggee.add(this.$targetItem[0])
      );

      // Keep the target item at the front of the list
      this.$draggee = $(
        [this.$targetItem[0]].concat($draggee.not(this.$targetItem).toArray())
      );

      // Create the helper(s)
      if (this.settings.singleHelper) {
        this._createHelper(0);
      } else {
        for (var i = 0; i < this.$draggee.length; i++) {
          this._createHelper(i);
        }
      }

      if (this.settings.removeDraggee) {
        this.$draggee.hide();
      } else if (this.settings.collapseDraggees) {
        this.$targetItem.css('visibility', 'hidden');
        this.$draggee.not(this.$targetItem).hide();
      } else if (this.settings.hideDraggee) {
        this.$draggee.css('visibility', 'hidden');
      }
    },

    /**
     * Appends additional items to the draggee.
     */
    appendDraggee: function ($newDraggee) {
      if (!$newDraggee.length) {
        return;
      }

      if (!this.settings.collapseDraggees) {
        var oldLength = this.$draggee.length;
      }

      this.$draggee = $(this.$draggee.toArray().concat($newDraggee.toArray()));

      // Create new helpers?
      if (!this.settings.collapseDraggees) {
        var newLength = this.$draggee.length;

        for (var i = oldLength; i < newLength; i++) {
          this._createHelper(i);
        }
      }

      if (this.settings.removeDraggee || this.settings.collapseDraggees) {
        $newDraggee.hide();
      } else if (this.settings.hideDraggee) {
        $newDraggee.css('visibility', 'hidden');
      }
    },

    /**
     * Drag
     */
    drag: function (didMouseMove) {
      // Update the draggee's virtual midpoint
      this.draggeeVirtualMidpointX =
        this.mouseX - this.mouseOffsetX + this.targetItemWidth / 2;
      this.draggeeVirtualMidpointY =
        this.mouseY - this.mouseOffsetY + this.targetItemHeight / 2;

      this.base(didMouseMove);
    },

    /**
     * Stop Dragging
     */
    stopDragging: function () {
      // Clear the helper animation
      Garnish.cancelAnimationFrame(this.updateHelperPosFrame);

      this.base();
    },

    /**
     * Identifies the item(s) that are being dragged.
     */
    findDraggee: function () {
      switch (typeof this.settings.filter) {
        case 'function': {
          return this.settings.filter();
        }

        case 'string': {
          return this.$items.filter(this.settings.filter);
        }

        default: {
          return this.$targetItem;
        }
      }
    },

    /**
     * Returns the helper’s target X position
     */
    getHelperTargetX: function (real) {
      if (!real && this.settings.moveHelperToCursor) {
        return this.mouseX;
      }
      return this.mouseX - this.mouseOffsetX;
    },

    /**
     * Returns the helper’s target Y position
     */
    getHelperTargetY: function (real) {
      if (!real && this.settings.moveHelperToCursor) {
        return this.mouseY;
      }
      return this.mouseY - this.mouseOffsetY;
    },

    /**
     * Return Helpers to Draggees
     */
    returnHelpersToDraggees: function () {
      this._returningHelpersToDraggees = true;

      for (var i = 0; i < this.helpers.length; i++) {
        var $draggee = this.$draggee.eq(i),
          $helper = this.helpers[i];

        $draggee.css({
          display: this.draggeeDisplay,
          visibility: this.settings.hideDraggee ? 'hidden' : '',
        });

        var draggeeOffset = $draggee.offset();
        var callback;

        if (i === 0) {
          callback = this._showDraggee.bind(this);
        } else {
          callback = null;
        }

        $helper.velocity(
          {left: draggeeOffset.left, top: draggeeOffset.top},
          Garnish.FX_DURATION,
          callback
        );
      }
    },

    // Events
    // ---------------------------------------------------------------------

    onReturnHelpersToDraggees: function () {
      Garnish.requestAnimationFrame(
        function () {
          this.trigger('returnHelpersToDraggees');
          this.settings.onReturnHelpersToDraggees();
        }.bind(this)
      );
    },

    // Private methods
    // ---------------------------------------------------------------------

    /**
     * Creates a helper.
     */
    _createHelper: function (index) {
      var $draggee = this.$draggee.eq(index),
        $draggeeHelper = $draggee.clone().addClass('draghelper');

      if ($draggee.parents('#content').length) {
        $draggeeHelper.addClass('drag-in-content');
      }
      if ($draggee.parents('.slideout-container').length) {
        $draggeeHelper.addClass('drag-in-slideout');
      }

      if (this.settings.copyDraggeeInputValuesToHelper) {
        Garnish.copyInputValues($draggee, $draggeeHelper);
      }

      // Remove any name= attributes so radio buttons don't lose their values
      $draggeeHelper.find('[name]').attr('name', '');

      $draggeeHelper
        .outerWidth(Math.ceil($draggee.outerWidth()))
        .outerHeight(Math.ceil($draggee.outerHeight()))
        .css({margin: 0, 'pointer-events': 'none'});

      if (this.settings.helper) {
        if (typeof this.settings.helper === 'function') {
          $draggeeHelper = this.settings.helper($draggeeHelper, index);
        } else {
          $draggeeHelper = $(this.settings.helper).append($draggeeHelper);
        }
      }

      $draggeeHelper.appendTo(Garnish.$bod);

      const helperPos = this._getHelperTarget(index, true);

      $draggeeHelper.css({
        position: 'absolute',
        top: helperPos.top,
        left: helperPos.left,
        zIndex: this.settings.helperBaseZindex + this.$draggee.length - index,
        display: this.draggeeDisplay,
      });

      if (this.settings.helperOpacity != 1) {
        $draggeeHelper.css('opacity', this.settings.helperOpacity);
      }

      this.helperPositions[index] = {
        top: helperPos.top,
        left: helperPos.left,
      };

      this.helpers.push($draggeeHelper);
    },

    /**
     * Update Helper Position
     */
    _updateHelperPos: function () {
      // Has the mouse moved?
      if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY) {
        // Get the new target helper positions
        for (
          this._updateHelperPos._i = 0;
          this._updateHelperPos._i < this.helpers.length;
          this._updateHelperPos._i++
        ) {
          this.helperTargets[this._updateHelperPos._i] = this._getHelperTarget(
            this._updateHelperPos._i
          );
        }

        this.lastMouseX = this.mouseX;
        this.lastMouseY = this.mouseY;
      }

      // Gravitate helpers toward their target positions
      for (
        this._updateHelperPos._j = 0;
        this._updateHelperPos._j < this.helpers.length;
        this._updateHelperPos._j++
      ) {
        this._updateHelperPos._lag =
          this.settings.helperLagBase +
          this.helperLagIncrement * this._updateHelperPos._j;

        this.helperPositions[this._updateHelperPos._j] = {
          left:
            this.helperPositions[this._updateHelperPos._j].left +
            (this.helperTargets[this._updateHelperPos._j].left -
              this.helperPositions[this._updateHelperPos._j].left) /
              this._updateHelperPos._lag,
          top:
            this.helperPositions[this._updateHelperPos._j].top +
            (this.helperTargets[this._updateHelperPos._j].top -
              this.helperPositions[this._updateHelperPos._j].top) /
              this._updateHelperPos._lag,
        };

        this.helpers[this._updateHelperPos._j].css(
          this.helperPositions[this._updateHelperPos._j]
        );
      }

      // Let's do this again on the next frame!
      this.updateHelperPosFrame = Garnish.requestAnimationFrame(
        this.updateHelperPosProxy
      );
    },

    /**
     * Get the helper position for a draggee helper
     */
    _getHelperTarget: function (index, real) {
      return {
        left:
          this.getHelperTargetX(real) + this.settings.helperSpacingX * index,
        top: this.getHelperTargetY(real) + this.settings.helperSpacingY * index,
      };
    },

    _showDraggee: function () {
      // Remove the helpers
      for (var i = 0; i < this.helpers.length; i++) {
        this.helpers[i].remove();
      }

      this.helpers = null;

      this.$draggee.show().css('visibility', '');

      this.onReturnHelpersToDraggees();

      this._returningHelpersToDraggees = false;
    },
  },
  {
    defaults: {
      filter: null,
      singleHelper: false,
      collapseDraggees: false,
      removeDraggee: false,
      hideDraggee: true,
      copyDraggeeInputValuesToHelper: false,
      helperOpacity: 1,
      moveHelperToCursor: false,
      helper: null,
      helperBaseZindex: 1000,
      helperLagBase: 3,
      helperLagIncrementDividend: 1.5,
      helperSpacingX: 5,
      helperSpacingY: 5,
      onReturnHelpersToDraggees: $.noop,
    },
  }
);
