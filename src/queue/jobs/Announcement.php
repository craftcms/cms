<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\queue\BaseJob;
use DateTime;
use yii\base\Exception;

/**
 * Announcement job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class Announcement extends BaseJob
{
    /**
     * @var string|string[] The announcement heading
     */
    public $heading;

    /**
     * @var string|string[] The announcement body
     */
    public $body;

    /**
     * @var string|null The plugin handle
     */
    public $pluginHandle;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute($queue)
    {
        if ($this->pluginHandle !== null) {
            $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo($this->pluginHandle);
            if ($pluginInfo === null) {
                Craft::warning("Couldn’t push announcement because the plugin handle was invalid: $this->pluginHandle", __METHOD__);
                return;
            }
            $pluginId = $pluginInfo['id'];
        } else {
            $pluginId = null;
        }

        // Fetch all of the CP users
        $userQuery = User::find()
            ->can('accessCp');

        $totalUsers = $userQuery->count();
        $batchSize = 100;
        $dateCreated = Db::prepareDateForDb(new DateTime());
        $db = Craft::$app->getDb();

        foreach (Db::batch($userQuery, $batchSize) as $batchIndex => $batch) {
            /** @var User[] $batch */
            $this->setProgress($queue, ($batchIndex * $batchSize) / $totalUsers);

            $rows = [];

            foreach ($batch as $user) {
                $heading = $this->heading;
                $body = $this->body;

                if (is_array($heading) || is_array($body)) {
                    $language = $user->getPreferredLanguage() ?? Craft::$app->language;
                    if (is_array($heading)) {
                        $heading = $heading[$language] ?? $heading[Craft::$app->language] ?? reset($heading);
                    }
                    if (is_array($body)) {
                        $body = $body[$language] ?? $body[Craft::$app->language] ?? reset($body);
                    }
                }

                $rows[] = [
                    $user->id,
                    $pluginId,
                    $heading,
                    $body,
                    $dateCreated,
                ];
            }

            $db->createCommand()->batchInsert(Table::ANNOUNCEMENTS, [
                'userId',
                'pluginId',
                'heading',
                'body',
                'dateCreated',
            ], $rows, false)->execute();
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Pushing announcement to control panel users');
    }
}
