(function($) {


Blocks.RecentEntriesWidget = Blocks.Base.extend({

	params: null,
	$widget: null,
	$body: null,
	$container: null,
	$tbody: null,

	init: function(widgetId, params)
	{
		this.params = params;
		this.$widget = $('#widget'+widgetId);
		this.$body = this.$widget.find('.body:first');
		this.$container = this.$widget.find('.container:first');
		this.$tbody = this.$container.find('tbody:first');

		Blocks.RecentEntriesWidget.instances.push(this);
	},

	addEntry: function(entry)
	{
		this.$container.css('margin-top', 0);
		var oldHeight = this.$container.height();

		this.$tbody.prepend(
			'<tr>' +
				'<td>' +
					'<a href="'+entry.url+'">'+entry.title+'</a> ' +
					'<span class="light">' +
						entry.postDate +
						(entry.username ? Blocks.t('by {author}', { author: entry.username }) : '') +
					'</span>' +
				'</td>' +
			'</tr>'
		);

		var newHeight = this.$container.height(),
			heightDiff = newHeight - oldHeight;

		this.$container.css('margin-top', -heightDiff);
		this.$container.animate({ 'margin-top': 0 });
	}
}, {
	instances: []
});


})(jQuery);
