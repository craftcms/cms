<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\User;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.1
 */
class CategoryAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'categories';
    }

    public static function elementType(): string
    {
        return Category::class;
    }

    protected function criteriaProperties(): array
    {
        return [
            'slug' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Category slug criteria.'],
            'group' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Category group handle or handles.'],
            'groupId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Category group ID or IDs.'],
        ];
    }

    protected function createAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'groupId' => ['type' => 'integer', 'description' => 'Category group ID.'],
                'siteId' => ['type' => 'integer', 'description' => 'Site ID.'],
                ...$this->sharedAttributeProperties(),
            ],
            'required' => ['groupId'],
            'additionalProperties' => false,
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->sharedAttributeProperties(),
            'additionalProperties' => false,
        ];
    }

    protected function fieldSchemaContextSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'For a new category, provide groupId.',
            'properties' => [
                'groupId' => ['type' => 'integer', 'description' => 'Category group ID of a new category.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function duplicateModes(): array
    {
        return ['copy'];
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        if (($element === null) === !isset($context['groupId'])) {
            throw new ToolCallException('Provide a category ID or UID, or provide context.groupId for a new category.');
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }

    public function duplicate(ElementInterface $element, ?string $mode): ElementInterface
    {
        if ($mode !== null && $mode !== 'copy') {
            throw new ToolCallException('Categories only support the copy duplication mode.');
        }

        return $this->lifecycle->duplicate($element);
    }

    /** @param Category $element */
    protected function authorizeSave(User $actor, ElementInterface $element): void
    {
        if (Craft::$app->getIsMultiSite() && !$actor->can("editSite:{$element->getSite()->uid}")) {
            throw new ToolCallException('You are not authorized to edit categories for this site.');
        }

        parent::authorizeSave($actor, $element);
    }

    /** @return array<string, array<string, mixed>> */
    private function sharedAttributeProperties(): array
    {
        return [
            'title' => ['type' => ['string', 'null'], 'description' => 'Category title.'],
            'slug' => ['type' => ['string', 'null'], 'description' => 'Category slug.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the category is enabled.'],
            'parentId' => ['type' => ['integer', 'null'], 'description' => 'Parent category ID.'],
        ];
    }
}
