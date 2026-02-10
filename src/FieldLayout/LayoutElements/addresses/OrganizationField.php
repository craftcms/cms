<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\addresses;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\Support\Arr;
use Override;

use function CraftCms\Cms\t;

class OrganizationField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public string $attribute = 'organization';

    /**
     * {@inheritdoc}
     */
    public bool $requirable = true;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        parent::__construct(Arr::except($config, [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Organization');
    }
}
