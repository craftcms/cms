<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Cp\FormDefinitions;

use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Yii2Adapter\Cp\FormDefinitions\Elements\LegacySettings;
use CraftCms\Yii2Adapter\Tests\TestCase;

class LegacySettingsTest extends TestCase
{
    public function test_it_projects_the_adapter_owned_fragment(): void
    {
        $definition = FormDefinition::make([
            LegacySettings::make(new HtmlFragment(
                html: '<input name="settings[label]">',
                headHtml: '<style>.legacy { color: red; }</style>',
                bodyHtml: '<script>window.legacySettings = true;</script>',
            ))->key('legacy-settings'),
        ]);

        self::assertSame([
            'elements' => [[
                'type' => 'yii2-adapter:legacy-settings',
                'key' => 'legacy-settings',
                'props' => [
                    'fragment' => [
                        'html' => '<input name="settings[label]">',
                        'headHtml' => '<style>.legacy { color: red; }</style>',
                        'bodyHtml' => '<script>window.legacySettings = true;</script>',
                    ],
                ],
            ]],
        ], $definition->toArray());
    }
}
