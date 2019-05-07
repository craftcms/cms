<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use craft\records\Session;
use craft\test\Fixture;

/**
 * Unit tests for SessionsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SessionsFixture extends Fixture
{
    public $dataFile = __DIR__.'/data/sessions.php';
    public $modelClass = Session::class;
}