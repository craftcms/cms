<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Yii2Adapter\FieldLayout\LegacyFormEvents;
use CraftCms\Yii2Adapter\Ui\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Ui\LegacyHtml;
use InvalidArgumentException;

/** @phpstan-require-extends FieldLayoutElement */
trait LegacyUiNode
{
    abstract public function formHtml(?ElementInterface $element = null, bool $static = false): ?string;

    public function uiNode(FieldLayoutElementContext $context): ?Node
    {
        if (!$this->uid) {
            throw new InvalidArgumentException('Legacy FieldLayout elements require stable UIDs.');
        }

        $mode = $context->ui->mode === ControlMode::Editable
            ? $context->mode
            : $context->ui->mode;
        $layout = $this->getLayout() ?? throw new InvalidArgumentException('Legacy FieldLayout elements require a layout.');
        $legacyEvent = app(LegacyFormEvents::class)->prepare($layout, $context->element, $context->ui);

        if ($legacyEvent !== null) {
            $mode = $legacyEvent->static ? ControlMode::ReadOnly : ControlMode::Editable;
        }

        $node = app(LegacyHtml::class)->capture(
            path: ['__legacyFieldLayout', $this->uid],
            hook: fn(): ?string => $mode === ControlMode::Disabled
                ? Html::disableInputs(fn(): ?string => $this->formHtml($context->element, true))
                : $this->formHtml($context->element, $mode !== ControlMode::Editable),
            namespace: LegacyHtml::namespace($context->ui->namespace),
            mode: match ($mode) {
                ControlMode::Editable => LegacyHtmlMode::Editable,
                ControlMode::ReadOnly => LegacyHtmlMode::Static,
                ControlMode::Disabled => LegacyHtmlMode::Disabled,
            },
        );

        $node?->getControl()->deltaGroupAtNamespace()->expandValues();

        return $node;
    }
}
