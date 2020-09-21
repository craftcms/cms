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
     * @var bool Whether to eager-load the matching elements
     * @since 3.5.12
     */
    public $all = false;

    /**
     * @var bool Whether to eager-load the count of the matching elements
     */
    public $count = false;

    /**
     * @var callable|null A PHP callable whose return value determines whether to apply eager-loaded elements to the given element.
     *
     * The signature of the callable should be `function (\craft\base\ElementInterface $element): bool`, where `$element` refers to the element
     * the eager-loaded elements are about to be applied to. The callable should return a boolean value.
     *
     * @since 3.5.12
     */
    public $when;

    /**
     * @var EagerLoadPlan[] Nested eager-loading plans to apply to the eager-loaded elements.
     */
    public $nested = [];
}
