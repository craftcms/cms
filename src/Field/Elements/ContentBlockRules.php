<?php

declare(strict_types=0);

namespace CraftCms\Cms\Field\Elements;

use CraftCms\Cms\Element\Validation\ElementRules;
use Override;

/**
 * @extends ElementRules<ContentBlock>
 *
 * @since 6.0.0
 */
class ContentBlockRules extends ElementRules
{
    /** @return array<string, mixed> */
    #[Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'fieldId' => ['nullable', 'integer'],
            'ownerId' => ['nullable', 'integer'],
            'primaryOwnerId' => ['nullable', 'integer'],
            'sortOrder' => ['nullable', 'integer'],
        ]);
    }
}
