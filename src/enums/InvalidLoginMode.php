<?php
namespace Craft;

/**
 * Class InvalidLoginMode
 *
 * @package craft.app.enums
 */
abstract class InvalidLoginMode extends BaseEnum
{
	const Cooldown = 'cooldown';
	const Lockout  = 'lockout';
}
