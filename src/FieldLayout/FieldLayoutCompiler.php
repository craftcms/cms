<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutUiResolving;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class FieldLayoutCompiler
{
    public function __construct(private readonly UiResolver $resolver) {}

    public function compile(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        UiContext $context = new UiContext,
    ): UiPayload {
        $context = $this->normalizeErrors($layout, $context);

        return $this->resolver->resolve($this->ui($layout, $element, $context), $context);
    }

    /** @internal Nested Controls need the unresolved definition during compilation. */
    public function ui(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        UiContext $context = new UiContext,
    ): Ui {
        $ui = Ui::make();

        foreach ($layout->getTabs() as $layoutTab) {
            if (! $layoutTab->showInForm($element)) {
                continue;
            }

            $nodes = [];

            foreach ($layoutTab->getElements() as $layoutElement) {
                if (! $layoutElement->showInForm($element)) {
                    continue;
                }

                $node = $layoutElement->uiNode(new FieldLayoutElementContext(
                    $element,
                    $context,
                    $layoutElement->uiMode($element),
                ));

                if ($node !== null) {
                    $nodes[] = $node;
                }
            }

            if ($nodes === []) {
                continue;
            }

            if (! $layoutTab->uid) {
                $ui->add(...$nodes);

                continue;
            }

            $ui->addTab(
                t($layoutTab->name ?? '', category: 'site'),
                $nodes,
                $layoutTab->uid,
            );
        }

        event($event = new FieldLayoutUiResolving($layout, $ui, $context, $element));

        return $event->ui;
    }

    private function normalizeErrors(FieldLayout $layout, UiContext $context): UiContext
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

        return new UiContext(
            namespace: $context->namespace,
            values: $context->values,
            errors: $errors,
            globalErrors: $context->globalErrors,
            mode: $context->mode,
            refreshable: $context->refreshable,
        );
    }
}
