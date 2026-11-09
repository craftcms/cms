<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Mixins;

use Closure;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Session\SessionManager;

/**
 * @since 6.0.0
 */
class SessionMixin
{
    /**
     * The most messages a response broadcasts. A bulk save would otherwise list every element it touched; past this,
     * {@see OVERFLOW_BROADCAST} stands in for the rest.
     */
    public const int MAX_BROADCASTS = 50;

    /**
     * Sent after {@see MAX_BROADCASTS} messages, to say that more were left out. Listeners for element changes refresh
     * every element they show.
     */
    public const array OVERFLOW_BROADCAST = ['event' => 'broadcastOverflow'];

    /**
     * Queues a message for every open control panel tab, including the one the request came from. It goes out with
     * the response, or the next page after a redirect.
     */
    public function broadcastToJs(): Closure
    {
        return function (string|array $message): void {
            if (! request()->isCpRequest()) {
                return;
            }

            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            $messages = $this->get('__broadcasts', []);

            if (in_array($message, $messages, true) || in_array(SessionMixin::OVERFLOW_BROADCAST, $messages, true)) {
                return;
            }

            if (count($messages) >= SessionMixin::MAX_BROADCASTS) {
                $message = SessionMixin::OVERFLOW_BROADCAST;
            }

            $this->flash('__broadcasts', [...$messages, $message]);
        };
    }

    /**
     * Returns the messages queued with `broadcastToJs()`, which Inertia pages broadcast themselves.
     */
    public function getBroadcasts(): Closure
    {
        return function (bool $delete = true): array {
            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            return $delete ? $this->pull('__broadcasts', []) : $this->get('__broadcasts', []);
        };
    }

    /**
     * Returns the JS flashed for the next page, including scripts that broadcast the messages queued with
     * `broadcastToJs()`.
     */
    public function getJs(): Closure
    {
        return function (bool $delete = true): array {
            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            $scripts = $delete ? $this->pull('__js', []) : $this->get('__js', []);

            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            foreach ($this->getBroadcasts($delete) as $message) {
                $message = Json::encode($message);
                $scripts[] = [
                    "window.dispatchEvent(new CustomEvent('craft:broadcast', {detail: $message}));",
                    Position::Ready->value,
                    null,
                ];
            }

            return $scripts;
        };
    }

    public function flashJs(): Closure
    {
        return function (string $js, Position $position = Position::Head, ?string $key = null): void {
            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            $scripts = $this->get('__js', []);
            $scripts[] = [$js, $position->value, $key];
            $this->flash('__js', $scripts);
        };
    }

    public function getSuccess(): Closure
    {
        return fn () => Flash::getSuccess();
    }

    public function getError(): Closure
    {
        return fn () => Flash::getError();
    }

    public function getNotice(): Closure
    {
        return fn () => Flash::getNotice();
    }
}
