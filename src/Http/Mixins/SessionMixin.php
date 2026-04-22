<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Mixins;

use Closure;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;

class SessionMixin
{
    public function broadcastToJs(): Closure
    {
        return function (string|array $message): void {
            if (! request()->isCpRequest()) {
                return;
            }

            $jsonMessage = Json::encode($message);

            $this->flashJs();
        };
    }

    public function getJs(): Closure
    {
        return function (bool $delete = true): array {
            if ($delete) {
                return $this->pull('__js', []);
            }

            return $this->get('__js', []);
        };
    }

    public function flashJs(): Closure
    {
        return function (string $js, Position $position = Position::Head, ?string $key = null): void {
            $scripts = $this->getJs();
            $scripts[] = [$js, $position->value, $key];
            $this->flash('__js', $scripts);
        };
    }
}
