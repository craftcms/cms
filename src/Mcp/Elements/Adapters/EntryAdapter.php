<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Elements\Adapters;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\ElementLifecycle;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Mcp\Exception\ToolCallException;
use RuntimeException;

/**
 * @since 6.0.0
 */
class EntryAdapter extends BaseElementAdapter
{
    private const array SharedAttributes = ['title', 'slug', 'enabled', 'authorId', 'authorIds', 'postDate', 'expiryDate', 'parentId'];

    private const array DuplicateModes = ['unpublished', 'canonical'];

    public function __construct(
        ElementSerializer $elementSerializer,
        UserInitiatedElementSave $userInitiatedElementSave,
        ElementLifecycle $lifecycle,
        private readonly Sites $sites,
        private readonly Drafts $drafts,
        private readonly Elements $elements,
        private readonly ElementActivity $elementActivity,
        private readonly Workflows $workflows,
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

    protected function createAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->propertySchemas(['sectionId', 'typeId', 'siteId', ...self::SharedAttributes], [
                'typeId' => 'Defaults to the section’s first entry type.',
                'authorId' => 'Defaults to the acting user.',
            ]),
            'required' => ['sectionId'],
            'additionalProperties' => false,
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->propertySchemas(['typeId', ...self::SharedAttributes]),
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
            'In a section with an approval workflow, saving an enabled entry creates a draft. The result then sets savedAsDraft and suggests the next tool call.',
            'elements.get/update/field-schema load canonical entries and saved drafts by their own element ID or UID, excluding provisional drafts and revisions. A canonical ID selects the original entry; a draft ID selects that draft. Updating a draft saves only the draft and leaves the canonical entry unchanged until the draft is applied with drafts.apply. To find drafts of an entry, use drafts.list with the canonical ID.',
            'For editorial changes, inspect the saved draft with workflows.review, submit when its actions permit, and follow the returned review state. Use the draft element ID for workflow transitions and drafts.apply. Refresh review state before decisions or applying; the tool schemas identify the run and stage values to send. Approval does not publish; drafts.apply updates the canonical entry separately. Inspect the resulting status to confirm publication.',
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

    public function createUnpublishedDraft(array $attributes, array $fields, CraftUser $actor, ?string $draftName = null, ?string $draftNotes = null): array
    {
        $entry = $this->prepareCreate($attributes, $fields, $actor);

        if (! Gate::forUser($actor)->allows('createDrafts', $entry)) {
            throw new ToolCallException('You are not authorized to create drafts of this entry.');
        }

        $entry->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

        if (! $this->drafts->saveElementAsDraft($entry, $actor->getCraftUserId(), $draftName, $draftNotes, markAsSaved: true)) {
            throw $this->saveFailed($entry);
        }

        $this->elementActivity->trackActivity($entry, ElementActivityType::Save, $actor->asElement());

        return $this->draftResult(
            $entry,
            "The entry was saved as an unpublished draft (ID {$entry->id}). It is not live until the draft is applied.",
            suggestSubmit: true,
        );
    }

    protected function findQuery(): ElementQueryInterface
    {
        return Entry::find()->drafts(null)->status(null);
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

        $this->setFieldValues($element, $fields);
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
        if ($element->getIsDraft()) {
            $this->saveDraft($element, $actor);

            $summary = $element->getIsUnpublishedDraft()
                ? "Your changes were saved to unpublished draft {$element->id}. It is not live until the draft is applied."
                : "Your changes were saved to draft {$element->id}. Entry {$element->getCanonicalId()} is unchanged until the draft is applied.";

            return $this->draftResult($element, $summary, suggestSubmit: false);
        }

        $saved = $this->saveElement($element, $actor);

        if (! $saved->getIsDraft()) {
            return ['element' => $this->serialize($saved)];
        }

        $summary = $saved->getIsUnpublishedDraft()
            ? "This section requires approval, so the entry was saved as an unpublished draft (ID {$saved->id}) instead of being published."
            : "This section requires approval, so your changes were saved as a new draft (ID {$saved->id}). Entry {$saved->getCanonicalId()} is unchanged until the draft is approved and applied.";

        return $this->draftResult($saved, $summary, suggestSubmit: true);
    }

    /**
     * Saves changes to an existing draft without creating another draft, as the control panel does.
     */
    private function saveDraft(ElementInterface $draft, CraftUser $actor): void
    {
        $mutex = Cache::lock("element:{$draft->id}", 15);

        if (! $mutex->get()) {
            throw new ToolCallException('Could not acquire a lock to save the entry.');
        }

        try {
            $draft->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            $saved = $this->elements->saveElement($draft);
        } finally {
            $mutex->release();
        }

        if (! $saved) {
            throw $this->saveFailed($draft);
        }

        $this->elementActivity->trackActivity($draft, ElementActivityType::Save, $actor->asElement());
    }

    /**
     * @return array{element: array<string, mixed>, savedAsDraft: true, instructions: string, nextToolCall?: array<string, mixed>}
     */
    private function draftResult(ElementInterface $draft, string $summary, bool $suggestSubmit): array
    {
        $result = [
            'element' => $this->serialize($draft),
            'savedAsDraft' => true,
        ];

        if (! $this->workflows->requiresApproval($draft)) {
            return [
                ...$result,
                'instructions' => "{$summary} Use the draft ID with elements.get, elements.update, and the drafts.* tools. Publish it with drafts.apply when it is ready.",
            ];
        }

        $result['instructions'] = "{$summary} Use the draft ID with elements.get, elements.update, and the drafts.* and workflows.* tools. "
            .($suggestSubmit
                ? 'Submit the draft for review with workflows.submit, then publish it with drafts.apply once it is approved.'
                : 'Check its review state with workflows.review, and publish it with drafts.apply once it is approved.');

        if ($suggestSubmit) {
            $result['nextToolCall'] = [
                'name' => 'workflows.submit',
                'arguments' => ['type' => self::handle(), 'id' => $draft->id, 'siteId' => $draft->siteId],
            ];
        }

        return $result;
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
