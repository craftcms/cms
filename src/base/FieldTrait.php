<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\models\FieldGroup;

/**
 * FieldTrait implements the common methods and properties for field classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait FieldTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var integer The field’s ID
	 */
	public $id;

	/**
	 * @var integer The field’s group’s ID
	 */
	public $groupId;

	/**
	 * @var string The field’s name
	 */
	public $name;

	/**
	 * @var string The field’s handle
	 */
	public $handle;

	/**
	 * @var string The field’s context
	 */
	public $context;

	/**
	 * @var string The field’s instructions
	 */
	public $instructions;

	/**
	 * @var boolean Whether the field is required
	 */
	public $required = false;

	/**
	 * @var boolean Whether the field is translatable
	 */
	public $translatable = false;

	/**
	 * @var string The field’s previous handle
	 */
	public $oldHandle;

	/**
	 * @var string The field’s content column prefix
	 */
	public $columnPrefix;

	/**
	 * @var ElementInterface|Element The element that the field is currently associated with
	 */
	public $element;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the field’s group.
	 *
	 * @return FieldGroup
	 */
	public function getGroup()
	{
		return Craft::$app->fields->getGroupById($this->groupId);
	}
}
