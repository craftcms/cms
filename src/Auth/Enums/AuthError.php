<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Enums;

enum AuthError: string
{
    case InvalidCredentials = 'invalid_credentials';
    case PendingVerification = 'pending_verification';
    case AccountLocked = 'account_locked';
    case AccountCooldown = 'account_cooldown';
    case PasswordResetRequired = 'password_reset_required';
    case AccountSuspended = 'account_suspended';
    case NoCpAccess = 'no_cp_access';
    case NoCpOfflineAccess = 'no_cp_offline_access';
    case NoSiteOfflineAccess = 'no_site_offline_access';
}
