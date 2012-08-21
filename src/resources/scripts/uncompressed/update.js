(function($) {

var $status = $('#status'),
	updateInfo,
	totalUpdates,
	updating = -1;

function showError(msg)
{
	$status.addClass('error');
	$status.html(msg);
}

function showSuccess(msg)
{
	$status.addClass('success');
	$status.html(msg);
}


// make sure an update handle was provided
if (!updateHandle)
{
	showError(blx.t('Unable to determine what to update.'));
	return;
}

function getUpdateInfo()
{
	// get the name and latest version
	$.getJSON(updateInfoUrl + updateHandle, function(data, textStatus) {
		if (!data || textStatus != 'success')
		{
			showError(blx.t('An unknown error occurred.'));
			return;
		}

		if (data.error)
		{
			showError(data.error);
			return;
		}

		if (!data.updateInfo)
		{
			showSuccess(blx.t('You’re already up-to-date.'));
			return;
		}

		updateInfo = data.updateInfo;
		totalUpdates = updateInfo.length;

		updateNext();
	});
}

function updateNext()
{
	updating++;

	$status.html('Updating '+updateInfo[updating].name+' to version '+updateInfo[updating].version+' ('+updating+' of '+totalUpdates+')');

	$.post(updateUrl + updateInfo[updating].handle, function(data, textStatus) {
		if (!data || textStatus != 'success')
		{
			showError(blx.t('An unknown error occurred while updating {name}.', {'name': updateInfo[updating].name}));
			return;
		}

		if (data.error)
		{
			showError(data.error);
			return;
		}

		if (updating == totalUpdates-1)
		{
			showSuccess(blx.t('All done!'));

			// Redirect to the Dashboard in half a second
			setTimeout(function() {
				window.location = blx.baseUrl+'dashboard';
			}, 500);

			return;
		}

		updateNext();
	});
}

getUpdateInfo();

})(jQuery);
