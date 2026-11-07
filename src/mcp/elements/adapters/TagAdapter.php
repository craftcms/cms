<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use craft\base\ElementInterface;
use craft\elements\Tag;
use craft\elements\User;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.1
 */
class TagAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'tags';
    }

    public static function elementType(): string
    {
        return Tag::class;
    }

    protected function criteriaProperties(): array
    {
        return [
            'group' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Tag group handle or handles.'],
            'groupId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Tag group ID or IDs.'],
        ];
    }

    protected function createAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'groupId' => ['type' => 'integer', 'description' => 'Tag group ID.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'description' => 'Tag title.'],
            ],
            'required' => ['groupId', 'title'],
            'additionalProperties' => false,
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1, 'description' => 'Tag title.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function fieldSchemaContextSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'For a new tag, provide groupId.',
            'properties' => [
                'groupId' => ['type' => 'integer', 'description' => 'Tag group ID of a new tag.'],
            ],
            'additionalProperties' => false,
        ];
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        if (($element === null) === !isset($context['groupId'])) {
            throw new ToolCallException('Provide a tag ID or UID, or provide context.groupId for a new tag.');
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }
}
