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
}


blx.cp =
{
	dom:
	{
		$nav: $('#nav'),
		$sidebars: $('#sidebars'),
		$main: $('#main')
	},

	navHeight: null,
	windowWidth: null,
	windowHeight: null,

	/**
	 * Updates #sidebar's height and #main's min-height
	 */
	onWindowResizeHeight: function()
	{
		var bodyHeight = blx.windowHeight - this.navHeight;
		this.dom.$sidebars.height(bodyHeight);
		this.dom.$main.css('minHeight', bodyHeight);
	},

	onWindowScroll: function(event)
	{
		if (document.body.scrollTop > 15)
		{
			this.dom.$nav.addClass('scrolling');
		}
		else
		{
			this.dom.$nav.removeClass('scrolling');
		}
	}
};


blx.cp.navHeight = blx.cp.dom.$nav.outerHeight();

$(window).on('resize.blx', $.proxy(blx, 'onWindowResize'));
$(window).on('resizeHeight.blx', $.proxy(blx.cp, 'onWindowResizeHeight'));
$(window).on('scroll.blx', $.proxy(blx.cp, 'onWindowScroll'))
blx.onWindowResize();
blx.cp.onWindowScroll();


/*$('.btn.menu').each(function() {
	var $btn = $(this),
		$menu = $('> ul.menu', this);

	new blx.HUD($btn, $menu, {
		onShow: function() {
			$btn.addClass('sel');
		},
		onHide: function() {
			$btn.removeClass('sel');
		}
	});
});*/


})(jQuery);
