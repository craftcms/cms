<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = AssetElement::find();
        // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryAssets()) {
            return Collection::empty();
        }

        $volumesService = Craft::$app->getVolumes();
        $volumeIds = array_filter(array_map(function(string $uid) use ($volumesService) {
            $volume = $volumesService->getVolumeByUid($uid);
            return $volume->id ?? null;
        }, $pairs['volumes']));

        $query->andWhere(['in', 'assets.volumeId', $volumeIds]);

        return $query;
    }
}
