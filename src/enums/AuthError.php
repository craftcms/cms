<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The AuthError class is an abstract class that defines all of the login errors that could occur.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class AuthError extends BaseEnum
{
	// Constants
	// =========================================================================

	const InvalidCredentials    = 'invalid_credentials';
	const PendingVerification   = 'pending_verification';
	const AccountLocked         = 'account_locked';
	const AccountCooldown       = 'account_cooldown';
	const PasswordResetRequired = 'password_reset_required';
	const AccountSuspended      = 'account_suspended';
	const NoCpAccess            = 'no_cp_access';
	const NoCpOfflineAccess     = 'no_cp_offline_access';
}
