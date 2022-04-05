<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\elements\User;

/**
 * Class UserFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class UserFixture extends BaseElementFixture
{
    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new User();
    }
}
