<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\arguments\elements\EntryMutation;
use craft\gql\base\Mutation;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\types\generators\EntryType;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

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
                $mutationArguments = EntryMutation::getArguments();

                /** @var Field $contentField */
                foreach ($contentFields as $contentField) {
                    $mutationArguments[$contentField->handle] = $contentField->getContentGqlInputType();
                }

                $section = $entryType->getSection();

                $mutationList[$mutationName] = [
                    'name' => $mutationName,
                    'description' => 'Create a new “' . $entryType->name . '” entry' . ($section->type === 'single'  ? '.' : ' in the “' . $section->name . '” section.'),
                    'args' => $mutationArguments,
                    'resolve' => 'Eh',
                    'type' => EntryType::generateType($entryType)
                ];
            }
        }

        return $mutationList;
    }
}
