<?php

declare(strict_types=1);

namespace CraftCms\Cms\Announcement\Jobs;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Override;

class SendAnnouncement extends Job
{
    /**
     * Creates a new SendAnnouncement job.
     *
     * @param  string  $heading  The announcement heading.
     * @param  string  $body  The announcement body.
     * @param  string|null  $pluginHandle  The plugin handle.
     * @param  bool  $adminsOnly  Whether only admins should receive the announcement.
     */
    public function __construct(
        public string $heading,
        public string $body,
        public ?string $pluginHandle = null,
        public bool $adminsOnly = false,
    ) {
        parent::__construct();
    }

    public function handle(Plugins $plugins): void
    {
        if (isset($this->pluginHandle)) {
            $pluginInfo = $plugins->getStoredPluginInfo($this->pluginHandle);

            if ($pluginInfo === null) {
                Log::warning("Couldn't push announcement because the plugin handle was invalid: $this->pluginHandle", [__METHOD__]);

                return;
            }
            $pluginId = $pluginInfo['id'];
        } else {
            $pluginId = null;
        }

        // Fetch all of the control panel users
        $userQuery = User::find();

        if (Edition::isAtLeast(Edition::Pro)) {
            $userQuery->can('accessCp');
        }

        if ($this->adminsOnly) {
            $userQuery->admin();
        }

        $totalUsers = $userQuery->count();
        $batchSize = 100;
        $dateCreated = now();

        $userQuery->chunk($batchSize, function (Collection $users, int $batchIndex) use ($dateCreated, $pluginId, $totalUsers, $batchSize): void {
            $this->setProgress((int) ((($batchIndex * $batchSize) / max($totalUsers, 1)) * 100));

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

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Pushing announcement to control panel users');
    }
}
