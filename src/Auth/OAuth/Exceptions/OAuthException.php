<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Exceptions;

use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\User\Elements\User;
use RuntimeException;
use Throwable;

final class OAuthException extends RuntimeException
{
    public function __construct(
        string $message = 'Auth error',
        public readonly ?User $user = null,
        public readonly ?AuthError $authError = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
