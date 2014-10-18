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
	// Public Methods
	// =========================================================================

	/**
	 * Returns the component’s name.
	 *
	 * This is what your component will be called throughout the Control Panel.
	 *
	 * @return string The component’s name.
	 */
	public function getName();

	/**
	 * Returns the component’s handle, ideally based on the class name.
	 *
	 * @return string The component’s handle.
	 */
	public function getClassHandle();

	/**
	 * Returns whether this component should be shown when the user is creating a component of this type.
	 *
	 * @return bool Whether the component should be selectable.
	 */
	public function isSelectable();
}
