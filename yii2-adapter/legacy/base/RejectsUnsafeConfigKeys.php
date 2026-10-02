<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\InvalidConfigException;

trait RejectsUnsafeConfigKeys
{
    protected function ensureConfigKeyIsSafe(mixed $name): void
    {
        if (is_string($name) && (str_starts_with($name, 'on ') || str_starts_with($name, 'as '))) {
            throw new InvalidConfigException('Config keys beginning with `on ` or `as ` are not supported.');
        }
    }
}
