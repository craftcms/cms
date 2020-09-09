<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\db\Table;
use craft\helpers\Console;
use yii\console\ExitCode;
use yii\db\Expression;

/**
 * Updates all users’ usernames to ensure they match their email address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.8
 */
class UpdateUsernamesController extends Controller
{
    /**
     * Updates all users’ usernames to ensure they match their email address
     *
     * @return int
     */
    public function actionIndex(): int
    {
        // Make sure useEmailAsUsername is enabled
        if (!Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $this->stderr('The useEmailAsUsername config setting is not enabled.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $affected = Craft::$app->getDb()->createCommand()
            ->update(Table::USERS, [
                'username' => new Expression('[[email]]')
            ], new Expression('[[username]] <> [[email]]'), [], false)
            ->execute();

        $this->stdout("$affected usernames updated." . PHP_EOL);

        return ExitCode::OK;
    }
}
