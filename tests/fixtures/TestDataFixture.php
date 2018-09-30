<?php
namespace craftunit\fixtures;

use craft\records\User;
use yii\test\ActiveFixture;

/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 9/30/2018
 * Time: 7:14 PM
 */

class TestDataFixture extends ActiveFixture
{
    public $modelClass = User::class;
}