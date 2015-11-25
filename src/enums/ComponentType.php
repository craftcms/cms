<?php
namespace Craft;

/**
 * The ComponentType class is an abstract class that defines all of the component types that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class ComponentType extends BaseEnum
{
	// Constants
	// =========================================================================

	const AssetSource   = 'assetSource';
	const Element       = 'element';
	const ElementAction = 'elementAction';
	const Field         = 'field';
	const Task          = 'task';
	const Tool          = 'tool';
	const Widget        = 'widget';
}
