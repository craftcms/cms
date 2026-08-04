<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;

class FormHtmlRenderer
{
    public function render(FormPayload $payload): string
    {
        return $this->globalErrors($payload->globalErrors).$this->renderNodes($payload->nodes, $payload);
    }

    /** @param list<NodePayload> $nodes */
    public function renderNodes(array $nodes, FormPayload $payload): string
    {
        return implode('', array_map(
            fn (NodePayload $node): string => $this->renderNode($node, $payload),
            $nodes,
        ));
    }

    private function renderNode(NodePayload $node, FormPayload $payload): string
    {
        $type = $node->type;

        if (! is_a($type, Node::class, true)) {
            throw new InvalidArgumentException("Unsupported Form Node type [{$type}].");
        }

        return $type::renderHtml($node, $payload, $this);
    }

    /** @param array<string, mixed> $values */
    public function renderControl(
        ControlPayload $control,
        array $values,
        string $id,
        bool $invalid,
        bool $required,
    ): string {
        $value = $this->valueAt($values, $control->path);
        $mode = $control->mode;
        $attributes = [
            'id' => $id,
            'name' => $mode === ControlMode::Editable ? $this->name($control->path) : null,
            'disabled' => $mode === ControlMode::Disabled,
            'readonly' => $mode === ControlMode::ReadOnly,
            'required' => $mode === ControlMode::Editable && $required,
            'aria' => [
                'invalid' => $invalid ? 'true' : null,
            ],
        ];

        $type = $control->type;

        if (! is_a($type, Control::class, true)) {
            throw new InvalidArgumentException("Unsupported Form Control type [{$type}].");
        }

        return $type::renderHtml($control, $value, $attributes, $this);
    }

    /** @param list<string> $path */
    public function id(array $path): string
    {
        return 'form-'.implode('-', array_map(rawurlencode(...), $path));
    }

    /** @param list<string> $path */
    private function name(array $path): string
    {
        return array_shift($path).implode('', array_map(fn (string $segment): string => "[{$segment}]", $path));
    }

    /** @param array<string, mixed> $values @param list<string> $path */
    private function valueAt(array $values, array $path): mixed
    {
        foreach ($path as $segment) {
            $values = $values[$segment];
        }

        return $values;
    }

    /** @param list<array{path: list<string>, messages: list<string>}> $errors @param list<string> $path @return list<string> */
    public function errorsFor(array $errors, array $path): array
    {
        return array_merge(...array_map(
            fn (array $error): array => $error['path'] === $path ? $error['messages'] : [],
            $errors,
        ));
    }

    /** @param list<string> $errors */
    private function globalErrors(array $errors): string
    {
        return $errors === [] ? '' : $this->errorList($errors, null, ['role' => 'alert']);
    }

    /** @param list<string> $errors @param array<string, mixed> $attributes */
    private function errorList(array $errors, ?string $id, array $attributes = []): string
    {
        return Html::tag('ul', implode('', array_map(
            fn (string $error): string => Html::tag('li', Html::encode($error)),
            $errors,
        )), [...$attributes, 'id' => $id, 'class' => 'error-list']);
    }
}
