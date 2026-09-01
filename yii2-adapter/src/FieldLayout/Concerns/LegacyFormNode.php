<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\FieldLayout\LegacyFormEvents;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use InvalidArgumentException;

/** @phpstan-require-extends FieldLayoutElement */
trait LegacyFormNode
{
    abstract public function formHtml(?ElementInterface $element = null, bool $static = false): ?string;

    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (!$this->uid) {
            throw new InvalidArgumentException('Legacy FieldLayout elements require stable UIDs.');
        }

        $mode = $context->form->mode === ControlMode::Editable
            ? $context->mode
            : $context->form->mode;
        $layout = $this->getLayout() ?? throw new InvalidArgumentException('Legacy FieldLayout elements require a layout.');
        $legacyEvent = app(LegacyFormEvents::class)->prepare($layout, $context->element, $context->form);

        if ($legacyEvent !== null) {
            $mode = $legacyEvent->static ? ControlMode::ReadOnly : ControlMode::Editable;
        }

        $node = app(LegacyHtml::class)->capture(
            path: ['__legacyFieldLayout', $this->uid],
            hook: fn(): ?string => $mode === ControlMode::Disabled
                ? Html::disableInputs(fn(): ?string => $this->formHtml($context->element, true))
                : $this->formHtml($context->element, $mode !== ControlMode::Editable),
            namespace: LegacyHtml::namespace($context->form->namespace),
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
