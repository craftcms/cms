<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Cp\FormDefinitions;

use CraftCms\Cms\Cp\Components\FormContainer;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Yii2Adapter\Cp\Components\LegacySettings;
use CraftCms\Yii2Adapter\Tests\TestCase;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class LegacySettingsTest extends TestCase
{
    public function test_it_projects_the_adapter_owned_fragment(): void
    {
        $component = LegacySettings::make(new HtmlFragment(
            html: '<input name="settings[label]">',
            headHtml: '<style>.legacy { color: red; }</style>',
            bodyHtml: '<script>window.legacySettings = true;</script>',
        ))->key('legacy-settings');

        self::assertInstanceOf(FormContainer::class, $component);
        self::assertInstanceOf(ProjectableFormElement::class, $component);

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
        ], FormDefinition::make([$component])->toArray());
    }

    public function test_it_renders_the_adapter_browser_primitive_with_its_fragment(): void
    {
        $html = LegacySettings::make(new HtmlFragment(
            html: '<input name="settings[label]">',
            headHtml: '<style>.legacy { color: red; }</style>',
            bodyHtml: '<script>window.legacySettings = true;</script>',
        ))->key('legacy-settings')->toHtml();

        $document = new DOMDocument();
        $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        $island = $document->documentElement;

        self::assertInstanceOf(DOMElement::class, $island);
        self::assertSame('craft-legacy-settings-island', $island->tagName);
        self::assertSame('legacy-settings', $island->getAttribute('data-form-element-key'));
        self::assertSame([
            'html' => '<input name="settings[label]">',
            'headHtml' => '<style>.legacy { color: red; }</style>',
            'bodyHtml' => '<script>window.legacySettings = true;</script>',
        ], Json::decode($island->getAttribute('data-fragment')));
    }

    public function test_it_fails_when_the_adapter_component_type_is_not_registered(): void
    {
        $types = new FormElementTypes();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown or unregistered Form Element Type "yii2-adapter:legacy-settings"');

        $types->project(LegacySettings::make(new HtmlFragment(html: '<input>')));
    }
}
