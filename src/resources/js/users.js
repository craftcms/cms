(function($) {


var $table = $('#groups'),
	totalGroups = $table.children('tbody').children().length;


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		groupName = $row.children(':first').children('a').text();

	var message = Blocks.t('Are you sure you want to delete the group “{group}”?', { group: groupName });

	if (confirm(message))
	{
		var data = {
			groupId: $row.attr('data-group-id')
		};

		$.post(Blocks.actionUrl+'userGroups/deleteGroup', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalGroups--;
				if (totalGroups == 0)
				{
					$table.remove();
					$('#nogroups').show();
				}

				Blocks.cp.displayNotice(Blocks.t('Group deleted.'));
			}
			else
			{
				Blocks.cp.displayError(Blocks.t('Couldn’t delete group.'));
			}
		});
	}
});


})(jQuery);
