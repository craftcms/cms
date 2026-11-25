<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification;

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends critical notifications to users with the `receiveCriticalNotifications` permission.
 *
 * Each user gets a problem once. They get it again when its details change, when they read it
 * and it’s still there 7 days later, or when it’s fixed and comes back.
 *
 * @since 6.0.0
 */
#[Singleton]
class CriticalNotifications
{
    /** @var list<class-string<CriticalCheck>> */
    private array $checks = [];

    /** @param class-string<CriticalCheck> $check */
    public function registerCheck(string $check): void
    {
        $this->checks[] = $check;
    }

    /** Runs every registered check, sending notifications for problems and resolving the rest. */
    public function runChecks(): void
    {
        foreach (array_unique($this->checks) as $class) {
            /** @var CriticalCheck $check */
            $check = app($class);

            try {
                $notification = $check->run();
            } catch (Throwable $e) {
                report($e);

                continue;
            }

            if ($notification === null) {
                $this->resolve($check->key());
            } else {
                $this->send($notification);
            }
        }
    }

    /** @return bool Whether anyone was sent the notification, rather than all skipped as repeats */
    public function send(CriticalNotification $notification): bool
    {
        $recipients = User::find()
            ->status(User::STATUS_ACTIVE)
            ->collect()
            ->filter(fn (User $user): bool => $user->can('receiveCriticalNotifications'))
            ->reject(fn (User $user): bool => $this->recent($notification->key, $user)
                ->contains(fn (DatabaseNotification $row): bool => $this->data($row)['fingerprint'] === $notification->fingerprint));

        if ($recipients->isEmpty()) {
            return false;
        }

        // The new details replace the old ones
        foreach ($recipients as $recipient) {
            $this->markAsRead($this->recent($notification->key, $recipient)->whereNull('read_at'));
        }

        Notification::send($recipients, $notification);

        return true;
    }

    /** Marks a problem as fixed, so it’s sent again if it comes back. */
    public function resolve(string $key): void
    {
        $this->markAsRead($this->recent($key), resolved: true);
    }

    /**
     * Returns the problem’s notifications that are unread, or were read in the last 7 days and not resolved.
     *
     * @return Collection<int, DatabaseNotification>
     */
    private function recent(string $key, ?User $user = null): Collection
    {
        $query = $user?->notifications() ?? DatabaseNotification::query();

        return $query
            ->where('type', CpNotification::TYPE)
            ->where(fn (Builder $query) => $query
                ->whereNull('read_at')
                // Laravel writes notifications, in the system time zone
                ->orWhere('read_at', '>', now()->subDays(7)->toDateTimeString()))
            ->get()
            ->filter(fn (DatabaseNotification $row): bool => ($this->data($row)['kind'] ?? null) === CriticalNotification::KIND
                && ($this->data($row)['key'] ?? null) === $key
                && ! ($this->data($row)['resolved'] ?? false))
            ->values();
    }

    /** @param Collection<int, DatabaseNotification> $rows */
    private function markAsRead(Collection $rows, bool $resolved = false): void
    {
        foreach ($rows as $row) {
            $row->forceFill([
                'read_at' => $row->getAttribute('read_at') ?? now()->toDateTimeString(),
                'data' => $resolved ? [...$this->data($row), 'resolved' => true] : $this->data($row),
            ])->save();
        }
    }

    /** @return array<string, mixed> */
    private function data(DatabaseNotification $row): array
    {
        return $row->getAttribute('data');
    }
}
