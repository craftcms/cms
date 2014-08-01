<?php
namespace Craft;

/**
 * Class CraftPackage
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class CraftPackage extends BaseEnum
{
	const PublishPro = 'PublishPro';
	const Users      = 'Users';
	const Localize   = 'Localize';
	const Cloud      = 'Cloud';
	const Rebrand    = 'Rebrand';
}
