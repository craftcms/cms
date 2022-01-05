describe("Garnish.HUD tests", function() {

	var $trigger = $('<a href="#">Trigger</a>').appendTo(Garnish.$bod);
	var bodyContents = 'test';

	var hud = new Garnish.HUD($trigger, bodyContents);

	it("Should instantiate the HUD.", function() {

		hudInstantiated = false;

		if(hud)
		{
			hudInstantiated = true;
		}

		expect(hudInstantiated).toBe(true);
	});

});