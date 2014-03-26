(function($) {

/**
 * Deprecator class
 */
var Deprecator = Garnish.Base.extend(
{
	$table: null,
	tracesModal: null,
	$tracesModalBody: null,

	init: function()
	{
		this.$table = $('#deprecationerrors');

		this.addListener(this.$table.find('.viewtraces'), 'click', 'viewLogTraces');
		this.addListener(this.$table.find('.delete'), 'click', 'deleteLog');
	},

	viewLogTraces: function(ev)
	{
		if (!this.tracesModal)
		{
			var $container = $('<div id="traces" class="modal loading"/>').appendTo(Garnish.$bod);
			this.$tracesModalBody = $('<div class="body"/>').appendTo($container);

			this.tracesModal = new Garnish.Modal($container, {
				resizable: true
			});
		}
		else
		{
			this.tracesModal.$container.addClass('loading');
			this.$tracesModalBody.empty();
			this.tracesModal.show();
		}

		var data = {
			logId: $(ev.currentTarget).closest('tr').data('id')
		};

		Craft.postActionRequest('utils/getDeprecationErrorTracesModal', data, $.proxy(function(response, textStatus)
		{
			this.tracesModal.$container.removeClass('loading');

			if (textStatus == 'success')
			{
				this.$tracesModalBody.html(response);
			}
		}, this));
	},

	deleteLog: function(ev)
	{
		var $tr = $(ev.currentTarget).closest('tr');

		var data = {
			logId: $tr.data('id')
		};

		Craft.postActionRequest('utils/deleteDeprecationError', data);

		$tr.remove();
	}
});

new Deprecator();

})(jQuery);
