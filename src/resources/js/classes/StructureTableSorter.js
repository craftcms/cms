Craft.StructureTableSorter = Garnish.DragSort.extend({

	// Properties
	// =========================================================================

	elementIndex: null,
	structureId: null,
	maxLevels: null,

	_helperMargin: null,

	_$firstRowCells: null,
	_$titleHelperCell: null,

	_titleHelperCellOuterWidth: null,

	_mouseLevelOffset: null,
	_targetItemOffsetX: null,

	_ancestors: null,
	_updateAncestorsFrame: null,
	_updateAncestorsProxy: null,

	_draggeeLevel: null,
	_draggeeLevelDelta: null,
	draggingLastElements: null,
	_loadingDraggeeLevelDelta: false,

	_targetLevel: null,
	_targetLevelBounds: null,

	_positionChanged: null,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 */
	init: function(elementIndex, $elements, settings)
	{
		this.elementIndex = elementIndex;
		this.structureId = this.elementIndex.$table.data('structure-id');
		this.maxLevels = parseInt(this.elementIndex.$table.attr('data-max-levels'));

		settings = $.extend({}, Craft.StructureTableSorter.defaults, settings, {
			handle:           '.move',
			collapseDraggees: true,
			singleHelper:     true,
			helperSpacingY:   2,
			magnetStrength:   4,
			helper:           $.proxy(this, 'getHelper'),
			helperLagBase:    1.5,
			axis:             Garnish.Y_AXIS
		});

		this.base($elements, settings);
	},

	/**
	 * Start Dragging
	 */
	startDragging: function()
	{
		this._helperMargin = Craft.StructureTableSorter.HELPER_MARGIN + (this.elementIndex.actions ? 24 : 0);
		this.base();
	},

	/**
	 * Returns the draggee rows (including any descendent rows).
	 */
	findDraggee: function()
	{
		this._draggeeLevel = this.$targetItem.data('level');
		this._draggeeLevelDelta = 0;

		var $draggee = $(this.$targetItem)
			$nextRow = this.$targetItem.next();

		while ($nextRow.length)
		{
			// See if this row is a descendant of the draggee
			var nextRowLevel = $nextRow.data('level');

			if (nextRowLevel <= this._draggeeLevel)
			{
				break;
			}

			// Is this the deepest descendant we've seen so far?
			var nextRowLevelDelta = nextRowLevel - this._draggeeLevel;

			if (nextRowLevelDelta > this._draggeeLevelDelta)
			{
				this._draggeeLevelDelta = nextRowLevelDelta;
			}

			// Add it and prep the next row
			$draggee = $draggee.add($nextRow);
			$nextRow = $nextRow.next();
		}

		// Are we dragging the last elements on the page?
		this.draggingLastElements = !$nextRow.length;

		// Do we have a maxLevels to enforce,
		// and does it look like this draggee has descendants we don't know about yet?
		if (
			this.maxLevels &&
			this.draggingLastElements &&
			this.elementIndex.morePending
		)
		{
			// Only way to know the true descendant level delta is to ask PHP
			this._loadingDraggeeLevelDelta = true;

			var data = this._getAjaxBaseData(this.$targetItem);

			Craft.postActionRequest('structures/getElementLevelDelta', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					this._loadingDraggeeLevelDelta = false;

					if (this.dragging)
					{
						this._draggeeLevelDelta = response.delta;
						this.drag(false);
					}
				}
			}, this));
		}

		return $draggee;
	},

	/**
	 * Returns the drag helper.
	 */
	getHelper: function($helperRow)
	{
		var $outerContainer = $('<div class="elements datatablesorthelper"/>').appendTo(Garnish.$bod),
			$innerContainer = $('<div class="tableview"/>').appendTo($outerContainer),
			$table = $('<table class="data"/>').appendTo($innerContainer),
			$tbody = $('<tbody/>').appendTo($table);

		$helperRow.appendTo($tbody);

		// Copy the column widths
		this._$firstRowCells = this.elementIndex.$elementContainer.children('tr:first').children();
		var $helperCells = $helperRow.children();

		for (var i = 0; i < $helperCells.length; i++)
		{
			var $helperCell = $($helperCells[i]);

			// Skip the checkbox cell
			if (Garnish.hasAttr($helperCell, 'data-checkboxcell'))
			{
				$helperCell.remove();
				continue;
			}

			// Hard-set the cell widths
			var $firstRowCell = $(this._$firstRowCells[i]),
				width = $firstRowCell.width();

			$firstRowCell.width(width);
			$helperCell.width(width);

			// Is this the title cell?
			if (Garnish.hasAttr($firstRowCell, 'data-titlecell'))
			{
				this._$titleHelperCell = $helperCell;

				var padding = parseInt($firstRowCell.css('padding-'+Craft.left));
				this._titleHelperCellOuterWidth = width + padding - (this.elementIndex.actions ? 12 : 0);

				$helperCell.css('padding-'+Craft.left, Craft.StructureTableSorter.BASE_PADDING);
			}
		}

		return $outerContainer;
	},

	/**
	 * Returns whether the draggee can be inserted before a given item.
	 */
	canInsertBefore: function($item)
	{
		if (this._loadingDraggeeLevelDelta)
		{
			return false;
		}

		return (this._getLevelBounds($item.prev(), $item) !== false);
	},

	/**
	 * Returns whether the draggee can be inserted after a given item.
	 */
	canInsertAfter: function($item)
	{
		if (this._loadingDraggeeLevelDelta)
		{
			return false;
		}

		return (this._getLevelBounds($item, $item.next()) !== false);
	},

	// Events
	// -------------------------------------------------------------------------

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		// Get the initial set of ancestors, before the item gets moved
		this._ancestors = this._getAncestors(this.$targetItem, this.$targetItem.data('level'));

		// Get some info we will need when determining the target level
		this._mouseLevelOffset = this.mouseOffsetX - this._getLevelIndent(this._draggeeLevel),
		this._targetItemOffsetX = this.$targetItem.offset().left;

		// Set the initial target level bounds
		this._setTargetLevelBounds();

		// Check to see if we should load more elements now
		this.elementIndex.maybeLoadMore();

		this.base();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		this.base();
		this._updateIndent();
	},

	/**
	 * On Insertion Point Change
	 */
	onInsertionPointChange: function()
	{
		this._setTargetLevelBounds();
		this._updateAncestorsBeforeRepaint();
		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		this._positionChanged = false;
		this.base();

		// Update the draggee's padding if the position just changed
		// ---------------------------------------------------------------------

		if (this._targetLevel != this._draggeeLevel)
		{
			var levelDiff = this._targetLevel - this._draggeeLevel;

			for (var i = 0; i < this.$draggee.length; i++)
			{
				var $draggee = $(this.$draggee[i]),
					oldLevel = $draggee.data('level'),
					newLevel = oldLevel + levelDiff,
					padding = Craft.StructureTableSorter.BASE_PADDING + (this.elementIndex.actions ? 14 : 0) + this._getLevelIndent(newLevel);

				$draggee.data('level', newLevel);
				$draggee.find('.element').data('level', newLevel);
				$draggee.children('[data-titlecell]:first').css('padding-'+Craft.left, padding);
			}

			this._positionChanged = true;
		}

		// Keep in mind this could have also been set by onSortChange()
		if (this._positionChanged)
		{
			// Tell the server about the new position
			// -----------------------------------------------------------------

			var data = this._getAjaxBaseData(this.$draggee);

			// Find the previous sibling/parent, if there is one
			var $prevRow = this.$draggee.first().prev();

			while ($prevRow.length)
			{
				var prevRowLevel = $prevRow.data('level');

				if (prevRowLevel == this._targetLevel)
				{
					data.prevId = $prevRow.data('id');
					break;
				}

				if (prevRowLevel < this._targetLevel)
				{
					data.parentId = $prevRow.data('id');

					// Going to need these after the Ajax request completes
					var $newParentRow = $prevRow,
						$targetRow = this.$targetItem;

					break;
				}

				$prevRow = $prevRow.prev();
			}

			Craft.postActionRequest('structures/moveElement', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					Craft.cp.displayNotice(Craft.t('New position saved.'));
					this.onPositionChange();

					// Is there a new parent?
					if ($newParentRow)
					{
						// Is this row collapsed?
						var $toggle = $newParentRow.find('> td > .toggle');

						if (!$toggle.hasClass('expanded'))
						{
							// Remove the target item, and then expand its new parent
							if (this.elementIndex.elementSelect)
							{
								this.elementIndex.elementSelect.removeItems($targetRow);
							}

							this.removeItems($targetRow);
							$targetRow.remove();
							this.elementIndex._totalVisible--;

							this.elementIndex._expandElement($toggle);
						}
					}
				}
			}, this));
		}
	},

	onSortChange: function()
	{
		if (this.elementIndex.elementSelect)
		{
			this.elementIndex.elementSelect.resetItemOrder();
		}

		this._positionChanged = true;
		this.base();
	},

	onPositionChange: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('positionChange');
			this.settings.onPositionChange();
		}, this));
	},

	onReturnHelpersToDraggees: function()
	{
		this._$firstRowCells.css('width', '');

		// If we were dragging the last elements on the page and ended up loading any additional elements in,
		// there could be a gap between the last draggee item and whatever now comes after it.
		// So remove the post-draggee elements and possibly load up the next batch.
		if (this.draggingLastElements && this.elementIndex.morePending)
		{
			// Update the element index's record of how many items are actually visible
			this.elementIndex._totalVisible += (this.newDraggeeIndexes[0] - this.oldDraggeeIndexes[0]);

			var $postDraggeeItems = this.$draggee.last().nextAll();

			if ($postDraggeeItems.length)
			{
				this.removeItems($postDraggeeItems);
				$postDraggeeItems.remove();
				this.elementIndex.maybeLoadMore();
			}
		}

		this.base();
	},

	// Private methods
	// =========================================================================

	/**
	 * Returns the min and max levels that the draggee could occupy between
	 * two given rows, or false if it’s not going to work out.
	 */
	_getLevelBounds: function($prevRow, $nextRow)
	{
		// Can't go any lower than the next row, if there is one
		if ($nextRow && $nextRow.length)
		{
			this._getLevelBounds._minLevel = $nextRow.data('level');
		}
		else
		{
			this._getLevelBounds._minLevel = 1;
		}

		// Can't go any higher than the previous row + 1
		if ($prevRow && $prevRow.length)
		{
			this._getLevelBounds._maxLevel = $prevRow.data('level') + 1;
		}
		else
		{
			this._getLevelBounds._maxLevel = 1;
		}

		// Does this structure have a max level?
		if (this.maxLevels)
		{
			// Make sure it's going to fit at all here
			if (
				this._getLevelBounds._minLevel != 1 &&
				this._getLevelBounds._minLevel + this._draggeeLevelDelta > this.maxLevels
			)
			{
				return false;
			}

			// Limit the max level if we have to
			if (this._getLevelBounds._maxLevel + this._draggeeLevelDelta > this.maxLevels)
			{
				this._getLevelBounds._maxLevel = this.maxLevels - this._draggeeLevelDelta;

				if (this._getLevelBounds._maxLevel < this._getLevelBounds._minLevel)
				{
					this._getLevelBounds._maxLevel = this._getLevelBounds._minLevel;
				}
			}
		}

		return {
			min: this._getLevelBounds._minLevel,
			max: this._getLevelBounds._maxLevel
		};
	},

	/**
	 * Determines the min and max possible levels at the current draggee's position.
	 */
	_setTargetLevelBounds: function()
	{
		this._targetLevelBounds = this._getLevelBounds(
			this.$draggee.first().prev(),
			this.$draggee.last().next()
		);
	},

	/**
	 * Determines the target level based on the current mouse position.
	 */
	_updateIndent: function(forcePositionChange)
	{
		// Figure out where the mouse is relative to the target item
		this._updateIndent._mouseOffset = this.realMouseX - this._targetItemOffsetX;

		// Figure out which level the cursor is closest to
		// ---------------------------------------------------------------------

		this._updateIndent._closestLevel = null;
		this._updateIndent._closestMouseDist = null;
		this._updateIndent._closestLevelIndent = null;

		for (this._updateIndent._level = this._targetLevelBounds.min; this._updateIndent._level <= this._targetLevelBounds.max; this._updateIndent._level++)
		{
			this._updateIndent._levelIndent = this._getLevelIndent(this._updateIndent._level)
			this._updateIndent._mouseDist = Math.abs(this._updateIndent._levelIndent + this._mouseLevelOffset - this._updateIndent._mouseOffset);

			if (
				this._updateIndent._closestLevel === null ||
				this._updateIndent._mouseDist < this._updateIndent._closestMouseDist
			)
			{
				this._updateIndent._closestLevel = this._updateIndent._level;
				this._updateIndent._closestMouseDist = this._updateIndent._mouseDist;
				this._updateIndent._closestLevelIndent = this._updateIndent._levelIndent;
			}
		}

		// Update the target level to whatever's closest
		if (this._targetLevel !== (this._targetLevel = this._updateIndent._closestLevel))
		{
			// Target level is changing, so update the ancestors
			this._updateAncestorsBeforeRepaint();
		}

		// Figure out which level the cursor is closest to
		// ---------------------------------------------------------------------

		// How far is the cursor stretching it away?
		this._updateIndent._magnetImpact = Math.round((this._updateIndent._mouseOffset - this._updateIndent._closestLevelIndent) / 15);

		// Put it on a leash
		if (Math.abs(this._updateIndent._magnetImpact) > Craft.StructureTableSorter.MAX_GIVE)
		{
			this._updateIndent._magnetImpact = (this._updateIndent._magnetImpact > 0 ? 1 : -1) * Craft.StructureTableSorter.MAX_GIVE;
		}

		// Apply the new margin/width
		this._updateIndent._closestLevelMagnetIndent = this._updateIndent._closestLevelIndent + this._updateIndent._magnetImpact;
		this.helpers[0].css('margin-'+Craft.left, this._updateIndent._closestLevelMagnetIndent + this._helperMargin);
		this._$titleHelperCell.width(this._titleHelperCellOuterWidth - (this._updateIndent._closestLevelMagnetIndent + Craft.StructureTableSorter.BASE_PADDING));
	},

	/**
	 * Returns the indent size for a given level
	 */
	_getLevelIndent: function(level)
	{
		return (level - 1) * Craft.StructureTableSorter.LEVEL_INDENT;
	},

	/**
	 * Returns the base data that should be sent with StructureController Ajax requests.
	 */
	_getAjaxBaseData: function($row)
	{
		return {
			structureId: this.structureId,
			elementId:   $row.data('id'),
			locale:      $row.find('.element:first').data('locale')
		};
	},

	/**
	 * Returns a row's ancestor rows
	 */
	_getAncestors: function($row, targetLevel)
	{
		this._getAncestors._ancestors = [];

		if (targetLevel != 0)
		{
			this._getAncestors._level = targetLevel;
			this._getAncestors._$prevRow = $row.prev();

			while (this._getAncestors._$prevRow.length)
			{
				if (this._getAncestors._$prevRow.data('level') < this._getAncestors._level)
				{
					this._getAncestors._ancestors.unshift(this._getAncestors._$prevRow);
					this._getAncestors._level = this._getAncestors._$prevRow.data('level');

					// Did we just reach the top?
					if (this._getAncestors._level == 0)
					{
						break;
					}
				}

				this._getAncestors._$prevRow = this._getAncestors._$prevRow.prev();
			}
		}

		return this._getAncestors._ancestors;
	},

	/**
	 * Prepares to have the ancestors updated before the screen is repainted.
	 */
	_updateAncestorsBeforeRepaint: function()
	{
		if (this._updateAncestorsFrame)
		{
			Garnish.cancelAnimationFrame(this._updateAncestorsFrame);
		}

		if (!this._updateAncestorsProxy)
		{
			this._updateAncestorsProxy = $.proxy(this, '_updateAncestors');
		}

		this._updateAncestorsFrame = Garnish.requestAnimationFrame(this._updateAncestorsProxy);
	},

	_updateAncestors: function()
	{
		this._updateAncestorsFrame = null;

		// Update the old ancestors
		// -----------------------------------------------------------------

		for (this._updateAncestors._i = 0; this._updateAncestors._i < this._ancestors.length; this._updateAncestors._i++)
		{
			this._updateAncestors._$ancestor = this._ancestors[this._updateAncestors._i];

			// One less descendant now
			this._updateAncestors._$ancestor.data('descendants', this._updateAncestors._$ancestor.data('descendants') - 1);

			// Is it now childless?
			if (this._updateAncestors._$ancestor.data('descendants') == 0)
			{
				// Remove its toggle
				this._updateAncestors._$ancestor.find('> td > .toggle:first').remove();
			}
		}

		// Update the new ancestors
		// -----------------------------------------------------------------

		this._updateAncestors._newAncestors = this._getAncestors(this.$targetItem, this._targetLevel);

		for (this._updateAncestors._i = 0; this._updateAncestors._i < this._updateAncestors._newAncestors.length; this._updateAncestors._i++)
		{
			this._updateAncestors._$ancestor = this._updateAncestors._newAncestors[this._updateAncestors._i];

			// One more descendant now
			this._updateAncestors._$ancestor.data('descendants', this._updateAncestors._$ancestor.data('descendants') + 1);

			// Is this its first child?
			if (this._updateAncestors._$ancestor.data('descendants') == 1)
			{
				// Create its toggle
				$('<span class="toggle expanded" title="'+Craft.t('Show/hide children')+'"></span>')
					.insertAfter(this._updateAncestors._$ancestor.find('> td .move:first'));

			}
		}

		this._ancestors = this._updateAncestors._newAncestors;

		delete this._updateAncestors._i;
		delete this._updateAncestors._$ancestor;
		delete this._updateAncestors._newAncestors;
	}
},

// Static Properties
// =============================================================================

{
	BASE_PADDING: 33,
	HELPER_MARGIN: -7,
	LEVEL_INDENT: 44,
	MAX_GIVE: 22,

	defaults: {
		onPositionChange: $.noop
	}
});
