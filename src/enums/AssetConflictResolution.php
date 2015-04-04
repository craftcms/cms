<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The AssetConflictResolution class is an abstract class that defines all of the Asset conflict resolution options that
 * are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class AssetConflictResolution extends BaseEnum
{
	// Constants
	// =========================================================================

	const KeepBoth = 'keepBoth';
	const Replace  = 'replace';
	const Cancel   = 'cancel';
}
