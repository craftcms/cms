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

/**
 * MissingWidget represents a widget with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        return false;
    }
}
