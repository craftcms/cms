<?php
namespace Craft;

/**
 * Class InvalidLoginMode
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class InvalidLoginMode extends BaseEnum
{
	const Cooldown = 'cooldown';
	const Lockout  = 'lockout';
}
