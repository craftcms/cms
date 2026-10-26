<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Hidden;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Ui;

/**
 * Describes a component’s settings as JSON Schema, from the settings UI the control panel shows for it.
 *
 * @since 6.0.0
 */
readonly class SettingsSchema
{
    /**
     * @param  list<Node>|Ui  $ui
     * @param  array<string, mixed>  $defaults  The component’s default settings.
     * @return array<string, mixed>
     */
    public function fromUi(Ui|array $ui, array $defaults = []): array
    {
        $schema = ['type' => 'object', 'properties' => [], 'additionalProperties' => true];
        $required = [];

        $this->walk($ui instanceof Ui ? $ui->nodes() : $ui, [], [], $defaults, $schema, $required);

        if ($required !== []) {
            $schema['required'] = array_values(array_unique($required));
        }

        if ($schema['properties'] === []) {
            $schema['properties'] = (object) [];
        }

        return $schema;
    }

    /**
     * @param  array<array-key, mixed>  $nodes
     * @param  list<string>  $prefix
     * @param  array{label?: string, instructions?: string, required?: bool, dependsOn?: string}  $context
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $required
     */
    private function walk(array $nodes, array $prefix, array $context, array $defaults, array &$schema, array &$required): void
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Node) {
                continue;
            }

            $props = $node->props();

            if (! empty($props['hidden'])) {
                continue;
            }

            $nodeContext = $context;
            $dependsOn = is_array($props['dependsOn'] ?? null) ? implode('.', $props['dependsOn']) : ($props['dependsOn'] ?? null);

            if (is_string($dependsOn) && $dependsOn !== '') {
                $nodeContext['dependsOn'] = $dependsOn;
            }

            if (! empty($props['asField'])) {
                $nodeContext = [
                    ...$nodeContext,
                    ...Arr::whereNotNull([
                        'label' => $this->text($props['label'] ?? null),
                        'instructions' => $this->text($props['instructions'] ?? null),
                    ]),
                    'required' => ! empty($props['required']),
                ];
            }

            $nodePrefix = $node instanceof Scope ? [...$prefix, ...$this->segments($node->path())] : $prefix;
            $control = $node->getControl();

            if ($node instanceof Field && $control instanceof Control && ! $control instanceof Hidden && $control->getMode() === ControlMode::Editable) {
                $this->addProperty(
                    [...$nodePrefix, ...$this->segments($control->path())],
                    $control,
                    [
                        ...$nodeContext,
                        ...$this->fieldContext($nodeContext, $props),
                        'required' => ! empty($props['required']) || ! empty($nodeContext['required']),
                    ],
                    $defaults,
                    $schema,
                    $required,
                );
            }

            $this->walk($node->children(), $nodePrefix, $nodeContext, $defaults, $schema, $required);
        }
    }

    /**
     * @param  list<string>  $path
     * @param  array{label?: string, instructions?: string, required?: bool, dependsOn?: string}  $context
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $required
     */
    private function addProperty(array $path, Control $control, array $context, array $defaults, array &$schema, array &$required): void
    {
        if ($path === []) {
            return;
        }

        $property = $control->inputSchema();
        $notes = array_filter([
            $property['description'] ?? null,
            $context['instructions'] ?? null,
            isset($context['dependsOn']) ? sprintf('Only applies when %s is enabled.', preg_replace('/^settings\./', '', $context['dependsOn'])) : null,
            $property === [] ? sprintf('Set in the control panel with a %s control; read an existing record’s settings for the value’s shape.', class_basename($control)) : null,
        ]);
        $default = Arr::get($defaults, implode('.', $path));

        $property = [
            ...Arr::whereNotNull([
                'title' => $context['label'] ?? null,
                'description' => $notes !== [] ? implode(' ', $notes) : null,
                'default' => $default,
            ]),
            ...Arr::except($property, ['description']),
        ];

        $target = &$schema;

        foreach (array_slice($path, 0, -1) as $segment) {
            $target['properties'][$segment] ??= ['type' => 'object', 'properties' => [], 'additionalProperties' => true];
            $target = &$target['properties'][$segment];
        }

        $target['properties'][end($path)] = $property;

        if (count($path) === 1 && ! empty($context['required'])) {
            $required[] = $path[0];
        }
    }

    /**
     * A field’s own label and instructions, noting the grouped field it belongs to.
     *
     * @param  array{label?: string, instructions?: string}  $context
     * @param  array<string, mixed>  $props
     * @return array{label?: string, instructions?: string}
     */
    private function fieldContext(array $context, array $props): array
    {
        $label = $this->text($props['label'] ?? null);
        $instructions = $this->text($props['instructions'] ?? null);

        if (isset($context['label']) && $label !== null && $label !== $context['label']) {
            $instructions = implode(' ', array_filter([
                $instructions,
                "Part of “{$context['label']}”".(isset($context['instructions']) ? ": {$context['instructions']}" : '.'),
            ]));
        }

        return Arr::whereNotNull([
            'label' => $label,
            'instructions' => $instructions ?? ($label === null ? null : ($context['instructions'] ?? null)),
        ]);
    }

    /**
     * @param  string|list<string>  $path
     * @return list<string>
     */
    private function segments(string|array $path): array
    {
        return is_string($path) ? ($path === '' ? [] : explode('.', $path)) : array_values($path);
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags((string) $value)));

        return $text !== '' ? $text : null;
    }
}
