<?php
namespace Craft;

/**
 * The CraftPackage class is an abstract class that defines all of the packages that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.enums
 * @since      1.0
 * @deprecated This class has been deprecated since Craft 2.0 since Craft no
 *             longer has packages.
 */
abstract class CraftPackage extends BaseEnum
{
	// Constants
	// =========================================================================

	const PublishPro = 'PublishPro';
	const Users      = 'Users';
	const Localize   = 'Localize';
	const Cloud      = 'Cloud';
	const Rebrand    = 'Rebrand';

	// Public Methods
	// =========================================================================

	/**
	 * @return CraftPackage
	 */
	public function __construct()
	{
		craft()->deprecator->log('CraftPackage', 'The CraftPackage enum has been deprecated since Craft no long has packages.');
	}
}
