/** global: Craft */
/** global: Garnish */
Craft.ElementTableSorter = Garnish.DragSort.extend(
  {
    tableView: null,

    _helperMargin: null,

    _$firstRowCells: null,
    _$titleHelperCell: null,

    _titleHelperCellOuterWidth: null,

    _ancestors: null,
    _updateAncestorsFrame: null,

    _draggeeLevel: null,
    _draggeeLevelDelta: null,
    draggingLastElements: null,
    _loadingDraggeeLevelDelta: false,

    _targetLevel: null,
    _targetLevelBounds: null,

    _positionChanged: null,

    /**
     * Constructor
     */
    init: function (tableView, $elements, settings) {
      this.tableView = tableView;

      this._helperMargin = this.tableView.elementIndex.actions ? 40 : 0;

      settings = $.extend({}, Craft.ElementTableSorter.defaults, settings, {
        handle: '.move',
        collapseDraggees: true,
        singleHelper: true,
        helperSpacingY: 2,
        magnetStrength: 4,
        helper: this.getHelper.bind(this),
        helperLagBase: 1.5,
        axis: Garnish.Y_AXIS,
      });

      this.base($elements, settings);
    },

    /**
     * Returns the draggee rows (including any descendent rows).
     */
    findDraggee: function () {
      this._draggeeLevel = this._targetLevel = this._level(this.$targetItem);
      this._draggeeLevelDelta = 0;

      var $draggee = $(this.$targetItem),
        $nextRow = this.$targetItem.next();

      while ($nextRow.length) {
        // See if this row is a descendant of the draggee
        const nextRowLevel = this._level($nextRow);

        if (nextRowLevel <= this._draggeeLevel) {
          break;
        }

        // Is this the deepest descendant we've seen so far?
        var nextRowLevelDelta = nextRowLevel - this._draggeeLevel;

        if (nextRowLevelDelta > this._draggeeLevelDelta) {
          this._draggeeLevelDelta = nextRowLevelDelta;
        }

        // Add it and prep the next row
        $draggee = $draggee.add($nextRow);
        $nextRow = $nextRow.next();
      }

      // Are we dragging the last elements on the page?
      this.draggingLastElements = !$nextRow.length;

      if (
        this.tableView.elementIndex.paginated &&
        this.settings.structureId == null
      ) {
        return $draggee;
      }

      // Do we have a maxLevels to enforce,
      // and does it look like this draggee has descendants we don't know about yet?
      if (
        this.settings.maxLevels &&
        ($draggee.has('> th button.toggle[aria-expanded=false]').length ||
          (this.draggingLastElements && this.tableView.getMorePending()))
      ) {
        // Only way to know the true descendant level delta is to ask PHP
        this._loadingDraggeeLevelDelta = true;

        var data = this._getAjaxBaseData(this.$targetItem);

        Craft.sendActionRequest('POST', 'structures/get-element-level-delta', {
          data,
        }).then((response) => {
          this._loadingDraggeeLevelDelta = false;

          if (this.dragging) {
            this._draggeeLevelDelta = response.data.delta;
            this._setTargetLevelBounds();
            this.drag(false);
          }
        });
      }

      return $draggee;
    },

    /**
     * Returns the drag helper.
     */
    getHelper: function ($helperRow) {
      var $outerContainer = $(
          '<div class="elements datatablesorthelper"/>'
        ).appendTo(Garnish.$bod),
        $innerContainer = $('<div class="tableview"/>').appendTo(
          $outerContainer
        ),
        $table = $('<table class="data"/>').appendTo($innerContainer),
        $tbody = $('<tbody/>').appendTo($table);

      $helperRow.appendTo($tbody);

      // Copy the column widths
      this._$firstRowCells = this.tableView.$elementContainer
        .children('tr:first')
        .children();
      var $helperCells = $helperRow.children();

      for (var i = 0; i < $helperCells.length; i++) {
        var $helperCell = $($helperCells[i]);

        // Skip the checkbox cell
        if ($helperCell.hasClass('checkbox-cell')) {
          $helperCell.remove();
          continue;
        }

        // Hard-set the cell widths
        var $firstRowCell = $(this._$firstRowCells[i]);
        var width = $firstRowCell[0].getBoundingClientRect().width;

        $firstRowCell.css('width', width + 'px');
        $helperCell.css('width', width + 'px');

        // Is this the title cell?
        if (Garnish.hasAttr($firstRowCell, 'data-titlecell')) {
          this._$titleHelperCell = $helperCell;
          this._titleHelperCellOuterWidth = width;

          $helperCell.children('div').css(`padding-${Craft.left}`, '24px');
        }
      }

      return $outerContainer;
    },

    /**
     * Returns whether the draggee can be inserted before a given item.
     */
    canInsertBefore: function ($item) {
      return this._getLevelBounds($item.prev(), $item) !== false;
    },

    /**
     * Returns whether the draggee can be inserted after a given item.
     */
    canInsertAfter: function ($item) {
      return this._getLevelBounds($item, $item.next()) !== false;
    },

    // Events
    // -------------------------------------------------------------------------

    /**
     * On Drag Start
     */
    onDragStart: function () {
      // Get the initial set of ancestors, before the item gets moved
      this._ancestors = this._getAncestors(
        this.$targetItem,
        this._level(this.$targetItem)
      );

      // Set the initial target level bounds
      this._setTargetLevelBounds();

      // Check to see if we should load more elements now

      if (!this.tableView.elementIndex.paginated) {
        this.tableView.maybeLoadMore();
      }

      this.base();
    },

    /**
     * On Drag
     */
    onDrag: function () {
      this.base();
      this._updateIndent();
    },

    /**
     * On Insertion Point Change
     */
    onInsertionPointChange: function () {
      this._setTargetLevelBounds();
      this._updateAncestorsBeforeRepaint();
      this.base();
    },

    /**
     * On Drag Stop
     */
    onDragStop: function () {
      this._positionChanged = false;
      this.base();

      // Update the draggee's padding if the position just changed
      // ---------------------------------------------------------------------

      if (this._targetLevel != this._draggeeLevel) {
        var levelDiff = this._targetLevel - this._draggeeLevel;

        for (var i = 0; i < this.$draggee.length; i++) {
          var $draggee = $(this.$draggee[i]),
            oldLevel = this._level($draggee),
            newLevel = oldLevel + levelDiff,
            padding = 24 + this._getLevelIndent(newLevel);
          const $structureTextAlternative = $draggee.find(
            '[data-text-alternative]'
          );
          const altText = Craft.t('app', 'Level {num}', {
            num: newLevel,
          });

          $draggee.data('level', newLevel);
          $draggee.find('.element').data('level', newLevel);
          $draggee
            .find('> [data-titlecell]:first > div')
            .css(`padding-${Craft.left}`, padding);

          // Update text alternative
          $structureTextAlternative.text(altText);
        }

        this._positionChanged = true;
      }

      // Keep in mind this could have also been set by onSortChange()
      if (this._positionChanged) {
        if (this.settings.structureId) {
          // Tell the server about the new position
          // -----------------------------------------------------------------

          const data = this._getAjaxBaseData(this.$draggee);

          // Find the previous sibling/parent, if there is one
          let $prevRow = this.$draggee.first().prev();
          let $spinnerRow;

          while ($prevRow.length) {
            const prevRowLevel = this._level($prevRow);

            if (prevRowLevel == this._targetLevel) {
              data.prevId = $prevRow.data('id');
              break;
            }

            if (prevRowLevel < this._targetLevel) {
              data.parentId = $prevRow.data('id');

              // Is this row collapsed?
              const $toggle = $prevRow.find('> th .toggle');

              if (!$toggle.hasClass('expanded')) {
                // Make it look expanded
                $toggle.addClass('expanded');

                // Add a temporary row
                $spinnerRow = this.tableView._createSpinnerRowAfter($prevRow);

                // Remove the target item
                if (this.tableView.elementSelect) {
                  this.tableView.elementSelect.removeItems(this.$targetItem);
                }

                this.removeItems(this.$targetItem);
                this.$targetItem.remove();
                this.tableView._totalVisible--;
              }

              break;
            }

            $prevRow = $prevRow.prev();
          }

          Craft.sendActionRequest('POST', 'structures/move-element', {data})
            .then((response) => {
              Craft.cp.displaySuccess(Craft.t('app', 'New position saved.'));
              this.onPositionChange();

              // Were we waiting on this to complete so we can expand the new parent?
              if ($spinnerRow && $spinnerRow.parent().length) {
                $spinnerRow.remove();
                this.tableView._expandElement($toggle, true);
              }

              // See if we should run any pending tasks
              Craft.cp.runQueue();
            })
            .catch((e) => {
              Craft.cp.displayError(e?.response?.data?.message);
              this.tableView.elementIndex.updateElements();
            });
        }
      }
    },

    onSortChange: function () {
      if (this.tableView.elementSelect) {
        this.tableView.elementSelect.resetItemOrder();
      }

      this._positionChanged = true;
      this.base();
    },

    onPositionChange: function () {
      Garnish.requestAnimationFrame(() => {
        this.trigger('positionChange');
        this.settings.onPositionChange();
      });
    },

    onReturnHelpersToDraggees: function () {
      this._$firstRowCells.css('width', '');

      // If we were dragging the last elements on the page (and it's not a paginated view) and ended up loading any additional elements in,
      // there could be a gap between the last draggee item and whatever now comes after it.
      // So remove the post-draggee elements and possibly load up the next batch.
      if (
        this.draggingLastElements &&
        !this.tableView.elementIndex.paginated &&
        this.tableView.getMorePending()
      ) {
        // Update the element index's record of how many items are actually visible
        this.tableView._totalVisible +=
          this.newDraggeeIndexes[0] - this.oldDraggeeIndexes[0];

        var $postDraggeeItems = this.$draggee.last().nextAll();

        if ($postDraggeeItems.length) {
          this.removeItems($postDraggeeItems);
          $postDraggeeItems.remove();
          this.tableView.maybeLoadMore();
        }
      }

      this.base();
    },

    /**
     * Returns the min and max levels that the draggee could occupy between
     * two given rows, or false if it’s not going to work out.
     */
    _getLevelBounds: function ($prevRow, $nextRow) {
      if (this._loadingDraggeeLevelDelta) {
        return false;
      }

      // Can't go any lower than the next row, if there is one
      if ($nextRow && $nextRow.length) {
        this._getLevelBounds._minLevel = this._level($nextRow);
      } else {
        this._getLevelBounds._minLevel = 1;
      }

      // Can't go any higher than the previous row + 1
      if ($prevRow && $prevRow.length) {
        this._getLevelBounds._maxLevel = this._level($prevRow) + 1;
      } else {
        this._getLevelBounds._maxLevel = 1;
      }

      // Does this structure have a max level?
      if (this.settings.maxLevels) {
        // Make sure it's going to fit at all here
        if (
          this._getLevelBounds._minLevel != 1 &&
          this._getLevelBounds._minLevel + this._draggeeLevelDelta >
            this.settings.maxLevels
        ) {
          return false;
        }

        // Limit the max level if we have to
        if (
          this._getLevelBounds._maxLevel + this._draggeeLevelDelta >
          this.settings.maxLevels
        ) {
          this._getLevelBounds._maxLevel =
            this.settings.maxLevels - this._draggeeLevelDelta;

          if (this._getLevelBounds._maxLevel < this._getLevelBounds._minLevel) {
            this._getLevelBounds._maxLevel = this._getLevelBounds._minLevel;
          }
        }
      }

      return {
        min: this._getLevelBounds._minLevel,
        max: this._getLevelBounds._maxLevel,
      };
    },

    /**
     * Determines the min and max possible levels at the current draggee's position.
     */
    _setTargetLevelBounds: function () {
      this._targetLevelBounds = this._getLevelBounds(
        this.$draggee.first().prev(),
        this.$draggee.last().next()
      );
    },

    /**
     * Determines the target level based on the current mouse position.
     */
    _updateIndent: function (forcePositionChange) {
      // Figure out the target level
      // ---------------------------------------------------------------------

      // How far has the cursor moved?
      this._updateIndent._mouseDist = this.realMouseX - this.mousedownX;

      // Flip that if this is RTL
      if (Craft.orientation === 'rtl') {
        this._updateIndent._mouseDist *= -1;
      }

      // What is that in indentation levels?
      this._updateIndent._indentationDist = Math.round(
        this._updateIndent._mouseDist / Craft.ElementTableSorter.LEVEL_INDENT
      );

      // Combine with the original level to get the new target level
      this._updateIndent._targetLevel =
        this._draggeeLevel + this._updateIndent._indentationDist;

      // Contain it within our min/max levels
      if (this._updateIndent._targetLevel < this._targetLevelBounds.min) {
        this._updateIndent._indentationDist +=
          this._targetLevelBounds.min - this._updateIndent._targetLevel;
        this._updateIndent._targetLevel = this._targetLevelBounds.min;
      } else if (
        this._updateIndent._targetLevel > this._targetLevelBounds.max
      ) {
        this._updateIndent._indentationDist -=
          this._updateIndent._targetLevel - this._targetLevelBounds.max;
        this._updateIndent._targetLevel = this._targetLevelBounds.max;
      }

      // Has the target level changed?
      if (
        this._targetLevel !==
        (this._targetLevel = this._updateIndent._targetLevel)
      ) {
        // Target level is changing, so update the ancestors
        this._updateAncestorsBeforeRepaint();
      }

      // Update the UI
      // ---------------------------------------------------------------------

      // How far away is the cursor from the exact target level distance?
      this._updateIndent._targetLevelMouseDiff =
        this._updateIndent._mouseDist -
        this._updateIndent._indentationDist *
          Craft.ElementTableSorter.LEVEL_INDENT;

      // What's the magnet impact of that?
      this._updateIndent._magnetImpact = Math.round(
        this._updateIndent._targetLevelMouseDiff / 15
      );

      // Put it on a leash
      if (
        Math.abs(this._updateIndent._magnetImpact) >
        Craft.ElementTableSorter.MAX_GIVE
      ) {
        this._updateIndent._magnetImpact =
          (this._updateIndent._magnetImpact > 0 ? 1 : -1) *
          Craft.ElementTableSorter.MAX_GIVE;
      }

      // Apply the new margin/width
      this._updateIndent._closestLevelMagnetIndent =
        this._getLevelIndent(this._targetLevel) +
        this._updateIndent._magnetImpact;
      this.helpers[0].css(
        `margin-${Craft.left}`,
        this._updateIndent._closestLevelMagnetIndent + this._helperMargin
      );
      this._$titleHelperCell.css(
        'width',
        this._titleHelperCellOuterWidth -
          this._updateIndent._closestLevelMagnetIndent
      );
    },

    /**
     * Returns the indent size for a given level
     */
    _getLevelIndent: function (level) {
      return (level - 1) * Craft.ElementTableSorter.LEVEL_INDENT;
    },

    /**
     * Returns the base data that should be sent with StructureController Ajax requests.
     */
    _getAjaxBaseData: function ($row) {
      return {
        structureId: this.settings.structureId,
        elementId: $row.data('id'),
        siteId: $row.find('.element:first').data('site-id'),
      };
    },

    /**
     * Returns a row's ancestor rows
     */
    _getAncestors: function ($row, targetLevel) {
      this._getAncestors._ancestors = [];

      if (targetLevel != 0) {
        this._getAncestors._level = targetLevel;
        this._getAncestors._$prevRow = $row.prev();

        while (this._getAncestors._$prevRow.length) {
          if (
            this._level(this._getAncestors._$prevRow) <
            this._getAncestors._level
          ) {
            this._getAncestors._ancestors.unshift(this._getAncestors._$prevRow);
            this._getAncestors._level = this._level(
              this._getAncestors._$prevRow
            );

            // Did we just reach the top?
            if (this._getAncestors._level == 0) {
              break;
            }
          }

          this._getAncestors._$prevRow = this._getAncestors._$prevRow.prev();
        }
      }

      return this._getAncestors._ancestors;
    },

    _level: function ($row) {
      return $row.data('level') || 1;
    },

    /**
     * Prepares to have the ancestors updated before the screen is repainted.
     */
    _updateAncestorsBeforeRepaint: function () {
      if (this._updateAncestorsFrame) {
        Garnish.cancelAnimationFrame(this._updateAncestorsFrame);
      }

      this._updateAncestorsFrame = Garnish.requestAnimationFrame(
        this._updateAncestors.bind(this)
      );
    },

    _updateAncestors: function () {
      this._updateAncestorsFrame = null;

      // Update the old ancestors
      // -----------------------------------------------------------------

      for (
        this._updateAncestors._i = 0;
        this._updateAncestors._i < this._ancestors.length;
        this._updateAncestors._i++
      ) {
        this._updateAncestors._$ancestor =
          this._ancestors[this._updateAncestors._i];

        // One less descendant now
        this._updateAncestors._$ancestor.data(
          'descendants',
          this._updateAncestors._$ancestor.data('descendants') - 1
        );

        // Is it now childless?
        if (this._updateAncestors._$ancestor.data('descendants') == 0) {
          // Remove its toggle
          this._updateAncestors._$ancestor.find('> th .toggle:first').remove();
        }
      }

      // Update the new ancestors
      // -----------------------------------------------------------------

      this._updateAncestors._newAncestors = this._getAncestors(
        this.$targetItem,
        this._targetLevel
      );

      for (
        this._updateAncestors._i = 0;
        this._updateAncestors._i < this._updateAncestors._newAncestors.length;
        this._updateAncestors._i++
      ) {
        this._updateAncestors._$ancestor =
          this._updateAncestors._newAncestors[this._updateAncestors._i];

        // One more descendant now
        this._updateAncestors._$ancestor.data(
          'descendants',
          this._updateAncestors._$ancestor.data('descendants') + 1
        );

        // Is this its first child?
        if (this._updateAncestors._$ancestor.data('descendants') == 1) {
          // Create its toggle
          const ancestorTitle = this._updateAncestors._$ancestor.data('title');
          $(
            '<button class="toggle expanded" type="button" aria-expanded="true" title="' +
              Craft.t('app', 'Show/hide children') +
              '" aria-label="' +
              Craft.t('app', 'Show {title} children', {title: ancestorTitle}) +
              '"></button>'
          ).insertAfter(
            this._updateAncestors._$ancestor.find('> th .move:first')
          );
        }
      }

      this._ancestors = this._updateAncestors._newAncestors;

      delete this._updateAncestors._i;
      delete this._updateAncestors._$ancestor;
      delete this._updateAncestors._newAncestors;
    },
  },
  {
    HELPER_MARGIN: 0,
    LEVEL_INDENT: 48,
    MAX_GIVE: 22,

    defaults: {
      structureId: null,
      maxLevels: 1,
      onPositionChange: $.noop,
    },
  }
);
