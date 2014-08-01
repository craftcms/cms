<?php
namespace Craft;

/**
 * Interface ITool
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tools
 * @since     1.0
 */
interface ITool extends IComponentType
{
	/**
	 * @return string
	 */
	public function getIconValue();

	/**
	 * @return string
	 */
	public function getOptionsHtml();

	/**
	 * @return string
	 */
	public function getButtonLabel();

	/**
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array());
}
