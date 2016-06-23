Craft.Grid = Garnish.Base.extend(
{
	$container: null,

	$items: null,
	items: null,
	totalCols: null,
	colPctWidth: null,
	sizeUnit: null,

	possibleItemColspans: null,
	possibleItemPositionsByColspan: null,

	itemPositions: null,
	itemColspansByPosition: null,

	layouts: null,
	layout: null,
	itemHeights: null,
	leftPadding: null,

	_refreshCols: {},
	_setItems: null,

	init: function(container, settings)
	{
		this.$container = $(container);

		// Is this already a grid?
		if (this.$container.data('grid'))
		{
			Garnish.log('Double-instantiating a grid on an element');
			this.$container.data('grid').destroy();
		}

		this.$container.data('grid', this);

		this.setSettings(settings, Craft.Grid.defaults);

		if (this.settings.mode == 'pct')
		{
			this.sizeUnit = '%';
		}
		else
		{
			this.sizeUnit = 'px';
		}

		// Set the refreshCols() proxy that container resizes will trigger
		this.handleContainerHeightProxy = $.proxy(function() {
			this.refreshCols(false, true);
		}, this);

		this.$items = this.$container.children(this.settings.itemSelector);
		this.setItems();
		this.refreshCols(true, false);

		Garnish.$doc.ready($.proxy(function() {
			this.refreshCols(false, false);
		}, this));
	},

	addItems: function(items)
	{
		this.$items = $().add(this.$items.add(items));
		this.setItems();
		this.refreshCols(true, true);
		$(items).velocity('finish');
	},

	removeItems: function(items)
	{
		this.$items = $().add(this.$items.not(items));
		this.setItems();
		this.refreshCols(true, true);
	},

	resetItemOrder: function()
	{
		this.$items = $().add(this.$items);
		this.setItems();
		this.refreshCols(true, true);
	},

	setItems: function()
	{
		this._setItems = {};

		this.items = [];

		for (this._setItems.i = 0; this._setItems.i < this.$items.length; this._setItems.i++)
		{
			this.items.push($(this.$items[this._setItems.i]));
		}

		delete this._setItems;
	},

	refreshCols: function(force, animate)
	{
		if (!this.items.length)
		{
			return;
		}

		this._refreshCols = {};

		// Check to see if the grid is actually visible
		this._refreshCols.oldHeight = this.$container[0].style.height;
		this.$container[0].style.height = 1;
		this._refreshCols.scrollHeight = this.$container[0].scrollHeight;
		this.$container[0].style.height = this._refreshCols.oldHeight;

		if (this._refreshCols.scrollHeight == 0)
		{
			delete this._refreshCols;
			return;
		}

		if (this.settings.cols)
		{
			this._refreshCols.totalCols = this.settings.cols;
		}
		else
		{
			this._refreshCols.totalCols = Math.floor(this.$container.width() / this.settings.minColWidth);

			if (this.settings.maxCols && this._refreshCols.totalCols > this.settings.maxCols)
			{
				this._refreshCols.totalCols = this.settings.maxCols;
			}
		}

		if (this._refreshCols.totalCols == 0)
		{
			this._refreshCols.totalCols = 1;
		}

		// Same number of columns as before?
		if (force !== true && this.totalCols === this._refreshCols.totalCols)
		{
			delete this._refreshCols;
			return;
		}

		this.totalCols = this._refreshCols.totalCols;

		// Temporarily stop listening to container resizes
		this.removeListener(this.$container, 'resize');

		if (this.settings.fillMode == 'grid')
		{
			this._refreshCols.itemIndex = 0;

			while (this._refreshCols.itemIndex < this.items.length)
			{
				// Append the next X items and figure out which one is the tallest
				this._refreshCols.tallestItemHeight = -1;
				this._refreshCols.colIndex = 0;

				for (this._refreshCols.i = this._refreshCols.itemIndex; (this._refreshCols.i < this._refreshCols.itemIndex + this.totalCols && this._refreshCols.i < this.items.length); this._refreshCols.i++)
				{
					this._refreshCols.itemHeight = this.items[this._refreshCols.i].height('auto').height();

					if (this._refreshCols.itemHeight > this._refreshCols.tallestItemHeight)
					{
						this._refreshCols.tallestItemHeight = this._refreshCols.itemHeight;
					}

					this._refreshCols.colIndex++;
				}

				if (this.settings.snapToGrid)
				{
					this._refreshCols.remainder = this._refreshCols.tallestItemHeight % this.settings.snapToGrid;

					if (this._refreshCols.remainder)
					{
						this._refreshCols.tallestItemHeight += this.settings.snapToGrid - this._refreshCols.remainder;
					}
				}

				// Now set their heights to the tallest one
				for (this._refreshCols.i = this._refreshCols.itemIndex; (this._refreshCols.i < this._refreshCols.itemIndex + this.totalCols && this._refreshCols.i < this.items.length); this._refreshCols.i++)
				{
					this.items[this._refreshCols.i].height(this._refreshCols.tallestItemHeight);
				}

				// set the this._refreshCols.itemIndex pointer to the next one up
				this._refreshCols.itemIndex += this.totalCols;
			}
		}
		else
		{
			this.removeListener(this.$items, 'resize');

			// If there's only one column, sneak out early
			if (this.totalCols == 1)
			{
				this.$container.height('auto');
				this.$items
					.show()
					.css({
						position: 'relative',
						width: 'auto',
						top: 0
					})
					.css(Craft.left, 0);
			}
			else
			{
				this.$items.css('position', 'absolute');

				if (this.settings.mode == 'pct')
				{
					this.colPctWidth = (100 / this.totalCols);
				}

				// The setup

				this.layouts = [];

				this.itemPositions = [];
				this.itemColspansByPosition = [];

				// Figure out all of the possible colspans for each item,
				// as well as all the possible positions for each item at each of its colspans

				this.possibleItemColspans = [];
				this.possibleItemPositionsByColspan = [];
				this.itemHeightsByColspan = [];

				for (this._refreshCols.item = 0; this._refreshCols.item < this.items.length; this._refreshCols.item++)
				{
					this.possibleItemColspans[this._refreshCols.item] = [];
					this.possibleItemPositionsByColspan[this._refreshCols.item] = {};
					this.itemHeightsByColspan[this._refreshCols.item] = {};

					this._refreshCols.$item = this.items[this._refreshCols.item].show();
					this._refreshCols.positionRight = (this._refreshCols.$item.data('position') == 'right');
					this._refreshCols.positionLeft = (this._refreshCols.$item.data('position') == 'left');
					this._refreshCols.minColspan = (this._refreshCols.$item.data('colspan') ? this._refreshCols.$item.data('colspan') : (this._refreshCols.$item.data('min-colspan') ? this._refreshCols.$item.data('min-colspan') : 1));
					this._refreshCols.maxColspan = (this._refreshCols.$item.data('colspan') ? this._refreshCols.$item.data('colspan') : (this._refreshCols.$item.data('max-colspan') ? this._refreshCols.$item.data('max-colspan') : this.totalCols));

					if (this._refreshCols.minColspan > this.totalCols) this._refreshCols.minColspan = this.totalCols;
					if (this._refreshCols.maxColspan > this.totalCols) this._refreshCols.maxColspan = this.totalCols;

					for (this._refreshCols.colspan = this._refreshCols.minColspan; this._refreshCols.colspan <= this._refreshCols.maxColspan; this._refreshCols.colspan++)
					{
						// Get the height for this colspan
						this._refreshCols.$item.css('width', this.getItemWidth(this._refreshCols.colspan) + this.sizeUnit);
						this.itemHeightsByColspan[this._refreshCols.item][this._refreshCols.colspan] = this._refreshCols.$item.outerHeight();

						this.possibleItemColspans[this._refreshCols.item].push(this._refreshCols.colspan);
						this.possibleItemPositionsByColspan[this._refreshCols.item][this._refreshCols.colspan] = [];

						if (this._refreshCols.positionLeft)
						{
							this._refreshCols.minPosition = 0;
							this._refreshCols.maxPosition = 0;
						}
						else if (this._refreshCols.positionRight)
						{
							this._refreshCols.minPosition = this.totalCols - this._refreshCols.colspan;
							this._refreshCols.maxPosition = this._refreshCols.minPosition;
						}
						else
						{
							this._refreshCols.minPosition = 0;
							this._refreshCols.maxPosition = this.totalCols - this._refreshCols.colspan;
						}

						for (this._refreshCols.position = this._refreshCols.minPosition; this._refreshCols.position <= this._refreshCols.maxPosition; this._refreshCols.position++)
						{
							this.possibleItemPositionsByColspan[this._refreshCols.item][this._refreshCols.colspan].push(this._refreshCols.position);
						}
					}
				}

				// Find all the possible layouts

				this._refreshCols.colHeights = [];

				for (this._refreshCols.i = 0; this._refreshCols.i < this.totalCols; this._refreshCols.i++)
				{
					this._refreshCols.colHeights.push(0);
				}

				this.createLayouts(0, [], [], this._refreshCols.colHeights, 0);

				// Now find the layout that looks the best.

				// First find the layouts with the highest number of used columns
				this._refreshCols.layoutTotalCols = [];

				for (this._refreshCols.i = 0; this._refreshCols.i < this.layouts.length; this._refreshCols.i++)
				{
					this._refreshCols.layoutTotalCols[this._refreshCols.i] = 0;

					for (this._refreshCols.j = 0; this._refreshCols.j < this.totalCols; this._refreshCols.j++)
					{
						if (this.layouts[this._refreshCols.i].colHeights[this._refreshCols.j])
						{
							this._refreshCols.layoutTotalCols[this._refreshCols.i]++;
						}
					}
				}

				this._refreshCols.highestTotalCols = Math.max.apply(null, this._refreshCols.layoutTotalCols);

				// Filter out the ones that aren't using as many columns as they could be
				for (this._refreshCols.i = this.layouts.length - 1; this._refreshCols.i >= 0; this._refreshCols.i--)
				{
					if (this._refreshCols.layoutTotalCols[this._refreshCols.i] != this._refreshCols.highestTotalCols)
					{
						this.layouts.splice(this._refreshCols.i, 1);
					}
				}

				// Find the layout(s) with the least overall height
				this._refreshCols.layoutHeights = [];

				for (this._refreshCols.i = 0; this._refreshCols.i < this.layouts.length; this._refreshCols.i++)
				{
					this._refreshCols.layoutHeights.push(Math.max.apply(null, this.layouts[this._refreshCols.i].colHeights));
				}

				this._refreshCols.shortestHeight = Math.min.apply(null, this._refreshCols.layoutHeights);
				this._refreshCols.shortestLayouts = [];
				this._refreshCols.emptySpaces = [];

				for (this._refreshCols.i = 0; this._refreshCols.i < this._refreshCols.layoutHeights.length; this._refreshCols.i++)
				{
					if (this._refreshCols.layoutHeights[this._refreshCols.i] == this._refreshCols.shortestHeight)
					{
						this._refreshCols.shortestLayouts.push(this.layouts[this._refreshCols.i]);

						// Now get its total empty space, including any trailing empty space
						this._refreshCols.emptySpace = this.layouts[this._refreshCols.i].emptySpace;

						for (this._refreshCols.j = 0; this._refreshCols.j < this.totalCols; this._refreshCols.j++)
						{
							this._refreshCols.emptySpace += (this._refreshCols.shortestHeight - this.layouts[this._refreshCols.i].colHeights[this._refreshCols.j]);
						}

						this._refreshCols.emptySpaces.push(this._refreshCols.emptySpace);
					}
				}

				// And the layout with the least empty space is...
				this.layout = this._refreshCols.shortestLayouts[$.inArray(Math.min.apply(null, this._refreshCols.emptySpaces), this._refreshCols.emptySpaces)];

				// Figure out the left padding based on the number of empty columns
				this._refreshCols.totalEmptyCols = 0;

				for (this._refreshCols.i = this.layout.colHeights.length-1; this._refreshCols.i >= 0; this._refreshCols.i--)
				{
					if (this.layout.colHeights[this._refreshCols.i] == 0)
					{
						this._refreshCols.totalEmptyCols++;
					}
					else
					{
						break;
					}
				}

				this.leftPadding = this.getItemWidth(this._refreshCols.totalEmptyCols) / 2;

				if (this.settings.mode == 'fixed')
				{
					this.leftPadding += (this.$container.width() - (this.settings.minColWidth * this.totalCols)) / 2;
				}

				// Set the item widths and left positions
				for (this._refreshCols.i = 0; this._refreshCols.i < this.items.length; this._refreshCols.i++)
				{
					this._refreshCols.css = {
						width: this.getItemWidth(this.layout.colspans[this._refreshCols.i]) + this.sizeUnit
					};
					this._refreshCols.css[Craft.left] = this.leftPadding + this.getItemWidth(this.layout.positions[this._refreshCols.i]) + this.sizeUnit;

					if (animate)
					{
						this.items[this._refreshCols.i].velocity(this._refreshCols.css, {
							queue: false
						});
					}
					else
					{
						this.items[this._refreshCols.i].velocity('finish').css(this._refreshCols.css);
					}
				}

				// If every item is at position 0, then let them lay out au naturel
				if (this.isSimpleLayout())
				{

					this.$container.height('auto');
					this.$items.css('position', 'relative');
				}
				else
				{
					this.$items.css('position', 'absolute');

					// Now position the items
					this.positionItems(animate);

					// Update the positions as the items' heigthts change
					this.addListener(this.$items, 'resize', 'onItemResize');
				}
			}
		}

		this.onRefreshCols();

		delete this._refreshCols;

		// Resume container resize listening
		this.addListener(this.$container, 'resize', this.handleContainerHeightProxy);
	},

	getItemWidth: function(colspan)
	{
		if (this.settings.mode == 'pct')
		{
			return (this.colPctWidth * colspan);
		}
		else
		{
			return (this.settings.minColWidth * colspan);
		}
	},

	createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace)
	{
		(new Craft.Grid.LayoutGenerator(this)).createLayouts(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace);
	},

	isSimpleLayout: function()
	{
		this.isSimpleLayout._ = {};

		for (this.isSimpleLayout._.i = 0; this.isSimpleLayout._.i < this.layout.positions.length; this.isSimpleLayout._.i++)
		{
			if (this.layout.positions[this.isSimpleLayout._.i] != 0)
			{
				delete this.isSimpleLayout._;
				return false;
			}
		}

		delete this.isSimpleLayout._;
		return true;
	},

	positionItems: function(animate)
	{
		this.positionItems._ = {};

		this.positionItems._.colHeights = [];

		for (this.positionItems._.i = 0; this.positionItems._.i < this.totalCols; this.positionItems._.i++)
		{
			this.positionItems._.colHeights.push(0);
		}

		for (this.positionItems._.i = 0; this.positionItems._.i < this.items.length; this.positionItems._.i++)
		{
			this.positionItems._.endingCol = this.layout.positions[this.positionItems._.i] + this.layout.colspans[this.positionItems._.i] - 1;
			this.positionItems._.affectedColHeights = [];

			for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++)
			{
				this.positionItems._.affectedColHeights.push(this.positionItems._.colHeights[this.positionItems._.col]);
			}

			this.positionItems._.top = Math.max.apply(null, this.positionItems._.affectedColHeights);

			if (animate)
			{
				this.items[this.positionItems._.i].velocity({ top: this.positionItems._.top }, {
					queue: false
				});
			}
			else
			{
				this.items[this.positionItems._.i].velocity('finish').css('top', this.positionItems._.top);
			}

			// Now add the new heights to those columns
			for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++)
			{
				this.positionItems._.colHeights[this.positionItems._.col] = this.positionItems._.top + this.itemHeightsByColspan[this.positionItems._.i][this.layout.colspans[this.positionItems._.i]];
			}
		}

		// Set the container height
		this.$container.height(Math.max.apply(null, this.positionItems._.colHeights));

		delete this.positionItems._;
	},

	onItemResize: function(ev)
	{
		this.onItemResize._ = {};

		// Prevent this from bubbling up to the container, which has its own resize listener
		ev.stopPropagation();

		this.onItemResize._.item = $.inArray(ev.currentTarget, this.$items);

		if (this.onItemResize._.item != -1)
		{
			// Update the height and reposition the items
			this.onItemResize._.newHeight = this.items[this.onItemResize._.item].outerHeight();

			if (this.onItemResize._.newHeight != this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]])
			{
				this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]] = this.onItemResize._.newHeight;
				this.positionItems(false);
			}
		}

		delete this.onItemResize._;
	},

	onRefreshCols: function()
	{
		this.trigger('refreshCols');
		this.settings.onRefreshCols();
	}
},
{
	defaults: {
		itemSelector: '.item',
		cols: null,
		maxCols: null,
		minColWidth: 320,
		mode: 'pct',
		fillMode: 'top',
		colClass: 'col',
		snapToGrid: null,

		onRefreshCols: $.noop
	}
});


Craft.Grid.LayoutGenerator = Garnish.Base.extend(
{
	grid: null,
	_: null,

	init: function(grid)
	{
		this.grid = grid;
	},

	createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace)
	{
		this._ = {};

		// Loop through all possible colspans
		for (this._.c = 0; this._.c < this.grid.possibleItemColspans[item].length; this._.c++)
		{
			this._.colspan = this.grid.possibleItemColspans[item][this._.c];

			// Loop through all the possible positions for this colspan,
			// and find the one that is closest to the top

			this._.tallestColHeightsByPosition = [];

			for (this._.p = 0; this._.p < this.grid.possibleItemPositionsByColspan[item][this._.colspan].length; this._.p++)
			{
				this._.position = this.grid.possibleItemPositionsByColspan[item][this._.colspan][this._.p];

				this._.colHeightsForPosition = [];
				this._.endingCol = this._.position + this._.colspan - 1;

				for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++)
				{
					this._.colHeightsForPosition.push(prevColHeights[this._.col]);
				}

				this._.tallestColHeightsByPosition[this._.p] = Math.max.apply(null, this._.colHeightsForPosition);
			}

			// And the shortest position for this colspan is...
			this._.p = $.inArray(Math.min.apply(null, this._.tallestColHeightsByPosition), this._.tallestColHeightsByPosition);
			this._.position = this.grid.possibleItemPositionsByColspan[item][this._.colspan][this._.p];

			// Now log the colspan/position placement
			this._.positions = prevPositions.slice(0);
			this._.colspans = prevColspans.slice(0);
			this._.colHeights = prevColHeights.slice(0);
			this._.emptySpace = prevEmptySpace;

			this._.positions.push(this._.position);
			this._.colspans.push(this._.colspan);

			// Add the new heights to those columns
			this._.tallestColHeight = this._.tallestColHeightsByPosition[this._.p];
			this._.endingCol = this._.position + this._.colspan - 1;

			for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++)
			{
				this._.emptySpace += this._.tallestColHeight - this._.colHeights[this._.col];
				this._.colHeights[this._.col] = this._.tallestColHeight + this.grid.itemHeightsByColspan[item][this._.colspan];
			}

			// If this is the last item, create the layout
			if (item == this.grid.items.length-1)
			{
				this.grid.layouts.push({
					positions:  this._.positions,
					colspans:   this._.colspans,
					colHeights: this._.colHeights,
					emptySpace: this._.emptySpace
				});
			}
			else
			{
				// Dive deeper
				this.grid.createLayouts(item+1, this._.positions, this._.colspans, this._.colHeights, this._.emptySpace);
			}
		}

		delete this._;
	}

});