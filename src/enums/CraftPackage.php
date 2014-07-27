<?php
namespace Craft;

/**
 * Class CraftPackage
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class CraftPackage extends BaseEnum
{
	const PublishPro = 'PublishPro';
	const Users      = 'Users';
	const Localize   = 'Localize';
	const Cloud      = 'Cloud';
	const Rebrand    = 'Rebrand';
}
