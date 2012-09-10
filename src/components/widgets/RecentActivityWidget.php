<?php
namespace Blocks;

/**
 *
 */
class RecentActivityWidget extends BaseWidget
{
	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Recent Activity');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		return TemplateHelper::render('_components/widgets/RecentActivityWidget/body', array(
			'actions' => $this->_getActions()
		));
	}

	/**
	 * Gets the recent user actions.
	 *
	 * @access private
	 * @return array
	 */
	private function _getActions()
	{
		return array(
			array(
				'action' => '<a>Brandon</a> is editing <a>@@@productDisplay@@@</a>',
				'date' => 'right now'
			),
			array(
				'action' => '<a>Brandon</a> published a new version of <a>Assets</a>',
				'date' => 'yesterday'
			),
			array(
				'action' => '<a>Brad</a> updated @@@productDisplay@@@ and Wygwam</a>',
				'date' => 'Sep 5, 2011'
			)
		);
	}
}
