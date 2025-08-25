<?php

namespace CraftCms\Cms\Announcement;

use craft\helpers\Html;
use craft\helpers\Queue;
use craft\i18n\Translation;
use craft\queue\jobs\Announcement as AnnouncementJob;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use yii\helpers\Markdown;

/**
 * @since 6.0.0
 */
#[Singleton]
final readonly class Announcements
{
    public function __construct(
        private Plugins $plugins,
    ) {}

    /**
     * Pushes a new announcement out to all control panel users.
     *
     * ::: tip
     * Run the heading and body through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
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
        /** @todo: Laravel queue */
        Queue::push(new AnnouncementJob([
            'heading' => $heading,
            'body' => $body,
            'pluginHandle' => $pluginHandle,
            'adminsOnly' => $adminsOnly,
        ]));
    }

    /**
     * Returns any announcements for the logged-in user.
     *
     * @since 6.0.0
     */
    public function get(): array
    {
        $userId = Auth::user()?->getKey();

        if (! $userId) {
            return [];
        }

        $query = Announcement::query()
            ->where('userId', $userId)
            ->visible();

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
                'heading' => Html::widont(Html::encode(Translation::translate($announcement->heading))),
                'body' => Html::widont(Markdown::processParagraph(Html::encode(Translation::translate($announcement->body)))),
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
