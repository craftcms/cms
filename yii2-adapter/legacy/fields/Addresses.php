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
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use Override;
use RuntimeException;

/**
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Addresses} instead.
 */
class Addresses extends \CraftCms\Cms\Field\Addresses implements LegacyField
{
    use LegacyBuiltInField;

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($element);
    }

    private function inputHtmlInternal(?ElementInterface $owner, bool $static = false): string
    {
        $config = $this->nestedElementManagerConfig($static);

        if ($this->viewMode !== self::VIEW_MODE_INDEX) {
            return Html::tag('div', $this->addressManager()->getCardsHtml($owner, $config), [
                'id' => $this->getInputId(),
            ]);
        }

        return $this->addressManager()->getIndexHtml($owner, $config);
    }
}
