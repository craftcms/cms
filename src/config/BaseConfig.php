<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use yii\base\BaseObject;

/**
 * Base config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.1.5
 */
class BaseConfig extends BaseObject
{
    /**
     * Factory method for creating new config objects
     *
     * @param array $config
     * @return static
     */
    public static function create(array $config = []): static
    {
        return new static($config);
    }

    /**
     * @inerhitdoc
     */
    final public function __construct($config = [])
    {
        parent::__construct($config);
    }
}
