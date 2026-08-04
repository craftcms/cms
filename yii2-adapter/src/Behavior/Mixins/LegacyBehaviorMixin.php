<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Behavior\Mixins;

use Closure;
use CraftCms\Yii2Adapter\Behavior\LegacyBehaviorCompatibility;
use yii\base\Behavior;
use yii\base\Event;

class LegacyBehaviorMixin
{
    public function ensureBehaviors(): Closure
    {
        return function(): void {
            LegacyBehaviorCompatibility::ensureBehaviors($this);
        };
    }

    public function getBehavior(): Closure
    {
        return function(string $name): ?Behavior {
            return LegacyBehaviorCompatibility::getBehavior($this, $name);
        };
    }

    public function getBehaviors(): Closure
    {
        return function(): array {
            return LegacyBehaviorCompatibility::getBehaviors($this);
        };
    }

    public function attachBehavior(): Closure
    {
        return function(string|int $name, string|array|Behavior $behavior): Behavior {
            return LegacyBehaviorCompatibility::attachBehavior($this, $name, $behavior);
        };
    }

    public function attachBehaviors(): Closure
    {
        return function(array $behaviors): void {
            LegacyBehaviorCompatibility::attachBehaviors($this, $behaviors);
        };
    }

    public function detachBehavior(): Closure
    {
        return function(string $name): ?Behavior {
            return LegacyBehaviorCompatibility::detachBehavior($this, $name);
        };
    }

    public function detachBehaviors(): Closure
    {
        return function(): void {
            LegacyBehaviorCompatibility::detachBehaviors($this);
        };
    }

    public function canGetProperty(): Closure
    {
        return function(string $name, bool $checkVars = true, bool $checkBehaviors = true): bool {
            return LegacyBehaviorCompatibility::canGetProperty($this, $name, $checkVars, $checkBehaviors);
        };
    }

    public function canSetProperty(): Closure
    {
        return function(string $name, bool $checkVars = true, bool $checkBehaviors = true): bool {
            return LegacyBehaviorCompatibility::canSetProperty($this, $name, $checkVars, $checkBehaviors);
        };
    }

    public function hasMethod(): Closure
    {
        return function(string $name, bool $checkBehaviors = true): bool {
            return LegacyBehaviorCompatibility::hasMethod($this, $name, $checkBehaviors);
        };
    }

    public function on(): Closure
    {
        return function(string $name, callable $handler, mixed $data = null, bool $append = true): void {
            LegacyBehaviorCompatibility::on($this, $name, $handler, $data, $append);
        };
    }

    public function off(): Closure
    {
        return function(string $name, ?callable $handler = null): bool {
            return LegacyBehaviorCompatibility::off($this, $name, $handler);
        };
    }

    public function hasEventHandlers(): Closure
    {
        return function(string $name): bool {
            return LegacyBehaviorCompatibility::hasEventHandlers($this, $name);
        };
    }

    public function trigger(): Closure
    {
        return function(string $name, ?Event $event = null): void {
            LegacyBehaviorCompatibility::trigger($this, $name, $event);
        };
    }
}
