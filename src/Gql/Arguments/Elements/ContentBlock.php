<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Support\Facades\Gql;

/**
 * @since 6.0.0
 */
class ContentBlock extends ElementArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), static::queryParamArguments(ContentBlockElement::class, [
            'field',
            'fieldId',
            'primaryOwnerId',
            'ownerId',
        ]));
    }

    #[\Override]
    public static function getContentArguments(): array
    {
        return Gql::getOrSetContentArguments(ContentBlockElement::class, function (): array {
            /** @var ContentBlockField[] $fields */
            $fields = app(Fields::class)->getFieldsByType(ContentBlockField::class);

            $arguments = [];
            foreach ($fields as $field) {
                $arguments += Gql::getFieldLayoutArguments($field->getFieldLayout());
            }

            return $arguments;
        });
    }
}
