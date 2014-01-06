Craft.Grid = Garnish.Base.extend({

	$container: null,

	$items: null,
	items: null,
	totalCols: null,
	colPctWidth: null,

	layouts: null,
	itemMinColspans: null,
	itemMaxColspans: null,
	itemHeights: null,

	init: function(container, settings)
	{
		this.$container = $(container);

		this.setSettings(settings, Craft.Grid.defaults);

		// Attribute setting overrides
		if (this.$container.data('item-selector'))     this.settings.itemSelector = this.$container.data('item-selector');
		if (this.$container.data('min-col-width'))     this.settings.minColWidth = parseInt(this.$container.data('min-col-width'));
		if (this.$container.data('percentage-widths')) this.settings.percentageWidths = !!this.$container.data('percentage-widths');
		if (this.$container.data('fill-mode'))         this.settings.fillMode = this.$container.data('fill-mode');
		if (this.$container.data('col-class'))         this.settings.colClass = this.$container.data('col-class');
		if (this.$container.data('snap-to-grid'))      this.settings.snapToGrid = !!this.$container.data('snap-to-grid');

		this.$items = this.$container.children(this.settings.itemSelector);
		this.setItems();

		this.setCols();

		// Adjust them when the window resizes
		this.addListener(Garnish.$win, 'resize', 'setCols');
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

	setCols: function()
	{
		var totalCols = Math.floor(this.$container.width() / this.settings.minColWidth);

		if (totalCols == 0)
		{
			totalCols = 1;
		}

		if (totalCols !== this.totalCols)
		{
			this.totalCols = totalCols;
			this.refreshCols();
			return true;
		}

		return false;
	},

	refreshCols: function()
	{
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
			if (this.settings.percentageWidths)
			{
				this.colPctWidth = (100 / this.totalCols);
			}

			// The setup

			this.layouts = [];
			this.itemMinColspans = [];
			this.itemMaxColspans = [];
			this.itemHeights = [];

			for (var i = 0; i < this.items.length; i++)
			{
				var $item = this.items[i].show();

				this.itemMinColspans[i] = ($item.data('colspan') ? $item.data('colspan') : ($item.data('min-colspan') ? $item.data('min-colspan') : 1)),
				this.itemMaxColspans[i] = ($item.data('colspan') ? $item.data('colspan') : ($item.data('max-colspan') ? $item.data('max-colspan') : this.totalCols));

				if (this.itemMinColspans[i] > this.totalCols) this.itemMinColspans[i] = this.totalCols;
				if (this.itemMaxColspans[i] > this.totalCols) this.itemMaxColspans[i] = this.totalCols;

				this.itemHeights[i] = [];

				for (var j = this.itemMinColspans[i]; j <= this.itemMaxColspans[i]; j++)
				{
					// Get the height for this colspan
					$item.width(this.getItemWidth(j));
					this.itemHeights[i][j] = $item.outerHeight();
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
			var layout = shortestLayouts[$.inArray(Math.min.apply(null, emptySpaces), emptySpaces)];

			// Now let's place the items
			var colHeights = [];

			for (var i = 0; i < this.totalCols; i++)
			{
				colHeights.push(0);
			}

			for (var i = 0; i < this.items.length; i++)
			{
				var endingCol = layout.positions[i] + layout.colspans[i] - 1,
					affectedColHeights = [];

				for (var col = layout.positions[i]; col <= endingCol; col++)
				{
					affectedColHeights.push(colHeights[col]);
				}

				var top = Math.max.apply(null, affectedColHeights);

				this.items[i].css({
					left: this.getItemWidth(layout.positions[i]),
					top: top,
					width: this.getItemWidth(layout.colspans[i])
				});

				// Now add the new heights to those columns
				for (var col = layout.positions[i]; col <= endingCol; col++)
				{
					colHeights[col] = top + this.itemHeights[i][layout.colspans[i]];
				}
			}

			// Set the container height
			this.$container.css({
				height: Math.max.apply(null, colHeights)
			});
		}
	},

	getItemWidth: function(colspan)
	{
		if (this.settings.percentageWidths)
		{
			return (this.colPctWidth * colspan) + '%';
		}
		else
		{
			return (this.settings.minColWidth * colspan) + 'px';
		}
	},

	createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace)
	{
		var maxPosition = this.totalCols - this.itemMinColspans[item];

		// Loop through all possible positions
		for (var position = 0; position <= maxPosition; position++)
		{
			var positions = prevPositions.slice(0);
			positions.push(position);

			// Loop through all possible colspans at this position
			var maxColspan = Math.min(this.itemMaxColspans[item], this.totalCols - position);

			for (var colspan = this.itemMinColspans[item]; colspan <= maxColspan; colspan++)
			{
				var colspans = prevColspans.slice(0),
					colHeights = prevColHeights.slice(0),
					emptySpace = prevEmptySpace;

				colspans.push(colspan);

				// Bump the col height for each of the columns this placement lives on

				// First find the tallest column
				var endingCol = position + colspan - 1,
					affectedColHeights = [];

				for (var col = position; col <= endingCol; col++)
				{
					affectedColHeights.push(colHeights[col]);
				}

				var tallestColHeight = Math.max.apply(null, affectedColHeights);

				// Now add the new heights to those columns
				for (var col = position; col <= endingCol; col++)
				{
					emptySpace += tallestColHeight - colHeights[col];
					colHeights[col] = tallestColHeight + this.itemHeights[item][colspan];
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
		}
	}
},
{
	defaults: {
		itemSelector: '.item',
		minColWidth: 300,
		percentageWidths: true,
		fillMode: 'top',
		colClass: 'col',
		snapToGrid: null
	}
});
