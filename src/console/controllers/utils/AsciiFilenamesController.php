<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use craft\errors\InvalidElementException;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use Throwable;
use yii\console\ExitCode;
use yii\db\Expression;

/**
 * Converts all non-ASCII asset filenames to ASCII.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.12
 */
class AsciiFilenamesController extends Controller
{
    /**
     * Converts all non-ASCII asset filenames to ASCII.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        // Make sure convertFilenamesToAscii is true now
        if (!Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii) {
            $warning = <<<EOD
The convertFilenamesToAscii config setting is set to false.
To avoid saving assets with non-ASCII filenames in the future,
change it to true in config/general.php.
EOD;
            Console::outputWarning($warning);
        }

        // Find assets with non-ASCII characters
        $query = Asset::find();
        if (Craft::$app->getDb()->getIsMysql()) {
            // h/t https://stackoverflow.com/a/11741314/1688568
            $query->andWhere(new Expression('[[filename]] <> CONVERT([[filename]] USING ASCII)'));
        } else {
            // h/t https://dba.stackexchange.com/a/167571/205387
            $query->andWhere(new Expression("[[filename]] ~ '[^[:ascii:]]'"));
        }

        /** @var Asset[] $assets */
        $assets = $query->all();
        $total = count($assets);

        if ($total === 0) {
            $this->stdout('No assets found with non-ASCII filenames.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("$total assets found with non-ASCII filenames:" . PHP_EOL . PHP_EOL);

        foreach ($assets as $i => $asset) {
            if ($i === 4 && $total !== 5) {
                $this->stdout('    - ...' . PHP_EOL);
                break;
            }

            $this->stdout("    - {$asset->getFilename()}" . PHP_EOL);
        }

        $this->stdout(PHP_EOL);

        if ($this->interactive && !$this->confirm('Ready to rename these filenames as ASCII?', true)) {
            return ExitCode::OK;
        }

        $this->stdout(PHP_EOL);
        $successCount = 0;
        $failCount = 0;

        foreach ($assets as $asset) {
            $asset->newFilename = FileHelper::sanitizeFilename($asset->getFilename(), [
                'asciiOnly' => true,
            ]);
            $this->stdout("    - Renaming {$asset->getFilename()} to $asset->newFilename ... ");
            try {
                if (!Craft::$app->getElements()->saveElement($asset)) {
                    throw new InvalidElementException($asset, implode(', ', $asset->getFirstErrors()));
                }
                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                $successCount++;
            } catch (Throwable $e) {
                $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                if (!$e instanceof InvalidElementException) {
                    Craft::$app->getErrorHandler()->logException($e);
                }
                $failCount++;
            }
        }

        $this->stdout(PHP_EOL);

        if ($successCount && $failCount) {
            $this->stdout("Successfully renamed $successCount assets, but $failCount assets failed." . PHP_EOL . PHP_EOL);
        } elseif ($successCount) {
            $this->stdout("Successfully renamed $successCount assets." . PHP_EOL . PHP_EOL);
        } else {
            $this->stdout("Failed to rename $failCount assets." . PHP_EOL . PHP_EOL);
        }

        return ExitCode::OK;
    }
}
