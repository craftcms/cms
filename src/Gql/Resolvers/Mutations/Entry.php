<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Mutations;

use Craft;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Gql\Concerns\PerformsStructureMutations;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use Error;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Auth;
use Override;
use Throwable;

class Entry extends ElementMutationResolver
{
    use PerformsStructureMutations;

    #[Override]
    protected array $immutableAttributes = ['id', 'uid', 'draftId'];

    public function saveEntry(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): EntryElement
    {
        $entry = $this->getEntryElement($arguments);

        // If saving an entry for a site and the enabled status is provided, honor it.
        if (array_key_exists('enabled', $arguments)) {
            try {
                $showStatusField = $entry->getType()->showStatusField;
            } catch (Throwable) {
                $showStatusField = true;
            }

            if ($showStatusField) {
                if (! empty($arguments['siteId'])) {
                    $entry->setEnabledForSite([$arguments['siteId'] => $arguments['enabled']]);
                    // Set the global status to true if it's currently disabled,
                    // and we're enabling entry for a site
                    if ($arguments['enabled'] && ! $entry->enabled) {
                        $entry->enabled = $arguments['enabled'];
                    }
                } else {
                    $entry->enabled = $arguments['enabled'];
                }
            }
            unset($arguments['enabled']);
        }

        // If saving an entry the slug is provided, check if we should allow changing it.
        if (array_key_exists('slug', $arguments)) {
            try {
                $showSlugField = $entry->getType()->showSlugField;
            } catch (Throwable) {
                $showSlugField = true;
            }

            if (! $showSlugField) {
                unset($arguments['slug']);
            }
        }

        // TODO refactor saving draft to its own method in 4.0
        if (array_key_exists('draftId', $arguments)) {
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        }

        $canIdentify = ! empty($arguments['id']) || ! empty($arguments['uid']) || ! empty($arguments['draftId']);

        $entry = $this->populateElementWithData($entry, $arguments, $resolveInfo);

        if (array_key_exists('asUnpublishedDraft', $arguments) && $arguments['asUnpublishedDraft']) {
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
            Drafts::saveElementAsDraft(
                $entry,
                creatorId: Auth::id(),
                name: $arguments['draftName'] ?? null,
                notes: $arguments['draftNotes'] ?? null,
            );
        } else {
            $entry = $this->saveElement($entry);
        }

        $this->performStructureOperations($entry, $arguments);

        /** @var EntryQuery $query */
        $query = Elements::createElementQuery(EntryElement::class)
            ->siteId($entry->siteId)
            ->status(null);

        // Refresh data from the DB
        if ($canIdentify) {
            $query = $this->identifyEntry($query, $arguments);
        } else {
            if (array_key_exists('asUnpublishedDraft', $arguments) && $arguments['asUnpublishedDraft']) {
                $query->drafts(null);
            }
            $query->id($entry->id);
        }

        return $query->first();
    }

    public function deleteEntry(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $entryId = $arguments['id'];
        $siteId = $arguments['siteId'] ?? null;

        $elementService = Craft::$app->getElements();
        /** @var EntryElement|null $entry */
        $entry = Elements::getElementById($entryId, EntryElement::class, $siteId);

        if (! $entry) {
            return false;
        }

        $section = $entry->getSection();
        $this->requireSchemaAction("sections.$section->uid", 'delete');

        return $elementService->deleteElementById($entryId);
    }

    public function createDraft(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $entryId = $arguments['id'];

        /** @var EntryElement|null $entry */
        $entry = Elements::getElementById($entryId, EntryElement::class);

        if (! $entry) {
            throw new Error('Unable to perform the action.');
        }

        $section = $entry->getSection();
        $field = $entry->getField();

        if ($section) {
            $this->requireSchemaAction("sections.$section->uid", 'save');
        } elseif ($field) {
            $this->requireSchemaAction("nestedentryfields.$field->uid", 'save');
        } else {
            throw new Error('Unable to perform the action.');
        }

        return Drafts::createDraft(
            canonical: $entry,
            creatorId: $arguments['creatorId'] ?? $entry->getAuthorId(),
            name: $arguments['name'] ?? '',
            notes: $arguments['notes'] ?? '',
            provisional: $arguments['provisional'] ?? false,
        )->draftId;
    }

    public function publishDraft(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): int
    {
        /** @var EntryElement|null $draft */
        $draft = Elements::createElementQuery(EntryElement::class)
            ->status(null)
            ->provisionalDrafts($arguments['provisional'] ?? false)
            ->draftId($arguments['id'])
            ->one();

        if (! $draft) {
            throw new Error('Unable to perform the action.');
        }

        $section = $draft->getSection();
        $field = $draft->getField();

        if ($section) {
            $this->requireSchemaAction("sections.$section->uid", 'save');
        } elseif ($field) {
            $this->requireSchemaAction("nestedentryfields.$field->uid", 'save');
        } else {
            throw new Error('Unable to perform the action.');
        }

        /** @var EntryElement $draft */
        $draft = Drafts::applyDraft($draft);

        return $draft->id;
    }

    protected function getEntryElement(array $arguments): EntryElement
    {
        /** @var Section|null $section */
        $section = $this->getResolutionData('section');
        /** @var ElementContainerFieldInterface|null $field */
        $field = $this->getResolutionData('field');
        /** @var EntryType $entryType */
        $entryType = $this->getResolutionData('entryType');

        // Figure out whether the mutation is about an already saved entry
        $canIdentify = (
            $section?->type === SectionType::Single ||
            ! empty($arguments['id']) ||
            ! empty($arguments['uid']) ||
            ! empty($arguments['draftId'])
        );

        // Check if relevant schema is present
        $action = $canIdentify ? 'save' : 'create';
        if ($section) {
            $this->requireSchemaAction("sections.$section->uid", $action);
        } elseif ($field) {
            $this->requireSchemaAction("nestedentryfields.$field->uid", $action);
        } else {
            throw new Error('Unable to perform the action.');
        }

        if ($canIdentify) {
            // Prepare the element query
            $siteId = $arguments['siteId'] ?? Sites::getPrimarySite()->id;
            /** @var EntryQuery $entryQuery */
            $entryQuery = Elements::createElementQuery(EntryElement::class)->status(null)->siteId($siteId);
            $entryQuery = $this->identifyEntry($entryQuery, $arguments);

            $entry = $entryQuery->one();

            if (! $entry) {
                throw new Error('No such entry exists');
            }
        } else {
            $entry = Elements::createElement(EntryElement::class);
        }

        // If they are identifying a specific entry, don't allow changing the section/field ID.
        if ($canIdentify) {
            if ($section) {
                if ($entry->sectionId !== $section->id) {
                    throw new Error('Impossible to change the section of an existing entry');
                }
            } elseif ($entry->fieldId !== $field->id) {
                throw new Error('Impossible to change the field of an existing entry');
            }
        }

        $entry->sectionId = $section?->id;
        $entry->fieldId = $field?->id;

        // Null the field layout ID in case the entry type changes.
        if ($entry->getTypeId() !== $entryType->id) {
            $entry->fieldLayoutId = null;
        }

        $entry->setTypeId($entryType->id);

        return $entry;
    }

    protected function identifyEntry(EntryQuery $entryQuery, array $arguments): EntryQuery
    {
        /** @var Section|null $section */
        $section = $this->getResolutionData('section');
        /** @var ElementContainerFieldInterface|null $field */
        $field = $this->getResolutionData('field');
        /** @var EntryType $entryType */
        $entryType = $this->getResolutionData('entryType');

        if ($field) {
            // nested entries won’t be queried if a field param isn’t set
            $entryQuery->fieldId($field->id);
        }
        if (! empty($arguments['draftId'])) {
            $entryQuery->draftId((int) $arguments['draftId']);

            if (array_key_exists('provisional', $arguments)) {
                $entryQuery->provisionalDrafts($arguments['provisional']);
            }
        } elseif ($section?->type === SectionType::Single) {
            $entryQuery->typeId($entryType->id);
        } elseif (! empty($arguments['uid'])) {
            $entryQuery->uid($arguments['uid']);
        } elseif (! empty($arguments['id'])) {
            $entryQuery->id($arguments['id']);
        } else {
            // Unable to identify, make sure nothing is returned.
            $entryQuery->id(-1);
        }

        return $entryQuery;
    }
}
