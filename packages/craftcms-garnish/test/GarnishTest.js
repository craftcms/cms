describe("Garnish tests", function() {

	it("Checks whether a variable is an array.", function() {

		var mockArray = ['row 1', 'row 2'];

		expect(Garnish.isArray(mockArray)).toBe(true);
	});

	it("Checks whether a variable is a string.", function() {

		var mockString = "Dummy string";

		expect(Garnish.isString(mockString)).toBe(true);
	});

	it("Checks whether an element has an attribute.", function() {

		var $element = $('<div class="someclass"></div>');

		expect(Garnish.hasAttr($element, 'class')).toBe(true);
	});

});