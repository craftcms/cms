<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Yii2Adapter\Ui\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Ui\LegacyHtml;
use RuntimeException;

/** @phpstan-require-implements FieldInterface */
trait LegacyFieldControl
{
    public function uiControl(FieldContext $context): Control
    {
        $mode = $context->ui->mode === ControlMode::Editable
            ? $context->mode
            : $context->ui->mode;
        $path = self::segments($context->path);
        $namespacePath = $path;
        array_pop($namespacePath);
        $node = app(LegacyHtml::class)->field(
            field: $this,
            value: $context->value,
            element: $context->element,
            path: $path,
            namespace: LegacyHtml::namespace([
                ...self::segments($context->ui->namespace),
                ...$namespacePath,
            ]),
            mode: match ($mode) {
                ControlMode::Editable => LegacyHtmlMode::Editable,
                ControlMode::ReadOnly => LegacyHtmlMode::ReadOnly,
                ControlMode::Disabled => LegacyHtmlMode::Disabled,
            },
            deltaGroup: $path,
            inline: $context->inline,
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
