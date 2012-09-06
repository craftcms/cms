<?php
namespace Blocks;

/**
 *
 */
class RecentActivityWidget extends BaseWidget
{
	public $actions = array();

	protected $bodyTemplate = '_components/widgets/RecentActivityWidget/body';

	/**
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Recent Activity');
	}

	/**
	 * Gets the widget body.
	 *
	 * @return string
	 */
	public function getBody()
	{
		$this->actions = array(
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

		return parent::getBody();
	}
}
