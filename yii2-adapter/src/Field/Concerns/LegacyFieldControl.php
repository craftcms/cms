<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use RuntimeException;

/** @phpstan-require-implements FieldInterface */
trait LegacyFieldControl
{
    public function formControl(FieldContext $context): Control
    {
        $mode = $context->form->mode === ControlMode::Editable
            ? $context->mode
            : $context->form->mode;
        $path = self::segments($context->path);
        $namespacePath = $path;
        array_pop($namespacePath);
        $node = app(LegacyHtml::class)->field(
            field: $this,
            value: $context->value,
            element: $context->element,
            path: $path,
            namespace: LegacyHtml::namespace([
                ...self::segments($context->form->namespace),
                ...$namespacePath,
            ]),
            mode: match ($mode) {
                ControlMode::Editable => LegacyHtmlMode::Editable,
                ControlMode::ReadOnly => LegacyHtmlMode::ReadOnly,
                ControlMode::Disabled => LegacyHtmlMode::Disabled,
            },
            deltaGroup: $path,
        );

        return $node?->getControl()->expandValues() ?? throw new RuntimeException(sprintf(
            '%s::getInputHtml() must return HTML.',
            static::class,
        ));
    }

    /** @return list<string> */
    private static function segments(string|array $path): array
    {
        return is_string($path)
            ? ($path === '' ? [] : explode('.', $path))
            : $path;
    }
}
