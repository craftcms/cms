<?php

declare(strict_types=1);

use craft\base\Widget;
use craft\widgets\Feed;
use CraftCms\Cms\Http\Controllers\Dashboard\InteractsWithWidgets;
use CraftCms\Cms\Support\Facades\HtmlStack;

it('renders HTML plugin widgets and their registered assets', function(string $type) {
    $widget = new $type(['id' => 1]);
    $renderer = new class() {
        use InteractsWithWidgets {
            getWidgetData as public render;
        }
    };

    $data = $renderer->render($widget);

    expect($data->component)->toBe('craft:html-widget')
        ->and($data->fragment->html)->toBe('<p>Plugin body</p>')
        ->and($data->fragment->bodyHtml)->toContain('window.widgetLoaded = true;');
})->with([DashboardBaseHtmlWidget::class, DashboardPluginFeed::class]);

it('lets descendants opt back into a Vue component', function() {
    $widget = new DashboardVueWidget();

    expect($widget->component())->toBe('example:dashboard-widget')
        ->and($widget->props())->toBe(['message' => 'Plugin component']);
});

it('hides a built-in subclass when its HTML override returns null', function() {
    $widget = new DashboardHiddenFeed();

    expect($widget->component())->toBeNull();
});

it('keeps unmodified built-in widgets on their core renderer', function() {
    $widget = new Feed(['url' => 'https://example.com/feed']);

    expect($widget->component())->toBe('craft:widget-feed')
        ->and($widget->props()['url'])->toBe('https://example.com/feed')
        ->and(new craft\widgets\MissingWidget()->component())->toBeNull();
});

class DashboardBaseHtmlWidget extends Widget
{
    public function getBodyHtml(): string
    {
        HtmlStack::js('window.widgetLoaded = true;');

        return '<p>Plugin body</p>';
    }
}

class DashboardPluginFeed extends Feed
{
    public function getBodyHtml(): ?string
    {
        HtmlStack::js('window.widgetLoaded = true;');

        return '<p>Plugin body</p>';
    }
}

class DashboardHiddenFeed extends DashboardPluginFeed
{
    public function getBodyHtml(): ?string
    {
        return null;
    }
}

class DashboardVueWidget extends DashboardPluginFeed
{
    public function component(): string
    {
        return 'example:dashboard-widget';
    }

    public function props(): array
    {
        return ['message' => 'Plugin component'];
    }
}
