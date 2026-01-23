<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Elements;

use CraftCms\Cms\Element\Validation\ElementRules;
use Override;

/**
 * @extends ElementRules<\CraftCms\Cms\Field\Elements\ContentBlock>
 */
final class ContentBlockRules extends ElementRules
{
    #[Override]
    protected function defineRules(): array
    {
        return [
            ...parent::defineRules(),
            [
                'fieldId' => ['nullable', 'integer'],
                'ownerId' => ['nullable', 'integer'],
                'primaryOwnerId' => ['nullable', 'integer'],
                'sortOrder' => ['nullable', 'integer'],
            ],
        ];
    }
}
