<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Cp\Components\FormContainer;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
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
    /** @var (Closure(FieldLayoutElement, FieldLayoutFormElementContext): (FormContainer|FormElement|null))|null */
    private ?Closure $unsupportedElementHandler = null;

    /** @param Closure(FieldLayoutElement, FieldLayoutFormElementContext): (FormContainer|FormElement|null) $handler */
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
    ): FieldComponent|FormContainer|FormElement|null {
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

        if ($provider instanceof FieldLayoutFormElementProviderInterface) {
            $input = $provider->formElement($elementContext);

            if ($input === null) {
                return null;
            }

            if ($input::isFormElementContainer()) {
                throw new LogicException(sprintf(
                    '%s must provide a projectable input component when projecting %s.',
                    $provider::class,
                    $layoutElement::class,
                ));
            }

            $formElement = FieldComponent::make()->input($input);

            if ($layoutElement instanceof BaseField) {
                $layoutElement->configureFormElement($formElement, $context->element, $readOnly);
            }
        } else {
            $formElement = $this->unsupportedElement($layoutElement, $elementContext);
        }

        if ($formElement === null) {
            return null;
        }

        $formElement->key($layoutElement->uid);

        if ($layoutElement->hasCustomWidth()) {
            if ($formElement instanceof FormElement) {
                $formElement->width($layoutElement->width);
            } else {
                $formElement->columnWidth($layoutElement->width);
            }
        }

        return $formElement;
    }

    private function unsupportedElement(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormElementContext $context,
    ): FormContainer|FormElement|null {
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
