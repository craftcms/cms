<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\base\ObjectType;

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
}
