<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Behavior;

use BadMethodCallException;
use craft\base\Event as YiiEvent;
use craft\events\DefineBehaviorsEvent;
use CraftCms\Cms\Support\Utils;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;
use WeakMap;
use Yii;
use yii\base\Behavior;
use yii\base\Event as BaseEvent;

class LegacyBehaviorCompatibility
{
    private const string DEFINE_BEHAVIORS = 'defineBehaviors';

    private const array BASE_BEHAVIOR_METHODS = ['attach', 'detach', 'events'];

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

    public static function registerEventDefinedBehaviorMethods(string $legacyClass, callable $handler, mixed $data = null): void
    {
        $event = new DefineBehaviorsEvent();
        $event->name = self::DEFINE_BEHAVIORS;
        $event->data = $data;

        try {
            $handler($event);
        } catch (Throwable) {
            return;
        }

        foreach (self::targetClassesForLegacyClass($legacyClass) as $targetClass) {
            foreach ($event->behaviors as $behavior) {
                self::registerBehaviorDefinitionMethods($targetClass, $behavior);
            }
        }
    }

    public static function registerDefinedBehaviorMethodsFromRegisteredEvents(): void
    {
        $eventsProperty = (new ReflectionClass(BaseEvent::class))->getProperty('_events');
        $events = $eventsProperty->getValue();

        foreach ($events[self::DEFINE_BEHAVIORS] ?? [] as $legacyClass => $handlers) {
            foreach ($handlers as [$handler, $data]) {
                self::registerEventDefinedBehaviorMethods($legacyClass, $handler, $data);
            }
        }
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
                self::attachDefinedBehaviors($object, self::definedBehaviors($object, $registration['legacyClass']));
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
        return self::loadedState($object)->behaviors;
    }

    public static function getBehavior(object $object, string $name): ?Behavior
    {
        $state = self::loadedState($object);
        $resolvedName = self::resolveBehaviorName($state, $name);

        return $resolvedName === null ? null : $state->behaviors[$resolvedName];
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

        $behavior = self::makeBehavior($behavior);

        $state = self::state($object);

        if (!is_int($name) && isset($state->behaviors[$name])) {
            $state->behaviors[$name]->detach();
        }

        self::syncObjectToLegacyOwner($object, $behavior);
        $behavior->attach(self::legacyOwner($object));
        self::registerBehaviorMethods($object::class, $behavior);

        self::storeBehavior($state, $name, $behavior);

        return $behavior;
    }

    /**
     * @param  array<int|string, string|array|Behavior>  $behaviors
     */
    public static function attachBehaviors(object $object, array $behaviors): void
    {
        self::ensureBehaviors($object);

        self::attachDefinedBehaviors($object, $behaviors);
    }

    public static function detachBehavior(object $object, string $name): ?Behavior
    {
        $state = self::loadedState($object);
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
        foreach (array_keys(self::loadedState($object)->behaviors) as $name) {
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

        return self::firstBehaviorWhere(
            $object,
            fn(Behavior $behavior) => $behavior->canGetProperty($name, $checkVars),
        ) !== null;
    }

    public static function canSetProperty(object $object, string $name, bool $checkVars = true, bool $checkBehaviors = true): bool
    {
        if (method_exists($object, 'set' . $name) || ($checkVars && property_exists($object, $name))) {
            return true;
        }

        if (!$checkBehaviors) {
            return false;
        }

        return self::firstBehaviorWhere(
            $object,
            fn(Behavior $behavior) => $behavior->canSetProperty($name, $checkVars),
        ) !== null;
    }

    public static function hasMethod(object $object, string $name, bool $checkBehaviors = true): bool
    {
        if (method_exists($object, $name)) {
            return true;
        }

        if (!$checkBehaviors) {
            return false;
        }

        return self::firstBehaviorWhere(
            $object,
            fn(Behavior $behavior) => $behavior->hasMethod($name),
        ) !== null;
    }

    public static function callBehaviorMethod(object $object, string $method, array $parameters): mixed
    {
        $behavior = self::firstBehaviorWhere(
            $object,
            fn(Behavior $behavior) => $behavior->hasMethod($method),
        );

        if ($behavior !== null) {
            $result = $behavior->$method(...$parameters);
            self::syncLegacyOwnerToObject($object, $behavior);

            return $result;
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

    private static function loadedState(object $object): LegacyBehaviorState
    {
        self::ensureBehaviors($object);

        return self::state($object);
    }

    private static function firstBehaviorWhere(object $object, callable $callback): ?Behavior
    {
        foreach (self::loadedState($object)->behaviors as $behavior) {
            self::syncObjectToLegacyOwner($object, $behavior);

            if ($callback($behavior)) {
                return $behavior;
            }
        }

        return null;
    }

    /**
     * @param  array<int|string, string|array|Behavior>  $behaviors
     */
    private static function attachDefinedBehaviors(object $object, array $behaviors): void
    {
        foreach ($behaviors as $name => $behavior) {
            self::attachBehavior($object, self::normalizedBehaviorName($name), $behavior, false);
        }
    }

    private static function normalizedBehaviorName(int|string $name): int|string
    {
        return is_int($name) ? $name : (string) $name;
    }

    private static function makeBehavior(string|array|Behavior $behavior): Behavior
    {
        if ($behavior instanceof Behavior) {
            return $behavior;
        }

        if (is_string($behavior)) {
            return new $behavior();
        }

        /** @var Behavior $behavior */
        $behavior = Yii::createObject($behavior);

        return $behavior;
    }

    private static function storeBehavior(LegacyBehaviorState $state, string|int $name, Behavior $behavior): void
    {
        if (is_int($name)) {
            $state->behaviors[] = $behavior;

            return;
        }

        $state->behaviors[$name] = $behavior;
    }

    /**
     * @param  class-string  $legacyClass
     * @return array<int|string, string|array|Behavior>
     */
    private static function definedBehaviors(object $object, string $legacyClass): array
    {
        $behaviors = self::classDefinedBehaviors($object, $legacyClass);

        if (!YiiEvent::hasHandlers($legacyClass, self::DEFINE_BEHAVIORS)) {
            return $behaviors;
        }

        $event = new DefineBehaviorsEvent([
            'sender' => $object,
            'behaviors' => $behaviors,
        ]);

        YiiEvent::trigger($legacyClass, self::DEFINE_BEHAVIORS, $event);

        return $event->behaviors;
    }

    /**
     * @param  class-string  $legacyClass
     * @return array<int|string, string|array|Behavior>
     */
    private static function classDefinedBehaviors(object $object, string $legacyClass): array
    {
        if (!method_exists($legacyClass, self::DEFINE_BEHAVIORS)) {
            return [];
        }

        try {
            $owner = $object instanceof $legacyClass ? $object : self::legacyOwner($object);
        } catch (BadMethodCallException) {
            return [];
        }

        if (!$owner instanceof $legacyClass) {
            return [];
        }

        $method = new ReflectionMethod($legacyClass, self::DEFINE_BEHAVIORS);

        return $method->invoke($owner);
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
            $state->legacyOwner = new $legacyClass(self::publicWritableProperties($object));
            $state->legacyOwnerClass = $legacyClass;
        }

        return $state->legacyOwner;
    }

    /**
     * @return class-string
     */
    private static function mostSpecificLegacyClass(object $object): string
    {
        $registrations = self::registrationsFor($object);

        foreach ($registrations as $registration) {
            $legacyClass = $registration['legacyClass'];

            if ($object instanceof $legacyClass) {
                return $legacyClass;
            }
        }

        foreach (array_reverse($registrations) as $registration) {
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
        $owner = $behavior?->owner;

        if (!is_object($owner)) {
            $owner = self::legacyOwner($object);
        }

        self::syncWritableProperties($object, $owner);
    }

    private static function syncLegacyOwnerToObject(object $object, Behavior $behavior): void
    {
        if (!is_object($behavior->owner)) {
            return;
        }

        self::syncWritableProperties($behavior->owner, $object);
    }

    private static function syncWritableProperties(object $source, object $target): void
    {
        if ($source === $target) {
            return;
        }

        foreach (self::publicWritableProperties($source) as $name => $value) {
            try {
                $target->$name = $value;
            } catch (Throwable) {
                // Read-only
            }
        }
    }

    private static function publicWritableProperties(object $object): array
    {
        return Utils::getPublicProperties(
            $object,
            fn(ReflectionProperty $property) => self::isWritablePublicProperty($property),
        );
    }

    private static function isWritablePublicProperty(ReflectionProperty $property): bool
    {
        if ($property->isReadOnly()) {
            return false;
        }

        return !$property->hasHook(\PropertyHookType::Get) || $property->hasHook(\PropertyHookType::Set);
    }

    private static function registerBehaviorMethods(string $targetClass, Behavior $behavior): void
    {
        self::registerBehaviorClassMethods($targetClass, $behavior::class);
    }

    private static function registerBehaviorDefinitionMethods(
        string $targetClass,
        string|array|Behavior $behavior,
    ): void {
        if ($behavior instanceof Behavior) {
            self::registerBehaviorMethods($targetClass, $behavior);

            return;
        }

        $behaviorClass = is_string($behavior) ? $behavior : ($behavior['class'] ?? null);

        if (!is_string($behaviorClass) || !class_exists($behaviorClass)) {
            return;
        }

        self::registerBehaviorClassMethods($targetClass, $behaviorClass);
    }

    private static function registerBehaviorClassMethods(string $targetClass, string $behaviorClass): void
    {
        if (!is_callable([$targetClass, 'hasMacro']) || !is_callable([$targetClass, 'macro'])) {
            return;
        }

        foreach ((new ReflectionClass($behaviorClass))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->isStatic() ||
                $method->class === Behavior::class ||
                in_array($method->getName(), self::BASE_BEHAVIOR_METHODS, true)
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

    /**
     * @return list<class-string>
     */
    private static function targetClassesForLegacyClass(string $legacyClass): array
    {
        $targets = [];
        $legacyClass = ltrim($legacyClass, '\\');

        foreach (self::$registrations as $targetClass => $legacyClasses) {
            foreach ($legacyClasses as $registeredLegacyClass) {
                if (!self::legacyClassesMatch($registeredLegacyClass, $legacyClass)) {
                    continue;
                }

                $targets[] = $targetClass;
            }
        }

        return array_values(array_unique($targets));
    }

    private static function legacyClassesMatch(string $registeredLegacyClass, string $legacyClass): bool
    {
        return $registeredLegacyClass === $legacyClass ||
            self::normalizedClassName($registeredLegacyClass) === self::normalizedClassName($legacyClass);
    }

    private static function normalizedClassName(string $class): string
    {
        $class = ltrim($class, '\\');

        if (!class_exists($class)) {
            return $class;
        }

        return (new ReflectionClass($class))->getName();
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
