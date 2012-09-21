(function($) {


var $table = $('#sections'),
	totalSections = $table.children('tbody').children().length;


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		sectionName = $row.children(':first').children('a').text();

	var message = Blocks.t('Are you sure you want to delete the section “{section}”?', { section: sectionName });

	if (confirm(message))
	{
		var data = {
			sectionId: $row.attr('data-section-id')
		};

		$.post(Blocks.actionUrl+'content/deleteSection', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalSections--;
				if (totalSections == 0)
				{
					$table.remove();
					$('#nosections').show();
				}

				Blocks.cp.displayNotice(Blocks.t('Section deleted.'));
			}
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t delete section.'));
		});
	}
});


})(jQuery);
