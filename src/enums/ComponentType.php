<?php
namespace Craft;

/**
 * Class ComponentType
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class ComponentType extends BaseEnum
{
	const AssetSource = 'assetSource';
	const Element     = 'element';
	const Field       = 'field';
	const Task        = 'task';
	const Tool        = 'tool';
	const Widget      = 'widget';
}
