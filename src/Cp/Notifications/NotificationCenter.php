<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Notifications;

use Carbon\CarbonInterface;
use CraftCms\Cms\Cp\Data\NotificationButtonData;
use CraftCms\Cms\Cp\Data\NotificationData;
use CraftCms\Cms\Cp\Enums\ButtonVariant;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

#[Singleton]
readonly class NotificationCenter
{
    /** @return list<NotificationData> */
    public function get(): array
    {
        $user = currentUser();

        if ($user === null) {
            return [];
        }

        $notifications = $this->notifications($user);

        if ($notifications === null) {
            return [];
        }

        return $notifications
            ->where('type', CpNotification::TYPE)
            ->where(fn (Builder $query) => $query
                ->whereNull('read_at')
                ->orWhere('read_at', '>', now()->subDays(7)))
            ->get()
            ->map(fn (DatabaseNotification $notification): NotificationData => $this->prepare($notification))
            ->all();
    }

    /** @param list<string> $ids */
    public function markAsRead(array $ids): void
    {
        if ($ids === [] || ! ($user = currentUser())) {
            return;
        }

        $notifications = $this->notifications($user);

        if ($notifications === null) {
            return;
        }

        $notifications
            ->reorder()
            ->where('type', CpNotification::TYPE)
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);
    }

    /** @return MorphMany<DatabaseNotification, Model>|null */
    private function notifications(CraftUser $user): ?MorphMany
    {
        if (! method_exists($user, 'notifications')) {
            return null;
        }

        return $user->notifications();
    }

    private function prepare(DatabaseNotification $notification): NotificationData
    {
        /** @var array<string, mixed> $data */
        $data = $notification->getAttribute('data');
        /** @var CarbonInterface $createdAt */
        $createdAt = $notification->getAttribute('created_at');

        return new NotificationData(
            id: $notification->id,
            kind: $data['kind'],
            title: isset($data['title']) ? t($data['title']) : null,
            messageHtml: Html::widont(Markdown::parseParagraph(Html::encode(t($data['message'])))),
            byline: isset($data['byline']) ? t($data['byline']) : null,
            icon: $data['icon'] ?? 'bell',
            image: $data['image'] ?? null,
            imageAlt: isset($data['imageAlt']) ? t($data['imageAlt']) : null,
            url: $data['url'] ?? null,
            buttons: array_map(fn (array $button): NotificationButtonData => new NotificationButtonData(
                label: t($button['label']),
                url: $button['url'],
                target: $button['target'] ?? null,
                icon: $button['icon'] ?? null,
                variant: ButtonVariant::from($button['variant'] ?? ButtonVariant::Solid->value),
            ), $data['buttons']),
            createdAt: $createdAt->toIso8601String(),
            createdAtLabel: I18N::getFormatter()->asRelativeTime($createdAt),
            unread: $notification->getAttribute('read_at') === null,
        );
    }
}
