<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use Craft;
use craft\base\ElementInterface;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use Mcp\Exception\ToolCallException;

/**
 * Global sets’ content can be read and updated. The sets themselves are configuration, created, renamed, and deleted
 * in Settings → Globals.
 *
 * @since 5.12.1
 */
class GlobalSetAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'globalSets';
    }

    public static function elementType(): string
    {
        return GlobalSet::class;
    }

    protected function criteriaProperties(): array
    {
        return [
            'handle' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Global set handle or handles.'],
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Global sets have no editable attributes. Update their custom fields with fields.',
            'maxProperties' => 0,
        ];
    }

    protected function notes(): array
    {
        return ['Only global set content can be updated. Global sets are created, renamed, and deleted in Settings → Globals, so they can’t be created, deleted, restored, or duplicated here.'];
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        if ($element === null) {
            throw new ToolCallException('Provide a global set ID or UID.');
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }

    /** @param GlobalSet $element */
    protected function authorizeSave(User $actor, ElementInterface $element): void
    {
        if (Craft::$app->getIsMultiSite() && !$actor->can("editSite:{$element->getSite()->uid}")) {
            throw new ToolCallException('You are not authorized to edit global sets for this site.');
        }

        parent::authorizeSave($actor, $element);
    }
}
