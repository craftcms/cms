(function($) {


var $table = $('#sections'),
	totalSections = $table.children('thead').children().length;


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		sectionName = $row.children(':first').children('a').text();

	if (confirm(blx.t('Are you sure you want to delete the section “{section}”?', { section: sectionName })))
	{
		var data = {
			sectionId: $row.attr('data-section-id')
		};

		$.post(blx.actionUrl+'content/deleteSection', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalSections--;
				if (totalSections == 0)
				{
					$table.remove();
					$('#nosections').show();
				}

				blx.cp.displayNotice(blx.t('Section deleted.'));
			}
			else
				blx.cp.displayError(blx.t('Couldn’t delete section.'));
		});
	}
});


})(jQuery);
