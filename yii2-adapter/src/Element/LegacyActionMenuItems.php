<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element;

use Closure;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Events\ElementActionMenuDescriptorsResolving;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Crypt;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;
use yii\base\Event as YiiEvent;

/**
 * Converts Craft 5-style action menu items (`url`/`action` + markup, see
 * {@see MenuHtml::disclosureMenu()}) into the behavior descriptors the Inertia
 * element editor and element chips render.
 *
 * Items that depend on JavaScript registered alongside them can't be converted,
 * and are dropped with a deprecation warning.
 *
 * @internal
 */
#[Singleton]
final class LegacyActionMenuItems
{
    private const array LEGACY_METHODS = ['safeActionMenuItems', 'destructiveActionMenuItems'];

    /** @var array<class-string, list<ReflectionMethod>> */
    private array $overrides = [];

    /**
     * Adds items from `EVENT_DEFINE_ACTION_MENU_ITEMS` handlers to the event.
     *
     * @param list<array<string, mixed>> $items
     */
    public function addEventItems(ElementActionMenuDescriptorsResolving $event, array $items): void
    {
        if ($items === []) {
            return;
        }

        $locations = $this->handlerLocations($event->element, 'defineActionMenuItems');

        foreach ($locations as [$file, $line]) {
            Deprecator::log(
                'Element::EVENT_DEFINE_ACTION_MENU_ITEMS',
                sprintf(
                    '`craft\base\Element::EVENT_DEFINE_ACTION_MENU_ITEMS` has been deprecated. Listen for `%s` and add action menu descriptors instead.',
                    ElementActionMenuDescriptorsResolving::class,
                ),
                $file,
                $line,
            );
        }

        // Pin unconvertible items to their handler when there's no ambiguity about which one it was.
        [$file, $line] = count($locations) === 1 ? $locations[0] : [null, null];

        $event->items = $this->merge($event->items, $this->toDescriptors($items, $file, $line));
    }

    /**
     * Adds items from plugin overrides of `safeActionMenuItems()` and
     * `destructiveActionMenuItems()`, unless the element type already
     * provides descriptors via `extraActionMenuDescriptors()`.
     */
    public function addOverrideItems(ElementActionMenuDescriptorsResolving $event): void
    {
        foreach ($this->legacyOverrides($event->element::class) as $method) {
            [$items, $baseItems] = $this->withoutJs(fn() => [
                $method->invoke($event->element),
                $this->firstPartyImplementation($method)->invoke($event->element),
            ]);

            $items = $this->withoutBaseItems($items, $baseItems);

            if ($items === []) {
                continue;
            }

            if ($method->name === 'destructiveActionMenuItems') {
                $items = array_map(fn(array $item) => $item + ['destructive' => true], $items);
            }

            $file = $method->getFileName() ?: null;
            $line = $method->getStartLine() ?: null;

            Deprecator::log(
                "{$method->class}::{$method->name}",
                sprintf(
                    '`%s::%s()` items are being converted to action menu descriptors by the Yii2 adapter. Override `extraActionMenuDescriptors()` to provide them directly.',
                    $method->class,
                    $method->name,
                ),
                $file,
                $line,
            );

            $event->items = $this->merge($event->items, $this->toDescriptors($items, $file, $line));
        }
    }

    /**
     * Runs a callback, discarding any JavaScript legacy item definitions register along the way.
     *
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    public function withoutJs(Closure $callback): mixed
    {
        $result = null;

        HtmlStack::capture(function() use ($callback, &$result) {
            $result = $callback();

            return '';
        });

        return $result;
    }

    /**
     * @param array<array-key, array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public function toDescriptors(array $items, ?string $file = null, ?int $line = null): array
    {
        $descriptors = [];

        foreach ($this->flatten($items) as $item) {
            if (($item['hidden'] ?? false) || ($item['disabled'] ?? false)) {
                continue;
            }

            $descriptor = $this->toDescriptor($item);

            if ($descriptor === null) {
                $label = $this->label($item) ?? $item['id'] ?? '(unlabeled)';

                Deprecator::log(
                    "action-menu-item:$label",
                    sprintf(
                        'The “%s” action menu item can’t be shown in the Inertia element editor or element chips because it relies on JavaScript. Give it a `url` or `action`, or add it as a descriptor via `%s`.',
                        $label,
                        ElementActionMenuDescriptorsResolving::class,
                    ),
                    $file,
                    $line,
                );

                continue;
            }

            $descriptors[] = $descriptor;
        }

        return $this->trimSeparators($descriptors);
    }

    /** @param array<string, mixed> $item */
    private function toDescriptor(array $item): ?array
    {
        if ($this->type($item) === MenuItemType::HR) {
            return ['type' => 'hr'];
        }

        $label = $this->label($item);

        if ($label === null) {
            return null;
        }

        $behavior = match (true) {
            isset($item['url']) => [
                'type' => 'link',
                'href' => Url::url((string) $item['url']),
                'newTab' => ($item['attributes']['target'] ?? null) === '_blank',
            ],
            isset($item['action']) => array_filter([
                'type' => 'submit',
                'actionUrl' => Url::actionUrl((string) $item['action']),
                'params' => is_array($item['params'] ?? null) ? $item['params'] : null,
                'redirect' => isset($item['redirect']) ? Crypt::encrypt((string) $item['redirect']) : null,
                'confirm' => $item['confirm'] ?? null,
                'requireElevatedSession' => ($item['requireElevatedSession'] ?? false) ?: null,
            ], fn($value) => $value !== null),
            default => null,
        };

        if ($behavior === null) {
            return null;
        }

        $color = $item['color'] ?? null;

        return array_filter([
            'label' => $label,
            'icon' => $this->icon($item['icon'] ?? null),
            'color' => $color instanceof Color ? $color->value : $color,
            'destructive' => ($item['destructive'] ?? false) ?: null,
            'showInChips' => isset($item['showInChips']) ? (bool) $item['showInChips'] : null,
            'behavior' => $behavior,
        ], fn($value) => $value !== null);
    }

    /**
     * Groups become their items between separators; descriptors have no headings.
     *
     * @param array<array-key, array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function flatten(array $items): array
    {
        $flat = [];

        foreach ($items as $item) {
            if ($this->type($item) !== MenuItemType::Group) {
                $flat[] = $item;
                continue;
            }

            $flat[] = ['type' => MenuItemType::HR];
            array_push($flat, ...$this->flatten($item['items'] ?? []));
            $flat[] = ['type' => MenuItemType::HR];
        }

        return $flat;
    }

    /** @param array<string, mixed> $item */
    private function type(array $item): MenuItemType
    {
        $type = app(MenuHtml::class)->normalizeMenuItems([$item])[0]['type'];

        return MenuItemType::tryFrom($type) ?? MenuItemType::Button;
    }

    /** @param array<string, mixed> $item */
    private function label(array $item): ?string
    {
        $label = $item['label'] ?? (isset($item['html']) ? trim(strip_tags((string) $item['html'])) : null);

        return $label === null || $label === '' ? null : (string) $label;
    }

    /** Qualifies the icon with its family, as the descriptor menu's icon renderer expects. */
    private function icon(mixed $icon): ?string
    {
        // File paths and inline SVGs have no descriptor equivalent.
        if (!is_string($icon) || !preg_match('/^[\w-]+$/', $icon)) {
            return null;
        }

        ['name' => $name, 'family' => $family] = Icons::resolveIconData($icon);

        return $family === 'solid' ? $name : "$family/$name";
    }

    /**
     * Inserts converted items ahead of the destructive ones, the way core's
     * own additions are.
     *
     * @param list<array<string, mixed>> $descriptors
     * @param list<array<string, mixed>> $converted
     * @return list<array<string, mixed>>
     */
    private function merge(array $descriptors, array $converted): array
    {
        $isDestructive = fn(array $item) => (bool) ($item['destructive'] ?? false);
        $position = array_find_key($descriptors, $isDestructive) ?? count($descriptors);
        $safe = array_values(array_filter($converted, fn(array $item) => !$isDestructive($item)));
        $destructive = array_values(array_filter($converted, $isDestructive));

        array_splice($descriptors, $position, 0, $safe);

        return $this->trimSeparators([...$descriptors, ...$destructive]);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function trimSeparators(array $items): array
    {
        $trimmed = [];

        foreach ($items as $item) {
            $isHr = ($item['type'] ?? null) === 'hr';
            $previousIsHr = ($trimmed[array_key_last($trimmed) ?? -1]['type'] ?? null) === 'hr';

            if ($isHr && ($trimmed === [] || $previousIsHr)) {
                continue;
            }

            $trimmed[] = $item;
        }

        if (($trimmed[array_key_last($trimmed) ?? -1]['type'] ?? null) === 'hr') {
            array_pop($trimmed);
        }

        return $trimmed;
    }

    /**
     * Drops the items the first-party implementation also returns, matched by
     * label since legacy item IDs are random.
     *
     * @param array<array-key, array<string, mixed>> $items
     * @param array<array-key, array<string, mixed>> $baseItems
     * @return list<array<string, mixed>>
     */
    private function withoutBaseItems(array $items, array $baseItems): array
    {
        $key = fn(array $item) => $this->type($item) === MenuItemType::HR ? "\0hr" : ($this->label($item) ?? '');
        $remaining = array_count_values(array_map($key, $baseItems));
        $added = [];

        foreach ($items as $item) {
            $itemKey = $key($item);

            if (($remaining[$itemKey] ?? 0) > 0) {
                $remaining[$itemKey]--;
                continue;
            }

            $added[] = $item;
        }

        return $added;
    }

    /**
     * @param class-string $class
     * @return list<ReflectionMethod>
     */
    private function legacyOverrides(string $class): array
    {
        if (isset($this->overrides[$class])) {
            return $this->overrides[$class];
        }

        $declaresDescriptors = !$this->isFirstParty(
            new ReflectionMethod($class, 'extraActionMenuDescriptors')->getDeclaringClass()->getName(),
        );

        return $this->overrides[$class] = $declaresDescriptors ? [] : array_values(array_filter(
            array_map(fn(string $name) => new ReflectionMethod($class, $name), self::LEGACY_METHODS),
            fn(ReflectionMethod $method) => !$this->isFirstParty($method->getDeclaringClass()->getName()),
        ));
    }

    private function firstPartyImplementation(ReflectionMethod $method): ReflectionMethod
    {
        $class = $method->getDeclaringClass();

        while (!$this->isFirstParty($class->getName())) {
            $class = $class->getParentClass();
        }

        return new ReflectionMethod($class->getName(), $method->name);
    }

    private function isFirstParty(string $class): bool
    {
        // Anonymous classes are named after their parent, so the namespace alone would claim them.
        return !str_contains($class, '@anonymous') &&
            (str_starts_with($class, 'CraftCms\\Cms\\') || str_starts_with($class, 'craft\\'));
    }

    /**
     * Where each class-level Yii handler for the event that applies to the
     * element was defined.
     *
     * @return list<array{string, int}>
     */
    private function handlerLocations(ElementInterface $element, string $name): array
    {
        /** @var array<string, array<string, list<array{0: callable, 1: mixed}>>> $events */
        $events = new ReflectionProperty(YiiEvent::class, '_events')->getValue();
        $locations = [];

        foreach ($events[$name] ?? [] as $class => $handlers) {
            if (!$element instanceof $class) {
                continue;
            }

            foreach ($handlers as [$handler]) {
                $reflection = match (true) {
                    $handler instanceof Closure => new ReflectionFunction($handler),
                    is_array($handler) => new ReflectionMethod($handler[0], $handler[1]),
                    is_string($handler) && str_contains($handler, '::') => new ReflectionMethod($handler),
                    is_string($handler) => new ReflectionFunction($handler),
                    default => null,
                };

                if ($reflection?->getFileName()) {
                    $locations[] = [$reflection->getFileName(), (int) $reflection->getStartLine()];
                }
            }
        }

        return array_values(array_unique($locations, SORT_REGULAR));
    }
}
