<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Dashboard\Widgets;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

/**
 * MissingWidget represents a widget with an invalid class.
 *
 * @property class-string<Widget> $expectedType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): ?string
    {
        return null;
    }
}
