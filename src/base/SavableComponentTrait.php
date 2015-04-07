<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * SavableComponentTrait implements the common methods and properties for savable component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait SavableComponentTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var integer The component’s ID
	 */
	public $id;

	/**
	 * @var DateTime The date that the component was created
	 */
	public $dateCreated;

	/**
	 * @var DateTime The date that the component was last updated
	 */
	public $dateUpdated;
}
