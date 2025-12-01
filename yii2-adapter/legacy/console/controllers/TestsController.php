<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;

/**
 * Provides support resources for testing both Craft’s services and your project’s Craft implementation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class TestsController extends Controller
{
    /**
     * Sets up a test suite for the current project.
     *
     * @param string|null $dst The folder that the test suite should be generated in.
     *                         Defaults to the current working directory.
     *
     * @return int
     */
    public function actionSetup(?string $dst = null): int
    {
        if ($dst === null) {
            $dst = getcwd();
        }

        $src = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'internal' . DIRECTORY_SEPARATOR . 'example-test-suite';

        // Figure out the plan and check for conflicts
        $plan = [];
        $conflicts = [];

        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;
            $humanTo = $to . (is_dir($from) ? DIRECTORY_SEPARATOR : '');
            $plan[] = $humanTo;
            if (file_exists($to)) {
                $conflicts[] = $humanTo;
            }
        }
        closedir($handle);

        // Warn about conflicts
        if (!empty($conflicts)) {
            $this->stdout('The following files/folders will be overwritten:' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);

            foreach ($conflicts as $file) {
                $this->stdout("- $file" . PHP_EOL, Console::FG_YELLOW);
            }

            $this->stdout(PHP_EOL);
            if ($this->interactive && !$this->confirm('Are you sure you want to continue?')) {
                $this->stdout('Aborting.' . PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $this->stdout(PHP_EOL);
        }

        // Confirm
        $this->stdout('The following files/folders will be created:' . PHP_EOL . PHP_EOL);
        foreach ($plan as $file) {
            $this->stdout("- $file" . PHP_EOL);
        }
        $this->stdout(PHP_EOL);
        if ($this->interactive && !$this->confirm('Continue?', true)) {
            $this->stdout('Aborting.' . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(PHP_EOL . 'Generating the test suite ... ');
        try {
            FileHelper::copyDirectory($src, $dst);
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stdout('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout('done.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Don't use this method - it won't actually execute anything.
     * It is just used internally to test Craft-based console controller testing.
     *
     * @return int
     * @internal
     */
    public function actionTest(): int
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
