<?php
namespace Craft;

/**
 *
 */
abstract class InvalidLoginMode extends BaseEnum
{
	const Cooldown          = 'cooldown';
	const Lockout           = 'lockout';
}
