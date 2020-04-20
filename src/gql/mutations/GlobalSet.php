<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\SaveGlobalSet;
use craft\gql\types\generators\GlobalSetType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;

/**
 * Class GlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class GlobalSet extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateGlobalSets()) {
            return [];
        }

        $mutationList = [];

        foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
            $scope = 'globalsets.' . $globalSet->uid;

            if (Gql::canSchema($scope, 'edit')) {
                $mutation = static::createSaveMutation($globalSet);
                $mutationList[$mutation['name']] = $mutation;
            }
        }

        return $mutationList;
    }

    /**
     * Create the per-global-set save mutation.
     *
     * @param GlobalSetElement $globalSet
     * @return array
     */
    protected static function createSaveMutation(GlobalSetElement $globalSet): array
    {
        $mutationName = GlobalSetElement::gqlMutationNameByContext($globalSet);
        $contentFields = $globalSet->getFields();
        $mutationArguments = [];
        $contentFieldHandles = [];
        $valueNormalizers = [];

        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();

            $mutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;

            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $valueNormalizers[$contentField->handle] = $configArray['normalizeValue'];
            }

            $resolverData = [
                'globalSet' => $globalSet,
                'contentFieldHandles' => $contentFieldHandles,
            ];

            $generatedType = GlobalSetType::generateType($globalSet);

            return [
                'name' => $mutationName,
                'description' => 'Update the ”' . $globalSet . '“ global set.',
                'args' => $mutationArguments,
                'resolve' => [new SaveGlobalSet($resolverData, $valueNormalizers), 'resolve'],
                'type' => $generatedType
            ];
        }
    }
}
