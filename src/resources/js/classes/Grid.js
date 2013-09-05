Craft.Grid = Garnish.Base.extend({

	$container: null,

	$items: null,
	items: null,
	totalCols: null,
	cols: null,
	colWidth: null,

	init: function(container, settings)
	{
		this.$container = $(container);

		this.setSettings(settings, Craft.Grid.defaults);

		this.$items = this.$container.children(this.settings.itemSelector);

		this.setCols();

		// Adjust them when the window resizes
		this.addListener(Garnish.$win, 'resize', 'setCols');
	},

	addItems: function(items)
	{
		this.$items = $().add(this.$items.add(items));
		this.refreshCols();
	},

	removeItems: function(items)
	{
		this.$items = $().add(this.$items.not(items));
		this.refreshCols();
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

			while (itemIndex < this.$items.length)
			{
				// Append the next X items and figure out which one is the tallest
				var tallestItemHeight = -1,
					colIndex = 0;

				for (var i = itemIndex; (i < itemIndex + this.totalCols && i < this.$items.length); i++)
				{
					var itemHeight = $(this.$items[i]).height('auto').height();
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
				for (var i = itemIndex; (i < itemIndex + this.totalCols && i < this.$items.length); i++)
				{
					$(this.$items[i]).height(tallestItemHeight);
				}

				// set the itemIndex pointer to the next one up
				itemIndex += this.totalCols;
			}
		}
		else
		{
			// Detach the items before we remove the columns so they keep their events
			for (var i = 0; i < this.$items.length; i++)
			{
				$(this.$items[i]).detach();
			}

			// Delete the old columns
			if (this.cols)
			{
				for (var i = 0; i < this.cols.length; i++)
				{
					this.cols[i].remove();
				}
			}

			// Create the new columns
			this.cols = [];

			if (this.settings.percentageWidths)
			{
				this.colWidth = Math.floor(100 / this.totalCols) + '%';
			}
			else
			{
				this.colWidth = this.settings.minColWidth + 'px';
			}

			var actualTotalCols = Math.min(this.totalCols, this.$items.length);
			for (var i = 0; i < actualTotalCols; i++)
			{
				this.cols[i] = new Craft.Grid.Col(this, i);
			}

			// Place the items
			if (this.cols.length == 1)
			{
				for (var i = 0; i < this.$items.length; i++)
				{
					this.cols[0].append(this.$items[i]);

					if (this.settings.snapToGrid)
					{
						var height = $(this.$items[i]).height('auto').height(),
							remainder = height % this.settings.snapToGrid;

						if (remainder)
						{
							$(this.$items[i]).height(height + this.settings.snapToGrid - remainder);
						}
					}
				}
			}
			else
			{
				switch (this.settings.fillMode)
				{
					case 'top':
					{
						// Add each item one at a time to the shortest column
						for (var i = 0; i < this.$items.length; i++)
						{
							this.getShortestCol().append(this.$items[i]);
						}

						break;
					}
					case 'ltr':
					{
						// First get the total height of the items
						this.itemHeights = [];
						this.ltrScenarios = [];
						this.totalItemHeight = 0;

						for (var i = 0; i < this.$items.length; i++)
						{
							this.cols[0].append(this.$items[i]);
							this.itemHeights[i] = $(this.$items[i]).height();
							this.totalItemHeight += this.itemHeights[i];
							$(this.$items[i]).detach();
						}

						this.avgColHeight = this.totalItemHeight / this.cols.length;

						// Get all the possible scenarios
						this.ltrScenarios.push(
							new Craft.Grid.LtrScenario(this, 0, 0, [[]], 0)
						);

						// Find the scenario with the shortest tallest column
						var shortestScenario = this.ltrScenarios[0];

						for (var i = 1; i < this.ltrScenarios.length; i++)
						{
							if (this.ltrScenarios[i].tallestColHeight < shortestScenario.tallestColHeight)
							{
								shortestScenario = this.ltrScenarios[i];
							}
						}

						// Lay out the items
						for (var i = 0; i < shortestScenario.placements.length; i++)
						{
							for (var j = 0; j < shortestScenario.placements[i].length; j++)
							{
								this.cols[i].append(this.$items[shortestScenario.placements[i][j]]);
							}
						}

						break;
					}
				}
			}
		}
	},

	getShortestCol: function()
	{
		var shortestCol, shortestColHeight;

		for (var i = 0; i < this.cols.length; i++)
		{
			var col = this.cols[i],
				colHeight = this.cols[i].height();

			if (typeof shortestCol == 'undefined' || colHeight < shortestColHeight)
			{
				shortestCol = col;
				shortestColHeight = colHeight;
			}
		}

		return shortestCol;
	},

	getTallestCol: function()
	{
		var tallestCol, tallestColHeight;

		for (var i = 0; i < this.cols.length; i++)
		{
			var col = this.cols[i],
				colHeight = this.cols[i].height();

			if (typeof tallestCol == 'undefined' || colHeight > tallestColHeight)
			{
				tallestCol = col;
				tallestColHeight = colHeight;
			}
		}

		return tallestCol;
	}

},
{
	defaults: {
		itemSelector: ':visible',
		minColWidth: 325,
		percentageWidths: true,
		fillMode: 'grid',
		snapToGrid: null
	}
});


Craft.Grid.Col = Garnish.Base.extend({

	grid: null,
	index: null,

	$outerContainer: null,
	$innerContainer: null,

	init: function(grid, index)
	{
		this.grid = grid;
		this.index = index;

		this.$outerContainer = $('<div class="col" style="width: '+this.grid.colWidth+'"/>').appendTo(this.grid.$container);
		this.$innerContainer = $('<div class="col-inner">').appendTo(this.$outerContainer);
	},

	height: function(height)
	{
		if (typeof height != 'undefined')
		{
			this.$innerContainer.height(height);
		}
		else
		{
			this.$innerContainer.height('auto');
			return this.$outerContainer.height();
		}
	},

	append: function(item)
	{
		this.$innerContainer.append(item);
	},

	remove: function()
	{
		this.$outerContainer.remove();
	}

});


Craft.Grid.LtrScenario = Garnish.Base.extend({

	placements: null,
	tallestColHeight: null,

	init: function(grid, itemIndex, colIndex, placements, tallestColHeight)
	{
		this.placements = placements;
		this.tallestColHeight = tallestColHeight;

		var runningColHeight = 0;

		for (itemIndex; itemIndex < grid.$items.length; itemIndex++)
		{
			var hypotheticalColHeight = runningColHeight + grid.itemHeights[itemIndex];

			// If there's enough room for this item, add it and move on
			if (hypotheticalColHeight <= grid.avgColHeight || colIndex == grid.cols.length-1)
			{
				this.placements[colIndex].push(itemIndex);
				runningColHeight += grid.itemHeights[itemIndex];
				this.checkColHeight(hypotheticalColHeight);
			}
			else
			{
				this.placements[colIndex+1] = [];

				// Create an alternate scenario where the item stays in this column
				var altPlacements = $.extend(true, [], this.placements);
				altPlacements[colIndex].push(itemIndex);
				var altTallestColHeight = Math.max(this.tallestColHeight, hypotheticalColHeight);
				grid.ltrScenarios.push(
					new Craft.Grid.LtrScenario(grid, itemIndex+1, colIndex+1, altPlacements, altTallestColHeight)
				);

				// As for this scenario, move it to the next column
				colIndex++;
				this.placements[colIndex].push(itemIndex);
				this.checkColHeight(grid.itemHeights[itemIndex]);
				runningColHeight = grid.itemHeights[itemIndex];
			}
		}
	},

	checkColHeight: function(colHeight)
	{
		if (colHeight > this.tallestColHeight)
		{
			this.tallestColHeight = colHeight;
		}
	}

});
