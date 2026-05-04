<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * MissingComponentInterface defines the common interface for classes that represent a missing component class.
     * A class implementing this interface should also implement [[ComponentInterface]] and [[\yii\base\Arrayable]],
     * and use [[MissingComponentTrait]].
     *
     * @mixin MissingComponentTrait
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Contracts\MissingComponentInterface} instead.
     */
    interface MissingComponentInterface extends \CraftCms\Cms\Component\Contracts\MissingComponentInterface
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\MissingComponentInterface::class, MissingComponentInterface::class);
