<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ElementTypeIndexViewModel;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

it('lists statuses defined with a label and color in the status menu by their label and color', function () {
    actingAs(User::findOne());

    $options = collect(new ElementTypeIndexViewModel(
        TestCustomStatusesElement::class,
        ElementIndexRequest::create('/admin/test')->setContainer(app()),
    )->statusOptions())->keyBy('value');

    expect($options['archived'])->toBe(['label' => 'Archived', 'value' => 'archived', 'color' => 'red'])
        ->and($options['enabled'])->toBe(['label' => 'Enabled', 'value' => 'enabled']);
});

it('reloads every prop on a filter change except those marked as unaffected by filters', function () {
    actingAs(User::findOne());

    $filterProps = new TestFilterPropsIndexViewModel(
        TestCustomStatusesElement::class,
        ElementIndexRequest::create('/admin/test')->setContainer(app()),
    )->filterProps();

    expect($filterProps)->toContain('data', 'pagination', 'actions', 'search', 'status', 'queue', 'pluginProp')
        ->and(array_intersect($filterProps, ['sourceNavItems', 'crumbs', 'sites', 'exporters', 'title', 'filterProps']))->toBe([]);
});

class TestFilterPropsIndexViewModel extends ElementTypeIndexViewModel
{
    public function pluginProp(): string
    {
        return '';
    }

    public function title(): string
    {
        return 'Overridden';
    }
}

class TestCustomStatusesElement extends Element
{
    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            'enabled' => 'Enabled',
            'archived' => ['label' => 'Archived', 'color' => Color::Red],
        ];
    }
}
