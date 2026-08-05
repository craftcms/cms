<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Tab;
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

                $node = $layoutElement->formNode($element, $context);

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

        return $this->resolver->resolve($event->form, $context);
    }
}
