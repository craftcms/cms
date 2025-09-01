<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\Element;
use craft\base\ElementContainerFieldInterface;
use craft\behaviors\DraftBehavior;
use craft\elements\db\EntryQuery;
use craft\elements\Entry as EntryElement;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\StructureMutationTrait;
use craft\models\EntryType;
use craft\models\Section;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;
use yii\base\InvalidConfigException;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Entry extends ElementMutationResolver
{
    use StructureMutationTrait;

    /** @inheritdoc */
    protected array $immutableAttributes = ['id', 'uid', 'draftId'];

    /**
     * Save an entry or draft using the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return EntryElement
     * @throws Throwable if reasons.
     */
    public function saveEntry(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): EntryElement
    {
        $entry = $this->getEntryElement($arguments);

        // If saving an entry for a site and the enabled status is provided, honor it.
        if (array_key_exists('enabled', $arguments)) {
            try {
                $showStatusField = $entry->getType()->showStatusField;
            } catch (InvalidConfigException) {
                $showStatusField = true;
            }

            if ($showStatusField) {
                if (!empty($arguments['siteId'])) {
                    $entry->setEnabledForSite([$arguments['siteId'] => $arguments['enabled']]);
                    // Set the global status to true if it's currently disabled,
                    // and we're enabling entry for a site
                    if ($arguments['enabled'] && !$entry->enabled) {
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
            } catch (InvalidConfigException) {
                $showSlugField = true;
            }

            if (!$showSlugField) {
                unset($arguments['slug']);
            }
        }

        // TODO refactor saving draft to its own method in 4.0
        if (array_key_exists('draftId', $arguments)) {
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        }

        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']) || !empty($arguments['draftId']);

        $entry = $this->populateElementWithData($entry, $arguments, $resolveInfo);

        if (array_key_exists('asUnpublishedDraft', $arguments) && $arguments['asUnpublishedDraft']) {
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
            Craft::$app->getDrafts()->saveElementAsDraft($entry);
        } else {
            $entry = $this->saveElement($entry);
        }

        $this->performStructureOperations($entry, $arguments);

        /** @var EntryQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(EntryElement::class)
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

        return $query->one();
    }

    /**
     * Delete an entry identified by the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     * @throws Throwable if reasons.
     */
    public function deleteEntry(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $entryId = $arguments['id'];
        $siteId = $arguments['siteId'] ?? null;

        $elementService = Craft::$app->getElements();
        /** @var EntryElement|null $entry */
        $entry = $elementService->getElementById($entryId, EntryElement::class, $siteId);

        if (!$entry) {
            return false;
        }

        $section = $entry->getSection();
        $this->requireSchemaAction("sections.$section->uid", 'delete');

        return $elementService->deleteElementById($entryId);
    }

    /**
     * Create a new draft for the entry ID identified by the arguments
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws Throwable if reasons.
     */
    public function createDraft(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $entryId = $arguments['id'];

        /** @var EntryElement|null $entry */
        $entry = Craft::$app->getElements()->getElementById($entryId, EntryElement::class);

        if (!$entry) {
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

        $draftName = $arguments['name'] ?? '';
        $draftNotes = $arguments['notes'] ?? '';
        $provisional = $arguments['provisional'] ?? false;
        $creatorId = $arguments['creatorId'] ?? null;

        /** @var EntryElement|DraftBehavior $draft */
        $draft = Craft::$app->getDrafts()->createDraft($entry, $creatorId ?? $entry->getAuthorId(), $draftName, $draftNotes, [], $provisional);

        return $draft->draftId;
    }

    /**
     * Publish a draft identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return int
     * @throws Throwable if reasons.
     */
    public function publishDraft(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): int
    {
        /** @var EntryElement|DraftBehavior|null $draft */
        $draft = Craft::$app->getElements()
            ->createElementQuery(EntryElement::class)
            ->status(null)
            ->provisionalDrafts($arguments['provisional'] ?? false)
            ->draftId($arguments['id'])
            ->one();

        if (!$draft) {
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
        $draft = Craft::$app->getDrafts()->applyDraft($draft);

        return $draft->id;
    }

    /**
     * Get the entry based on the arguments.
     *
     * @param array $arguments
     * @return EntryElement
     * @throws Exception if reasons
     */
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
            $section?->type === Section::TYPE_SINGLE ||
            !empty($arguments['id']) ||
            !empty($arguments['uid']) ||
            !empty($arguments['draftId'])
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

        $elementService = Craft::$app->getElements();

        if ($canIdentify) {
            // Prepare the element query
            $siteId = $arguments['siteId'] ?? Craft::$app->getSites()->getPrimarySite()->id;
            /** @var EntryQuery $entryQuery */
            $entryQuery = $elementService->createElementQuery(EntryElement::class)->status(null)->siteId($siteId);
            $entryQuery = $this->identifyEntry($entryQuery, $arguments);

            $entry = $entryQuery->one();

            if (!$entry) {
                throw new Error('No such entry exists');
            }
        } else {
            $entry = $elementService->createElement(EntryElement::class);
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

    /**
     * Identify the entry element.
     *
     * @param EntryQuery $entryQuery
     * @param array $arguments
     * @return EntryQuery
     */
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
        if (!empty($arguments['draftId'])) {
            $entryQuery->draftId($arguments['draftId']);

            if (array_key_exists('provisional', $arguments)) {
                $entryQuery->provisionalDrafts($arguments['provisional']);
            }
        } elseif ($section?->type === Section::TYPE_SINGLE) {
            $entryQuery->typeId($entryType->id);
        } elseif (!empty($arguments['uid'])) {
            $entryQuery->uid($arguments['uid']);
        } elseif (!empty($arguments['id'])) {
            $entryQuery->id($arguments['id']);
        } else {
            // Unable to identify, make sure nothing is returned.
            $entryQuery->id(-1);
        }

        return $entryQuery;
    }
}
