<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Behavior;

use BadMethodCallException;
use craft\base\Event as YiiEvent;
use craft\events\DefineBehaviorsEvent;
use CraftCms\Cms\Support\Utils;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use Throwable;
use WeakMap;
use Yii;
use yii\base\Behavior;
use yii\base\Event as BaseEvent;

class LegacyBehaviorCompatibility
{
    /**
     * @var array<class-string, list<class-string>>
     */
    private static array $registrations = [];

    /**
     * @var WeakMap<object, LegacyBehaviorState>
     */
    private static WeakMap $states;

    public static function register(string $class, string $legacyClass): void
    {
        self::$registrations[$class] ??= [];

        if (in_array($legacyClass, self::$registrations[$class], true)) {
            return;
        }

        self::$registrations[$class][] = $legacyClass;
    }

    public static function ensureBehaviors(object $object): void
    {
        $state = self::state($object);

        if ($state->behaviorsLoaded || $state->loadingBehaviors) {
            return;
        }

        $state->loadingBehaviors = true;

        try {
            foreach (self::registrationsFor($object) as $registration) {
                if (!YiiEvent::hasHandlers($registration['legacyClass'], 'defineBehaviors')) {
                    continue;
                }

                $event = new DefineBehaviorsEvent([
                    'sender' => $object,
                    'behaviors' => [],
                ]);

                YiiEvent::trigger($registration['legacyClass'], 'defineBehaviors', $event);

                foreach ($event->behaviors as $name => $behavior) {
                    self::attachBehavior($object, is_int($name) ? $name : (string) $name, $behavior, false);
                }
            }

            $state->behaviorsLoaded = true;
        } finally {
            $state->loadingBehaviors = false;
        }
    }

    /**
     * @return array<int|string, Behavior>
     */
    public static function getBehaviors(object $object): array
    {
        self::ensureBehaviors($object);

        return self::state($object)->behaviors;
    }

    public static function getBehavior(object $object, string $name): ?Behavior
    {
        self::ensureBehaviors($object);

        $behaviors = self::state($object)->behaviors;

        if (isset($behaviors[$name])) {
            return $behaviors[$name];
        }

        if (!class_exists($name)) {
            return null;
        }

        foreach ($behaviors as $behavior) {
            if ($behavior instanceof $name) {
                return $behavior;
            }
        }

        return null;
    }

    public static function attachBehavior(
        object $object,
        string|int $name,
        string|array|Behavior $behavior,
        bool $ensureBehaviors = true,
    ): Behavior {
        if ($ensureBehaviors) {
            self::ensureBehaviors($object);
        }

        if (!$behavior instanceof Behavior) {
            $behavior = is_string($behavior) ? new $behavior() : Yii::createObject($behavior);
        }

        $state = self::state($object);

        if (!is_int($name) && isset($state->behaviors[$name])) {
            $state->behaviors[$name]->detach();
        }

        self::syncObjectToLegacyOwner($object, $behavior);
        $behavior->attach(self::legacyOwner($object));
        self::registerBehaviorMethods($object::class, $behavior);

        if (is_int($name)) {
            $state->behaviors[] = $behavior;
        } else {
            $state->behaviors[$name] = $behavior;
        }

        return $behavior;
    }

    /**
     * @param  array<int|string, string|array|Behavior>  $behaviors
     */
    public static function attachBehaviors(object $object, array $behaviors): void
    {
        self::ensureBehaviors($object);

        foreach ($behaviors as $name => $behavior) {
            self::attachBehavior($object, is_int($name) ? $name : (string) $name, $behavior, false);
        }
    }

    public static function detachBehavior(object $object, string $name): ?Behavior
    {
        self::ensureBehaviors($object);

        $state = self::state($object);
        $resolvedName = self::resolveBehaviorName($state, $name);

        if ($resolvedName === null) {
            return null;
        }

        $behavior = $state->behaviors[$resolvedName];
        unset($state->behaviors[$resolvedName]);
        $behavior->detach();

        return $behavior;
    }

    public static function detachBehaviors(object $object): void
    {
        self::ensureBehaviors($object);

        foreach (array_keys(self::state($object)->behaviors) as $name) {
            self::detachBehavior($object, (string) $name);
        }
    }

    public static function canGetProperty(object $object, string $name, bool $checkVars = true, bool $checkBehaviors = true): bool
    {
        if (method_exists($object, 'get' . $name) || ($checkVars && property_exists($object, $name))) {
            return true;
        }

        if (!$checkBehaviors) {
            return false;
        }

        self::ensureBehaviors($object);

        foreach (self::state($object)->behaviors as $behavior) {
            self::syncObjectToLegacyOwner($object, $behavior);

            if ($behavior->canGetProperty($name, $checkVars)) {
                return true;
            }
        }

        return false;
    }

    public static function canSetProperty(object $object, string $name, bool $checkVars = true, bool $checkBehaviors = true): bool
    {
        if (method_exists($object, 'set' . $name) || ($checkVars && property_exists($object, $name))) {
            return true;
        }

        if (!$checkBehaviors) {
            return false;
        }

        self::ensureBehaviors($object);

        foreach (self::state($object)->behaviors as $behavior) {
            self::syncObjectToLegacyOwner($object, $behavior);

            if ($behavior->canSetProperty($name, $checkVars)) {
                return true;
            }
        }

        return false;
    }

    public static function hasMethod(object $object, string $name, bool $checkBehaviors = true): bool
    {
        if (method_exists($object, $name)) {
            return true;
        }

        if (!$checkBehaviors) {
            return false;
        }

        self::ensureBehaviors($object);

        foreach (self::state($object)->behaviors as $behavior) {
            self::syncObjectToLegacyOwner($object, $behavior);

            if ($behavior->hasMethod($name)) {
                return true;
            }
        }

        return false;
    }

    public static function callBehaviorMethod(object $object, string $method, array $parameters): mixed
    {
        self::ensureBehaviors($object);

        foreach (self::state($object)->behaviors as $behavior) {
            self::syncObjectToLegacyOwner($object, $behavior);

            if ($behavior->hasMethod($method)) {
                $result = $behavior->$method(...$parameters);
                self::syncLegacyOwnerToObject($object, $behavior);

                return $result;
            }
        }

        throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', $object::class, $method));
    }

    public static function on(
        object $object,
        string $name,
        callable $handler,
        mixed $data = null,
        bool $append = true,
    ): void {
        $events = &self::state($object)->events[$name];
        $events ??= [];

        if ($append) {
            $events[] = [$handler, $data];
        } else {
            array_unshift($events, [$handler, $data]);
        }
    }

    public static function off(object $object, string $name, ?callable $handler = null): bool
    {
        $state = self::state($object);
        $events = &$state->events[$name];

        if (empty($events)) {
            return false;
        }

        if ($handler === null) {
            unset($state->events[$name]);

            return true;
        }

        $removed = false;

        foreach ($events as $index => $eventHandler) {
            if ($eventHandler[0] === $handler) {
                unset($events[$index]);
                $removed = true;
            }
        }

        if ($removed) {
            $events = array_values($events);
        }

        if (empty($events)) {
            unset($state->events[$name]);
        }

        return $removed;
    }

    public static function hasEventHandlers(object $object, string $name): bool
    {
        return !empty(self::state($object)->events[$name]) || YiiEvent::hasHandlers($object, $name);
    }

    public static function trigger(object $object, string $name, ?BaseEvent $event = null): void
    {
        $event ??= new BaseEvent();
        $event->handled = false;
        $event->name = $name;
        $event->sender ??= $object;

        foreach (self::state($object)->events[$name] ?? [] as [$handler, $data]) {
            $event->data = $data;
            $handler($event);

            /** @phpstan-ignore-next-line */
            if ($event->handled) {
                return;
            }
        }

        YiiEvent::trigger($object, $name, $event);
    }

    private static function state(object $object): LegacyBehaviorState
    {
        self::$states ??= new WeakMap();

        return self::$states[$object] ??= new LegacyBehaviorState();
    }

    /**
     * @return list<array{class: class-string, legacyClass: class-string}>
     */
    private static function registrationsFor(object|string $object): array
    {
        $class = is_object($object) ? $object::class : $object;
        $classes = array_reverse([$class, ...array_values(class_parents($class) ?: [])]);
        $registrations = [];
        $seen = [];

        foreach ($classes as $registeredClass) {
            foreach (self::$registrations[$registeredClass] ?? [] as $legacyClass) {
                if (isset($seen[$legacyClass])) {
                    continue;
                }

                $seen[$legacyClass] = true;
                $registrations[] = [
                    'class' => $registeredClass,
                    'legacyClass' => $legacyClass,
                ];
            }
        }

        if ($registrations === []) {
            throw new BadMethodCallException(sprintf('Legacy behavior compatibility has not been registered for %s.', $class));
        }

        return $registrations;
    }

    private static function legacyOwner(object $object): object
    {
        $state = self::state($object);
        $legacyClass = self::mostSpecificLegacyClass($object);

        if ($object instanceof $legacyClass) {
            return $object;
        }

        if (!isset($state->legacyOwner) || $state->legacyOwnerClass !== $legacyClass) {
            $state->legacyOwner = new $legacyClass(Utils::getPublicProperties($object));
            $state->legacyOwnerClass = $legacyClass;
        }

        return $state->legacyOwner;
    }

    /**
     * @return class-string
     */
    private static function mostSpecificLegacyClass(object $object): string
    {
        foreach (self::registrationsFor($object) as $registration) {
            $legacyClass = $registration['legacyClass'];

            if ($object instanceof $legacyClass) {
                return $legacyClass;
            }
        }

        foreach (array_reverse(self::registrationsFor($object)) as $registration) {
            $legacyClass = $registration['legacyClass'];

            if (!class_exists($legacyClass)) {
                continue;
            }

            $reflection = new ReflectionClass($legacyClass);

            if (!$reflection->isAbstract()) {
                return $legacyClass;
            }
        }

        throw new BadMethodCallException(sprintf('Unable to determine a legacy owner for %s.', $object::class));
    }

    private static function syncObjectToLegacyOwner(object $object, ?Behavior $behavior = null): void
    {
        $owner = $behavior->owner ?? self::legacyOwner($object);

        if ($owner === $object) {
            return;
        }

        foreach (Utils::getPublicProperties($object) as $name => $value) {
            try {
                $owner->$name = $value;
            } catch (Throwable) {
                // Read-only
            }
        }
    }

    private static function syncLegacyOwnerToObject(object $object, Behavior $behavior): void
    {
        if (!is_object($behavior->owner) || $behavior->owner === $object) {
            return;
        }

        foreach (Utils::getPublicProperties($behavior->owner) as $name => $value) {
            try {
                $object->$name = $value;
            } catch (Throwable) {
                // Read-only
            }
        }
    }

    private static function registerBehaviorMethods(string $targetClass, Behavior $behavior): void
    {
        foreach (new ReflectionObject($behavior)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->isStatic() ||
                $method->class === Behavior::class ||
                in_array($method->getName(), ['attach', 'detach', 'events'], true)
            ) {
                continue;
            }

            $name = $method->getName();

            if ($targetClass::hasMacro($name)) {
                continue;
            }

            $targetClass::macro($name, function(...$parameters) use ($name) {
                /** @phpstan-ignore-next-line */
                return LegacyBehaviorCompatibility::callBehaviorMethod($this, $name, $parameters);
            });
        }
    }

    private static function resolveBehaviorName(LegacyBehaviorState $state, string $name): string|int|null
    {
        if (isset($state->behaviors[$name])) {
            return $name;
        }

        if (!class_exists($name)) {
            return null;
        }

        return array_find_key($state->behaviors, fn($behavior) => $behavior instanceof $name);
    }
}
