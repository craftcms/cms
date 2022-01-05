describe("Garnish.Menu tests", function() {

	var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
		$ul = $('<ul/>').appendTo($menu);

	var $anchor = $('<div class="anchor"></div>').appendTo(Garnish.$bod);
	
	var menu = new Garnish.Menu($menu, {
		anchor: $anchor,
	});

	it("Should instantiate the Menu.", function() {
		expect(menu.menuId).toEqual('menu' + menu._namespace);
	});

	it("Should show the Menu.", function() {
		menu.show();

		expect(menu.$container.css('opacity')).toEqual('1');
		expect(menu.$container.css('display')).toEqual('block');
		expect(menu.$menuList.attr('aria-hidden')).toEqual('false');
	});

	it("Should hide the Menu.", function() {
		menu.hide();

		setTimeout(function() {
			expect(menu.$container.css('opacity')).toEqual('0');
			expect(menu.$container.css('display')).toEqual('none');
		}, Garnish.FX_DURATION);

		expect(menu.$menuList.attr('aria-hidden')).toEqual('true');
	});

});