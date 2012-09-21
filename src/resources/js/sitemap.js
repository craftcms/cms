(function($) {


var SiteMap = Blocks.Base.extend({

	tiers: null,
	windowWidth: null,
	trailCurveWidth: null,
	maxVisibleNodes: null,

	init: function()
	{
		this.tiers = [];
		this.onWindowResize();
		this.addListener(Blocks.$window, 'resize', 'onWindowResize');
	},

	getMaxVisibleNodes: function()
	{
		var maxVisibleNodes = Math.floor((Blocks.$window.width()-SiteMap.minElementMargin) / (SiteMap.elementWidth+SiteMap.minElementMargin));
		if (maxVisibleNodes < 1)
			maxVisibleNodes = 1;
		return maxVisibleNodes;
	},

	onWindowResize: function()
	{
		if (this.windowWidth !== (this.windowWidth = Blocks.$window.width()))
		{
			// Update the trail curve width based on the new window width
			this.trailCurveWidth = 100 * SiteMap.trailCurveRadius / this.windowWidth;

			// Check to see if the max visible nodes has changed
			if (this.maxVisibleNodes !== (this.maxVisibleNodes = this.getMaxVisibleNodes()))
			{
				for (var i = 0; i < this.tiers.length; i++)
					this.tiers[i].positionNodes();
			}
		}
	}

}, {
	tierHeight: 293,
	elementWidth: 150,
	minElementMargin: 25,
	svgNS: 'http://www.w3.org/2000/svg',
	svgMidpoint: 45,
	svgHeight: 80,
	trailCurveRadius: 20
});


var siteMap = new SiteMap();



Blocks.Tier = Blocks.Base.extend({

	$container: null,
	$trailContainer: null,
	$nodeContainer: null,
	trailHead: null,
	totalNodes: null,
	totalVisibleNodes: null,
	nodes: null,

	init: function(container)
	{
		siteMap.tiers.push(this);

		this.$container = $(container);
		this.$nodeContainer = this.$container.children('.nodes:first');
		this.$trailContainer = $('<svg xmlns="'+SiteMap.svgNS+'" version="1.1" class="trails" viewBox="0 0 100 '+SiteMap.svgHeight+'" style="height: '+SiteMap.svgHeight+'px" preserveAspectRatio="none"/>').prependTo(this.$container);

		// Create the trail head
		this.trailHead = document.createElementNS(SiteMap.svgNS, 'path');
		this.trailHead.setAttributeNS(null, 'class', 'trail');
		this.trailHead.setAttributeNS(null, 'vector-effect', 'non-scaling-stroke');
		this.$trailContainer[0].appendChild(this.trailHead);

		// Find the nodes
		var $nodes = this.$nodeContainer.children();
		this.totalNodes = $nodes.length;

		// Initialize the nodes
		this.nodes = [];
		for (var i = 0; i < this.totalNodes; i++)
		{
			var node = new Blocks.Node(this, $nodes[i], i);
			this.nodes.push(node);
		}

		this.positionNodes();
	},

	positionNodes: function()
	{
		this.trailHead.setAttributeNS(null, 'd', 'M 50,2 V '+(SiteMap.svgMidpoint - SiteMap.trailCurveRadius));

		this.totalVisibleNodes = Math.min(siteMap.maxVisibleNodes, this.totalNodes);
		this.gap = 100 / (this.totalVisibleNodes+1);

		for (var i = 0; i < this.totalVisibleNodes; i++)
		{
			var x = this.gap + this.gap * i;
			this.nodes[i].setPosition(x);
		}

		for (var i = this.totalVisibleNodes; i < this.totalNodes; i++)
		{
			this.nodes[i].hide();
		}
	}

});


Blocks.Node = Blocks.Base.extend({

	tier: null,
	$elem: null,
	$trail: null,
	index: null,
	pos: null,
	type: null,

	init: function(tier, elem, index)
	{
		this.tier = tier;
		this.$elem = $(elem);
		this.index = index;

		var trail = document.createElementNS(SiteMap.svgNS, 'path');
		trail.setAttributeNS(null, 'class', 'trail');
		trail.setAttributeNS(null, 'vector-effect', 'non-scaling-stroke');
		this.tier.$trailContainer[0].appendChild(trail);
		this.$trail = $(trail);

		this.addListener(this.$elem, 'click', 'onClick');
	},

	getSiblings: function()
	{
		var siblings = [];
		for (var i = 0; i < this.tier.totalNodes; i++)
		{
			var node = this.tier.nodes[i];
			if (node != this)
				siblings.push(node);
		}
		return siblings;
	},

	onClick: function()
	{
		//this.tier.$trailContainer[0].appendChild(this.$trail[0]);
		//this.tier.$trailContainer[0].appendChild(this.tier.trailHead);
		//this.$trail[0].setAttributeNS(null, 'class', 'trail hover');
		//this.tier.trailHead.setAttributeNS(null, 'class', 'trail hover');
		this.$elem.addClass('sel');

		// Scroll the new tier into the middle of the screen
		var heightDiff = Blocks.$window.height() - Blocks.$body.outerHeight(),
			padding = heightDiff,// + SiteMap.tierHeight,
			scrollTop = Blocks.$body.scrollTop();
		Blocks.$body.css('padding-bottom', padding);
		Blocks.$body.animate({
			scrollTop: (scrollTop + SiteMap.tierHeight)
		}, $.proxy(function() {
			Blocks.$body.css('padding-bottom', heightDiff)
		}, this));

		// Animate the element to the center
		var posDiff = 50-this.pos,
			siblings = this.getSiblings();

		this.$elem.animate({
			left: '50%'
		}, {
			//duration: 'fast',

			step: $.proxy(function(now, fx) {
				this.setTrailPosition(now);

				var nowPosDiff = posDiff * fx.pos,
					//opacity = 0.25 + 0.75*(1 - fx.pos);
					opacity = 1 - fx.pos;

				for (var i = 0; i < siblings.length; i++)
				{
					var sibling = siblings[i],
						siblingPos = sibling.pos + nowPosDiff;
					sibling.$elem.css({
						opacity: opacity,
						left: siblingPos+'%'
					});
					sibling.$trail.css({
						opacity: opacity
					});
					sibling.setTrailPosition(siblingPos);
				}
			}, this),

			complete: $.proxy(function()
			{
				//setTimeout($.proxy(function() {

				//}, this), 1);
			}, this)
		});

		var $newTier = $('<div class="tier"/>').insertAfter(this.tier.$container),
			$newNodes = $('<div class="nodes"/>').appendTo($newTier);

		var newPages = [
			{ title: 'Chimaeric', image: 'Chimaeric.png' },
			{ title: 'Pure', image: 'Pure.png' },
			{ title: 'Message Marker Application', image: 'MessageMarker.png' }
			//{ title: 'Concrete5 Custom Admin', image: 'Concrete5.png' },
			//{ title: 'Absolute Restoration', image: 'AbsoluteRestoration.png' }
		];

		for (var i = 0; i < newPages.length; i++)
		{
			var $newNode = $('<div class="node page"/>').appendTo($newNodes);
			$('<img src="'+Blocks.resourceUrl+'images/screenshots/UI/'+newPages[i].image+'"/>').appendTo($newNode);
			$('<div class="title">'+newPages[i].title+'</div>').appendTo($newNode);
		}

		new Blocks.Tier($newTier, true);

	},

	setPosition: function(pos)
	{
		this.pos = pos;

		this.$elem.show();

		this.$elem.css('left', this.pos+'%');
		this.setTrailPosition(this.pos);

		this.$elem.css({
			opacity: 0
			//left: '50%'
		});
		this.$trail.css('opacity', 0);
		//this.setTrailPosition(50);

		this.$elem.animate({
			opacity: 1
			//left: this.pos+'%'
		}, {
			//duration: 'fast',

			step: $.proxy(function(now, fx)
			{
				//this.$elem.css('opacity', fx.pos);
				this.$trail.css('opacity', fx.pos);
				//this.setTrailPosition(now);
			}, this)
		});
	},

	setTrailPosition: function(x)
	{
		// Set the trail position
		var xDiff = Math.abs(x - 50),
			curveWidth = (x < 50 ? -1 : 1) * ((xDiff < siteMap.trailCurveWidth * 2) ? (xDiff / 2) : siteMap.trailCurveWidth),
			x2 = 50 + curveWidth,
			x3 = x - curveWidth,
			y0 = 50 - SiteMap.trailCurveRadius,
			y1 = SiteMap.svgMidpoint - SiteMap.trailCurveRadius,
			y3 = SiteMap.svgMidpoint + SiteMap.trailCurveRadius,
			d = 'M 50,'+y1+' Q 50,'+SiteMap.svgMidpoint+' '+x2+','+SiteMap.svgMidpoint+' H '+x3+' Q '+x+','+SiteMap.svgMidpoint+' '+x+','+y3+' V '+(SiteMap.svgHeight-2);
		this.$trail[0].setAttributeNS(null, 'd', d);
	},

	hide: function()
	{
		this.$elem.hide();
		this.$trail.hide();
	}

});



})(jQuery);
