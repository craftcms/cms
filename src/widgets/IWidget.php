<?php
namespace Craft;

/**
 * Interface IWidget
 *
 * @package craft.app.widgets
 */
interface IWidget extends ISavableComponentType
{
	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string
	 */
	public function getBodyHtml();
}
