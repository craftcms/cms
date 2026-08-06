<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Tab;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use InvalidArgumentException;

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
        $payload = $this->resolver->resolve($this->form($layout, $element, $context), $context);

        $this->registerDeltaGroups($payload, $element);

        return $payload;
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
                throw new InvalidArgumentException('Persisted FieldLayout tabs require stable UIDs.');
            }

            $form->add(Tab::make(
                $layoutTab->uid,
                t($layoutTab->name ?? '', category: 'site'),
                $nodes,
            ));
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

    private function registerDeltaGroups(FormPayload $payload, ?ElementInterface $element): void
    {
        $registered = [];

        $visit = function (array $nodes) use (&$visit, &$registered, $payload, $element): void {
            foreach ($nodes as $node) {
                if ($node->control !== null && $node->control->mode === ControlMode::Editable) {
                    $relativeGroup = array_slice($node->control->deltaGroup, count($payload->scope));
                    $group = $relativeGroup;
                    $name = array_shift($group).implode('', array_map(fn (string $segment): string => "[{$segment}]", $group));

                    if (! isset($registered[$name])) {
                        $forceModified = $element !== null
                            && ($relativeGroup[0] ?? null) === 'fields'
                            && isset($relativeGroup[1])
                            && $element->isFieldDirty($relativeGroup[1]);
                        DeltaRegistry::registerName($name, $forceModified);
                        $registered[$name] = true;
                    }
                }

                $visit($node->children ?? []);

                foreach ($node->control->forms ?? [] as $form) {
                    $visit($form->nodes);
                }
            }
        };

        $visit($payload->nodes);
    }
}
