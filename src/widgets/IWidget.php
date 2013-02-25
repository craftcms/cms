<?php
namespace Craft;

/**
 * Widget interface
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
