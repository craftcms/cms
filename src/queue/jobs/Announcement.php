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
use craft\enums\CmsEdition;
use craft\helpers\Db;
use craft\i18n\Translation;
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
     * @var string The announcement heading
     */
    public string $heading;

    /**
     * @var string The announcement body
     */
    public string $body;

    /**
     * @var string|null The plugin handle
     */
    public ?string $pluginHandle = null;

    /**
     * @var bool Whether only admins should receive the announcement.
     * @since 4.5.6
     */
    public bool $adminsOnly = false;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute($queue): void
    {
        if (isset($this->pluginHandle)) {
            $pluginInfo = Craft::$app->getPlugins()->getStoredPluginInfo($this->pluginHandle);
            if ($pluginInfo === null) {
                Craft::warning("Couldn’t push announcement because the plugin handle was invalid: $this->pluginHandle", __METHOD__);
                return;
            }
            $pluginId = $pluginInfo['id'];
        } else {
            $pluginId = null;
        }

        // Fetch all of the control panel users
        $userQuery = User::find();

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            $userQuery->can('accessCp');
        }

        if ($this->adminsOnly) {
            $userQuery->admin();
        }

        $totalUsers = $userQuery->count();
        $batchSize = 100;
        $dateCreated = Db::prepareDateForDb(new DateTime());
        $db = Craft::$app->getDb();

        foreach (Db::batch($userQuery, $batchSize) as $batchIndex => $batch) {
            /** @var User[] $batch */
            $this->setProgress($queue, ($batchIndex * $batchSize) / $totalUsers);

            $rows = [];

            foreach ($batch as $user) {
                $rows[] = [
                    $user->id,
                    $pluginId,
                    $this->heading,
                    $this->body,
                    $dateCreated,
                ];
            }

            $db->createCommand()->batchInsert(Table::ANNOUNCEMENTS, [
                'userId',
                'pluginId',
                'heading',
                'body',
                'dateCreated',
            ], $rows)->execute();
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Pushing announcement to control panel users');
    }
}
