<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use yii\base\BaseObject;

/**
 * AssetQuery represents a SELECT SQL statement for assets in a way that is independent of DBMS.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EagerLoadPlan extends BaseObject
{
    /**
     * @var string The eager-loading handle
     */
    public $handle;

    /**
     * @var string The eager-loading alias
     */
    public $alias;

    /**
     * @var array The criteria that should be applied when eager-loading these elements
     */
    public $criteria = [];

    /**
     * @var bool Whether to only eager-load the total results
     */
    public $count = false;

    /**
     * @var EagerLoadPlan[] Nested eager-loading plans to apply to the eager-loaded elements.
     */
    public $nested = [];
}
