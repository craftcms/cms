<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $pluginInfo = app(Plugins::class)->getStoredPluginInfo($this->pluginHandle);
            if ($pluginInfo === null) {
                Log::warning("Couldn’t push announcement because the plugin handle was invalid: $this->pluginHandle", [__METHOD__]);
                return;
            }
            $pluginId = $pluginInfo['id'];
        } else {
            $pluginId = null;
        }

        // Fetch all of the control panel users
        $userQuery = User::find();

        if (Edition::get()->value >= Edition::Pro->value) {
            $userQuery->can('accessCp');
        }

        if ($this->adminsOnly) {
            $userQuery->admin();
        }

        $totalUsers = $userQuery->count();
        $batchSize = 100;
        $dateCreated = now();

        $userQuery->chunk($batchSize, function(Collection $users, int $batchIndex) use ($dateCreated, $pluginId, $totalUsers, $batchSize, $queue) {
            $this->setProgress($queue, ($batchIndex * $batchSize) / $totalUsers);

            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    'userId' => $user->id,
                    'pluginId' => $pluginId,
                    'heading' => $this->heading,
                    'body' => $this->body,
                    'dateCreated' => $dateCreated,
                ];
            }

            DB::table(Table::ANNOUNCEMENTS)->insert($rows);
        });
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Pushing announcement to control panel users');
    }
}
