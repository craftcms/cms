<?php
namespace craft\app\filesourcetypes;

use Craft;
use craft\app\components\BaseSavableComponentType;

/**
 * File source type base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.filesourcetypes
 * @since     3.0
 */
abstract class BaseFileSourceType extends BaseSavableComponentType implements IFileSourceType
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'FileSourceType';
}
