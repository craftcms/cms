<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;

use function CraftCms\Cms\t;

class FieldLayoutCompiler
{
    public function __construct(private readonly FormResolver $resolver) {}

    public function compile(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        FormContext $context = new FormContext,
    ): FormPayload {
        $context = $this->normalizeErrors($layout, $context);

        return $this->resolver->resolve($this->form($layout, $element, $context), $context);
    }

    /** @internal Nested Controls need the unresolved definition during compilation. */
    public function form(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        FormContext $context = new FormContext,
    ): Form {
        $form = Form::make();

        foreach ($layout->getTabs() as $layoutTab) {
            if (! $layoutTab->showInForm($element)) {
                continue;
            }

            $nodes = [];

            foreach ($layoutTab->getElements() as $layoutElement) {
                if (! $layoutElement->showInForm($element)) {
                    continue;
                }

                $node = $layoutElement->formNode(new FieldLayoutElementContext(
                    $element,
                    $context,
                    $layoutElement->formMode($element),
                ));

                if ($node !== null) {
                    $nodes[] = $node;
                }
            }

            if ($nodes === []) {
                continue;
            }

            if (! $layoutTab->uid) {
                $form->add(...$nodes);

                continue;
            }

            $form->addTab(
                t($layoutTab->name ?? '', category: 'site'),
                $nodes,
                $layoutTab->uid,
            );
        }

        event($event = new FieldLayoutFormResolving($layout, $form, $context, $element));

        return $event->form;
    }

    private function normalizeErrors(FieldLayout $layout, FormContext $context): FormContext
    {
        $fieldHandles = array_fill_keys(array_map(
            fn ($field): string => $field->handle,
            $layout->getCustomFields(),
        ), true);
        $errors = [];

        foreach ($context->errors as $path => $messages) {
            $segments = explode('.', (string) $path);
            $path = isset($fieldHandles[$segments[0]]) ? "fields.{$path}" : $path;
            $errors[$path] = $messages;
        }

        return new FormContext(
            namespace: $context->namespace,
            values: $context->values,
            errors: $errors,
            globalErrors: $context->globalErrors,
            mode: $context->mode,
            refreshable: $context->refreshable,
        );
    }
}
