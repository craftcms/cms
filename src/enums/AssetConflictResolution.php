<?php
namespace Craft;

/**
 * The AssetConflictResolution class is an abstract class that defines all of the Asset conflict resolution options that
 * are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     2.1
 */
abstract class AssetConflictResolution extends BaseEnum
{
	// Constants
	// =========================================================================

	const KeepBoth = 'keepBoth';
	const Replace  = 'replace';
	const Cancel   = 'cancel';
}
