import {DragSort} from '@craftcms/garnish';

// Keeps the `declare const $` / `declare const Craft` seams: the structural
// row logic is jQuery-heavy and interops with the still-legacy table view
// (`this.tableView`, a P4a TableElementIndexView) and its jQuery selection.
// NOTE: modern DragSort exposes `$targetItem`/`$draggee`/`helpers` as
// HTMLElement(s) (not jQuery), so those are wrapped with `$(...)` where the
// legacy jQuery traversal is needed, and findDraggee returns a plain array.
declare const $: any;
declare const Craft: any;

const HELPER_MARGIN = 0;
const LEVEL_INDENT = 44;
const MAX_GIVE = 22;

// Module-level (a `static defaults` would collide with `DragSort.defaults`).
const DEFAULTS = {
  structureId: null,
  maxLevels: 1,
  onPositionChange: () => {},
};

/**
 * ElementTableSorter — a port of `Craft.ElementTableSorter` onto
 * `@craftcms/garnish` `DragSort`. Drag-reorders element table rows, including
 * structure-aware indentation (levels/ancestors) and persisting moves to the
 * server. Created by `Craft.TableElementIndexView`, so exposed on `window.Craft`.
 */
export class ElementTableSorter extends DragSort {
  static HELPER_MARGIN = HELPER_MARGIN;
  static LEVEL_INDENT = LEVEL_INDENT;
  static MAX_GIVE = MAX_GIVE;

  declare settings: any;

  tableView: any = null;

  _helperMargin: any = null;
  _$firstRowCells: any = null;
  _$titleHelperCell: any = null;
  _titleHelperCellOuterWidth: any = null;
  _ancestors: any = null;
  _updateAncestorsFrame: any = null;
  _draggeeLevel: any = null;
  _draggeeLevelDelta: any = null;
  draggingLastElements: any = null;
  _loadingDraggeeLevelDelta = false;
  _targetLevel: any = null;
  _targetLevelBounds: any = null;
  _positionChanged: any = null;

  constructor(tableView?: any, $elements?: any, settings?: any) {
    super(
      $elements,
      $.extend({}, DEFAULTS, settings, {
        handle: '.move',
        collapseDraggees: true,
        singleHelper: true,
        helperSpacingY: 2,
        magnetStrength: 4,
        helperLagBase: 1.5,
        axis: 'y', // Garnish.Y_AXIS
      })
    );

    this.tableView = tableView;
    this._helperMargin = this.tableView?.elementIndex?.actions ? 40 : 0;
    // `helper` needs `this`; it's a drag-time callback, wire it after super.
    this.settings.helper = ($helperRow: any) => this.getHelper($helperRow);
  }

  /** Returns the draggee rows (including any descendent rows). */
  override findDraggee(): any {
    const $targetItem = $(this.$targetItem);
    this._draggeeLevel = this._targetLevel = this._level($targetItem);
    this._draggeeLevelDelta = 0;

    let $draggee = $($targetItem);
    let $nextRow = $targetItem.next();

    while ($nextRow.length) {
      // See if this row is a descendant of the draggee
      const nextRowLevel = this._level($nextRow);

      if (nextRowLevel <= this._draggeeLevel) {
        break;
      }

      // Is this the deepest descendant we've seen so far?
      const nextRowLevelDelta = nextRowLevel - this._draggeeLevel;
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
      return $draggee.toArray();
    }

    // Do we have a maxLevels to enforce, and does it look like this draggee has
    // descendants we don't know about yet?
    if (
      this.settings.maxLevels &&
      ($draggee.has('> th button.toggle[aria-expanded=false]').length ||
        (this.draggingLastElements && this.tableView.getMorePending()))
    ) {
      // Only way to know the true descendant level delta is to ask PHP
      this._loadingDraggeeLevelDelta = true;

      const data = this._getAjaxBaseData($targetItem);

      Craft.sendActionRequest('POST', 'structures/get-element-level-delta', {
        data,
      }).then((response: any) => {
        this._loadingDraggeeLevelDelta = false;

        if (this.dragging) {
          this._draggeeLevelDelta = response.data.delta;
          this._setTargetLevelBounds();
          this.drag(false);
        }
      });
    }

    return $draggee.toArray();
  }

  /** Returns the drag helper. */
  getHelper($helperRow: any): any {
    const $outerContainer = $(
      '<div class="elements datatablesorthelper"/>'
    ).appendTo(document.body);
    const $innerContainer = $('<div class="tableview"/>').appendTo(
      $outerContainer
    );
    const $table = $('<table class="data"/>').appendTo($innerContainer);
    const $tbody = $('<tbody/>').appendTo($table);

    $helperRow.appendTo($tbody);

    // Copy the column widths
    this._$firstRowCells = this.tableView.$elementContainer
      .children('tr:first')
      .children();
    const $helperCells = $helperRow.children();

    for (let i = 0; i < $helperCells.length; i++) {
      const $helperCell = $($helperCells[i]);

      // Skip the checkbox cell
      if ($helperCell.hasClass('checkbox-cell')) {
        $helperCell.remove();
        continue;
      }

      // Hard-set the cell widths
      const $firstRowCell = $(this._$firstRowCells[i]);
      const width = $firstRowCell[0].getBoundingClientRect().width;

      $firstRowCell.css('width', width + 'px');
      $helperCell.css('width', width + 'px');

      // Is this the title cell?
      if ($firstRowCell[0]?.hasAttribute('data-titlecell')) {
        this._$titleHelperCell = $helperCell;
        this._titleHelperCellOuterWidth = width;

        $helperCell.children('div').css(`padding-${Craft.left}`, '24px');
      }
    }

    return $outerContainer;
  }

  override canInsertBefore(item: any): boolean {
    const $item = $(item);
    return this._getLevelBounds($item.prev(), $item) !== false;
  }

  override canInsertAfter(item: any): boolean {
    const $item = $(item);
    return this._getLevelBounds($item, $item.next()) !== false;
  }

  // Events
  // -------------------------------------------------------------------------

  override onDragStart(): void {
    const $targetItem = $(this.$targetItem);
    // Get the initial set of ancestors, before the item gets moved
    this._ancestors = this._getAncestors($targetItem, this._level($targetItem));

    // Set the initial target level bounds
    this._setTargetLevelBounds();

    // Check to see if we should load more elements now
    if (!this.tableView.elementIndex.paginated) {
      this.tableView.maybeLoadMore();
    }

    super.onDragStart();
  }

  override onDrag(): void {
    super.onDrag();
    this._updateIndent();
  }

  override onInsertionPointChange(): void {
    this._setTargetLevelBounds();
    this._updateAncestorsBeforeRepaint();
    super.onInsertionPointChange();
  }

  override onDragStop(): void {
    this._positionChanged = false;
    super.onDragStop();

    const $draggee = $(this.$draggee);
    const $targetItem = $(this.$targetItem);

    // Update the draggee's padding if the position just changed
    if (this._targetLevel != this._draggeeLevel) {
      const levelDiff = this._targetLevel - this._draggeeLevel;

      for (let i = 0; i < $draggee.length; i++) {
        const $row = $($draggee[i]);
        const oldLevel = this._level($row);
        const newLevel = oldLevel + levelDiff;
        const padding = 24 + this._getLevelIndent(newLevel);
        const $structureTextAlternative = $row.find('[data-text-alternative]');
        const altText = Craft.t('app', 'Level {num}', {num: newLevel});

        $row.data('level', newLevel);
        $row.find('.element').data('level', newLevel);
        $row
          .find('> [data-titlecell]:first > div')
          .css(`padding-${Craft.left}`, padding);

        // Update text alternative
        $structureTextAlternative.text(altText);
      }

      this._positionChanged = true;
    }

    // Keep in mind this could have also been set by onSortChange()
    if (this._positionChanged && this.settings.structureId) {
      // Tell the server about the new position
      const data = this._getAjaxBaseData($draggee);

      // Find the previous sibling/parent, if there is one
      let $prevRow = $draggee.first().prev();
      let $spinnerRow: any;
      let $toggle: any;

      while ($prevRow.length) {
        const prevRowLevel = this._level($prevRow);

        if (prevRowLevel == this._targetLevel) {
          data.prevId = $prevRow.data('id');
          break;
        }

        if (prevRowLevel < this._targetLevel) {
          data.parentId = $prevRow.data('id');

          // Is this row collapsed?
          $toggle = $prevRow.find('> th .toggle');

          if (!$toggle.hasClass('expanded')) {
            // Make it look expanded
            $toggle.addClass('expanded');

            // Add a temporary row
            $spinnerRow = this.tableView._createSpinnerRowAfter($prevRow);

            // Remove the target item
            if (this.tableView.elementSelect) {
              this.tableView.elementSelect.removeItems($targetItem);
            }

            this.removeItems(this.$targetItem);
            $targetItem.remove();
            this.tableView._totalVisible--;
          }

          break;
        }

        $prevRow = $prevRow.prev();
      }

      Craft.sendActionRequest('POST', 'structures/move-element', {data})
        .then(() => {
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
        .catch((e: any) => {
          Craft.cp.displayError(e?.response?.data?.message);
          this.tableView.elementIndex.updateElements();
        });
    }
  }

  override onSortChange(): void {
    if (this.tableView.elementSelect) {
      this.tableView.elementSelect.resetItemOrder();
    }

    this._positionChanged = true;
    super.onSortChange();
  }

  onPositionChange(): void {
    requestAnimationFrame(() => {
      this.trigger('positionChange');
      this.settings.onPositionChange();
    });
  }

  override onReturnHelpersToDraggees(): void {
    this._$firstRowCells.css('width', '');

    // If we were dragging the last elements on the page (and it's not a
    // paginated view) and ended up loading any additional elements in, there
    // could be a gap between the last draggee item and whatever now comes after
    // it. So remove the post-draggee elements and possibly load up the next batch.
    if (
      this.draggingLastElements &&
      !this.tableView.elementIndex.paginated &&
      this.tableView.getMorePending()
    ) {
      // Update the element index's record of how many items are actually visible
      this.tableView._totalVisible +=
        this.newDraggeeIndexes![0]! - this.oldDraggeeIndexes![0]!;

      const $postDraggeeItems = $(this.$draggee).last().nextAll();

      if ($postDraggeeItems.length) {
        this.removeItems($postDraggeeItems.toArray());
        $postDraggeeItems.remove();
        this.tableView.maybeLoadMore();
      }
    }

    super.onReturnHelpersToDraggees();
  }

  /**
   * Returns the min and max levels that the draggee could occupy between two
   * given rows, or false if it's not going to work out.
   */
  _getLevelBounds(
    $prevRow: any,
    $nextRow: any
  ): {min: number; max: number} | false {
    if (this._loadingDraggeeLevelDelta) {
      return false;
    }

    // Can't go any lower than the next row, if there is one
    let minLevel: number;
    if ($nextRow && $nextRow.length) {
      minLevel = this._level($nextRow);
    } else {
      minLevel = 1;
    }

    // Can't go any higher than the previous row + 1
    let maxLevel: number;
    if ($prevRow && $prevRow.length) {
      maxLevel = this._level($prevRow) + 1;
    } else {
      maxLevel = 1;
    }

    // Does this structure have a max level?
    if (this.settings.maxLevels) {
      // Make sure it's going to fit at all here
      if (
        minLevel != 1 &&
        minLevel + this._draggeeLevelDelta > this.settings.maxLevels
      ) {
        return false;
      }

      // Limit the max level if we have to
      if (maxLevel + this._draggeeLevelDelta > this.settings.maxLevels) {
        maxLevel = this.settings.maxLevels - this._draggeeLevelDelta;

        if (maxLevel < minLevel) {
          maxLevel = minLevel;
        }
      }
    }

    return {min: minLevel, max: maxLevel};
  }

  /** Determines the min and max possible levels at the current draggee's position. */
  _setTargetLevelBounds(): void {
    const $draggee = $(this.$draggee);
    this._targetLevelBounds = this._getLevelBounds(
      $draggee.first().prev(),
      $draggee.last().next()
    );
  }

  /** Determines the target level based on the current mouse position. */
  _updateIndent(): void {
    // How far has the cursor moved?
    let mouseDist = this.realMouseX! - this.mousedownX!;

    // Flip that if this is RTL
    if (Craft.orientation === 'rtl') {
      mouseDist *= -1;
    }

    // What is that in indentation levels?
    let indentationDist = Math.round(mouseDist / LEVEL_INDENT);

    // Combine with the original level to get the new target level
    let targetLevel = this._draggeeLevel + indentationDist;

    // Contain it within our min/max levels
    if (targetLevel < this._targetLevelBounds.min) {
      indentationDist += this._targetLevelBounds.min - targetLevel;
      targetLevel = this._targetLevelBounds.min;
    } else if (targetLevel > this._targetLevelBounds.max) {
      indentationDist -= targetLevel - this._targetLevelBounds.max;
      targetLevel = this._targetLevelBounds.max;
    }

    // Has the target level changed?
    if (this._targetLevel !== (this._targetLevel = targetLevel)) {
      // Target level is changing, so update the ancestors
      this._updateAncestorsBeforeRepaint();
    }

    // How far away is the cursor from the exact target level distance?
    const targetLevelMouseDiff = mouseDist - indentationDist * LEVEL_INDENT;

    // What's the magnet impact of that?
    let magnetImpact = Math.round(targetLevelMouseDiff / 15);

    // Put it on a leash
    if (Math.abs(magnetImpact) > MAX_GIVE) {
      magnetImpact = (magnetImpact > 0 ? 1 : -1) * MAX_GIVE;
    }

    // Apply the new margin/width
    const closestLevelMagnetIndent =
      this._getLevelIndent(this._targetLevel) + magnetImpact;
    $(this.helpers[0]).css(
      `margin-${Craft.left}`,
      closestLevelMagnetIndent + this._helperMargin
    );
    this._$titleHelperCell.css(
      'width',
      this._titleHelperCellOuterWidth - closestLevelMagnetIndent
    );
  }

  /** Returns the indent size for a given level */
  _getLevelIndent(level: number): number {
    return (level - 1) * LEVEL_INDENT;
  }

  /** Returns the base data that should be sent with StructureController Ajax requests. */
  _getAjaxBaseData($row: any): any {
    return {
      structureId: this.settings.structureId,
      elementId: $row.data('id'),
      siteId: $row.find('.element:first').data('site-id'),
    };
  }

  /** Returns a row's ancestor rows */
  _getAncestors($row: any, targetLevel: number): any[] {
    const ancestors: any[] = [];

    if (targetLevel != 0) {
      let level = targetLevel;
      let $prevRow = $row.prev();

      while ($prevRow.length) {
        if (this._level($prevRow) < level) {
          ancestors.unshift($prevRow);
          level = this._level($prevRow);

          // Did we just reach the top?
          if (level == 0) {
            break;
          }
        }

        $prevRow = $prevRow.prev();
      }
    }

    return ancestors;
  }

  _level($row: any): number {
    return $row.data('level') || 1;
  }

  /** Prepares to have the ancestors updated before the screen is repainted. */
  _updateAncestorsBeforeRepaint(): void {
    if (this._updateAncestorsFrame) {
      cancelAnimationFrame(this._updateAncestorsFrame);
    }

    this._updateAncestorsFrame = requestAnimationFrame(
      this._updateAncestors.bind(this)
    );
  }

  _updateAncestors(): void {
    this._updateAncestorsFrame = null;

    // Update the old ancestors
    for (let i = 0; i < this._ancestors.length; i++) {
      const $ancestor = this._ancestors[i];

      // One less descendant now
      $ancestor.data('descendants', $ancestor.data('descendants') - 1);

      // Is it now childless?
      if ($ancestor.data('descendants') == 0) {
        // Remove its toggle
        $ancestor.find('> th .toggle:first').remove();
      }
    }

    // Update the new ancestors
    const newAncestors = this._getAncestors(
      $(this.$targetItem),
      this._targetLevel
    );

    for (let i = 0; i < newAncestors.length; i++) {
      const $ancestor = newAncestors[i];

      // One more descendant now
      $ancestor.data('descendants', $ancestor.data('descendants') + 1);

      // Is this its first child?
      if ($ancestor.data('descendants') == 1) {
        // Create its toggle
        const ancestorTitle = $ancestor.data('title');
        $('<button/>', {
          class: 'toggle expanded',
          type: 'button',
          'aria-expanded': 'true',
          title: Craft.t('app', 'Show/hide children'),
          'aria-label': Craft.t('app', 'Show {title} children', {
            title: ancestorTitle,
          }),
        }).insertAfter($ancestor.find('> th .move:first'));
      }
    }

    this._ancestors = newAncestors;
  }
}
