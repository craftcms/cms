(function($) {


/**
 * Section admin table class
 */
Blocks.ui.SectionAdminTable = Blocks.ui.AdminTable.extend({

	init: function()
	{
		this.base({
			tableSelector: '#sections',
			noObjectsSelector: '#nosections',
			deleteAction: 'sections/deleteSection'
		});
	},

	confirmDeleteObject: function($row)
	{
		var name = $row.attr(this.settings.nameAttribute),
			entries = parseInt($row.attr('data-entries'));

		if (!entries)
		{
			return confirm(Blocks.t(this.settings.confirmDeleteMessage, { name: name }));
		}
		else
		{

			return confirm(Blocks.t('Are you sure you want to delete “{name}” and its {entries} entries?', { name: name, entries: entries }));
		}

		return confirm(msg);
	}
});


})(jQuery);
