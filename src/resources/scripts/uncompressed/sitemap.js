(function($) {


b.Tier = b.Base.extend({

	$container: null,
	$trailContainer: $(),
	totalElements: null,
	elements: null,
	trails: null,
	elementsPerRow: null,
	totalRows: null,

	init: function(container)
	{
		this.$container = $(container);

		var $elements = this.$container.children();
		this.totalElements = $elements.length;

		this.$trailContainer = $('<svg xmlns="'+b.Tier.svgNS+'" version="1.1" class="trails" viewBox="0 0 100 100" preserveAspectRatio="none">'
			+   '<line class="trail" x1="50" y1="28" x2="50" y2="'+(50-b.Tier.pathCurveRadius)+'" vector-effect="non-scaling-stroke"/>'
			+ '</svg>').prependTo(this.$container);

		this.elements = [];
		this.trails = [];

		for (var i = 0; i < this.totalElements; i++)
		{
			var $element = $($elements[i]),
				//$dot = $('<svg xmlns="'+b.Tier.svgNS+'" version="1.1" class="dot"><circle cx="2" cy="2" r="2"/></svg>').prependTo($element),
				path = document.createElementNS(b.Tier.svgNS, 'path');

			path.setAttributeNS(null, 'class', 'trail');
			path.setAttributeNS(null, 'vector-effect', 'non-scaling-stroke');
			this.$trailContainer[0].appendChild(path);

			this.elements.push($element);
			this.trails.push({ path: path });
		}

		this.positionElements();
		this.addListener(b.$window, 'resize', 'positionElements');
	},

	positionElements: function()
	{
		var animate = (this.elementsPerRow !== null);

		var elementsPerRow = Math.floor((b.$window.width()-b.Tier.minElementMargin) / (b.Tier.elementWidth+b.Tier.minElementMargin));

		if (elementsPerRow < 1)
			elementsPerRow = 1;
		else if (elementsPerRow > this.totalElements)
			elementsPerRow = this.totalElements;

		var totalRows = Math.ceil(this.totalElements / elementsPerRow);
		if (totalRows !== this.totalRows)
		{
			this.totalRows = totalRows;

			// Reset the height of the svg
			var height = 100 + ((this.totalRows-1) * 250);

			if (animate)
			{
				this.$trailContainer.animate({ height: height }, {
					duration: 'fast',
					step: $.proxy(function(now)
					{
						this.$trailContainer[0].setAttributeNS(null, 'viewBox', '0 0 100 '+now);
					}, this)
				});
			}
			else
			{
				this.$trailContainer.css('height', height);
				this.$trailContainer[0].setAttributeNS(null, 'viewBox', '0 0 100 '+height);
			}
		}

		// Prevent an orphan when there are 4 elements and 2 rows
		elementsPerRow = Math.ceil(this.totalElements / totalRows);

		if (elementsPerRow !== this.elementsPerRow)
		{
			this.elementsPerRow = elementsPerRow;
			this.gap = 100 / (elementsPerRow+1);

			var row = 0,
				elementsInRow = 0,
				gap = this.gap;
				console.log(gap);

			for (var i = 0; i < this.totalElements; i++)
			{
				if (elementsInRow < this.elementsPerRow)
					elementsInRow++;
				else
				{
					row++;
					elementsInRow = 1;

					if (row == totalRows-1)
					{
						// Keep elements on the last row centered
						var remainingElements = this.totalElements - i;
						if (remainingElements < this.elementsPerRow)
							gap = 100 / (remainingElements+1);
					}
				}

				var $element = this.elements[i],
					trail = this.trails[i],
					x = gap * elementsInRow,
					y = 100 + (250*row);

				if (animate)
					this.animateElementPosition($element, trail, x, y);
				else
				{
					$element.css({ left: x+'%', top: y });
					$element.show();

					var coords = this.getTrailCoordinates(x, y);
					this.setTrailPosition(trail, coords);
				}
			}
		}
	},

	/**
	 * Returns the path's point coordinates based on a target position.
	 * @param int x
	 * @param int y
	 */
	getTrailCoordinates: function(x, y)
	{
		var coords = { x: x, y: y };

		// Is the element in the middle, and would a straght line would intersect other elements?
		if (x == 50 && y != 100 && this.elementsPerRow % 2 == 1)
		{
			// Should we bend it to the left or the right?
			var dir = (((y-100)/250 % 2 == 0) ? -1 : 1);

			coords.midX = 50 + ((this.gap/2)*dir);
			coords.midY1 = 50;
			coords.midY2 = y/2;
		}
		else
		{
			coords.midX = 50 + ((x-50)/2);
			coords.midY1 = coords.midY2 = y/2;
		}

		return coords;
	},

	/**
	 * Animate an element and its trail path to a position.
	 * @param jQuery $element
	 * @param object trail
	 * @param int targetX
	 * @param int targetY
	 */
	animateElementPosition: function($element, trail, targetX, targetY)
	{
		var oldCoords = $.extend({}, trail.coords),
			targetCoords = this.getTrailCoordinates(targetX, targetY),
			coordDiffs = {};

		for (var i in targetCoords)
		{
			coordDiffs[i] = targetCoords[i] - trail.coords[i];
		}

		var lastPos = 0;
		$element.animate({ left: targetX+'%', top: targetY }, {
			duration: 'fast',
			step: $.proxy(function(now, fx)
			{
				// step() gets called once for each property being animated
				// so make sure we're only updating the path once per step
				if (fx.pos !== lastPos)
				{
					var coords = {};
					for (var i in targetCoords)
					{
						coords[i] = oldCoords[i] + (coordDiffs[i] * fx.pos);
					}

					this.setTrailPosition(trail, coords);

					lastPos = fx.pos;
				}
			}, this)
		});
	},

	/**
	 * Sets a trail path's position.
	 * @param object trail
	 * @param object coords
	 */
	setTrailPosition: function(trail, coords)
	{
		var curveWidth = (coords.x == 50 ? 0 : ((coords.x < 50 ? -1 : 1) * (this.$container.width()/100)/(100/b.Tier.pathCurveRadius))),
			x2 = 50 + curveWidth,
			x3 = coords.x - curveWidth,
			y0 = 50 - b.Tier.pathCurveRadius,
			y2 = 28 + Math.round((coords.y - 38)/2),
			y1 = y2 - b.Tier.pathCurveRadius,
			y3 = y2 + b.Tier.pathCurveRadius,
			y4 = coords.y - 10,
			d = 'M 50,'+y0+' V '+y1+' Q 50,'+y2+' '+x2+','+y2+' H '+x3+' Q '+coords.x+','+y2+' '+coords.x+','+y3+' V '+y4;
		trail.path.setAttributeNS(null, 'd', d);

		// Save the coordinates for later
		trail.coords = coords;
	}

},
{
	svgNS: 'http://www.w3.org/2000/svg',
	elementWidth: 158,
	minElementMargin: 25,
	pathCurveRadius: 6
});


})(jQuery);
