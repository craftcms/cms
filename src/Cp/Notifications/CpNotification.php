<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Notifications;

use Closure;
use CraftCms\Cms\Cp\Data\NotificationButtonData;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Laravel\SerializableClosure\SerializableClosure;
use UnexpectedValueException;

use function CraftCms\Cms\t;

/**
 * A notification shown in the control panel’s notification center, and optionally sent through other channels.
 *
 * Projects choose the channels for each kind of notification with {@see viaUsing()}, and add the
 * `toX()` methods other channels need as macros:
 *
 * ```php
 * CpNotification::viaUsing(fn (CpNotification $notification, CraftUser $notifiable) => match ($notification->kind) {
 *     'critical' => [...$notification->defaultChannels($notifiable), 'slack'],
 *     default => $notification->defaultChannels($notifiable),
 * });
 *
 * CpNotification::macro('toSlack', fn (CraftUser $notifiable) => (new SlackMessage)
 *     ->text($this->toDatabase($notifiable)['message']));
 * ```
 *
 * @since 6.0.0
 */
class CpNotification extends Notification
{
    use Macroable;

    public const string TYPE = 'craft-cp';

    /** @var (Closure(CpNotification, CraftUser): list<string>)|null */
    private static ?Closure $viaUsing = null;

    public protected(set) string $kind;

    protected string|SerializableClosure|null $title = null;

    protected string|SerializableClosure|null $byline = null;

    protected string|SerializableClosure|null $icon = null;

    protected string|SerializableClosure|null $image = null;

    protected string|SerializableClosure|null $imageAlt = null;

    protected string|SerializableClosure|null $url = null;

    /** @var list<NotificationButtonData>|SerializableClosure */
    protected array|SerializableClosure $buttons = [];

    protected string|SerializableClosure $message;

    /** @param string|Closure(CraftUser): string $message */
    public function __construct(string|Closure $message)
    {
        $this->kind = static::class;
        $this->message = $this->serializable($message);
    }

    /**
     * Sets a callback that returns the channels a notification is sent through, in place of its {@see defaultChannels()}.
     *
     * @param  (Closure(CpNotification, CraftUser): list<string>)|null  $callback
     */
    public static function viaUsing(?Closure $callback): void
    {
        self::$viaUsing = $callback;
    }

    /** @return list<string> */
    public function via(CraftUser $notifiable): array
    {
        return self::$viaUsing !== null
            ? (self::$viaUsing)($this, $notifiable)
            : $this->defaultChannels($notifiable);
    }

    /** @return list<string> The channels used when the project hasn’t set {@see viaUsing()} */
    public function defaultChannels(CraftUser $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toMail(CraftUser $notifiable): SystemMessageMailable
    {
        $url = $this->resolve($this->url, $notifiable);

        return app(SystemMessages::class)->mailable(
            key: 'cp_notification',
            user: $notifiable->asElement(),
            variables: [
                'title' => t($this->resolve($this->title, $notifiable) ?? 'Notification'),
                'message' => t($this->resolve($this->message, $notifiable)),
                'link' => $url !== null ? Template::raw(Url::url($url)) : null,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toDatabase(CraftUser $notifiable): array
    {
        return Arr::whereNotNull([
            'kind' => $this->kind,
            'title' => $this->resolve($this->title, $notifiable),
            'message' => $this->resolve($this->message, $notifiable),
            'byline' => $this->resolve($this->byline, $notifiable),
            'icon' => $this->resolve($this->icon, $notifiable),
            'image' => $this->resolve($this->image, $notifiable),
            'imageAlt' => $this->resolve($this->imageAlt, $notifiable),
            'url' => $this->resolve($this->url, $notifiable),
            'buttons' => collect($this->resolveButtons($notifiable))->toArray(),
        ]);
    }

    public function databaseType(CraftUser $notifiable): string
    {
        return self::TYPE;
    }

    public function kind(string $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    /** @param string|Closure(CraftUser): string|null $title */
    public function title(string|Closure|null $title): static
    {
        $this->title = $this->serializable($title);

        return $this;
    }

    /** @param string|Closure(CraftUser): string|null $byline */
    public function byline(string|Closure|null $byline): static
    {
        $this->byline = $this->serializable($byline);

        return $this;
    }

    /** @param string|Closure(CraftUser): string|null $icon */
    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $this->serializable($icon);

        return $this;
    }

    /**
     * @param  string|Closure(CraftUser): string  $url
     * @param  string|Closure(CraftUser): string  $alt
     */
    public function image(string|Closure $url, string|Closure $alt): static
    {
        $this->image = $this->serializable($url);
        $this->imageAlt = $this->serializable($alt);

        return $this;
    }

    /** @param string|Closure(CraftUser): string|null $url */
    public function url(string|Closure|null $url): static
    {
        $this->url = $this->serializable($url);

        return $this;
    }

    /** @param list<NotificationButtonData>|Closure(CraftUser): list<NotificationButtonData> $buttons */
    public function buttons(array|Closure $buttons): static
    {
        $this->buttons = $buttons instanceof Closure ? new SerializableClosure($buttons) : $buttons;

        return $this;
    }

    private function serializable(string|Closure|null $value): string|SerializableClosure|null
    {
        return $value instanceof Closure ? new SerializableClosure($value) : $value;
    }

    private function resolve(string|SerializableClosure|null $value, CraftUser $notifiable): mixed
    {
        return $value instanceof SerializableClosure ? $value($notifiable) : $value;
    }

    /** @return array<array-key, mixed> */
    private function resolveButtons(CraftUser $notifiable): array
    {
        if (is_array($this->buttons)) {
            return $this->buttons;
        }

        $buttons = ($this->buttons)($notifiable);

        if (! is_array($buttons)) {
            throw new UnexpectedValueException('CP notification button callbacks must return an array.');
        }

        return $buttons;
    }
}
