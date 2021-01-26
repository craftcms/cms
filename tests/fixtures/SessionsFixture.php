<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\Session;
use craft\test\ActiveFixture;

/**
 * Class SessionsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SessionsFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/sessions.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Session::class;
}
