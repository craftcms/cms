(function($) {


if (typeof blx == 'undefined')
	blx = {};


blx.onWindowResize = function()
{
	// has the window width changed?
	if (this.windowWidth !== (this.windowWidth = $(window).width()))
		$(window).trigger('resizeWidth', this.windowWidth);

	// has the window height changed?
	if (this.windowHeight !== (this.windowHeight = $(window).height()))
		$(window).trigger('resizeHeight', this.windowHeight)
};


/**
 * Returns the body's proper scrollTop, discarding any document banding in Safari
 */
blx.getBodyScrollTop = function()
{
	var scrollTop = document.body.scrollTop;

	if (scrollTop < 0)
	{
		scrollTop = 0;
	}
	else
	{
		var maxScrollTop = $(document.body).outerHeight() - this.windowHeight;
		if (scrollTop > maxScrollTop)
			scrollTop = maxScrollTop;
	}

	return scrollTop;
};


blx.CP =
{
	hasSidebar: $(document.body).hasClass('sidebar-layout'),
	navHeight: null,
	footerHeight: null,

	dom:
	{
		$nav: $('#nav'),
		$footer: $('#footer'),
		$sidebar: $('#sidebar'),
		$body: $('#body'),
		$main: $('#main')
	},

	onWindowResizeHeight: function()
	{
		// set the min #main height
		var minMainHeight = blx.windowHeight - this.navHeight - this.footerHeight - 40;
		this.dom.$main.css('minHeight', minMainHeight);

		if (this.hasSidebar)
			this.setSidebarHeight();
	},

	onWindowScroll: function(event)
	{
		if (blx.getBodyScrollTop() > 15)
		{
			this.dom.$nav.addClass('scrolling');
		}
		else
		{
			this.dom.$nav.removeClass('scrolling');
		}

		if (this.hasSidebar)
			this.setSidebarHeight();
	},

	setSidebarHeight: function()
	{
		// is the footer visible?
		var footerScrollOffset = this.dom.$footer.offset().top - blx.getBodyScrollTop();
		if (footerScrollOffset < blx.windowHeight)
			visibleFooterHeight = blx.windowHeight - footerScrollOffset;
		else
			visibleFooterHeight = 0;

		var sidebarHeight = blx.windowHeight - this.navHeight - visibleFooterHeight - 40;
		this.dom.$sidebar.height(sidebarHeight);
	}
};


blx.CP.navHeight = blx.CP.dom.$nav.outerHeight();
blx.CP.footerHeight = blx.CP.dom.$footer.outerHeight();

$(window).on('resize.blx', $.proxy(blx, 'onWindowResize'));
$(window).on('resizeHeight.cp', $.proxy(blx.CP, 'onWindowResizeHeight'));
$(window).on('scroll.cp', $.proxy(blx.CP, 'onWindowScroll'));
blx.onWindowResize();
blx.CP.onWindowScroll();


blx.utils =
{
	/**
	 * Format a number with commas
	 * ex: 1000 => 1,000
	 */
	numCommas: function(num)
	{
		num = num.toString();

		var regex = /(\d+)(\d{3})/;
		while (regex.test(num)) {
			num = num.replace(regex, '$1'+','+'$2');
		}

		return num;
	},

	/**
	 * Get the distance between two coordinates
	 */
	getDist: function(x1, y1, x2, y2)
	{
		return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
	},

	/**
	 * Check if an element is touching an x/y coordinate
	 */
	hitTest: function(x0, y0, element)
	{
		var $element = $(element),
			offset = $element.offset(),
			x1 = offset.left,
			y1 = offset.top,
			x2 = x1 + $element.width(),
			y2 = y1 + $element.height();

		return (x0 >= x1 && x0 < x2 && y0 >= y1 && y0 < y2);
	},

	/**
	 * Check if the cursor is over an element
	 */
	isCursorOver: function(event, element)
	{
		return blx.utils.hitTest(event.pageX, event.pageY, element);
	},

	/**
	 * Case insensative sort
	 */
	caseInsensativeSort: function(arr)
	{
		return arr.sort(this.caseInsensativeCompare)
	},

	/**
	 * Case insensative string comparison
	 */
	caseInsensativeCompare: function(a, b)
	{
		a = a.toLowerCase();
		b = b.toLowerCase();
		return a < b ? -1 : (a > b ? 1 : 0);
	}
};



/*$('.btn.menu').each(function() {
	var $btn = $(this),
		$menu = $('> ul.menu', this);

	new blx.ui.HUD($btn, $menu, {
		onShow: function() {
			$btn.addClass('sel');
		},
		onHide: function() {
			$btn.removeClass('sel');
		}
	});
});*/


})(jQuery);
