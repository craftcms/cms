<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use Override;

class Tabs extends FormContainer
{
    #[Override]
    public static function make(iterable|Closure $children = []): static
    {
        return parent::make()->children($children);
    }

    public static function formElementType(): string
    {
        return 'craft:tabs';
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-tabs';
    }

    /** @return list<string> */
    #[Override]
    protected function hostOwnedFormElementAttributes(): array
    {
        return [...parent::hostOwnedFormElementAttributes(), 'selected-index'];
    }

    /** @return list<FormElementData> */
    #[Override]
    protected function formElementChildren(): array
    {
        return array_map(
            fn (Tab $tab): FormElementData => app(FormElementTypes::class)->project($tab),
            $this->resolvedTabs('Form'),
        );
    }

    #[Override]
    protected function renderSlots(): string
    {
        $tabs = $this->resolvedTabs('HTML');

        if ($tabs === []) {
            $this->invalidOutputOption('children', 'HTML');
        }

        $single = count($tabs) === 1;

        return implode('', array_map(
            fn (Tab $tab): string => $tab->renderForTabs($single),
            $tabs,
        ));
    }

    /** @return list<Tab> */
    private function resolvedTabs(string $output): array
    {
        $tabs = [];

        foreach ($this->resolvedChildren() as $index => $child) {
            if (! $child instanceof Tab) {
                $this->invalidChild($index, $child, Tab::class, $output);
            }

            $tabs[] = $child;
        }

        return $tabs;
    }
}
