<?php

declare(strict_types=1);

namespace craft\services;

use CraftCms\Cms\Cp\Components\Icon;
use CraftCms\Cms\Cp\Notifications\NotificationCenter;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Announcement\Jobs\SendAnnouncement;
use yii\base\Component;

/**
 * @deprecated 6.0.0
 */
class Announcements extends Component
{
    public const string KIND = 'announcement';

    public function push(string $heading, string $body, ?string $pluginHandle = null, bool $adminsOnly = false): void
    {
        dispatch(new SendAnnouncement(
            heading: $heading,
            body: $body,
            pluginHandle: $pluginHandle,
            adminsOnly: $adminsOnly,
        ));
    }

    /** @return list<array{id: string, icon: string|false, label: string, heading: string, body: string, unread: bool}> */
    public function get(): array
    {
        return collect(app(NotificationCenter::class)->get())
            ->where('kind', self::KIND)
            ->map(fn($notification): array => [
                'id' => $notification->id,
                'icon' => $notification->icon ? Icon::make()->name($notification->icon)->toHtml() : false,
                'label' => $notification->byline ?? 'Craft CMS',
                'heading' => Html::widont(Html::encode($notification->title ?? '')),
                'body' => $notification->messageHtml,
                'unread' => $notification->unread,
            ])
            ->all();
    }

    /** @param string[] $ids */
    public function markAsRead(array $ids): void
    {
        $ids = collect(app(NotificationCenter::class)->get())
            ->where('kind', self::KIND)
            ->pluck('id')
            ->intersect($ids)
            ->values()
            ->all();

        app(NotificationCenter::class)->markAsRead($ids);
    }
}
