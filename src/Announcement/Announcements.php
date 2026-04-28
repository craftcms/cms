<?php

declare(strict_types=1);

namespace CraftCms\Cms\Announcement;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Announcement\Jobs\SendAnnouncement;
use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\t;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

#[Singleton]
readonly class Announcements
{
    public function __construct(
        private Plugins $plugins,
    ) {}

    /**
     * Pushes a new announcement out to all control panel users.
     *
     * ::: tip
     * Run the heading and body through {@see I18N::prep} rather than {@see t()}
     * so they can be lazy-translated for users’ preferred languages rather than the current app language.
     * :::
     *
     * @param  string  $heading  The announcement heading.
     * @param  string  $body  The announcement body.
     * @param  string|null  $pluginHandle  The plugin handle, if this announcement belongs to a plugin
     * @param  bool  $adminsOnly  Whether only admin users should receive the announcement
     */
    public function push(string $heading, string $body, ?string $pluginHandle = null, bool $adminsOnly = false): void
    {
        dispatch(new SendAnnouncement(
            heading: $heading,
            body: $body,
            pluginHandle: $pluginHandle,
            adminsOnly: $adminsOnly,
        ));
    }

    /**
     * Returns any announcements for the logged-in user.
     */
    public function get(): array
    {
        $userId = Auth::user()?->getKey();

        if (! $userId) {
            return [];
        }

        $query = Announcement::query()
            ->where('userId', $userId)
            ->visible()
            ->orderByDesc('dateCreated');

        // Any enabled plugins?
        $enabledPluginHandles = Collection::make($this->plugins->getAllPlugins())
            ->map(fn (PluginInterface $plugin) => $plugin->handle);

        $query->when(
            $enabledPluginHandles->isNotEmpty(),
            fn (Builder $query) => $query->with('plugin:id,handle'),
            fn (Builder $query) => $query->whereNull('pluginId'),
        );

        return $query->get()->map(function (Announcement $announcement) {
            $plugin = ! empty($announcement->pluginId)
                ? $this->plugins->getPlugin($announcement->plugin->handle)
                : null;

            if ($plugin) {
                $icon = $this->plugins->getPluginIconSvg($plugin->handle);
                $label = $plugin->name;
            } else {
                $icon = file_get_contents(Aliases::get('@appicons/craft-cms.svg'));
                $label = 'Craft CMS';
            }

            return [
                'id' => $announcement->id,
                'icon' => $icon,
                'label' => $label,
                'heading' => Html::widont(Html::encode(t($announcement->heading))),
                'body' => Html::widont(Markdown::parseParagraph(Html::encode(t($announcement->body)))),
                'unread' => $announcement->unread,
            ];
        })->all();
    }

    /**
     * Marks the user’s announcements as read.
     *
     * @param  int[]  $ids
     */
    public function markAsRead(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $userId = Auth::user()?->getKey();

        if (! $userId) {
            return;
        }

        Announcement::query()
            ->whereIn('id', $ids)
            ->where('userId', $userId)
            ->update([
                'unread' => false,
                'dateRead' => now(),
            ]);
    }
}
