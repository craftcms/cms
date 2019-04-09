/** global: Craft */
/** global: Garnish */
Craft.Grid = Garnish.Base.extend(
    {
        $container: null,

        $items: null,
        items: null,
        totalCols: null,
        colGutterDrop: null,
        colPctWidth: null,

        possibleItemColspans: null,
        possibleItemPositionsByColspan: null,

        itemPositions: null,
        itemColspansByPosition: null,

        layouts: null,
        layout: null,
        itemHeights: null,
        leftPadding: null,

        _refreshingCols: false,
        _refreshColsAfterRefresh: false,
        _forceRefreshColsAfterRefresh: false,

        init: function(container, settings) {
            this.$container = $(container);

            // Is this already a grid?
            if (this.$container.data('grid')) {
                Garnish.log('Double-instantiating a grid on an element');
                this.$container.data('grid').destroy();
            }

            this.$container.data('grid', this);

            this.setSettings(settings, Craft.Grid.defaults);

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

        addItems: function(items) {
            this.$items = $().add(this.$items.add(items));
            this.setItems();
            this.refreshCols(true, true);
        },

        removeItems: function(items) {
            this.$items = $().add(this.$items.not(items));
            this.setItems();
            this.refreshCols(true, true);
        },

        resetItemOrder: function() {
            this.$items = $().add(this.$items);
            this.setItems();
            this.refreshCols(true, true);
        },

        setItems: function() {
            this.setItems._ = {};

            this.items = [];

            for (this.setItems._.i = 0; this.setItems._.i < this.$items.length; this.setItems._.i++) {
                this.items.push($(this.$items[this.setItems._.i]));
            }

            delete this.setItems._;
        },

        refreshCols: function(force) {
            if (this._refreshingCols) {
                this._refreshColsAfterRefresh = true;
                if (force) {
                    this._forceRefreshColsAfterRefresh = true;
                }
                return;
            }

            this._refreshingCols = true;

            if (!this.items.length) {
                this.completeRefreshCols();
                return;
            }

            this.refreshCols._ = {};

            // Check to see if the grid is actually visible
            this.refreshCols._.oldHeight = this.$container[0].style.height;
            this.$container[0].style.height = 1;
            this.refreshCols._.scrollHeight = this.$container[0].scrollHeight;
            this.$container[0].style.height = this.refreshCols._.oldHeight;

            if (this.refreshCols._.scrollHeight === 0) {
                this.completeRefreshCols();
                return;
            }

            if (this.settings.cols) {
                this.refreshCols._.totalCols = this.settings.cols;
            }
            else {
                this.refreshCols._.totalCols = Math.floor(this.$container.width() / this.settings.minColWidth);

                // If we're adding a new column, require an extra 20 pixels in case a scrollbar shows up
                if (this.totalCols !== null && this.refreshCols._.totalCols > this.totalCols) {
                    this.refreshCols._.totalCols = Math.floor((this.$container.width() - 20) / this.settings.minColWidth)
                }

                if (this.settings.maxCols && this.refreshCols._.totalCols > this.settings.maxCols) {
                    this.refreshCols._.totalCols = this.settings.maxCols;
                }
            }

            if (this.refreshCols._.totalCols === 0) {
                this.refreshCols._.totalCols = 1;
            }

            // Same number of columns as before?
            if (force !== true && this.totalCols === this.refreshCols._.totalCols) {
                this.completeRefreshCols();
                return;
            }

            this.totalCols = this.refreshCols._.totalCols;
            this.colGutterDrop = this.settings.gutter * (this.totalCols - 1) / this.totalCols;

            // Temporarily stop listening to container resizes
            this.removeListener(this.$container, 'resize');

            if (this.settings.fillMode === 'grid') {
                this.refreshCols._.itemIndex = 0;

                while (this.refreshCols._.itemIndex < this.items.length) {
                    // Append the next X items and figure out which one is the tallest
                    this.refreshCols._.tallestItemHeight = -1;
                    this.refreshCols._.colIndex = 0;

                    for (this.refreshCols._.i = this.refreshCols._.itemIndex; (this.refreshCols._.i < this.refreshCols._.itemIndex + this.totalCols && this.refreshCols._.i < this.items.length); this.refreshCols._.i++) {
                        this.refreshCols._.itemHeight = this.items[this.refreshCols._.i].height('auto').height();

                        if (this.refreshCols._.itemHeight > this.refreshCols._.tallestItemHeight) {
                            this.refreshCols._.tallestItemHeight = this.refreshCols._.itemHeight;
                        }

                        this.refreshCols._.colIndex++;
                    }

                    if (this.settings.snapToGrid) {
                        this.refreshCols._.remainder = this.refreshCols._.tallestItemHeight % this.settings.snapToGrid;

                        if (this.refreshCols._.remainder) {
                            this.refreshCols._.tallestItemHeight += this.settings.snapToGrid - this.refreshCols._.remainder;
                        }
                    }

                    // Now set their heights to the tallest one
                    for (this.refreshCols._.i = this.refreshCols._.itemIndex; (this.refreshCols._.i < this.refreshCols._.itemIndex + this.totalCols && this.refreshCols._.i < this.items.length); this.refreshCols._.i++) {
                        this.items[this.refreshCols._.i].height(this.refreshCols._.tallestItemHeight);
                    }

                    // set the this.refreshCols._.itemIndex pointer to the next one up
                    this.refreshCols._.itemIndex += this.totalCols;
                }
            }
            else {
                this.removeListener(this.$items, 'resize');

                // If there's only one column, sneak out early
                if (this.totalCols === 1) {
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
                else {
                    this.$items.css('position', 'absolute');
                    this.colPctWidth = (100 / this.totalCols);

                    // The setup

                    this.layouts = [];

                    this.itemPositions = [];
                    this.itemColspansByPosition = [];

                    // Figure out all of the possible colspans for each item,
                    // as well as all the possible positions for each item at each of its colspans

                    this.possibleItemColspans = [];
                    this.possibleItemPositionsByColspan = [];
                    this.itemHeightsByColspan = [];

                    for (this.refreshCols._.item = 0; this.refreshCols._.item < this.items.length; this.refreshCols._.item++) {
                        this.possibleItemColspans[this.refreshCols._.item] = [];
                        this.possibleItemPositionsByColspan[this.refreshCols._.item] = {};
                        this.itemHeightsByColspan[this.refreshCols._.item] = {};

                        this.refreshCols._.$item = this.items[this.refreshCols._.item].show();
                        this.refreshCols._.positionRight = (this.refreshCols._.$item.data('position') === 'right');
                        this.refreshCols._.positionLeft = (this.refreshCols._.$item.data('position') === 'left');
                        this.refreshCols._.minColspan = (this.refreshCols._.$item.data('colspan') ? this.refreshCols._.$item.data('colspan') : (this.refreshCols._.$item.data('min-colspan') ? this.refreshCols._.$item.data('min-colspan') : 1));
                        this.refreshCols._.maxColspan = (this.refreshCols._.$item.data('colspan') ? this.refreshCols._.$item.data('colspan') : (this.refreshCols._.$item.data('max-colspan') ? this.refreshCols._.$item.data('max-colspan') : this.totalCols));

                        if (this.refreshCols._.minColspan > this.totalCols) {
                            this.refreshCols._.minColspan = this.totalCols;
                        }
                        if (this.refreshCols._.maxColspan > this.totalCols) {
                            this.refreshCols._.maxColspan = this.totalCols;
                        }

                        for (this.refreshCols._.colspan = this.refreshCols._.minColspan; this.refreshCols._.colspan <= this.refreshCols._.maxColspan; this.refreshCols._.colspan++) {
                            // Get the height for this colspan
                            this.refreshCols._.$item.css('width', this.getItemWidthCss(this.refreshCols._.colspan));
                            this.itemHeightsByColspan[this.refreshCols._.item][this.refreshCols._.colspan] = this.refreshCols._.$item.outerHeight();

                            this.possibleItemColspans[this.refreshCols._.item].push(this.refreshCols._.colspan);
                            this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan] = [];

                            if (this.refreshCols._.positionLeft) {
                                this.refreshCols._.minPosition = 0;
                                this.refreshCols._.maxPosition = 0;
                            }
                            else if (this.refreshCols._.positionRight) {
                                this.refreshCols._.minPosition = this.totalCols - this.refreshCols._.colspan;
                                this.refreshCols._.maxPosition = this.refreshCols._.minPosition;
                            }
                            else {
                                this.refreshCols._.minPosition = 0;
                                this.refreshCols._.maxPosition = this.totalCols - this.refreshCols._.colspan;
                            }

                            for (this.refreshCols._.position = this.refreshCols._.minPosition; this.refreshCols._.position <= this.refreshCols._.maxPosition; this.refreshCols._.position++) {
                                this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan].push(this.refreshCols._.position);
                            }
                        }
                    }

                    // Find all the possible layouts

                    this.refreshCols._.colHeights = [];

                    for (this.refreshCols._.i = 0; this.refreshCols._.i < this.totalCols; this.refreshCols._.i++) {
                        this.refreshCols._.colHeights.push(0);
                    }

                    this.createLayouts(0, [], [], this.refreshCols._.colHeights, 0);

                    // Now find the layout that looks the best.

                    // First find the layouts with the highest number of used columns
                    this.refreshCols._.layoutTotalCols = [];

                    for (this.refreshCols._.i = 0; this.refreshCols._.i < this.layouts.length; this.refreshCols._.i++) {
                        this.refreshCols._.layoutTotalCols[this.refreshCols._.i] = 0;

                        for (this.refreshCols._.j = 0; this.refreshCols._.j < this.totalCols; this.refreshCols._.j++) {
                            if (this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j]) {
                                this.refreshCols._.layoutTotalCols[this.refreshCols._.i]++;
                            }
                        }
                    }

                    this.refreshCols._.highestTotalCols = Math.max.apply(null, this.refreshCols._.layoutTotalCols);

                    // Filter out the ones that aren't using as many columns as they could be
                    for (this.refreshCols._.i = this.layouts.length - 1; this.refreshCols._.i >= 0; this.refreshCols._.i--) {
                        if (this.refreshCols._.layoutTotalCols[this.refreshCols._.i] !== this.refreshCols._.highestTotalCols) {
                            this.layouts.splice(this.refreshCols._.i, 1);
                        }
                    }

                    // Find the layout(s) with the least overall height
                    this.refreshCols._.layoutHeights = [];

                    for (this.refreshCols._.i = 0; this.refreshCols._.i < this.layouts.length; this.refreshCols._.i++) {
                        this.refreshCols._.layoutHeights.push(Math.max.apply(null, this.layouts[this.refreshCols._.i].colHeights));
                    }

                    this.refreshCols._.shortestHeight = Math.min.apply(null, this.refreshCols._.layoutHeights);
                    this.refreshCols._.shortestLayouts = [];
                    this.refreshCols._.emptySpaces = [];

                    for (this.refreshCols._.i = 0; this.refreshCols._.i < this.refreshCols._.layoutHeights.length; this.refreshCols._.i++) {
                        if (this.refreshCols._.layoutHeights[this.refreshCols._.i] === this.refreshCols._.shortestHeight) {
                            this.refreshCols._.shortestLayouts.push(this.layouts[this.refreshCols._.i]);

                            // Now get its total empty space, including any trailing empty space
                            this.refreshCols._.emptySpace = this.layouts[this.refreshCols._.i].emptySpace;

                            for (this.refreshCols._.j = 0; this.refreshCols._.j < this.totalCols; this.refreshCols._.j++) {
                                this.refreshCols._.emptySpace += (this.refreshCols._.shortestHeight - this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j]);
                            }

                            this.refreshCols._.emptySpaces.push(this.refreshCols._.emptySpace);
                        }
                    }

                    // And the layout with the least empty space is...
                    this.layout = this.refreshCols._.shortestLayouts[$.inArray(Math.min.apply(null, this.refreshCols._.emptySpaces), this.refreshCols._.emptySpaces)];

                    // Set the item widths and left positions
                    for (this.refreshCols._.i = 0; this.refreshCols._.i < this.items.length; this.refreshCols._.i++) {
                        this.refreshCols._.css = {
                            width: this.getItemWidthCss(this.layout.colspans[this.refreshCols._.i])
                        };
                        this.refreshCols._.css[Craft.left] = this.getItemLeftPosCss(this.layout.positions[this.refreshCols._.i]);
                        this.items[this.refreshCols._.i].css(this.refreshCols._.css);
                    }

                    // If every item is at position 0, then let them lay out au naturel
                    if (this.isSimpleLayout()) {

                        this.$container.height('auto');
                        this.$items.css({
                            position: 'relative',
                            top: 0,
                            'margin-bottom': this.settings.gutter+'px'
                        });
                    }
                    else {
                        this.$items.css('position', 'absolute');

                        // Now position the items
                        this.positionItems();

                        // Update the positions as the items' heigthts change
                        this.addListener(this.$items, 'resize', 'onItemResize');
                    }
                }
            }

            this.completeRefreshCols();

            // Resume container resize listening
            this.addListener(this.$container, 'resize', this.handleContainerHeightProxy);

            this.onRefreshCols();
        },

        completeRefreshCols: function() {
            // Delete the internal variable object
            if (typeof this.refreshCols._ !== 'undefined') {
                delete this.refreshCols._;
            }

            this._refreshingCols = false;

            if (this._refreshColsAfterRefresh) {
                var force = this._forceRefreshColsAfterRefresh;
                this._refreshColsAfterRefresh = false;
                this._forceRefreshColsAfterRefresh = false;

                Garnish.requestAnimationFrame($.proxy(function() {
                    this.refreshCols(force);
                }, this));
            }
        },

        getItemWidth: function(colspan) {
            return (this.colPctWidth * colspan);
        },

        getItemWidthCss: function(colspan) {
            return 'calc(' + this.getItemWidth(colspan) + '% - ' + this.colGutterDrop + 'px)';
        },

        getItemWidthInPx: function(colspan) {
            return this.getItemWidth(colspan)/100 * this.$container.width() - this.colGutterDrop;
        },

        getItemLeftPosCss: function(position) {
            return 'calc(' + '(' + this.getItemWidth(1) + '% + ' + (this.settings.gutter - this.colGutterDrop) + 'px) * ' + position + ')';
        },

        getItemLeftPosInPx: function(position) {
            return (this.getItemWidth(1)/100 * this.$container.width() + (this.settings.gutter - this.colGutterDrop)) * position;
        },

        createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace) {
            (new Craft.Grid.LayoutGenerator(this)).createLayouts(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace);
        },

        isSimpleLayout: function() {
            this.isSimpleLayout._ = {};

            for (this.isSimpleLayout._.i = 0; this.isSimpleLayout._.i < this.layout.positions.length; this.isSimpleLayout._.i++) {
                if (this.layout.positions[this.isSimpleLayout._.i] !== 0) {
                    delete this.isSimpleLayout._;
                    return false;
                }
            }

            delete this.isSimpleLayout._;
            return true;
        },

        positionItems: function() {
            this.positionItems._ = {};

            this.positionItems._.colHeights = [];

            for (this.positionItems._.i = 0; this.positionItems._.i < this.totalCols; this.positionItems._.i++) {
                this.positionItems._.colHeights.push(0);
            }

            for (this.positionItems._.i = 0; this.positionItems._.i < this.items.length; this.positionItems._.i++) {
                this.positionItems._.endingCol = this.layout.positions[this.positionItems._.i] + this.layout.colspans[this.positionItems._.i] - 1;
                this.positionItems._.affectedColHeights = [];

                for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++) {
                    this.positionItems._.affectedColHeights.push(this.positionItems._.colHeights[this.positionItems._.col]);
                }

                this.positionItems._.top = Math.max.apply(null, this.positionItems._.affectedColHeights);
                if (this.positionItems._.top > 0) {
                    this.positionItems._.top += this.settings.gutter;
                }

                this.items[this.positionItems._.i].css('top', this.positionItems._.top);

                // Now add the new heights to those columns
                for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++) {
                    this.positionItems._.colHeights[this.positionItems._.col] = this.positionItems._.top + this.itemHeightsByColspan[this.positionItems._.i][this.layout.colspans[this.positionItems._.i]];
                }
            }

            // Set the container height
            this.$container.height(Math.max.apply(null, this.positionItems._.colHeights));

            delete this.positionItems._;
        },

        onItemResize: function(ev) {
            this.onItemResize._ = {};

            // Prevent this from bubbling up to the container, which has its own resize listener
            ev.stopPropagation();

            this.onItemResize._.item = $.inArray(ev.currentTarget, this.$items);

            if (this.onItemResize._.item !== -1) {
                // Update the height and reposition the items
                this.onItemResize._.newHeight = this.items[this.onItemResize._.item].outerHeight();

                if (this.onItemResize._.newHeight !== this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]]) {
                    this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]] = this.onItemResize._.newHeight;
                    this.positionItems(false);
                }
            }

            delete this.onItemResize._;
        },

        onRefreshCols: function() {
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
            gutter: 14,
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

        init: function(grid) {
            this.grid = grid;
        },

        createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace) {
            this._ = {};

            // Loop through all possible colspans
            for (this._.c = 0; this._.c < this.grid.possibleItemColspans[item].length; this._.c++) {
                this._.colspan = this.grid.possibleItemColspans[item][this._.c];

                // Loop through all the possible positions for this colspan,
                // and find the one that is closest to the top

                this._.tallestColHeightsByPosition = [];

                for (this._.p = 0; this._.p < this.grid.possibleItemPositionsByColspan[item][this._.colspan].length; this._.p++) {
                    this._.position = this.grid.possibleItemPositionsByColspan[item][this._.colspan][this._.p];

                    this._.colHeightsForPosition = [];
                    this._.endingCol = this._.position + this._.colspan - 1;

                    for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++) {
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

                for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++) {
                    this._.emptySpace += this._.tallestColHeight - this._.colHeights[this._.col];
                    this._.colHeights[this._.col] = this._.tallestColHeight + this.grid.itemHeightsByColspan[item][this._.colspan];
                }

                // If this is the last item, create the layout
                if (item === this.grid.items.length - 1) {
                    this.grid.layouts.push({
                        positions: this._.positions,
                        colspans: this._.colspans,
                        colHeights: this._.colHeights,
                        emptySpace: this._.emptySpace
                    });
                }
                else {
                    // Dive deeper
                    this.grid.createLayouts(item + 1, this._.positions, this._.colspans, this._.colHeights, this._.emptySpace);
                }
            }

            delete this._;
        }

    });
