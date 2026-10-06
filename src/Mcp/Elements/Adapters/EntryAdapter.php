<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Elements\Adapters;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\ElementLifecycle;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Gate;
use Mcp\Exception\ToolCallException;
use RuntimeException;

/**
 * @since 6.0.0
 */
class EntryAdapter extends BaseElementAdapter
{
    private const array DuplicateModes = ['unpublished', 'canonical'];

    public function __construct(
        ElementSerializer $elementSerializer,
        UserInitiatedElementSave $userInitiatedElementSave,
        ElementLifecycle $lifecycle,
        private readonly Sites $sites,
    ) {
        parent::__construct($elementSerializer, $userInitiatedElementSave, $lifecycle);
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
            'In a section with an approval workflow, saving an enabled entry creates a draft. The result then sets savedAsDraft and suggests the next tool call.',
            'Duplicating defaults to the unpublished mode, which creates an unpublished draft. The canonical mode uses Craft’s normal enabled-state behavior. Revisions and provisional drafts cannot be duplicated, and nested entries keep their owner.',
        ];
    }

    public function fieldSchemaElement(?ElementInterface $element, array $context, CraftUser $actor): ElementInterface
    {
        if ($element === null && ! isset($context['sectionId'])) {
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

        if (! in_array($mode, self::DuplicateModes, true)) {
            throw new ToolCallException('Duplication mode must be unpublished or canonical.');
        }

        return $this->lifecycle->duplicate($element, asUnpublishedDraft: $mode === 'unpublished');
    }

    protected function findQuery(): ElementQueryInterface
    {
        return Entry::find()->status(null);
    }

    protected function newElement(array $context, CraftUser $actor): ElementInterface
    {
        $entry = new Entry;

        if ($context === []) {
            return $entry;
        }

        Typecast::configure($entry, Arr::only($context, ['sectionId', 'typeId', 'siteId']));
        $this->defaultEntryType($entry);
        $entry->fieldLayoutId = null;
        $entry->setAuthorId($actor->getCraftUserId());

        return $entry;
    }

    /** @param Entry $element */
    protected function populate(ElementInterface $element, array $attributes, array $fields, CraftUser $actor): void
    {
        $authorIdProvided = array_key_exists('authorId', $attributes);
        $authorIdsProvided = array_key_exists('authorIds', $attributes);

        if ($authorIdProvided && $authorIdsProvided) {
            throw new ToolCallException('Provide only one of: authorId, authorIds.');
        }

        $authorId = Arr::pull($attributes, 'authorId');
        $authorIds = Arr::pull($attributes, 'authorIds');

        Typecast::configure($element, $attributes);
        $this->defaultEntryType($element);
        $element->fieldLayoutId = null;

        if ($authorIdProvided || $authorIdsProvided) {
            if (! Gate::forUser($actor)->allows('changeAuthor', $element)) {
                throw new ToolCallException('You are not authorized to change this entry author.');
            }

            $element->setAuthorIds($authorIdsProvided ? $authorIds : $authorId);
        } elseif (! $element->id && $actor->getCraftUserId() !== null) {
            $element->setAuthorId($actor->getCraftUserId());
        }

        $element->setFieldValues($fields);
    }

    /** @param Entry $element */
    protected function authorizeSave(CraftUser $actor, ElementInterface $element): void
    {
        if ($this->sites->isMultiSite() && ! $actor->can("editSite:{$element->getSite()->uid}")) {
            throw new ToolCallException('You are not authorized to edit entries for this site.');
        }

        parent::authorizeSave($actor, $element);
    }

    protected function save(ElementInterface $element, CraftUser $actor): array
    {
        $saved = $this->saveElement($element, $actor);
        $result = ['element' => $this->serialize($saved)];

        if (! $saved->getIsDraft()) {
            return $result;
        }

        $instructions = $saved->getIsUnpublishedDraft()
            ? "This section requires approval, so the entry was saved as an unpublished draft (ID {$saved->id}) instead of being published."
            : "This section requires approval, so your changes were saved as a new draft (ID {$saved->id}). Entry {$saved->getCanonicalId()} is unchanged until the draft is approved and applied.";

        return [
            ...$result,
            'savedAsDraft' => true,
            'instructions' => $instructions.' Use the draft ID with the drafts.* and workflows.* tools; elements.get and elements.update do not load drafts. Submit the draft for review with workflows.submit, then publish it with drafts.apply once it is approved.',
            'nextToolCall' => [
                'name' => 'workflows.submit',
                'arguments' => ['type' => self::handle(), 'id' => $saved->id, 'siteId' => $saved->siteId],
            ],
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
        } catch (RuntimeException $exception) {
            throw new ToolCallException('Entry section or type is invalid.', previous: $exception);
        }
    }
}
