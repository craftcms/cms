<?php
namespace Blocks;

/**
 *
 */
class UpdatesWidget extends BaseWidget
{
	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Updates');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		if (blx()->updates->isUpdateInfoCached())
		{
			return blx()->templates->render('_components/widgets/Updates/body', array(
				'total' => blx()->updates->getTotalNumberOfAvailableUpdates()
			));
		}
		else
		{
			blx()->templates->includeJsResource('js/UpdatesWidget.js');
			blx()->templates->includeJs('new Blocks.UpdatesWidget('.$this->model->id.');');

			return '<p class="centeralign">'.Blocks::t('Checking for updates…').'</p>';
		}
	}
}
