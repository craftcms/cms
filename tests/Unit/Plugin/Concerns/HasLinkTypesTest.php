<?php

declare(strict_types=1);

use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers configured link types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setLinkTypes([TestPluginLinkType::class]);
    $plugin->bootHasLinkTypes();

    expect(app(LinkTypes::class)->types())->toContain(TestPluginLinkType::class);
});

abstract class TestPluginLinkType extends BaseLinkType
{
    #[Override]
    public static function id(): string
    {
        return 'test-plugin';
    }
}
