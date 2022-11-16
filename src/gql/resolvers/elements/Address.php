<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\elements\Address as AddressElement;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql as GqlHelper;
use Illuminate\Support\Collection;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = AddressElement::find();
        } else {
            // If not, get the prepared element query
            /** @var AddressQuery $query */
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        if (!GqlHelper::canQueryUsers()) {
            return Collection::empty();
        }

        return $query;
    }
}
