<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            AssetInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var AssetElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'url' && !empty($arguments)) {
            $generateNow = $arguments['immediately'] ?? Craft::$app->getConfig()->general->generateTransformsBeforePageLoad;
            unset($arguments['immediately']);

            if (!empty($arguments['handle'])) {
                $transform = $arguments['handle'];
            } else if (!empty($arguments['transform'])) {
                $transform = $arguments['transform'];
            } else {
                $transform = $arguments;
            }

            return Craft::$app->getAssets()->getAssetUrl($source, $transform, $generateNow);
        }

        return $source->$fieldName;
    }
}
