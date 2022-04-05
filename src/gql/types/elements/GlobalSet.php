<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;

/**
 * Class GlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GlobalSet extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            GlobalSetInterface::getType(),
        ];

        parent::__construct($config);
    }
}
