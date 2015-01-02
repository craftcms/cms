<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\components;

/**
 * ComponentTypeInterface.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ComponentTypeInterface
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
