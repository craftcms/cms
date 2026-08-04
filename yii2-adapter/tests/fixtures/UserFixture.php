<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\UserFixture as BaseUserFixture;

/**
 * Class UsersFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserFixture extends BaseUserFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/users.php';

    /**
     * @inheritdoc
     */
    public $depends = [FieldLayoutFixture::class];
}
