<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;

/**
 * @since 5.7.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Json} instead.
 */
class Json extends \CraftCms\Cms\Field\Json implements LegacyField
{
    use LegacyBuiltInField;

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): ?JsonData
    {
        return parent::normalizeValueFromRequest($this->capturedRequestValue($value, $element), $element);
    }
}
