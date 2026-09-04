<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Notifications;

use Closure;
use CraftCms\Cms\Cp\Data\NotificationButtonData;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class CpNotification extends Notification
{
    public const string TYPE = 'craft-cp';

    protected string $kind;

    protected string|Closure|null $title = null;

    protected string|Closure|null $byline = null;

    protected string|Closure|null $icon = null;

    protected string|Closure|null $image = null;

    protected string|Closure|null $imageAlt = null;

    protected string|Closure|null $url = null;

    /** @var list<NotificationButtonData>|Closure(object): list<NotificationButtonData> */
    protected array|Closure $buttons = [];

    /** @param string|Closure(object): string $message */
    public function __construct(
        protected string|Closure $message,
    ) {
        $this->kind = static::class;
    }

    /** @return class-string[] */
    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return Arr::whereNotNull([
            'kind' => $this->kind,
            'title' => value($this->title, $notifiable),
            'message' => value($this->message, $notifiable),
            'byline' => value($this->byline, $notifiable),
            'icon' => value($this->icon, $notifiable),
            'image' => value($this->image, $notifiable),
            'imageAlt' => value($this->imageAlt, $notifiable),
            'url' => value($this->url, $notifiable),
            'buttons' => collect(value($this->buttons, $notifiable))->toArray(),
        ]);
    }

    public function databaseType(object $notifiable): string
    {
        return self::TYPE;
    }

    public function kind(string $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    /** @param string|Closure(object): string|null $title */
    public function title(string|Closure|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    /** @param string|Closure(object): string|null $byline */
    public function byline(string|Closure|null $byline): static
    {
        $this->byline = $byline;

        return $this;
    }

    /** @param string|Closure(object): string|null $icon */
    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  string|Closure(object): string  $url
     * @param  string|Closure(object): string  $alt
     */
    public function image(string|Closure $url, string|Closure $alt): static
    {
        $this->image = $url;
        $this->imageAlt = $alt;

        return $this;
    }

    /** @param string|Closure(object): string|null $url */
    public function url(string|Closure|null $url): static
    {
        $this->url = $url;

        return $this;
    }

    /** @param list<NotificationButtonData>|Closure(object): list<NotificationButtonData> $buttons */
    public function buttons(array|Closure $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }
}
