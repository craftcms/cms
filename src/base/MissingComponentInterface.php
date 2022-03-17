<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * MissingComponentInterface defines the common interface for classes that represent a missing component class.
 * A class implementing this interface should also implement [[ComponentInterface]] and [[\yii\base\Arrayable]],
 * and use [[MissingComponentTrait]].
 *
 * @mixin MissingComponentTrait
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface MissingComponentInterface
{
}
