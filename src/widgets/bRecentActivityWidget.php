<?php

/**
 *
 */
class bRecentActivityWidget extends bWidget
{
	public $title = 'Recent Activity';
	public $className = 'recent_activity';

	/**
	 * @return mixed
	 */
	public function displayBody()
	{
		$tags = array(
			'actions' => array(
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
			)
		);

		return Blocks::app()->controller->loadTemplate('_widgets/RecentActivityWidget/body', $tags, true);
	}
}
