<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\Forms\Contracts\PositionableFormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutFormElementProviderInterface;
use CraftCms\Cms\FieldLayout\Exceptions\UnsupportedFieldLayoutFormElementException;
use Illuminate\Container\Attributes\Singleton;
use LogicException;

use function CraftCms\Cms\t;

#[Singleton]
class FieldLayoutFormProjector
{
    /** @var (Closure(FieldLayoutElement, FieldLayoutFormElementContext): ((ViewComponent&PositionableFormElement)|null))|null */
    private ?Closure $unsupportedElementHandler = null;

    /** @param Closure(FieldLayoutElement, FieldLayoutFormElementContext): ((ViewComponent&PositionableFormElement)|null) $handler */
    public function handleUnsupportedElementsUsing(Closure $handler): void
    {
        $this->unsupportedElementHandler = $handler;
    }

    public function project(
        FieldLayout $fieldLayout,
        FieldLayoutFormContext $context,
    ): Form {
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
            return Form::make([]);
        }

        return Form::make([
            Tabs::make($tabs)->key($fieldLayout->uid),
        ]);
    }

    private function projectElement(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormContext $context,
    ): (ViewComponent&PositionableFormElement)|null {
        $elementContext = $layoutElement->formElementContext($context);

        try {
            $formElement = $layoutElement instanceof FieldLayoutFormElementProviderInterface
                ? $layoutElement->formElement($elementContext)
                : $this->unsupportedElement($layoutElement, $elementContext);
        } catch (UnsupportedFieldLayoutFormElementException $exception) {
            $formElement = $this->unsupportedElement($layoutElement, $elementContext, $exception);
        }

        if ($formElement === null) {
            return null;
        }

        $formElement->key($layoutElement->uid);

        if ($layoutElement->hasCustomWidth()) {
            $formElement->columnWidth($layoutElement->width);
        }

        return $formElement;
    }

    private function unsupportedElement(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormElementContext $context,
        ?UnsupportedFieldLayoutFormElementException $previous = null,
    ): (ViewComponent&PositionableFormElement)|null {
        if ($this->unsupportedElementHandler !== null) {
            return ($this->unsupportedElementHandler)($layoutElement, $context);
        }

        throw new LogicException(sprintf(
            '%s%s does not provide a Form Element and no compatibility fallback is registered.',
            $layoutElement::class,
            isset($layoutElement->uid) ? " ({$layoutElement->uid})" : '',
        ), previous: $previous);
    }
}
