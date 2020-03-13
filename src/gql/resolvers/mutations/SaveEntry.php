<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\Element;
use craft\elements\Entry as EntryElement;
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
        $entry = null;
        $updateEntry = false;

        /** @var Section $section */
        /** @var EntryType $entryType */
        /** @var array $contentFieldHandles */
        $section = $this->_getData('section');
        $entryType = $this->_getData('entryType');
        $contentFieldHandles = $this->_getData('contentFieldHandles');

        if ($section->type == Section::TYPE_SINGLE) {
            $entry = EntryElement::findOne(['typeId' => $entryType->id]);
        } else if (!empty($arguments['uid'])) {
            $entry = EntryElement::findOne(['uid' => $arguments['uid']]);
            $updateEntry = true;
        } else if (!empty($arguments['id'])) {
            $entry = EntryElement::findOne(['id' => $arguments['id']]);
            $updateEntry = true;
        }

        if (!$entry) {
            if ($updateEntry) {
                throw new Error('No such entry exists');
            }
            $entry = new EntryElement();
        }

        $entry->sectionId = $section->id;
        $entry->typeId = $entryType->id;

        foreach ($arguments as $argument => $value) {
            if (isset($contentFieldHandles[$argument])) {
                $entry->setFieldValue($argument, $value);
            } else {
                $entry->{$argument} = $value;
            }
        }

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
}
