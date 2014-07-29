<?php
namespace Craft;

/**
 * Class PatchManifestFileAction
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class PatchManifestFileAction extends BaseEnum
{
	const Add    = 'Add';
	const Remove = 'Remove';
}
