<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Support\Json;
use InvalidArgumentException;
use JsonException;

class FormResolver
{
    /** @var list<list<string>> */
    private array $controlPaths = [];

    /** @var list<string> */
    private array $nodeUids = [];

    /** @var array<string, mixed> */
    private array $values = [];

    public function __construct(
        private readonly FormNodeTypes $nodeTypes,
        private readonly FormControlTypes $controlTypes,
    ) {}

    /** @throws JsonException */
    public function resolve(Form $form, FormContext $context): FormPayload
    {
        $this->controlPaths = [];
        $this->nodeUids = [];
        $this->values = [];
        $namespace = $this->normalizePath($context->namespace, 'Form context');
        $nodes = array_map(
            fn (Node $node): NodePayload => $this->resolveNode($node, $context, $namespace),
            $form->nodes(),
        );
        [$errors, $globalErrors] = $this->resolveErrors($context, $namespace);

        $payload = new FormPayload(
            scope: $namespace,
            refreshable: $context->refreshable,
            nodes: $nodes,
            values: $this->values,
            errors: $errors,
            globalErrors: $globalErrors,
        );

        Json::encode($payload, JSON_THROW_ON_ERROR);

        return $payload;
    }

    /**
     * @param  list<string>  $namespace
     */
    private function resolveNode(Node $node, FormContext $context, array $namespace): NodePayload
    {
        $type = $node::class;
        $component = $node->component();
        $control = $node->getControl();
        $uid = $node->uid();
        $controlIdentity = $control === null ? 'unknown' : $this->pathIdentity($control->path());
        $identity = $control === null
            ? ($uid === null || $uid === '' ? 'unknown' : $uid)
            : implode('.', [...$namespace, $controlIdentity]);

        if ($this->nodeTypes->types()->doesntContain($type)) {
            throw new InvalidArgumentException("Form Node type [{$type}] with component [{$component}] at [{$identity}] is not registered.");
        }

        $children = $node->children();
        $props = $node->props();
        $this->ensureJsonSafe($props, "Form Node [{$type}] with component [{$component}] at [{$identity}] properties");

        if ($control === null) {
            if ($uid === null || $uid === '') {
                throw new InvalidArgumentException("Form Node [{$type}] with component [{$component}] at [{$identity}] requires a stable UID.");
            }

            if (in_array($uid, $this->nodeUids, true)) {
                throw new InvalidArgumentException("Duplicate Node UID [{$uid}] for Form Node [{$type}] with component [{$component}].");
            }

            $this->nodeUids[] = $uid;
        }

        return new NodePayload(
            type: $type,
            component: $component,
            props: $props,
            uid: $uid,
            control: $control !== null ? $this->resolveControl($control, $context, $namespace) : null,
            children: $control === null || $children !== []
                ? array_map(
                    fn (Node $child): NodePayload => $this->resolveNode($child, $context, $namespace),
                    $children,
                )
                : null,
        );
    }

    /**
     * @param  list<string>  $namespace
     */
    private function resolveControl(Control $control, FormContext $context, array $namespace): ControlPayload
    {
        $type = $control::class;
        $component = $control->component();
        $path = [...$namespace, ...$this->normalizePath(
            $control->path(),
            "Form Control [{$type}] with component [{$component}] at [unknown]",
        )];
        $identity = implode('.', $path);

        if ($this->controlTypes->types()->doesntContain($type)) {
            throw new InvalidArgumentException("Form Control type [{$type}] with component [{$component}] at [{$identity}] is not registered.");
        }

        $value = $this->has($context->values, $path)
            ? $this->get($context->values, $path)
            : $control->getValue();
        $props = $control->props($value);
        $this->ensureJsonSafe($props, "Form Control [{$type}] with component [{$component}] at [{$identity}] properties");

        if ($path === []) {
            throw new InvalidArgumentException("Control [{$type}] requires a path; component [{$component}], identity [unknown].");
        }

        if (in_array($path, $this->controlPaths, true)) {
            throw new InvalidArgumentException("Duplicate Control path [{$identity}] for type [{$type}] with component [{$component}].");
        }

        $mode = $context->mode === ControlMode::Editable ? $control->getMode() : $context->mode;
        $deltaGroup = $control->getDeltaGroup();
        $deltaGroup = $deltaGroup === null
            ? $path
            : [...$namespace, ...$this->normalizePath(
                $deltaGroup,
                "Form Control [{$type}] with component [{$component}] at [{$identity}] delta group",
            )];

        if (array_slice($path, 0, count($deltaGroup)) !== $deltaGroup) {
            throw new InvalidArgumentException("Control delta groups must be ancestors of their paths; type [{$type}], component [{$component}], path [{$identity}].");
        }

        $this->set($this->values, $path, $value);
        $this->controlPaths[] = $path;

        return new ControlPayload(
            type: $type,
            component: $component,
            props: $props,
            path: $path,
            mode: $mode,
            deltaGroup: $deltaGroup,
        );
    }

    /**
     * @param  list<string>  $namespace
     * @return array{list<array{path: list<string>, messages: list<string>}>, list<string>}
     */
    private function resolveErrors(FormContext $context, array $namespace): array
    {
        $errors = [];
        $globalErrors = $context->globalErrors;

        foreach ($context->errors as $path => $messages) {
            $absolutePath = [...$namespace, ...$this->normalizePath((string) $path, 'Form error')];
            $messages = is_array($messages) ? array_values($messages) : [$messages];

            $ownerPath = $this->owningControlPath($absolutePath);

            if ($ownerPath === null) {
                array_push($globalErrors, ...$messages);

                continue;
            }

            $errors[] = ['path' => $ownerPath, 'messages' => $messages];
        }

        return [$errors, $globalErrors];
    }

    /**
     * @param  list<string>  $path
     * @return null|list<string>
     */
    private function owningControlPath(array $path): ?array
    {
        $matches = array_filter(
            $this->controlPaths,
            fn (array $controlPath): bool => array_slice($path, 0, count($controlPath)) === $controlPath,
        );

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (array $a, array $b): int => count($b) <=> count($a));

        return $matches[0];
    }

    /** @param string|list<string> $path @return list<string> */
    private function normalizePath(string|array $path, string $location): array
    {
        $segments = is_string($path) ? ($path === '' ? [] : explode('.', $path)) : array_values($path);

        foreach ($segments as $segment) {
            if (! is_string($segment) || $segment === '') {
                throw new InvalidArgumentException("{$location} paths must contain non-empty string segments.");
            }
        }

        return $segments;
    }

    /** @param string|list<string> $path */
    private function pathIdentity(string|array $path): string
    {
        if (is_string($path)) {
            return $path === '' ? 'unknown' : $path;
        }

        foreach ($path as $segment) {
            if (! is_string($segment) || $segment === '') {
                return 'unknown';
            }
        }

        return $path === [] ? 'unknown' : implode('.', $path);
    }

    /** @param array<string, mixed> $values @param list<string> $path */
    private function has(array $values, array $path): bool
    {
        foreach ($path as $segment) {
            if (! array_key_exists((string) $segment, $values)) {
                return false;
            }

            $values = is_array($values[$segment]) ? $values[$segment] : [];
        }

        return true;
    }

    /** @param array<string, mixed> $values @param list<string> $path */
    private function get(array $values, array $path): mixed
    {
        foreach ($path as $segment) {
            $values = $values[$segment];
        }

        return $values;
    }

    /** @param array<string, mixed> $values @param list<string> $path */
    private function set(array &$values, array $path, mixed $value): void
    {
        $current = &$values;

        foreach ($path as $index => $segment) {
            if ($index === array_key_last($path)) {
                $current[$segment] = $value;

                return;
            }

            $current[$segment] ??= [];
            $current = &$current[$segment];
        }
    }

    private function ensureJsonSafe(mixed $value, string $location): void
    {
        if (is_float($value) && ! is_finite($value)) {
            throw new InvalidArgumentException("{$location} must be JSON-safe.");
        }

        if ($value === null || is_scalar($value)) {
            return;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("{$location} must be JSON-safe.");
        }

        foreach ($value as $child) {
            $this->ensureJsonSafe($child, $location);
        }
    }
}
