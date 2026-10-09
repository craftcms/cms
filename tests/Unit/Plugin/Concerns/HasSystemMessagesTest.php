<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    I18N::shouldReceive('getSiteLocaleIds')->andReturn(collect([app()->getLocale()]));
    I18N::shouldReceive('translate')->andReturnUsing(fn (string $message) => $message);
});

it('registers configured system message factories', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setSystemMessages([
        'test_plugin' => fn () => new SystemMessage([
            'key' => 'test_plugin',
            'heading' => 'Heading',
            'subject' => 'Subject',
            'body' => 'Body',
        ]),
    ]);
    $plugin->bootHasSystemMessages();

    expect(app(SystemMessages::class)->messages())->toHaveKey('test_plugin');
});
