<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Import;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into User elements.
 */
class UserImporter extends ElementImporter
{
    #[Override]
    public static function targetClass(): string
    {
        return User::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Users');
    }

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }
}
