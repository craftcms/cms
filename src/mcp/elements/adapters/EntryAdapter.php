<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\elements\User;
use craft\mcp\Authorization;
use craft\mcp\ElementLifecycle;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use craft\mcp\serializers\ElementSerializer;
use Illuminate\Support\Arr;
use Mcp\Exception\ToolCallException;
use yii\base\InvalidConfigException;

/**
 * @since 5.12.0
 */
class EntryAdapter extends BaseElementAdapter
{
    private const DuplicateModes = ['unpublished', 'canonical'];

    public function __construct(
        ElementSerializer $elementSerializer,
        ElementLifecycle $lifecycle,
    ) {
        parent::__construct($elementSerializer, $lifecycle);
    }

    public static function handle(): string
    {
        return 'entries';
    }

    public static function elementType(): string
    {
        return Entry::class;
    }

    protected function criteriaProperties(): array
    {
        return ElementQueryCriteria::EntrySchemaProperties;
    }

    protected function createAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sectionId' => ['type' => 'integer', 'description' => 'Section ID.'],
                'typeId' => ['type' => 'integer', 'description' => 'Entry type ID. Defaults to the section’s first entry type.'],
                'siteId' => ['type' => 'integer', 'description' => 'Site ID.'],
                ...$this->sharedAttributeProperties(),
                'authorId' => ['type' => ['integer', 'null'], 'description' => 'Author user ID. Defaults to the acting user.'],
            ],
            'required' => ['sectionId'],
            'additionalProperties' => false,
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'typeId' => ['type' => 'integer', 'description' => 'Entry type ID.'],
                ...$this->sharedAttributeProperties(),
            ],
            'additionalProperties' => false,
        ];
    }

    protected function fieldSchemaContextSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'For a new entry, provide sectionId. For an existing entry, typeId previews another entry type’s layout.',
            'properties' => [
                'sectionId' => ['type' => 'integer', 'description' => 'Section ID of a new entry.'],
                'typeId' => ['type' => 'integer', 'description' => 'Entry type ID. Defaults to the section’s first entry type.'],
                'siteId' => ['type' => 'integer', 'description' => 'Site ID of a new entry.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function duplicateModes(): array
    {
        return self::DuplicateModes;
    }

    protected function notes(): array
    {
        return [
            'elements.get/update/field-schema load canonical entries, excluding drafts and revisions. Use drafts.list to inspect saved drafts.',
            'Duplicating defaults to an unpublished draft. The canonical mode preserves the enabled state. Nested entries keep their owner.',
        ];
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, User $actor): ElementInterface
    {
        if ($element === null && !isset($context['sectionId'])) {
            throw new ToolCallException('Provide an entry ID or UID, or provide context.sectionId for a new entry.');
        }

        if ($element !== null && isset($context['sectionId'])) {
            throw new ToolCallException('Provide context.sectionId only for a new entry.');
        }

        if ($element instanceof Entry) {
            $this->authorizeSave($actor, $element);

            if (isset($context['typeId'])) {
                $element->typeId = $context['typeId'];
                $element->fieldLayoutId = null;
            }
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }

    public function duplicate(ElementInterface $element, ?string $mode): ElementInterface
    {
        $mode ??= 'unpublished';

        if (!in_array($mode, self::DuplicateModes, true)) {
            throw new ToolCallException('Duplication mode must be unpublished or canonical.');
        }

        return $this->lifecycle->duplicate($element, asUnpublishedDraft: $mode === 'unpublished');
    }

    protected function findQuery(): ElementQueryInterface
    {
        return Entry::find()->status(null);
    }

    protected function newElement(array $context, User $actor): ElementInterface
    {
        $entry = new Entry();

        if ($context === []) {
            return $entry;
        }

        Craft::configure($entry, Arr::only($context, ['sectionId', 'typeId', 'siteId']));
        $this->defaultEntryType($entry);
        $entry->fieldLayoutId = null;
        $entry->setAuthorId($actor->id);

        return $entry;
    }

    /** @param Entry $element */
    protected function populate(ElementInterface $element, array $attributes, array $fields, User $actor): void
    {
        $authorIdProvided = array_key_exists('authorId', $attributes);
        $authorIdsProvided = array_key_exists('authorIds', $attributes);

        if ($authorIdProvided && $authorIdsProvided) {
            throw new ToolCallException('Provide only one of: authorId, authorIds.');
        }

        $authorId = Arr::pull($attributes, 'authorId');
        $authorIds = Arr::pull($attributes, 'authorIds');

        Craft::configure($element, $attributes);
        $this->defaultEntryType($element);
        $element->fieldLayoutId = null;

        if ($authorIdProvided || $authorIdsProvided) {
            if (!Authorization::allows($actor, 'changeAuthor', $element)) {
                throw new ToolCallException('You are not authorized to change this entry author.');
            }

            $element->setAuthorIds($authorIdsProvided ? $authorIds : $authorId);
        } elseif (!$element->id && $actor->id !== null) {
            $element->setAuthorId($actor->id);
        }

        $this->setFieldValues($element, $fields);
    }

    /** @param Entry $element */
    protected function authorizeSave(User $actor, ElementInterface $element): void
    {
        if (Craft::$app->getIsMultiSite() && !$actor->can("editSite:{$element->getSite()->uid}")) {
            throw new ToolCallException('You are not authorized to edit entries for this site.');
        }

        parent::authorizeSave($actor, $element);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sharedAttributeProperties(): array
    {
        return [
            'title' => ['type' => ['string', 'null'], 'description' => 'Entry title.'],
            'slug' => ['type' => ['string', 'null'], 'description' => 'Entry slug.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the entry is enabled.'],
            'authorId' => ['type' => ['integer', 'null'], 'description' => 'Author user ID.'],
            'authorIds' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Author user IDs.'],
            'postDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Post date.'],
            'expiryDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Expiry date.'],
            'parentId' => ['type' => ['integer', 'null'], 'description' => 'Parent entry ID for structured sections.'],
        ];
    }

    private function defaultEntryType(Entry $entry): void
    {
        try {
            $entry->typeId ??= $entry->getAvailableEntryTypes()[0]->id;
        } catch (InvalidConfigException $exception) {
            throw new ToolCallException('Entry section or type is invalid.', previous: $exception);
        }
    }
}
