/**
 * Table Element Index View
 */
Craft.TableElementIndexView = Craft.BaseElementIndexView.extend(
{
	$table: null,
	$selectedSortHeader: null,

	structureTableSort: null,

	_totalVisiblePostStructureTableDraggee: null,
	_morePendingPostStructureTableDraggee: false,

	getElementContainer: function()
	{
		// Save a reference to the table
		this.$table = this.$container.find('table:first');
		return this.$table.children('tbody:first');
	},

	afterInit: function()
	{
		// Make the table collapsible for mobile devices
		Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
		Craft.cp.updateResponsiveTables();

		// Set the sort header
		this.initTableHeaders();

		// Create the Structure Table Sorter
		if (
			this.elementIndex.settings.context == 'index' &&
			this.elementIndex.getSelectedSortAttribute() == 'structure' &&
			Garnish.hasAttr(this.$table, 'data-structure-id')
		)
		{
			this.structureTableSort = new Craft.StructureTableSorter(this, this.getAllElements(), {
				onSortChange: $.proxy(this, '_onStructureTableSortChange')
			});
		}
		else
		{
			this.structureTableSort = null;
		}

		// Handle expand/collapse toggles for Structures
		if (this.elementIndex.getSelectedSortAttribute() == 'structure')
		{
			this.addListener(this.$elementContainer, 'click', function(ev)
			{
				var $target = $(ev.target);

				if ($target.hasClass('toggle'))
				{
					if (this._collapseElement($target) === false)
					{
						this._expandElement($target);
					}
				}
			});
		}
	},

	initTableHeaders: function()
	{
		var selectedSortAttr = this.elementIndex.getSelectedSortAttribute(),
			$tableHeaders = this.$table.children('thead').children().children('[data-attribute]');

		for (var i = 0; i < $tableHeaders.length; i++)
		{
			var $header = $tableHeaders.eq(i),
				attr = $header.attr('data-attribute');

			// Is this the selected sort attribute?
			if (attr == selectedSortAttr)
			{
				this.$selectedSortHeader = $header;
				var selectedSortDir = this.elementIndex.getSelectedSortDirection();

				$header
					.addClass('ordered '+selectedSortDir)
					.click($.proxy(this, '_handleSelectedSortHeaderClick'));
			}
			else
			{
				// Is this attribute sortable?
				var $sortAttribute = this.elementIndex.getSortAttributeOption(attr);

				if ($sortAttribute.length)
				{
					$header
						.addClass('orderable')
						.click($.proxy(this, '_handleUnselectedSortHeaderClick'));
				}
			}
		}
	},

	isVerticalList: function()
	{
		return true;
	},

	getTotalVisible: function()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._totalVisiblePostStructureTableDraggee;
		}
		else
		{
			return this._totalVisible;
		}
	},

	setTotalVisible: function(totalVisible)
	{
		if (this._isStructureTableDraggingLastElements())
		{
			this._totalVisiblePostStructureTableDraggee = totalVisible;
		}
		else
		{
			this._totalVisible = totalVisible;
		}
	},

	getMorePending: function()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._morePendingPostStructureTableDraggee;
		}
		else
		{
			return this._morePending;
		}
	},

	setMorePending: function(morePending)
	{
		if (this._isStructureTableDraggingLastElements())
		{
			this._morePendingPostStructureTableDraggee = morePending;
		}
		else
		{
			this._morePending = this._morePendingPostStructureTableDraggee = morePending;
		}
	},

	getLoadMoreParams: function()
	{
		var params = this.base();

		// If we are dragging the last elements on the page,
		// tell the controller to only load elements positioned after the draggee.
		if (this._isStructureTableDraggingLastElements())
		{
			params.criteria.positionedAfter = this.structureTableSort.$targetItem.data('id');
		}

		return params;
	},

	appendElements: function($newElements)
	{
		this.base($newElements);

		if (this.structureTableSort)
		{
			this.structureTableSort.addItems($newElements);
		}

		Craft.cp.updateResponsiveTables();
	},

	createElementEditor: function($element)
	{
		new Craft.ElementEditor($element, {
			params: {
				includeTableAttributesForSource: this.elementIndex.sourceKey
			},
			onSaveElement: $.proxy(function(response) {
				if (response.tableAttributes) {
					this._updateTableAttributes($element, response.tableAttributes);
				}
			}, this)
		});
	},

	destroy: function()
	{
		if (this.$table)
		{
			// Remove the soon-to-be-wiped-out table from the list of collapsible tables
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		this.base();
	},

	_collapseElement: function($toggle, force)
	{
		if (!force && !$toggle.hasClass('expanded'))
		{
			return false;
		}

		$toggle.removeClass('expanded');

		// Find and remove the descendant rows
		var $row = $toggle.parent().parent(),
			id = $row.data('id'),
			level = $row.data('level'),
			$nextRow = $row.next();

		while ($nextRow.length)
		{
			if (!Garnish.hasAttr($nextRow, 'data-spinnerrow'))
			{
				if ($nextRow.data('level') <= level)
				{
					break;
				}

				if (this.elementSelect)
				{
					this.elementSelect.removeItems($nextRow);
				}

				if (this.structureTableSort)
				{
					this.structureTableSort.removeItems($nextRow);
				}

				this._totalVisible--;
			}

			var $nextNextRow = $nextRow.next();
			$nextRow.remove();
			$nextRow = $nextNextRow;
		}

		// Remember that this row should be collapsed
		if (!this.elementIndex.instanceState.collapsedElementIds)
		{
			this.elementIndex.instanceState.collapsedElementIds = [];
		}

		this.elementIndex.instanceState.collapsedElementIds.push(id);
		this.elementIndex.setInstanceState('collapsedElementIds', this.elementIndex.instanceState.collapsedElementIds);

		// Bottom of the index might be viewable now
		this.maybeLoadMore();
	},

	_expandElement: function($toggle, force)
	{
		if (!force && $toggle.hasClass('expanded'))
		{
			return false;
		}

		$toggle.addClass('expanded');

		// Remove this element from our list of collapsed elements
		if (this.elementIndex.instanceState.collapsedElementIds)
		{
			var $row = $toggle.parent().parent(),
				id = $row.data('id'),
				index = $.inArray(id, this.elementIndex.instanceState.collapsedElementIds);

			if (index != -1)
			{
				this.elementIndex.instanceState.collapsedElementIds.splice(index, 1);
				this.elementIndex.setInstanceState('collapsedElementIds', this.elementIndex.instanceState.collapsedElementIds);

				// Add a temporary row
				var $spinnerRow = this._createSpinnerRowAfter($row);

				// Load the nested elements
				var params = $.extend(true, {}, this.settings.params);
				params.criteria.descendantOf = id;

				Craft.postActionRequest('elementIndex/getMoreElements', params, $.proxy(function(response, textStatus)
				{
					// Do we even care about this anymore?
					if (!$spinnerRow.parent().length)
					{
						return;
					}

					if (textStatus == 'success')
					{
						var $newElements = $(response.html);

						// Are there more descendants we didn't get in this batch?
						var totalVisible = (this._totalVisible + $newElements.length),
							morePending = (this.settings.batchSize && $newElements.length == this.settings.batchSize);

						if (morePending)
						{
							// Remove all the elements after it
							var $nextRows = $spinnerRow.nextAll();

							if (this.elementSelect)
							{
								this.elementSelect.removeItems($nextRows);
							}

							if (this.structureTableSort)
							{
								this.structureTableSort.removeItems($nextRows);
							}

							$nextRows.remove();
							totalVisible -= $nextRows.length;
						}
						else
						{
							// Maintain the current 'more' status
							morePending = this._morePending;
						}

						$spinnerRow.replaceWith($newElements);

						if (this.elementIndex.actions || this.settings.selectable)
						{
							this.elementSelect.addItems($newElements.filter(':not(.disabled)'));
							this.elementIndex.updateActionTriggers();
						}

						if (this.structureTableSort)
						{
							this.structureTableSort.addItems($newElements);
						}

						Craft.appendHeadHtml(response.headHtml);
						Craft.appendFootHtml(response.footHtml);
						Craft.cp.updateResponsiveTables();

						this.setTotalVisible(totalVisible);
						this.setMorePending(morePending);

						// Is there room to load more right now?
						this.maybeLoadMore();
					}

				}, this));
			}
		}
	},

	_createSpinnerRowAfter: function($row)
	{
		return $(
			'<tr data-spinnerrow>' +
				'<td class="centeralign" colspan="'+$row.children().length+'">' +
					'<div class="spinner"/>' +
				'</td>' +
			'</tr>'
		).insertAfter($row);
	},

	_isStructureTableDraggingLastElements: function()
	{
		return (
			this.structureTableSort &&
			this.structureTableSort.dragging &&
			this.structureTableSort.draggingLastElements
		);
	},

	_handleSelectedSortHeaderClick: function(ev)
	{
		var $header = $(ev.currentTarget);

		if ($header.hasClass('loading'))
		{
			return;
		}

		// Reverse the sort direction
		var selectedSortDir = this.elementIndex.getSelectedSortDirection(),
			newSortDir = (selectedSortDir == 'asc' ? 'desc' : 'asc');

		this.elementIndex.setSortDirection(newSortDir);
		this._handleSortHeaderClick(ev, $header);
	},

	_handleUnselectedSortHeaderClick: function(ev)
	{
		var $header = $(ev.currentTarget);

		if ($header.hasClass('loading'))
		{
			return;
		}

		var attr = $header.attr('data-attribute');

		this.elementIndex.setSortAttribute(attr);
		this._handleSortHeaderClick(ev, $header);
	},

	_handleSortHeaderClick: function(ev, $header)
	{
		if (this.$selectedSortHeader)
		{
			this.$selectedSortHeader.removeClass('ordered asc desc');
		}

		$header.removeClass('orderable').addClass('ordered loading');
		this.elementIndex.storeSortAttributeAndDirection();
		this.elementIndex.updateElements();

		// No need for two spinners
		this.elementIndex.setIndexAvailable();
	},

	_updateTableAttributes: function($element, tableAttributes)
	{
		var $tr = $element.closest('tr');

		for (var attr in tableAttributes)
		{
			$tr.children('td[data-attr="'+attr+'"]:first').html(tableAttributes[attr]);
		}
	}
});
