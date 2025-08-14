<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use craft\console\Controller;
use craft\helpers\Console;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Db\Table;
use Illuminate\Support\Facades\DB;
use yii\console\ExitCode;

/**
 * Updates all users’ usernames to ensure they match their email address.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.8
 */
class UpdateUsernamesController extends Controller
{
    /**
     * Updates all users’ usernames to ensure they match their email address.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        // Make sure useEmailAsUsername is enabled
        if (!app(GeneralConfig::class)->useEmailAsUsername) {
            $this->stderr('The useEmailAsUsername config setting is not enabled.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $affected = DB::table(Table::USERS)
            ->whereColumn('username', '!=', 'email')
            ->update([
                'username' => DB::raw('email'),
            ]);

        $this->stdout("$affected usernames updated." . PHP_EOL);

        return ExitCode::OK;
    }
}
