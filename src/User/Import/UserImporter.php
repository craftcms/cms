<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into User elements.
 *
 * @since 6.0.0
 */
class UserImporter extends ElementImporter
{
    /**
     * The sensitive user attributes that can be imported. They don’t grant any privileges.
     */
    private const array IMPORTABLE_SENSITIVE_ATTRIBUTES = [
        'affiliatedSiteId',
        'email',
        'passwordResetRequired',
        'photoId',
    ];

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

        $this->resolveDefaultFieldLayout();
    }

    /**
     * Users have one field layout for the element type, so there's nothing for the step to
     * choose — resolve it as soon as the importer is built.
     */
    #[Override]
    public function resolveDefaultFieldLayout(): void
    {
        $fieldLayout = Fields::getLayoutByType(User::class, false);
        $this->fieldLayout($fieldLayout ? $fieldLayout->uid : User::class);
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes, array $data): void
    {
        // sensitive user attributes (e.g. admin) can't be imported, apart from the ones the importer offers
        parent::setAttributesForImport(
            $element,
            Arr::except($attributes, array_diff(User::SENSITIVE_ATTRIBUTES, self::IMPORTABLE_SENSITIVE_ATTRIBUTES)),
            $data,
        );
    }
}
