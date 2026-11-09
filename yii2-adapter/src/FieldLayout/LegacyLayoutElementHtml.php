<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutElementControlResolving;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Yii2Adapter\Ui\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Ui\LegacyHtml;
use CraftCms\Yii2Adapter\Ui\Nodes\LegacyHtmlField;
use InvalidArgumentException;

/**
 * Renders field layout elements through their Craft 5 `formHtml()` and `inputHtml()` methods.
 */
class LegacyLayoutElementHtml
{
    /**
     * Gives fields that only implement `inputHtml()` or `formHtml()` a UI on Craft 6 edit screens.
     */
    public function __invoke(FieldLayoutElementControlResolving $event): void
    {
        $layoutElement = $event->layoutElement;

        if (method_exists($layoutElement, 'formHtml')) {
            $event->node = $this->capture($layoutElement, $event->context, $layoutElement->formHtml(...));

            return;
        }

        if (method_exists($layoutElement, 'inputHtml')) {
            $inputHtml = (fn(): Closure => $this->inputHtml(...))->call($layoutElement);
            $event->control = $this->capture($layoutElement, $event->context, $inputHtml, $layoutElement->attribute())?->getControl();
        }
    }

    /**
     * @param  Closure(?ElementInterface, bool): ?string  $html
     * @param  string|list<string>|null  $path  Defaults to a path based on the layout element’s UID
     */
    public function capture(
        FieldLayoutElement $layoutElement,
        FieldLayoutElementContext $context,
        Closure $html,
        string|array|null $path = null,
    ): ?LegacyHtmlField {
        $path ??= ['__legacyFieldLayout', $layoutElement->uid ?: throw new InvalidArgumentException('Legacy FieldLayout elements require stable UIDs.')];

        $mode = $context->ui->mode === ControlMode::Editable
            ? $context->mode
            : $context->ui->mode;
        $layout = $layoutElement->getLayout() ?? throw new InvalidArgumentException('Legacy FieldLayout elements require a layout.');
        $legacyEvent = app(LegacyFormEvents::class)->prepare($layout, $context->element, $context->ui);

        if ($legacyEvent !== null) {
            $mode = $legacyEvent->static ? ControlMode::ReadOnly : ControlMode::Editable;
        }

        $node = app(LegacyHtml::class)->capture(
            path: $path,
            hook: fn(): ?string => $mode === ControlMode::Disabled
                ? Html::disableInputs(fn(): ?string => $html($context->element, true))
                : $html($context->element, $mode !== ControlMode::Editable),
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
