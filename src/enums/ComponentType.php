<?php
namespace Craft;

/**
 * Class ComponentType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class ComponentType extends BaseEnum
{
	////////////////////
	// CONSTANTS
	////////////////////

	const AssetSource = 'assetSource';
	const Element     = 'element';
	const Field       = 'field';
	const Task        = 'task';
	const Tool        = 'tool';
	const Widget      = 'widget';
}
