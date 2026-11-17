<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Users;

use CraftCms\Cms\FieldLayout\LayoutElements\FullNameField as BaseFullNameField;
use Override;

/**
 * @since 6.0.0
 */
class FullNameField extends BaseFullNameField
{
    #[Override]
    public bool $mandatory = true;

    #[Override]
    protected function uiIcon(): ?string
    {
        return 'id-card';
    }
}
