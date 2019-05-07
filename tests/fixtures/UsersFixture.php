<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use craft\test\elementfixtures\UserFixture;

/**
 * Unit tests for UsersFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class UsersFixture extends UserFixture
{
    public $dataFile = __DIR__.'/data/users.php';
}