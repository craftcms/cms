<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\db\EntryQuery;
use craft\elements\Entry as EntryElement;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\StructureMutationTrait;
use craft\helpers\Db;
use craft\models\EntryType;
use craft\models\Section;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

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
    protected $immutableAttributes = ['id', 'uid', 'draftId'];

    /**
     * Save an entry or draft using the passed arguments.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function saveEntry($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $entry = $this->getEntryElement($arguments);

        $entry = $this->populateElementWithData($entry, $arguments);

        $entry = $this->saveElement($entry);
        $this->performStructureOperations($entry, $arguments);

        return Craft::$app->getElements()->createElementQuery(EntryElement::class)->anyStatus()->id($entry->id)->one();
    }

    /**
     * Delete an entry identified by the passed arguments.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function deleteEntry($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $entryId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        $entry = $elementService->getElementById($entryId, EntryElement::class);

        if (!$entry) {
            return true;
        }

        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $entry->typeId);
        $this->requireSchemaAction('entrytypes.' . $entryTypeUid, 'delete');

        $elementService->deleteElementById($entryId);

        return true;
    }

    /**
     * Create a new draft for the entry id identified by the arguments
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function createDraft($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $entryId = $arguments['id'];

        /** @var EntryElement $entry */
        $entry = Craft::$app->getElements()->getElementById($entryId, EntryElement::class);

        if (!$entry) {
            throw new Error('Unable to perform the action.');
        }

        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $entry->typeId);
        $this->requireSchemaAction('entrytypes.' . $entryTypeUid, 'save');

        /** @var Entry $draft */
        $draft = Craft::$app->getDrafts()->createDraft($entry, $entry->authorId);

        return $draft->draftId;
    }

    /**
     * Publish a draft identified by the arguments.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function publishDraft($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $draft = Craft::$app->getElements()->createElementQuery(EntryElement::class)->anyStatus()->draftId($arguments['id'])->one();

        if (!$draft) {
            throw new Error('Unable to perform the action.');
        }

        $entryTypeUid = Db::uidById(Table::ENTRYTYPES, $draft->typeId);
        $this->requireSchemaAction('entrytypes.' . $entryTypeUid, 'save');

        /** @var Entry $draft */
        $draft = Craft::$app->getDrafts()->applyDraft($draft);

        return $draft->id;
    }

    /**
     * Get the entry based on the arguments.
     *
     * @param array $arguments
     * @return EntryElement
     * @throws \Exception if reasons
     */
    protected function getEntryElement(array $arguments): EntryElement
    {
        /** @var Section $section */
        /** @var EntryType $entryType */
        $section = $this->getResolutionData('section');
        $entryType = $this->getResolutionData('entryType');

        $entry = null;

        // Figure out whether the mutation is about an already saved entry
        $canIdentify = $section->type === Section::TYPE_SINGLE || !empty($arguments['id']) || !empty($arguments['uid']);

        // Check if relevant schema is present
        $this->requireSchemaAction('entrytypes.' . $entryType->uid, $canIdentify ? 'save' : 'create');

        $elementService = Craft::$app->getElements();

        if ($canIdentify) {
            // Prepare the element query
            $siteId = $arguments['siteId'] ?? Craft::$app->getSites()->getPrimarySite()->id;
            $entryQuery = $elementService->createElementQuery(EntryElement::class)->anyStatus()->siteId($siteId);
            $entryQuery = $this->identifyEntry($entryQuery, $arguments);

            $entry = $entryQuery->one();

            if (!$entry) {
                throw new Error('No such entry exists');
            }
        } else {
            $entry = $elementService->createElement(EntryElement::class);
        }

        // If they are identifying a specific entry, don't allow changing the section id.
        if ($canIdentify && $entry->sectionId !== $section->id) {
            throw new Error('Impossible to change the section of an existing entry');
        }

        // Null the field layout id in case the entry type changes.
        if ($entry->typeId != $entryType->id) {
            $entry->fieldLayoutId = null;
        }

        $entry->sectionId = $section->id;
        $entry->typeId = $entryType->id;

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
        /** @var Section $section */
        /** @var EntryType $entryType */
        $section = $this->getResolutionData('section');
        $entryType = $this->getResolutionData('entryType');

        if (!empty($arguments['draftId'])) {
            $entryQuery->draftId($arguments['draftId']);
        } else if ($section->type === Section::TYPE_SINGLE) {
            $entryQuery->typeId($entryType->id);
        } else if (!empty($arguments['uid'])) {
            $entryQuery->uid($arguments['uid']);
        } else if (!empty($arguments['id'])) {
            $entryQuery->id($arguments['id']);
        } else {
            // Unable to identify, make sure nothing is returned.
            $entryQuery->id(-1);
        }

        return $entryQuery;
    }
}
