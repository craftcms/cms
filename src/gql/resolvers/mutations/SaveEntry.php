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
use craft\elements\Entry as EntryElement;
use craft\errors\GqlException;
use craft\gql\base\MutationResolver;
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

        $entry = $this->populateEntryWithData($entry, $arguments);

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

        return $entry;
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

        $entryQuery = $this->identifyEntry($entryQuery, $arguments);

        if ($entryQuery) {
            $entry = $entryQuery->one();

            if (!$entry) {
                throw new Error('No such entry exists');
            }
        } else {
            $entry = new EntryElement();
        }

        $entry->sectionId = $section->id;
        $entry->typeId = $entryType->id;

        return $entry;
    }

    /**
     * Populate the entry with submitted data.
     *
     * @param EntryElement $entry
     * @param array $arguments
     * @return EntryElement
     * @throws GqlException if data not found.
     */
    protected function populateEntryWithData(EntryElement $entry, array $arguments): EntryElement
    {
        /** @var array $contentFieldHandles */
        $contentFieldHandles = $this->_getData('contentFieldHandles');

        foreach ($arguments as $argument => $value) {
            if (isset($contentFieldHandles[$argument])) {
                $entry->setFieldValue($argument, $value);
            } else {
                $entry->{$argument} = $value;
            }
        }

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
