<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Import;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Support\Facades\Fields;
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

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Users');
    }

    #[Override]
    public function storeSettings(array $settings): void
    {
        parent::storeSettings($settings);

        $fieldLayout = Fields::getLayoutByType(User::class, false);
        $this->fieldLayout($fieldLayout ? $fieldLayout->uid : User::class);
    }
}
