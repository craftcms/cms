<?php

declare(strict_types=1);

use craft\base\Plugin;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\LegacyApp;
use Illuminate\Support\Facades\Event;

it('does not resolve a second legacy app while loading legacy plugins', function() {
    LegacyAppTestPlugin::$resolvedCraftApp = null;

    $loading = false;
    $loadCount = 0;
    $plugins = Mockery::mock(Plugins::class);
    $plugins->shouldReceive('getAllPlugins')->andReturnUsing(function() use (&$loading, &$loadCount) {
        $loadCount++;

        if ($loading) {
            return [];
        }

        $loading = true;

        try {
            return [
                'legacy-app-test' => LegacyAppTestPlugin::create([
                    'handle' => 'legacy-app-test',
                    'name' => 'Legacy App Test',
                    'packageName' => 'craftcms/legacy-app-test',
                    'version' => '1.0.0',
                    'basePath' => __DIR__,
                ]),
            ];
        } finally {
            $loading = false;
        }
    });

    app()->instance(Plugins::class, $plugins);
    Event::forget(PageEnded::class);
    app()->forgetInstance('Craft');

    new LegacyApp()->register($this->app);
    app('Craft');

    HtmlStack::clear();
    HtmlStack::js('window.legacyAppTest = true', Position::Head);
    event($event = new PageEnded());

    expect($loadCount)->toBe(1)
        ->and(app('Craft'))->toBe(Craft::$app)
        ->and(LegacyAppTestPlugin::$resolvedCraftApp)->toBe(Craft::$app)
        ->and($event->headHtml)->toContain('window.legacyAppTest = true;');
});

class LegacyAppTestPlugin extends Plugin
{
    public static ?object $resolvedCraftApp = null;

    public static function config(): array
    {
        self::$resolvedCraftApp = app('Craft');

        return [];
    }
}
