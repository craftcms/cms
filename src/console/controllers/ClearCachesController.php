<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\actions\ClearCacheAction;
use craft\console\Controller;
use craft\helpers\Console;
use craft\utilities\ClearCaches;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\console\ExitCode;

/**
 * Allows you to clear various Craft caches.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.37
 */
class ClearCachesController extends Controller
{
    /**
     * Lists the caches that can be cleared.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout("The following caches can be cleared:\n\n", Console::FG_YELLOW);

        $lengths = [];
        foreach ($this->actions() as $action) {
            if (($action['class'] ?? null) === ClearCacheAction::class) {
                $lengths[] = strlen($action['label']);
            }
        }
        $maxLength = max($lengths);

        foreach ($this->actions() as $id => $action) {
            if (($action['class'] ?? null) === ClearCacheAction::class) {
                $this->stdout('- ');
                $this->stdout(str_pad($id, $maxLength, ' '), Console::FG_YELLOW);
                $this->stdout('  ' . $action['label'] . PHP_EOL);
            }
        }

        $this->stdout(PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Clear all caches.
     *
     * @return int
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function actionAll(): int
    {
        foreach ($this->actions() as $id => $action) {
            if (($action['class'] ?? null) === ClearCacheAction::class) {
                $this->runAction($id);
            }
        }
        return ExitCode::OK;
    }

    /**
     * @inheritdoc
     */
    protected function defineActions(): array
    {
        $actions = parent::defineActions();

        foreach (ClearCaches::cacheOptions() as $option) {
            $actions[$option['key']] = [
                'helpSummary' => $option['label'],
                'action' => [
                    'class' => ClearCacheAction::class,
                    'action' => $option['action'],
                    'label' => $option['label'],
                    'params' => $option['params'] ?? null,
                ],
            ];
        }

        return $actions;
    }
}
