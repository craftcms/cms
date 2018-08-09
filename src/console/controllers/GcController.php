<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Garbage collector.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class GcController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'run';

    /**
     * Runs garbage collection.
     *
     * @return int
     */
    public function actionRun(): int
    {
        $this->stdout('Running garbage collection ... ', Console::FG_YELLOW);
        Craft::$app->getGc()->run(true);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
