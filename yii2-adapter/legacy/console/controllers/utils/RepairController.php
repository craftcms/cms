<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use craft\console\Controller;
use craft\helpers\Console;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use yii\console\ExitCode;

/**
 * Repairs data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.24
 */
class RepairController extends Controller
{
    /**
     * @var bool Whether to only do a dry run of the repair process.
     */
    public bool $dryRun = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'dryRun';
        return $options;
    }

    /**
     * Repairs double-packed associative arrays in the project config.
     *
     * @since 3.4.26
     */
    public function actionProjectConfig(): int
    {
        $projectConfigService = app(ProjectConfig::class);
        $config = $projectConfigService->get();

        $this->stdout('Repairing project config ...' . PHP_EOL);
        foreach ($config as $key => $value) {
            $this->_repairProjectConfigItem($projectConfigService, $key, $value);
        }
        $this->stdout('Finished repairing project config' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Repairs a single item within the project config, recursively.
     *
     * @param ProjectConfig $projectConfigService
     * @param string $path
     * @param mixed $value
     * @return mixed
     */
    private function _repairProjectConfigItem(ProjectConfig $projectConfigService, string $path, mixed $value): mixed
    {
        if (is_array($value)) {
            // Is this a packed array?
            if (isset($value[ProjectConfig::ASSOC_KEY])) {
                $double = false;
                while (
                    isset($value[ProjectConfig::ASSOC_KEY][0][0]) &&
                    $value[ProjectConfig::ASSOC_KEY][0][0] === ProjectConfig::ASSOC_KEY
                ) {
                    $value[ProjectConfig::ASSOC_KEY] = $value[ProjectConfig::ASSOC_KEY][0][1] ?? [];
                    $double = true;
                }

                if ($double) {
                    $this->stdout("- double-packed array found at $path" . PHP_EOL);
                    $projectConfigService->set($path, $value);
                }
            }

            foreach ($value as $k => $v) {
                $value[$k] = $this->_repairProjectConfigItem($projectConfigService, "$path.$k", $v);
            }
        }

        return $value;
    }
}
