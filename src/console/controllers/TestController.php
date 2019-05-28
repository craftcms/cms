<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use yii\base\InvalidArgumentException;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * @todo This class is just for show.
 * @internal It will be removed before the tests are release for public. No need for cleaning the code.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class TestController extends Controller
{
    public function actionTest()
    {
        $this->stdout('22');
        $this->stderr('123321123');
        $val = $this->select('Select', ['2', '22']);

        if ($val !== '2') {
            throw new InvalidArgumentException('FAIL');
        }

        $confirm = $this->confirm('asd', true);
        if ($confirm !== true) {
            throw new InvalidArgumentException('FAIL');
        }

        $prompts = $this->prompt('A prompt', ['2', '22']);
        if ($prompts !== 'hi') {
            throw new InvalidArgumentException('FAIL');
        }

        return ExitCode::OK;
    }
}
