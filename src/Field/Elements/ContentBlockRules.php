<?php

declare(strict_types=0);

namespace CraftCms\Cms\Field\Elements;

use CraftCms\Cms\Element\Validation\ElementRules;
use Override;

/**
 * @extends ElementRules<ContentBlock>
 */
class ContentBlockRules extends ElementRules
{
    #[Override]
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            'fieldId' => ['nullable', 'integer'],
            'ownerId' => ['nullable', 'integer'],
            'primaryOwnerId' => ['nullable', 'integer'],
            'sortOrder' => ['nullable', 'integer'],
        ]);
    }
}
