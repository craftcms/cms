(function($) {

/**
 * Deprecator class
 */
var Deprecator = Garnish.Base.extend(
{
	$clearAllBtn: null,
	$table: null,
	tracesModal: null,
	$tracesModalBody: null,

	init: function()
	{
		this.$clearAllBtn = $('#clearall');
		this.$table = $('#deprecationerrors');
		this.$noLogsMessage = $('#nologs');

		this.addListener(this.$clearAllBtn, 'click', 'clearAllLogs');
		this.addListener(this.$table.find('.viewtraces'), 'click', 'viewLogTraces');
		this.addListener(this.$table.find('.delete'), 'click', 'deleteLog');
	},

	clearAllLogs: function()
	{
		Craft.postActionRequest('utils/deleteAllDeprecationErrors');
		this.onClearAll();
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

		if ($tr.siblings().length)
		{
			$tr.remove();
		}
		else
		{
			this.onClearAll();
		}
	},

	onClearAll: function()
	{
		this.$clearAllBtn.parent().remove();
		this.$table.remove();
		this.$noLogsMessage.removeClass('hidden');
	}
});

new Deprecator();

})(jQuery);
