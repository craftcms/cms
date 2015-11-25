<?php
namespace Craft;

/**
 * The PatchManifestFileAction class is an abstract class that defines all of the different path manifest file actions
 * that are available in Craft during an auto-update.
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
abstract class PatchManifestFileAction extends BaseEnum
{
	// Constants
	// =========================================================================

	const Add    = 'Add';
	const Remove = 'Remove';
}
