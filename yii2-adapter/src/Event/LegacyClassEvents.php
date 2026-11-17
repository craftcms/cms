<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use ReflectionClass;
use ReflectionProperty;
use yii\base\Event as YiiBaseEvent;
use yii\helpers\StringHelper;

/**
 * Triggers class-level Yii events for core classes, including handlers attached to their legacy aliases.
 *
 * @internal
 */
class LegacyClassEvents
{
    public static function hasHandlers(string $class, string $name): bool
    {
        return self::handlers($class, $name) !== [];
    }

    public static function trigger(string $class, string $name, YiiBaseEvent $event): void
    {
        $event->handled = false;
        $event->name = $name;

        foreach (self::handlers($class, $name) as [$handler, $data]) {
            $event->data = $data;
            $handler($event);

            // @phpstan-ignore if.alwaysFalse (handlers can mark the event handled)
            if ($event->handled) {
                return;
            }
        }
    }

    /**
     * The class-level Yii handlers for an event on the class, in the order Yii
     * would call them.
     *
     * Yii looks handlers up by the class's name and its parents' and
     * interfaces' names as PHP reports them, which are the native ones, so it
     * never finds handlers attached to their legacy aliases, such as
     * `craft\base\Element`. This looks both up.
     *
     * @return list<array{0: callable, 1: mixed}>
     */
    private static function handlers(string $class, string $name): array
    {
        static $eventsProperty = new ReflectionProperty(YiiBaseEvent::class, '_events');
        static $wildcardsProperty = new ReflectionProperty(YiiBaseEvent::class, '_eventWildcards');

        $events = $eventsProperty->getValue()[$name] ?? [];
        $wildcards = [];

        foreach ($wildcardsProperty->getValue() as $nameWildcard => $classHandlers) {
            if (StringHelper::matchWildcard($nameWildcard, $name)) {
                $wildcards = array_merge($wildcards, $classHandlers);
            }
        }

        if ($events === [] && $wildcards === []) {
            return [];
        }

        $handlersByClass = [];

        foreach ($events as $registeredClass => $classHandlers) {
            $resolvedClass = self::resolvedClass($registeredClass);
            $handlersByClass[$resolvedClass] = array_merge($handlersByClass[$resolvedClass] ?? [], $classHandlers);
        }

        $class = self::resolvedClass(ltrim($class, '\\'));
        $handlers = [];

        foreach ([$class, ...class_parents($class), ...class_implements($class)] as $targetClass) {
            foreach ($wildcards as $classWildcard => $classHandlers) {
                if (StringHelper::matchWildcard($classWildcard, $targetClass, ['escape' => false])) {
                    array_push($handlers, ...$classHandlers);
                    unset($wildcards[$classWildcard]);
                }
            }

            array_push($handlers, ...$handlersByClass[$targetClass] ?? []);
        }

        return $handlers;
    }

    private static function resolvedClass(string $class): string
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return $class;
        }

        return new ReflectionClass($class)->getName();
    }
}
