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
 * Fixes any duplicate UUIDs found within field layout components in the project config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.3
 */
class FixFieldLayoutUidsController extends Controller
{
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

        if ($count) {
            $summary = sprintf('Fixed %s duplicate %s.', $count, $count === 1 ? 'UUID' : 'UUIDs');
        } else {
            $summary = 'No duplicate UUIDs were found.';
        }

        $this->stdout('Done. ', Console::FG_GREEN);
        $this->stdout("$summary\n");


        return ExitCode::OK;
    }

    private function _fixUids(array $config, int &$count, string $path = '', array &$uids = []): void
    {
        if (isset($config['fieldLayouts']) && is_array($config['fieldLayouts'])) {
            $modified = false;

            foreach ($config['fieldLayouts'] as $fieldLayoutUid => &$fieldLayoutConfig) {
                if (isset($fieldLayoutConfig['tabs']) && is_array($fieldLayoutConfig['tabs'])) {
                    foreach ($fieldLayoutConfig['tabs'] as $tabIndex => &$tabConfig) {
                        $tabPath = ($path ? "$path." : '') . "fieldLayouts.$fieldLayoutUid.tabs.$tabIndex";
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

    private function _checkUid(array &$config, int &$count, array &$uids, bool &$modified, string $path): void
    {
        if (isset($config['uid'])) {
            if (isset($uids[$config['uid']])) {
                $config['uid'] = StringHelper::UUID();
                $count++;
                $modified = true;

                $this->stdout('    > Duplicate found at ');
                $this->stdout($path, Console::FG_CYAN);
                $this->stdout(".\n    Changing to ");
                $this->stdout($config['uid'], Console::FG_CYAN);
                $this->stdout(".\n");
            } else {
                $uids[$config['uid']] = true;
            }
        }
    }
}
