<?php
namespace Craft;

/**
 * Component type interface.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.components
 * @since     1.0
 */
interface IComponentType
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getClassHandle();

	/**
	 * @return bool
	 */
	public function isSelectable();
}
