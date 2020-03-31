<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\Element;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\elements\Entry as EntryElement;
use craft\errors\GqlException;
use craft\gql\base\MutationResolver;
use craft\helpers\Gql;
use craft\models\EntryType;
use craft\models\Section;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveEntry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveEntry extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $entry = $this->getEntryElement($arguments);

        // Prevent modification of immutables.
        unset ($arguments['id'], $arguments['uid'], $arguments['draftId']);

        $entry = $this->populateElementWithData($entry, $arguments);

        if ($entry->enabled) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        Craft::$app->getElements()->saveElement($entry);

        if ($entry->hasErrors()) {
            $validationErrors = [];

            foreach ($entry->getFirstErrors() as $attribute => $errorMessage) {
                $validationErrors[] = $errorMessage;
            }

            throw new UserError(implode("\n", $validationErrors));
        }

        return Entry::findOne($entry->id);
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
        $section = $this->_getData('section');
        $entryType = $this->_getData('entryType');

        $entry = null;

        $siteId = $arguments['siteId'] ?? Craft::$app->getSites()->getPrimarySite()->id;
        $entryQuery = EntryElement::find()->anyStatus()->siteId($siteId);

        $canIdentify = $section->type == Section::TYPE_SINGLE || !empty($arguments['id']) || !empty($arguments['uid']);
        $this->requireSchemaAction('entrytypes.' . $entryType->uid, $canIdentify ? 'save' : 'create');

        $entryQuery = $this->identifyEntry($entryQuery, $arguments);

        if ($canIdentify) {
            $entry = $entryQuery->one();

            if (!$entry) {
                throw new Error('No such entry exists');
            }
        } else {
            $entry = new EntryElement();
        }

        if ($entry->sectionId !== $section->id) {
            throw new Error('Impossible to change the section of an existing entry');
        }

        // Null the field layout id in case the entry type changes.
        if ($entry->typeId !== $entryType->id) {
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
     * @throws GqlException if data not found.
     */
    protected function identifyEntry(EntryQuery $entryQuery, array $arguments): EntryQuery
    {
        /** @var Section $section */
        /** @var EntryType $entryType */
        $section = $this->_getData('section');
        $entryType = $this->_getData('entryType');

        if ($section->type == Section::TYPE_SINGLE) {
            $entryQuery->typeId($entryType->id);
        } else if (!empty($arguments['uid'])) {
            $entryQuery->uid($arguments['uid']);
        } else if (!empty($arguments['id'])) {
            $entryQuery->id($arguments['id']);
        }

        return $entryQuery;
    }
}
