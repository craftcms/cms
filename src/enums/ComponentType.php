<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The ComponentType class is an abstract class that defines all of the component types that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class ComponentType extends BaseEnum
{
	// Constants
	// =========================================================================

	const AssetSource   = 'assetSource';
	const ElementAction = 'elementAction';
	const Field         = 'field';
	const Task          = 'task';
	const Tool          = 'tool';
	const Widget        = 'widget';
}
