<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Notifications;

use Closure;
use CraftCms\Cms\Cp\Data\NotificationButtonData;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\SerializableClosure;
use UnexpectedValueException;

class CpNotification extends Notification
{
    public const string TYPE = 'craft-cp';

    protected string $kind;

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

    /** @return class-string[] */
    public function via(CraftUser $notifiable): array
    {
        return [DatabaseChannel::class];
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
