<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Mixins;

use Closure;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Session\SessionManager;

class SessionMixin
{
    public function broadcastToJs(): Closure
    {
        return function (string|array $message): void {
            if (! request()->isCpRequest()) {
                return;
            }

            $message = Json::encode($message);

            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            $this->flashJs(<<<JS
if (Craft?.broadcaster) {
    Craft.broadcaster.postMessage($message);
}
JS, Position::Ready);
        };
    }

    public function getJs(): Closure
    {
        return function (bool $delete = true): array {
            if ($delete) {
                /**
                 * @var SessionManager $this
                 *
                 * @phpstan-ignore-next-line
                 */
                return $this->pull('__js', []);
            }

            /**
             * @var SessionManager $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->get('__js', []);
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
