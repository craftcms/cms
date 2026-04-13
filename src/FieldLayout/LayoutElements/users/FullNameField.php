<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\users;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\FullNameField as BaseFullNameField;
use CraftCms\Cms\User\Elements\User;
use InvalidArgumentException;
use Override;

class FullNameField extends BaseFullNameField
{
    #[Override]
    public bool $mandatory = true;

    #[Override]
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        if (! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        return [
            'autocomplete' => $element->getIsCurrent() ? 'name' : 'off',
        ];
    }
}
