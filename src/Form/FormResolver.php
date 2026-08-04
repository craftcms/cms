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

    /** @throws JsonException */
    public function resolve(Form $form, FormContext $context): FormPayload
    {
        $this->controlPaths = [];
        $this->nodeUids = [];
        $this->values = [];
        $namespace = $this->normalizePath($context->namespace);
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
        $control = $node->getControl();
        $children = $node->children();
        $uid = null;

        if ($control === null) {
            $uid = $node->uid();

            if ($uid === null || $uid === '') {
                throw new InvalidArgumentException("Pathless Node [{$type}] requires a stable UID.");
            }

            if (in_array($uid, $this->nodeUids, true)) {
                throw new InvalidArgumentException("Duplicate Node UID [{$uid}].");
            }

            $this->nodeUids[] = $uid;
        }

        return new NodePayload(
            type: $type,
            component: $node->component(),
            props: $node->props(),
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
        $path = [...$namespace, ...$this->normalizePath($control->path())];

        if ($path === []) {
            throw new InvalidArgumentException("Control [{$type}] requires a path.");
        }

        if (in_array($path, $this->controlPaths, true)) {
            throw new InvalidArgumentException('Duplicate Control path ['.implode('.', $path).'].');
        }

        $mode = $context->mode === ControlMode::Editable ? $control->getMode() : $context->mode;
        $deltaGroup = $control->getDeltaGroup();
        $deltaGroup = $deltaGroup === null
            ? $path
            : [...$namespace, ...$this->normalizePath($deltaGroup)];

        if (array_slice($path, 0, count($deltaGroup)) !== $deltaGroup) {
            throw new InvalidArgumentException('Control delta groups must be ancestors of their paths.');
        }

        $value = $this->has($context->values, $path)
            ? $this->get($context->values, $path)
            : $control->getValue();
        $this->set($this->values, $path, $value);
        $this->controlPaths[] = $path;

        return new ControlPayload(
            type: $type,
            component: $control->component(),
            props: $control->props(),
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
            $absolutePath = [...$namespace, ...$this->normalizePath((string) $path)];
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
    private function normalizePath(string|array $path): array
    {
        $segments = is_string($path) ? ($path === '' ? [] : explode('.', $path)) : array_values($path);

        foreach ($segments as $segment) {
            if (! is_string($segment) || $segment === '') {
                throw new InvalidArgumentException('Form paths must contain non-empty string segments.');
            }
        }

        return $segments;
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
}
