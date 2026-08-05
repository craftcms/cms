<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FormHtmlRenderer
{
    private ?FormPayload $payload = null;

    public function __construct(
        private readonly FormNodeTypes $nodeTypes,
        private readonly FormControlTypes $controlTypes,
    ) {}

    public function render(FormPayload $payload): string
    {
        $this->payload = $payload;

        try {
            return $this->globalErrors($payload->globalErrors).$this->renderNodes($payload->nodes, $payload);
        } finally {
            $this->payload = null;
        }
    }

    /** @param list<NodePayload> $nodes */
    public function renderNodes(array $nodes, FormPayload $payload): string
    {
        return implode('', array_map(
            fn (NodePayload $node): string => $this->renderNode($node, $payload),
            $nodes,
        ));
    }

    public function renderNestedForm(NestedFormPayload $form): string
    {
        if ($this->payload === null) {
            throw new RuntimeException('Nested Forms can only be rendered within a Form payload.');
        }

        return $this->renderNodes($form->nodes, $this->payload);
    }

    private function renderNode(NodePayload $node, FormPayload $payload): string
    {
        $type = $node->type;
        $identity = $node->uid ?? implode('.', $node->control->path);

        if ($this->nodeTypes->types()->doesntContain($type)) {
            throw new InvalidArgumentException("Form Node type [{$type}] with component [{$node->component}] at [{$identity}] is not registered.");
        }

        try {
            return $type::renderHtml($node, $payload, $this);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Failed to render Form Node [{$type}] with component [{$node->component}] at [{$identity}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }
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
        $identity = implode('.', $control->path);

        if ($this->controlTypes->types()->doesntContain($type)) {
            throw new InvalidArgumentException("Form Control type [{$type}] with component [{$control->component}] at [{$identity}] is not registered.");
        }

        try {
            return $type::renderHtml($control, $value, $attributes, $this);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Failed to render Form Control [{$type}] with component [{$control->component}] at [{$identity}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }
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
