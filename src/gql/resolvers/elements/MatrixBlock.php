<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\base\ElementResolver;

/**
 * Class MatrixBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlock extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = MatrixBlockElement::find();
            // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }
}
