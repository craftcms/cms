(function($) {
$(document).ready(function() {

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
	showError(Blocks.t('Unable to determine what to update.'));
	return;
}

function getUpdateInfo()
{
	// get the name and latest version
	var data = {
		handle: updateHandle
	};

	Blocks.postActionRequest('update/getUpdates', data, function(response) {
		if (response.success)
		{
			if (response.updateInfo)
			{
				updateInfo = data.updateInfo;
				totalUpdates = updateInfo.length;

				updateNext();
			}
			else
			{
				showSuccess(Blocks.t('You’re already up-to-date.'));
			}
		}
		else
		{
			var error = (data.error || Blocks.t('An unknown error occurred.'));
			showError(error)
		}
	});
}

function updateNext()
{
	updating++;

	$status.html('Updating '+updateInfo[updating].name+' to version '+updateInfo[updating].version+' ('+updating+' of '+totalUpdates+')');

	var data = {
		handle: updateInfo[updating].handle
	};

	Blocks.postActionRequest('update/runAutoUpdate', data, function(data, textStatus) {
		if (!data || textStatus != 'success')
		{
			showError(Blocks.t('An unknown error occurred while updating {name}.', {'name': updateInfo[updating].name}));
			return;
		}

		if (data.error)
		{
			showError(data.error);
			return;
		}

		if (updating == totalUpdates-1)
		{
			showSuccess(Blocks.t('All done!'));

			// Redirect to the Dashboard in half a second
			setTimeout(function() {
				window.location = Blocks.baseUrl+'dashboard';
			}, 500);

			return;
		}

		updateNext();
	});
}

getUpdateInfo();

});
})(jQuery);
