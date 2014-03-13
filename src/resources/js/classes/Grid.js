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

	init: function(container, settings)
	{
		this.$container = $(container);

		this.setSettings(settings, Craft.Grid.defaults);

		if (this.settings.mode == 'pct')
		{
			this.sizeUnit = '%';
		}
		else
		{
			this.sizeUnit = 'px';
		}

		this.$items = this.$container.children(this.settings.itemSelector);
		this.setItems();
		this.refreshCols();

		// Adjust them when the container is resized
		this.addListener(this.$container, 'resize', 'refreshCols');

		// Trigger a window resize event in case anything needs to adjust itself, now that the items are layed out.
		Garnish.$win.trigger('resize');
	},

	addItems: function(items)
	{
		this.$items = $().add(this.$items.add(items));
		this.setItems();
		this.refreshCols();
	},

	removeItems: function(items)
	{
		this.$items = $().add(this.$items.not(items));
		this.setItems();
		this.refreshCols();
	},

	setItems: function()
	{
		this.items = [];

		for (var i = 0; i < this.$items.length; i++)
		{
			this.items.push($(this.$items[i]));
		}
	},

	refreshCols: function()
	{
		if (!this.items.length)
		{
			return;
		}

		if (this.settings.cols)
		{
			this.totalCols = this.settings.cols;
		}
		else
		{
			this.totalCols = Math.floor(this.$container.width() / this.settings.minColWidth);
		}

		if (this.totalCols == 0)
		{
			this.totalCols = 1;
		}

		if (this.settings.fillMode == 'grid')
		{
			var itemIndex = 0;

			while (itemIndex < this.items.length)
			{
				// Append the next X items and figure out which one is the tallest
				var tallestItemHeight = -1,
					colIndex = 0;

				for (var i = itemIndex; (i < itemIndex + this.totalCols && i < this.items.length); i++)
				{
					var itemHeight = this.items[i].height('auto').height();
					if (itemHeight > tallestItemHeight)
					{
						tallestItemHeight = itemHeight;
					}

					colIndex++;
				}

				if (this.settings.snapToGrid)
				{
					var remainder = tallestItemHeight % this.settings.snapToGrid;

					if (remainder)
					{
						tallestItemHeight += this.settings.snapToGrid - remainder;
					}
				}

				// Now set their heights to the tallest one
				for (var i = itemIndex; (i < itemIndex + this.totalCols && i < this.items.length); i++)
				{
					this.items[i].height(tallestItemHeight);
				}

				// set the itemIndex pointer to the next one up
				itemIndex += this.totalCols;
			}
		}
		else
		{
			this.removeListener(this.$items, 'resize');

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

			for (var item = 0; item < this.items.length; item++)
			{
				this.possibleItemColspans[item] = [];
				this.possibleItemPositionsByColspan[item] = {};
				this.itemHeightsByColspan[item] = {};

				var $item = this.items[item].show(),
					positionRight = ($item.data('position') == 'right'),
					positionLeft = ($item.data('position') == 'left'),
					minColspan = ($item.data('colspan') ? $item.data('colspan') : ($item.data('min-colspan') ? $item.data('min-colspan') : 1)),
					maxColspan = ($item.data('colspan') ? $item.data('colspan') : ($item.data('max-colspan') ? $item.data('max-colspan') : this.totalCols));

				if (minColspan > this.totalCols) minColspan = this.totalCols;
				if (maxColspan > this.totalCols) maxColspan = this.totalCols;

				for (var colspan = minColspan; colspan <= maxColspan; colspan++)
				{
					// Get the height for this colspan
					$item.css('width', this.getItemWidth(colspan) + this.sizeUnit);
					this.itemHeightsByColspan[item][colspan] = $item.outerHeight();

					this.possibleItemColspans[item].push(colspan);
					this.possibleItemPositionsByColspan[item][colspan] = [];

					if (positionLeft)
					{
						var minPosition = 0,
							maxPosition = 0;
					}
					else if (positionRight)
					{
						var minPosition = this.totalCols - colspan,
							maxPosition = minPosition;
					}
					else
					{
						var minPosition = 0,
							maxPosition = this.totalCols - colspan;
					}

					for (var position = minPosition; position <= maxPosition; position++)
					{
						this.possibleItemPositionsByColspan[item][colspan].push(position);
					}
				}
			}

			// Find all the possible layouts

			var colHeights = [];

			for (var i = 0; i < this.totalCols; i++)
			{
				colHeights.push(0);
			}

			this.createLayouts(0, [], [], colHeights, 0);

			// Now find the layout that looks the best.
			// We'll determine that by first finding all of the layouts that have the lowest overall height,
			// and of those, find the one with the least empty space

			// Find the layout(s) with the least overall height
			var layoutHeights = [];

			for (var i = 0; i < this.layouts.length; i++)
			{
				layoutHeights.push(Math.max.apply(null, this.layouts[i].colHeights));
			}

			var shortestHeight = Math.min.apply(null, layoutHeights),
				shortestLayouts = [],
				emptySpaces = [];

			for (var i = 0; i < layoutHeights.length; i++)
			{
				if (layoutHeights[i] == shortestHeight)
				{
					shortestLayouts.push(this.layouts[i]);

					// Now get its total empty space, including any trailing empty space
					var emptySpace = this.layouts[i].emptySpace;

					for (var j = 0; j < this.totalCols; j++)
					{
						emptySpace += (shortestHeight - this.layouts[i].colHeights[j]);
					}

					emptySpaces.push(emptySpace);
				}
			}

			// And the layout with the least empty space is...
			this.layout = shortestLayouts[$.inArray(Math.min.apply(null, emptySpaces), emptySpaces)];

			// Figure out the left padding based on the number of empty columns
			var totalEmptyCols = 0;

			for (var i = this.layout.colHeights.length-1; i >= 0; i--)
			{
				if (this.layout.colHeights[i] == 0)
				{
					totalEmptyCols++;
				}
				else
				{
					break;
				}
			}

			this.leftPadding = this.getItemWidth(totalEmptyCols) / 2;

			if (this.settings.mode == 'fixed')
			{
				this.leftPadding += (this.$container.width() - (this.settings.minColWidth * this.totalCols)) / 2;
			}

			// Now position the items
			this.positionItems();

			// Update the positions as the items' heigthts change
			this.addListener(this.$items, 'resize', 'onItemResize');
		}
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
		// Loop through all possible colspans
		for (var c = 0; c < this.possibleItemColspans[item].length; c++)
		{
			var colspan = this.possibleItemColspans[item][c];

			// Loop through all the possible positions for this colspan,
			// and find the one that is closest to the top

			var tallestColHeightsByPosition = [];

			for (var p = 0; p < this.possibleItemPositionsByColspan[item][colspan].length; p++)
			{
				var position = this.possibleItemPositionsByColspan[item][colspan][p];

				var colHeightsForPosition = [],
					endingCol = position + colspan - 1;

				for (var col = position; col <= endingCol; col++)
				{
					colHeightsForPosition.push(prevColHeights[col]);
				}

				tallestColHeightsByPosition[p] = Math.max.apply(null, colHeightsForPosition);
			}

			// And the shortest position for this colspan is...
			var p = $.inArray(Math.min.apply(null, tallestColHeightsByPosition), tallestColHeightsByPosition),
				position = this.possibleItemPositionsByColspan[item][colspan][p];

			// Now log the colspan/position placement
			var positions = prevPositions.slice(0),
				colspans = prevColspans.slice(0),
				colHeights = prevColHeights.slice(0),
				emptySpace = prevEmptySpace;

			positions.push(position);
			colspans.push(colspan);

			// Add the new heights to those columns
			var tallestColHeight = tallestColHeightsByPosition[p],
				endingCol = position + colspan - 1;

			for (var col = position; col <= endingCol; col++)
			{
				emptySpace += tallestColHeight - colHeights[col];
				colHeights[col] = tallestColHeight + this.itemHeightsByColspan[item][colspan];
			}

			// If this is the last item, create the layout
			if (item == this.items.length-1)
			{
				this.layouts.push({
					positions: positions,
					colspans: colspans,
					colHeights: colHeights,
					emptySpace: emptySpace
				});
			}
			else
			{
				// Dive deeper
				this.createLayouts(item+1, positions, colspans, colHeights, emptySpace);
			}
		}
	},

	positionItems: function()
	{
		var colHeights = [];

		for (var i = 0; i < this.totalCols; i++)
		{
			colHeights.push(0);
		}

		for (var i = 0; i < this.items.length; i++)
		{
			var endingCol = this.layout.positions[i] + this.layout.colspans[i] - 1,
				affectedColHeights = [];

			for (var col = this.layout.positions[i]; col <= endingCol; col++)
			{
				affectedColHeights.push(colHeights[col]);
			}

			var top = Math.max.apply(null, affectedColHeights);

			var css = {
				top: top,
				width: this.getItemWidth(this.layout.colspans[i]) + this.sizeUnit
			};
			css[Craft.left] = this.leftPadding + this.getItemWidth(this.layout.positions[i]) + this.sizeUnit;

			this.items[i].css(css);

			// Now add the new heights to those columns
			for (var col = this.layout.positions[i]; col <= endingCol; col++)
			{
				colHeights[col] = top + this.itemHeightsByColspan[i][this.layout.colspans[i]];
			}
		}

		// Set the container height
		this.$container.css({
			height: Math.max.apply(null, colHeights)
		});
	},

	onItemResize: function(ev)
	{
		// Prevent this from bubbling up to the container, which has its own resize listener
		ev.stopPropagation();

		var item = $.inArray(ev.currentTarget, this.$items);

		if (item != -1)
		{
			// Update the height and reposition the items
			var newHeight = this.items[item].outerHeight();

			if (newHeight != this.itemHeightsByColspan[item][this.layout.colspans[item]])
			{
				this.itemHeightsByColspan[item][this.layout.colspans[item]] = newHeight;
				this.positionItems();
			}
		}
	}
},
{
	defaults: {
		itemSelector: '.item',
		cols: null,
		minColWidth: 320,
		mode: 'pct',
		fillMode: 'top',
		colClass: 'col',
		snapToGrid: null
	}
});
