<?php
namespace Blocks;

/**
 *
 */
class RecentActivityWidget extends BaseWidget
{
	public $name = 'Recent Activity';

	public $actions = array();

	protected $bodyTemplate = '_widgets/RecentActivityWidget/body';

	/**
	 * Gets the widget title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return 'Recent Activity';
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
				'action' => '<a>Brandon</a> is editing <a>Blocks</a>',
				'date' => 'right now'
			),
			array(
				'action' => '<a>Brandon</a> published a new version of <a>Assets</a>',
				'date' => 'yesterday'
			),
			array(
				'action' => '<a>Brad</a> updated Blocks and Wygwam</a>',
				'date' => 'Sep 5, 2011'
			)
		);

		return parent::getBody();
	}
}
