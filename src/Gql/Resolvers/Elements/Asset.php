<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Elements;

use BadMethodCallException;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementResolver;
use CraftCms\Cms\Support\Facades\Volumes;

class Asset extends ElementResolver
{
    /** @param array<string, mixed> $arguments */
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
        if (! $query instanceof ElementQueryInterface) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (BadMethodCallException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema();

        if (! GqlHelper::canQueryAssets()) {
            return ElementCollection::empty();
        }

        $volumeIds = collect($pairs['volumes'])
            ->map(fn (string $uid) => Volumes::getVolumeByUid($uid)?->id)
            ->filter(fn (?int $id) => $id !== null)
            ->all();

        $query->whereIn('assets.volumeId', $volumeIds);

        return $query;
    }
}
