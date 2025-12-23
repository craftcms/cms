<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\StringHelper;
use yii\console\ExitCode;

/**
 * Fixes any duplicate or missing UUIDs found within field layout components in the project config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.3
 */
class FixFieldLayoutUidsController extends Controller
{
    private array $topLevelUids = [];

    /**
     * Fixes any duplicate UUIDs found within field layout components in the project config.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout("Looking for duplicate UUIDs ...\n");
        $count = 0;
        $this->_fixUids(Craft::$app->getProjectConfig()->get(), $count);

        $this->fixFieldLayoutUids($count);

        if ($count) {
            $summary = sprintf('Fixed %s duplicate or missing %s.', $count, $count === 1 ? 'UUID' : 'UUIDs');
        } else {
            $summary = 'No duplicate UUIDs were found.';
        }

        $this->stdout('Done. ', Console::FG_GREEN);
        $this->stdout("$summary\n");


        return ExitCode::OK;
    }

    private function _fixUids(array $config, int &$count, string $path = '', array &$uids = []): void
    {
        if (is_array($config['fieldLayouts'] ?? null)) {
            $modified = false;
            foreach ($config['fieldLayouts'] as $fieldLayoutUid => &$fieldLayoutConfig) {
                $this->topLevelUids[$fieldLayoutUid][] = $path;
                if (is_array($fieldLayoutConfig)) {
                    $fieldLayoutPath = sprintf('%sfieldLayouts.%s', $path ? "$path." : '', $fieldLayoutUid);
                    $this->_fixUidsInLayout($fieldLayoutConfig, $count, $fieldLayoutPath, $uids, $modified);
                }
            }
            if ($modified) {
                Craft::$app->getProjectConfig()->set($path, $config);
            }
            return;
        }

        if (is_array($config['fieldLayout'] ?? null)) {
            $modified = false;
            $fieldLayoutPath = sprintf('%sfieldLayout', $path ? "$path." : '');
            $this->_fixUidsInLayout($config['fieldLayout'], $count, $fieldLayoutPath, $uids, $modified);
            if ($modified) {
                Craft::$app->getProjectConfig()->set($path, $config);
            }
            return;
        }

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->_fixUids($value, $count, ($path ? "$path." : '') . $key, $uids);
            }
        }
    }

    private function _fixUidsInLayout(
        array &$fieldLayoutConfig,
        int &$count,
        string $path,
        array &$uids,
        bool &$modified,
    ): void {
        if (isset($fieldLayoutConfig['tabs']) && is_array($fieldLayoutConfig['tabs'])) {
            foreach ($fieldLayoutConfig['tabs'] as $tabIndex => &$tabConfig) {
                $tabPath = "$path.tabs.$tabIndex";
                $this->_checkUid($tabConfig, $count, $uids, $modified, $tabPath);

                if (isset($tabConfig['elements']) && is_array($tabConfig['elements'])) {
                    foreach ($tabConfig['elements'] as $elementIndex => &$elementConfig) {
                        $elementPath = "$tabPath.elements.$elementIndex";
                        $this->_checkUid($elementConfig, $count, $uids, $modified, $elementPath);
                    }
                }
            }
        }
    }

    private function _checkUid(array &$config, int &$count, array &$uids, bool &$modified, string $path): void
    {
        if (empty($config['uid'])) {
            $reason = 'Missing UUID found at';
        } elseif (isset($uids[$config['uid']])) {
            $reason = 'Duplicate UUID at';
        } else {
            $uids[$config['uid']] = true;
            return;
        }

        $config['uid'] = StringHelper::UUID();
        $count++;
        $modified = true;

        $this->stdout("    > $reason ");
        $this->stdout($path, Console::FG_CYAN);
        $this->stdout(".\n    Setting to ");
        $this->stdout($config['uid'], Console::FG_CYAN);
        $this->stdout(".\n");
    }

    private function fixFieldLayoutUids(int &$count): void
    {
        foreach ($this->topLevelUids as $fieldLayoutUid => $paths) {
            if (count($paths) == 1) {
                unset($this->topLevelUids[$fieldLayoutUid]);
            }
        }

        // we still have some duplicates remaining
        if (!empty($this->topLevelUids)) {
            foreach ($this->topLevelUids as $fieldLayoutUid => $paths) {
                // leave the first path as is
                array_shift($paths);
                // all others need to have their UIDs adjusted
                foreach ($paths as $path) {
                    $newUid = StringHelper::UUID();
                    $config = Craft::$app->getProjectConfig()->get($path);
                    $innerConfig = $config['fieldLayouts'][$fieldLayoutUid];
                    unset($config['fieldLayouts'][$fieldLayoutUid]);
                    $config['fieldLayouts'][$newUid] = $innerConfig;

                    $this->stdout("    > Duplicate UUID at ");
                    $this->stdout($path . ".fieldLayouts." . $fieldLayoutUid, Console::FG_CYAN);
                    $this->stdout(".\n    Setting to ");
                    $this->stdout($newUid, Console::FG_CYAN);
                    $this->stdout(".\n");

                    Craft::$app->getProjectConfig()->set($path, $config);
                    $count++;
                }
            }
        }
    }
}
