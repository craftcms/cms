<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\db\Query;
use yii\base\Component as YiiComponent;

/**
 * Trait ClonefixTrait.
 *
 * This provides an improved `__clone()` method over [[\yii\base\Component::__clone()]],
 * which rushes a fix for https://github.com/yiisoft/yii2/issues/16247.
 *
 * @since 3.0.13
 * @mixin YiiComponent
 */
trait ClonefixTrait
{
    public function __clone()
    {
        /** @var Model|Query $this */
        $behaviors = $this->getBehaviors();
        parent::__clone();
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehavior($name, clone $behavior);
        }
    }
}
