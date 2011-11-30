(function($) {


window.Update = Base.extend({

	dom: null,
	expanded: false,

	constructor: function(div, i)
	{
		this.dom = {};
		this.dom.$update = $(div);
		this.dom.$toggle = $('.notes-toggle', this.dom.$update);
		this.dom.$notesContainer = $('.notes-container', this.dom.$update);
		this.dom.$notes = $('.notes', this.dom.$notesContainer);

		this.dom.$toggle.on('click.update', $.proxy(this, 'toggle'));

		setTimeout($.proxy(this, 'fadeIn'), i * 100);
	},

	fadeIn: function()
	{
		this.dom.$update.fadeIn();
	},

	toggle: function()
	{
		if (!this.expanded)
			this.expand();
		else
			this.collapse();
	},

	expand: function()
	{
		var height = this.dom.$notes.outerHeight();
		this.dom.$notesContainer.stop().animate({height: height}, $.proxy(function() {
			//this.dom.$notesContainer.height('auto');
		}, this));

		this.dom.$toggle.html('Hide release notes');
		this.expanded = true;
	},

	collapse: function()
	{
		this.dom.$notesContainer.stop().animate({height: 0});
		this.dom.$toggle.html('Show release notes');
		this.expanded = false;
	}
});


var updatesUrl = blx.baseUrl+'/settings/updates/updates';
$('#updates').load(updatesUrl, function() {
	$('#checking').fadeOut();
	$('#updates').fadeIn(function() {
		$('.update', this).each(function(i) {
			var update = new Update(this, i);
		});
	});
});


})(jQuery);
