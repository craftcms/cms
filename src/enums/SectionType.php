<?php
namespace Craft;

/**
 * Class SectionType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class SectionType extends BaseEnum
{
	const Single    = 'single';
	const Channel   = 'channel';
	const Structure = 'structure';
}
