<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\helpers\FileHelper;
use yii\base\InvalidArgumentException;
use craft\console\Controller;
use yii\console\ExitCode;
use Craft;

/**
 * Clear caches via the CLI
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TestController extends Controller
{
    // Public functions
    // =========================================================================

    /**
     * @return int
     * @throws \yii\base\Exception
     */
    public function actionSetupTests()
    {
        if (!$this->confirm('Are you sure you want to generate the tests suite?')) {
            $this->stdout('Aborted!');
            return ExitCode::OK;
        }

        if ($this->confirm('Do you want a custom path?')) {
            $dstPath = $this->prompt('Which path should the "tests/" dir be placed in?');
        } else {
            $oneUpAtVendor = dirname(Craft::$app->getPath()->getVendorPath());
            $dstPath = $oneUpAtVendor.DIRECTORY_SEPARATOR.'generated-tests';
        }

        $testPath = Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'example-test-suite';

        FileHelper::copyDirectory(
            $testPath,
            $dstPath
        );

        $this->stdout('Test suite generated. Ensure you update you update your composer dependencies.');
        return ExitCode::OK;
    }

    /**
     * Dont use this method - it wont actually execute anything.
     * It is just used internally to test Craft based console controller testing.
     * @return int
     */
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

        $this->outputCommand('An output command');

        return ExitCode::OK;
    }
}
