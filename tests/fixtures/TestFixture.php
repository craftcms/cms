<?php
namespace craftunit\fixtures;

use craft\records\User;

/**
 * A super simple test fixture demonstrating how fixtures work in craft/yii.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class TestFixture extends \yii\test\ActiveFixture
{
    public $modelClass = User::class;
    public $dataFile = __DIR__.'/data/test.php';

}