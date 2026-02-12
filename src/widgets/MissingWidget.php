<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use craft\base\Widget;
use craft\base\WidgetInterface;

/**
 * MissingWidget represents a widget with an invalid class.
 *
 * @property class-string<WidgetInterface> $expectedType
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        return null;
    }
}
