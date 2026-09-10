<?php

declare(strict_types=1);

use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\services\Announcements;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\LegacyApp;
use Illuminate\Support\Facades\Event;

it('only merges configuration for the current application type', function() {
    config()->set('craft.app.web.id', 'web-app');
    config()->set('craft.app.console.id', 'console-app');

    app()->forgetInstance('Craft');

    new LegacyApp()->register($this->app);

    expect(app('Craft')->id)->toBe('web-app');
});

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

it('exposes the announcement service through the legacy application', function() {
    expect(Craft::$app->getAnnouncements())->toBeInstanceOf(Announcements::class)
        ->and(Craft::$app->announcements)->toBe(Craft::$app->getAnnouncements());
});

it('retains the legacy mark announcements action', function() {
    $announcements = Mockery::mock(Announcements::class);
    $announcements->shouldReceive('markAsRead')
        ->once()
        ->with(['4ca5a942-8851-4d43-9d08-0ebc6273445f']);
    app()->instance(Announcements::class, $announcements);
    Craft::$app->request->setBodyParams([
        'ids' => ['4ca5a942-8851-4d43-9d08-0ebc6273445f'],
    ]);

    $response = new UsersController('users', Craft::$app)->actionMarkAnnouncementsAsRead();

    expect($response->statusCode)->toBe(302);
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
