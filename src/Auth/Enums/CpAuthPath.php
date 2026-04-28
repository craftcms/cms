<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Enums;

enum CpAuthPath: string
{
    case Login = 'login';
    case TwoFactorChallenge = 'two-factor-challenge';
    case Logout = 'logout';
    case SetPassword = 'set-password';
    case VerifyEmail = 'verify-email';
    case Update = 'update';
}
