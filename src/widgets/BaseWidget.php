<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use craft\app\components\BaseSavableComponentType;

/**
 * Widget base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseWidget extends BaseSavableComponentType implements WidgetInterface
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'Widget';

	/**
	 * Whether users should be able to select more than one of this widget type.
	 *
	 * @var bool
	 */
	protected $multi = true;

	/**
	 * How many columns the widget should span, if there's enough room.
	 *
	 * @var bool
	 */
	protected $colspan = 1;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		if ($this->multi || !craft()->dashboard->doesUserHaveWidget($this->getClassHandle()))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritDoc WidgetInterface::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		// Default to the widget name
		return $this->getName();
	}

	/**
	 * @inheritDoc WidgetInterface::getColspan()
	 *
	 * @return int
	 */
	public function getColspan()
	{
		return $this->colspan;
	}

	/**
	 * @inheritDoc WidgetInterface::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		return '<div style="margin: 0 -30px -30px;">' .
				'<img style="display: block; width: 100%;" src="'.UrlHelper::getResourceUrl('images/prg.jpg').'">' .
			'</div>';
	}
}
