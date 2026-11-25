<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Enums\TabType;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutUiResolving;
use CraftCms\Cms\Ui\Nodes\Tab;
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

    /**
     * @param  list<string>|null  $tabs  The UIDs of the only tabs to compile, or `null` for all of them
     * @param  bool  $deferOthers  Whether tabs left out of `$tabs` are compiled as empty, deferred tabs, for the
     *                             client to fill in later, rather than left out
     */
    public function compile(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        UiContext $context = new UiContext,
        ?array $tabs = null,
        bool $deferOthers = false,
    ): UiPayload {
        $context = $this->normalizeErrors($layout, $context);

        return $this->resolver->resolve($this->ui($layout, $element, $context, $tabs, $deferOthers), $context);
    }

    /**
     * @internal Nested Controls need the unresolved definition during compilation.
     *
     * @param  list<string>|null  $tabs
     */
    public function ui(
        FieldLayout $layout,
        ?ElementInterface $element = null,
        UiContext $context = new UiContext,
        ?array $tabs = null,
        bool $deferOthers = false,
    ): Ui {
        $ui = Ui::make();

        foreach ($layout->getTabs() as $layoutTab) {
            if (! $layoutTab->showInForm($element)) {
                continue;
            }

            if ($tabs !== null && $layoutTab->uid && ! in_array($layoutTab->uid, $tabs, true)) {
                if ($deferOthers && $this->hasFormElements($layoutTab, $element)) {
                    $ui->add(Tab::make($layoutTab->uid, t($layoutTab->name ?? '', category: 'site'))
                        ->icon($layoutTab->icon)
                        ->sidebar($layoutTab->type === TabType::Sidebar)
                        ->deferred());
                }

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
                $layoutTab->icon,
                $layoutTab->type === TabType::Sidebar,
            );
        }

        event($event = new FieldLayoutUiResolving($layout, $ui, $context, $element));

        return $event->ui;
    }

    /**
     * Returns the UIDs of the content tabs a form doesn't show until they're selected: all but the first, leaving
     * out sidebar tabs, which always show.
     *
     * @return list<string>
     */
    public function deferrableTabs(FieldLayout $layout, ?ElementInterface $element = null): array
    {
        $uids = [];
        $first = true;

        foreach ($layout->getTabs() as $layoutTab) {
            if (
                ! $layoutTab->uid ||
                $layoutTab->type === TabType::Sidebar ||
                ! $layoutTab->showInForm($element) ||
                ! $this->hasFormElements($layoutTab, $element)
            ) {
                continue;
            }

            if ($first) {
                $first = false;
            } else {
                $uids[] = $layoutTab->uid;
            }
        }

        return $uids;
    }

    /**
     * Whether a tab has anything to show in the form, short of compiling it. Elements can still compile to nothing,
     * which leaves the deferred tab empty once it's filled in.
     */
    private function hasFormElements(FieldLayoutTab $layoutTab, ?ElementInterface $element): bool
    {
        return array_any($layoutTab->getElements(), fn ($layoutElement) => $layoutElement->showInForm($element));
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
            preview: $context->preview,
        );
    }
}
