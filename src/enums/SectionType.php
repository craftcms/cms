<?php
namespace Craft;

/**
 * The SectionType class is an abstract class that defines the different section types available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     1.2
 */
abstract class SectionType extends BaseEnum
{
	// Constants
	// =========================================================================

	const Single    = 'single';
	const Channel   = 'channel';
	const Structure = 'structure';
}
