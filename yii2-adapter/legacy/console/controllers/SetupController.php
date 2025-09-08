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
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use m150207_210500_i18n_init;
use yii\console\ExitCode;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class SetupController extends Controller
{
    /**
     * Creates a database table for storing PHP session information.
     *
     * @return int
     * @since 3.4.0
     */
    public function actionPhpSessionTable(): int
    {
        $this->stdout('Running this command is deprecated, run `php craft make:session-table` instead.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);

        if (Schema::hasTable(Table::PHPSESSIONS)) {
            $this->stdout("The `phpsessions` table already exists.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        Artisan::call('make:session-table');
        Artisan::call('migrate');

        $this->stdout("The `phpsessions` table was created successfully.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Creates database tables for storing message translations. (EXPERIMENTAL!)
     *
     * @return int
     * @since 4.5.0
     */
    public function actionMessageTables(): int
    {
        $db = Craft::$app->getDb();
        if ($db->tableExists('{{%source_message}}')) {
            $this->stdout("The `source_message` table already exists.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        if ($db->tableExists('{{%message}}')) {
            $this->stdout("The `message` table already exists.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        require Craft::getAlias('@vendor/yiisoft/yii2/i18n/migrations/m150207_210500_i18n_init.php');
        /** @phpstan-ignore-next-line */
        $migration = new m150207_210500_i18n_init();
        /** @phpstan-ignore-next-line */
        if ($migration->up() === false) {
            $this->stderr("An error occurred while creating the `source_message` and `message` tables.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("The `source_message` and `message` tables were created successfully.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Creates a database table for storing DB caches.
     *
     * @return int
     * @since 3.4.14
     */
    public function actionDbCacheTable(): int
    {
        $this->stdout('Running this command is deprecated, run `php craft make:cache-table` instead.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);

        if (Schema::hasTable(Table::CACHE)) {
            $this->stdout('The `cache` table already exists.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        Artisan::call('make:cache-table');
        Artisan::call('migrate');

        $this->stdout('The `cache` table was created successfully.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
