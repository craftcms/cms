<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Announcement\Jobs;

use craft\services\Announcements;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Override;

class SendAnnouncement extends Job
{
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
        if (isset($this->pluginHandle) && $plugins->getStoredPluginInfo($this->pluginHandle) === null) {
            Log::warning("Couldn't push announcement because the plugin handle was invalid: $this->pluginHandle", [__METHOD__]);

            return;
        }

        $byline = isset($this->pluginHandle)
            ? $plugins->getPlugin($this->pluginHandle)->name ?? $this->pluginHandle
            : 'Craft CMS';

        $userQuery = UserElement::find();

        if (Edition::isAtLeast(Edition::Pro)) {
            $userQuery->can('accessCp');
        }

        if ($this->adminsOnly) {
            $userQuery->admin();
        }

        $totalUsers = $userQuery->count();
        $batchSize = 100;
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model');

        $userQuery->chunk($batchSize, function(Collection $users, int $batchIndex) use ($totalUsers, $batchSize, $byline, $userModel): void {
            $this->setProgress((int) ((($batchIndex * $batchSize) / max($totalUsers, 1)) * 100));

            $userModels = $userModel::query()
                ->whereKey($users->pluck('id'))
                ->get();

            Notification::send($userModels, new CpNotification($this->body)
                ->kind(Announcements::KIND)
                ->title($this->heading)
                ->byline($byline)
                ->icon($this->pluginHandle === null ? 'custom-icons/craft-cms' : null));
        });
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Pushing announcement to control panel users');
    }
}
