<?php
namespace Craft;

/**
 *
 */
abstract class ComponentType extends BaseEnum
{
	const AssetSource = 'assetSource';
	const Element     = 'element';
	const Field       = 'field';
	const Tool        = 'tool';
	const Widget      = 'widget';
}
