describe("Garnish.CheckboxSelect tests", function() {

	var $container = $('<div />');
		$divAll = $('<div />').appendTo($container);
		$all = $('<input type="checkbox" class="all" />').appendTo($divAll);
		$divOption1 = $('<div />').appendTo($container);
		$option1 = $('<input type="checkbox" />').appendTo($divOption1);
		$divOption2 = $('<div />').appendTo($container);
		$option2 = $('<input type="checkbox" />').appendTo($divOption2);

	var checkboxSelect = new Garnish.CheckboxSelect($container);

	it("$all should be defined", function() {
		expect(checkboxSelect.$all.get(0)).toBeDefined();
	});

	it("$options length should be greater than 0", function() {
		expect(checkboxSelect.$options.length).toBeGreaterThan(0);
	});

	it("all options should be checked", function() {
		checkboxSelect.$all.prop('checked', true);
		checkboxSelect.$all.trigger('change');

		var $option = $(checkboxSelect.$options[0]);

		expect($option.prop('checked')).toBe(true);
	});

	it("Instantiating the checkbox select a second time should destroy the first instance and create a new one", function() {

		var checkboxSelect2 = new Garnish.CheckboxSelect($container);

		expect(checkboxSelect._namespace).not.toEqual(checkboxSelect2._namespace);
	});

});