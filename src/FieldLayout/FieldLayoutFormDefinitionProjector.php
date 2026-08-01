<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\InputElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tab;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tabs;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutFormElementProviderInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use Illuminate\Container\Attributes\Singleton;
use LogicException;

use function CraftCms\Cms\t;

#[Singleton]
class FieldLayoutFormDefinitionProjector
{
    /** @var (Closure(FieldLayoutElement, FieldLayoutFormElementContext): ?FormElement)|null */
    private ?Closure $unsupportedElementHandler = null;

    /** @param Closure(FieldLayoutElement, FieldLayoutFormElementContext): ?FormElement $handler */
    public function handleUnsupportedElementsUsing(Closure $handler): void
    {
        $this->unsupportedElementHandler = $handler;
    }

    public function project(
        FieldLayout $fieldLayout,
        FieldLayoutFormDefinitionContext $context,
    ): FormDefinition {
        $tabs = [];

        foreach ($fieldLayout->getTabs() as $layoutTab) {
            if (! isset($layoutTab->uid)) {
                throw new LogicException(sprintf(
                    'Field Layout tab "%s" must have a UID before it can be projected.',
                    $layoutTab->name ?? '',
                ));
            }

            if (! $layoutTab->showInForm($context->element)) {
                continue;
            }

            $elements = [];

            foreach ($layoutTab->getElements() as $layoutElement) {
                if (! isset($layoutElement->uid)) {
                    throw new LogicException(sprintf(
                        '%s layout element must have a UID before it can be projected.',
                        $layoutElement::class,
                    ));
                }

                if (! $layoutElement->showInForm($context->element)) {
                    continue;
                }

                $formElement = $this->projectElement($layoutElement, $context);

                if ($formElement !== null) {
                    $elements[] = $formElement;
                }
            }

            if ($elements === []) {
                continue;
            }

            $tabs[] = Tab::make(
                $layoutTab->uid,
                t($layoutTab->name ?? '', category: 'site'),
                $elements,
            )->hasErrors($context->element !== null && $layoutTab->elementHasErrors($context->element));
        }

        if ($tabs === []) {
            return FormDefinition::make([]);
        }

        return FormDefinition::make([
            Tabs::make($tabs)->key($fieldLayout->uid),
        ]);
    }

    private function projectElement(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormDefinitionContext $context,
    ): ?FormElement {
        $readOnly = $context->readOnly
            || ($layoutElement instanceof CustomField && ! $layoutElement->editable($context->element));
        $inputName = $layoutElement instanceof BaseField ? $layoutElement->attribute() : null;
        $value = $layoutElement instanceof BaseField ? $layoutElement->formElementValue($context->element) : null;
        $provider = $layoutElement;

        if ($layoutElement instanceof CustomField) {
            $provider = $layoutElement->getField();
            $inputName = "fields.{$layoutElement->attribute()}";
        }

        $elementContext = new FieldLayoutFormElementContext(
            layoutElement: $layoutElement,
            element: $context->element,
            inputName: $inputName,
            value: $value,
            readOnly: $readOnly,
            inputNamespace: $context->inputNamespace,
        );

        $formElement = $provider instanceof FieldLayoutFormElementProviderInterface
            ? $provider->formElement($elementContext)
            : $this->unsupportedElement($layoutElement, $elementContext);

        if ($formElement === null) {
            return null;
        }

        if ($layoutElement instanceof BaseField) {
            if (! $formElement instanceof InputElement) {
                throw new LogicException(sprintf(
                    '%s must provide an InputElement when projecting %s.',
                    $provider::class,
                    $layoutElement::class,
                ));
            }

            $layoutElement->configureFormElement($formElement, $context->element, $readOnly);
        }

        $formElement->key($layoutElement->uid);

        if ($layoutElement->hasCustomWidth()) {
            $formElement->width($layoutElement->width);
        }

        return $formElement;
    }

    private function unsupportedElement(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormElementContext $context,
    ): ?FormElement {
        if ($this->unsupportedElementHandler !== null) {
            return ($this->unsupportedElementHandler)($layoutElement, $context);
        }

        throw new LogicException(sprintf(
            '%s%s does not provide a Form Element and no compatibility fallback is registered.',
            $layoutElement::class,
            isset($layoutElement->uid) ? " ({$layoutElement->uid})" : '',
        ));
    }
}
