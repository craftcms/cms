<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\arguments\elements\EntryMutation as EntryMutationArguments;
use craft\gql\base\Mutation;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\types\generators\EntryType;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use craft\models\Section;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Entry extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateEntries()) {
            return [];
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('write');
        $allowedSectionUids = $pairs['sections'];
        $allowedSectionIds = Db::idsByUids(Table::SECTIONS, $allowedSectionUids);
        $allowedEntryTypeUids = $pairs['entrytypes'];

        $allEntryTypes = Craft::$app->getSections()->getAllEntryTypes();

        $mutationList = [];

        foreach ($allEntryTypes as $entryType) {
            $isAllowedSection = in_array($entryType->sectionId, $allowedSectionIds, true);
            $isAllowedEntryType = in_array($entryType->uid, $allowedEntryTypeUids, true);

            if ($isAllowedEntryType && $isAllowedSection) {
                $mutationName = EntryElement::gqlMutationNameByContext($entryType);
                $contentFields = $entryType->getFields();
                $mutationArguments = EntryMutationArguments::getArguments();
                $contentFieldHandles = [];

                /** @var Field $contentField */
                foreach ($contentFields as $contentField) {
                    $contentFieldType = $contentField->getContentGqlInputType();

                    $mutationArguments[$contentField->handle] = $contentFieldType;
                    $contentFieldHandles[$contentField->handle] = true;
                }

                $section = $entryType->getSection();

                // TODO Feels like the mutation should be created in a separate class to de-clutter this.
                switch ($section->type) {
                    case Section::TYPE_SINGLE:
                        $description = 'Create a new “' . $entryType->name . '” entry.';
                        unset($mutationArguments['authorId'], $mutationArguments['id'], $mutationArguments['uid']);
                        break;
                    case Section::TYPE_STRUCTURE:
                        $mutationArguments['newParentId'] = [
                            'name' => 'newParentId',
                            'type' => Type::id(),
                            'description' => 'The ID of the parent entry.'
                        ];
                    default:
                        $description = 'Create a new “' . $entryType->name . '” entry in the “' . $section->name . '” section.';
                }

                $mutationList[$mutationName] = [
                    'name' => $mutationName,
                    'description' => $description,
                    'args' => $mutationArguments,
                    'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($entryType, $section, $contentFields, $contentFieldHandles) {
                        $entry = null;

                        $updateEntry = false;

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

                        $result = Craft::$app->getElements()->saveElement($entry);

                        if ($entry->hasErrors()) {
                            $validationErrors = [];

                            foreach ($entry->getFirstErrors() as $attribute => $errorMessage) {
                                $validationErrors[] = $errorMessage;
                            }

                            throw new UserError(implode("\n", $validationErrors));
                        }

                        return $entry;
                    },
                    'type' => EntryType::generateType($entryType)
                ];
            }
        }

        return $mutationList;
    }
}
