<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * SavableComponent is the base class for classes representing savable Craft components in terms of objects.
 *
 * @property string $type The class name that should be used to represent the field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class SavableComponent extends Component implements SavableComponentInterface
{
	// Traits
	// =========================================================================

	use SavableComponentTrait;

	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function isSelectable()
	{
		return true;
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * @inheritdoc
	 */
	public function getSettings()
	{
		$settings = [];

		foreach ($this->settingsAttributes() as $attribute)
		{
			$settings[$attribute] = $this->$attribute;
		}

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function settingsAttributes()
	{
		$class = new \ReflectionClass($this);
		$names = [];

		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
		{
			if (!$property->isStatic() && $property->getDeclaringClass()->getName() === static::className())
			{
				$names[] = $property->getName();
			}
		}

		return $names;
	}
}
