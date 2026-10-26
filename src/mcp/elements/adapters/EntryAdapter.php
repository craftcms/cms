<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\elements\User;
use craft\mcp\Authorization;
use craft\mcp\ElementLifecycle;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use craft\mcp\serializers\ElementSerializer;
use craft\models\ElementActivity;
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
            'description' => 'For a new entry, provide sectionId. For an existing entry or draft, typeId previews another entry type’s layout.',
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
            'elements.create with asUnpublishedDraft: true saves a new entry as an unpublished draft instead of a live entry, optionally named with draftName and draftNotes. The result sets savedAsDraft and explains the next step.',
            'elements.get/update/field-schema load canonical entries and saved drafts by their own element ID or UID, excluding provisional drafts and revisions. A canonical ID selects the original entry; a draft ID selects that draft. Updating a draft saves only the draft and leaves the canonical entry unchanged until the draft is applied with drafts.apply. To find drafts of an entry, use drafts.list with the canonical ID.',
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

    public function createUnpublishedDraft(array $attributes, array $fields, User $actor, ?string $draftName = null, ?string $draftNotes = null): array
    {
        $entry = $this->prepareCreate($attributes, $fields, $actor);

        if (!Authorization::allows($actor, 'createDrafts', $entry)) {
            throw new ToolCallException('You are not authorized to create drafts of this entry.');
        }

        $entry->setScenario(Element::SCENARIO_ESSENTIALS);

        if (!Craft::$app->getDrafts()->saveElementAsDraft($entry, $actor->id, $draftName, $draftNotes, markAsSaved: true)) {
            throw $this->saveFailed($entry);
        }

        Craft::$app->getElements()->trackActivity($entry, ElementActivity::TYPE_SAVE, $actor);

        return $this->draftResult($entry, "The entry was saved as an unpublished draft (ID {$entry->id}). It is not live until the draft is applied.");
    }

    protected function findQuery(): ElementQueryInterface
    {
        return Entry::find()->drafts(null)->status(null);
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

    protected function save(ElementInterface $element, User $actor): array
    {
        if (!$element->getIsDraft()) {
            return parent::save($element, $actor);
        }

        $this->saveDraft($element, $actor);

        $summary = $element->getIsUnpublishedDraft()
            ? "Your changes were saved to unpublished draft {$element->id}. It is not live until the draft is applied."
            : "Your changes were saved to draft {$element->id}. Entry {$element->getCanonicalId()} is unchanged until the draft is applied.";

        return $this->draftResult($element, $summary);
    }

    /**
     * Saves changes to an existing draft without creating another draft, as the control panel does.
     */
    private function saveDraft(ElementInterface $draft, User $actor): void
    {
        $lockKey = "element:$draft->id";
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lockKey, 15)) {
            throw new ToolCallException('Could not acquire a lock to save the entry.');
        }

        try {
            $draft->setScenario(Element::SCENARIO_ESSENTIALS);
            $saved = Craft::$app->getElements()->saveElement($draft);
        } finally {
            $mutex->release($lockKey);
        }

        if (!$saved) {
            throw $this->saveFailed($draft);
        }

        Craft::$app->getElements()->trackActivity($draft, ElementActivity::TYPE_SAVE, $actor);
    }

    /**
     * @return array{element: array<string, mixed>, savedAsDraft: true, instructions: string}
     */
    private function draftResult(ElementInterface $draft, string $summary): array
    {
        return [
            'element' => $this->serialize($draft),
            'savedAsDraft' => true,
            'instructions' => "$summary Use the draft ID with elements.get, elements.update, and the drafts.* tools. Publish it with drafts.apply when it is ready.",
        ];
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
