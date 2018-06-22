<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\db\Query;

/**
 * Trait ClonefixTrait.
 *
 * This provides an improved `__clone()` method over [[\yii\base\Component::__clone()]],
 * which rushes a fix for https://github.com/yiisoft/yii2/issues/16247.
 *
 * @todo remove this in 4.0
 */
trait ClonefixTrait
{
    public function __clone()
    {
        /** @var Model|Query $this */
        $behaviors = $this->getBehaviors();
        parent::__clone();
        /** @var \yii\base\Component $this */
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehavior($name, clone $behavior);
        }
    }
}
