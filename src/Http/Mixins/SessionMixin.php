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
     * Queues a message for the next page to broadcast to every open control panel tab.
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
            $this->flash('__broadcasts', [...$this->get('__broadcasts', []), $message]);
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
            $scripts = $this->getJs();
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
