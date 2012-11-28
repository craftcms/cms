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
		$id = $this->model->id;

		$js = "new Blocks.UpdatesWidget({$id});";

		blx()->templates->includeJsResource('js/UpdatesWidget.js');
		blx()->templates->includeJs($js);

		return blx()->templates->render('_components/widgets/Updates/body');
	}
}
