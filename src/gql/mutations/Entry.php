<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\mutations\Draft as DraftMutationArguments;
use craft\gql\arguments\mutations\Entry as EntryMutationArguments;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\Section;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

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

        $mutationList = [];

        $createDeleteMutation = false;
        $createDraftMutations = false;

        foreach (Craft::$app->getSections()->getAllEntryTypes() as $entryType) {
            $scope = 'entrytypes.' . $entryType->uid;
            $canCreate = Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                // Create a mutation for each entry type
                foreach (static::createSaveMutations($entryType, $canSave) as $mutation) {
                    $mutationList[$mutation['name']] = $mutation;
                }
            }

            if (!$createDraftMutations && $canSave) {
                $createDraftMutations = true;
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation || $createDraftMutations) {
            $resolver = Craft::createObject(EntryMutationResolver::class);

            if ($createDeleteMutation) {
                $mutationList['deleteEntry'] = [
                    'name' => 'deleteEntry',
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                        'siteId' => Type::int(),
                    ],
                    'resolve' => [$resolver, 'deleteEntry'],
                    'description' => 'Delete an entry.',
                    'type' => Type::boolean(),
                ];
            }

            if ($createDraftMutations) {
                $mutationList['createDraft'] = [
                    'name' => 'createDraft',
                    'args' => [
                        'id' => [
                            'name' => 'id',
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'The id for the entry to create the draft for.',
                        ],
                        'name' => [
                            'name' => 'name',
                            'type' => Type::string(),
                            'description' => 'The name of the draft',
                        ],
                        'notes' => [
                            'name' => 'notes',
                            'type' => Type::string(),
                            'description' => 'Draft notes',
                        ],
                        'provisional' => [
                            'name' => 'provisional',
                            'type' => Type::boolean(),
                            'description' => 'Whether the draft should be a provisional draft or not.',
                        ],
                    ],
                    'resolve' => [$resolver, 'createDraft'],
                    'description' => 'Create a draft for an entry and return the draft ID.',
                    'type' => Type::id(),
                ];

                $mutationList['publishDraft'] = [
                    'name' => 'publishDraft',
                    'args' => [
                        'id' => [
                            'name' => 'id',
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'The id of the draft to be published.',
                        ],
                        'provisional' => [
                            'name' => 'provisional',
                            'type' => Type::boolean(),
                            'description' => 'Whether the draft is a provisional draft.',
                        ],
                    ],
                    'resolve' => [$resolver, 'publishDraft'],
                    'description' => 'Publish a draft for the entry and return the entry ID.',
                    'type' => Type::id(),
                ];
            }
        }

        return $mutationList;
    }

    /**
     * Create the per-entry-type save mutations.
     *
     * @param EntryTypeModel $entryType
     * @param bool $createSaveDraftMutation
     * @return array
     * @throws InvalidConfigException
     */
    public static function createSaveMutations(EntryTypeModel $entryType, bool $createSaveDraftMutation): array
    {
        $mutations = [];

        $mutationName = EntryElement::gqlMutationNameByContext($entryType);
        $entryMutationArguments = EntryMutationArguments::getArguments();
        $draftMutationArguments = DraftMutationArguments::getArguments();
        $generatedType = EntryType::generateType($entryType);

        $section = $entryType->getSection();

        /** @var EntryMutationResolver $resolver */
        $resolver = Craft::createObject(EntryMutationResolver::class);
        $resolver->setResolutionData('entryType', $entryType);
        $resolver->setResolutionData('section', $section);

        static::prepareResolver($resolver, $entryType->getCustomFields());

        switch ($section->type) {
            case Section::TYPE_SINGLE:
                $description = 'Save the “' . $entryType->name . '” entry.';
                $draftDescription = 'Save the “' . $entryType->name . '” draft.';

                unset($entryMutationArguments['authorId'], $entryMutationArguments['id'], $entryMutationArguments['uid']);
                unset($draftMutationArguments['authorId'], $draftMutationArguments['id'], $draftMutationArguments['uid']);
                break;
            case Section::TYPE_STRUCTURE:
                $entryMutationArguments = array_merge($entryMutationArguments, StructureArguments::getArguments());
            // no break
            default:
                $description = 'Save a “' . $entryType->name . '” entry in the “' . $section->name . '” section.';
                $draftDescription = 'Save a “' . $entryType->name . '” entry draft in the “' . $section->name . '” section.';
        }

        $contentFields = $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY);
        $entryMutationArguments = array_merge($entryMutationArguments, $contentFields);
        $draftMutationArguments = array_merge($draftMutationArguments, $contentFields);

        $mutations[] = [
            'name' => $mutationName,
            'description' => $description,
            'args' => $entryMutationArguments,
            'resolve' => [$resolver, 'saveEntry'],
            'type' => $generatedType,
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => StringHelper::replaceEnding($mutationName, '_Entry', '_Draft'),
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => [$resolver, 'saveEntry'],
                'type' => $generatedType,
            ];
        }

        return $mutations;
    }
}
