/**
 * Oh what a privilege it is to be a web developer.
 */
Craft.SidebarPane = Garnish.Base.extend({

	$pane: null,
	$sidebar: null,
	$content: null,

	init: function(pane)
	{
		this.$pane = $(pane);
		this.$sidebar = this.$pane.children('.sidebar');

		this.updatePaneSize();
		this.addListener(this.$sidebar, 'resize', 'updatePaneSize');
	},

	updatePaneSize: function()
	{
		this.$pane.css('min-height', this.$sidebar.outerHeight());
	}
});
