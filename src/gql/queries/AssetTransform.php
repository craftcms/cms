<?php
namespace craft\gql\queries;

use Craft;
use craft\gql\types\AssetTransform as AssetTransformType;
use GraphQL\Type\Definition\Type;

/**
 * Class AssetTransform
 */
class AssetTransform
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'queryAssetTransform' => [
                'type' => AssetTransformType::getType(),
                'args' => [
                    'id' => Type::id(),
                    'uid' => Type::string(),
                    'handle' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['uid'])) {
                        return Craft::$app->getAssetTransforms()->getTransformByUid($args['uid']);
                    }

                    if (isset($args['id'])) {
                        return Craft::$app->getAssetTransforms()->getTransformById($args['id']);
                    }

                    if (isset($args['handle'])) {
                        return Craft::$app->getAssetTransforms()->getTransformByHandle($args['handle']);
                    }
                },
            ],
            'queryAllAssetTransforms'  => [
                'type' => Type::listOf(AssetTransformType::getType()),
                'resolve' => function () {
                    return Craft::$app->getAssetTransforms()->getAllTransforms();
                },
            ],
        ];
    }
}
