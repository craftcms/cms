<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\ConfigurableComponent;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Group;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Deprecator\Deprecator as DeprecatorService;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Yii2Adapter\Tests\TestCase;
use LogicException;

class LegacyFormDefinitionComponent extends ConfigurableComponent
{
    public function getSettingsHtml(): string
    {
        HtmlStack::css('.legacy-settings { color: red; }');
        HtmlStack::js("document.querySelector('#".InputNamespace::namespaceId('nested-label')."')");

        return '<input id="nested-label" name="nested[label]">';
    }

    public function getReadOnlySettingsHtml(): string
    {
        return '<span data-read-only-settings>Read only</span>';
    }
}

class NativeFormDefinitionComponent extends LegacyFormDefinitionComponent
{
    public function getSettingsFormDefinition(bool $readOnly): ?FormDefinition
    {
        return FormDefinition::make([
            Group::make([]),
        ]);
    }

    public function getSettingsHtml(): string
    {
        throw new LogicException('The legacy wrapper was invoked.');
    }
}

class SettingsFreeFormDefinitionComponent extends LegacyFormDefinitionComponent
{
    public function getSettingsFormDefinition(bool $readOnly): ?FormDefinition
    {
        return null;
    }

    public function getSettingsHtml(): string
    {
        throw new LogicException('The legacy wrapper was invoked.');
    }
}

class AnotherLegacyFormDefinitionComponent extends LegacyFormDefinitionComponent {}

class ConfigurableComponentFormDefinitionTest extends TestCase
{
    public function test_it_bridges_editable_settings_inside_the_final_input_namespace(): void
    {
        $component = new LegacyFormDefinitionComponent;

        $definition = InputNamespace::with(
            'widgets[example][settings]',
            fn (): ?FormDefinition => $component->getSettingsFormDefinition(false),
        );

        $element = $definition?->toArray()['elements'][0];

        self::assertSame('yii2-adapter:legacy-settings', $element['type']);
        self::assertSame(
            '<input id="widgets-example-settings-nested-label" name="widgets[example][settings][nested][label]">',
            $element['props']['fragment']['html'],
        );
        self::assertStringContainsString('.legacy-settings { color: red; }', $element['props']['fragment']['headHtml']);
        self::assertStringContainsString(
            "document.querySelector('#widgets-example-settings-nested-label')",
            $element['props']['fragment']['bodyHtml'],
        );
    }

    public function test_it_bridges_read_only_settings(): void
    {
        $component = new LegacyFormDefinitionComponent;

        $definition = $component->getSettingsFormDefinition(true);

        self::assertSame(
            '<span data-read-only-settings>Read only</span>',
            $definition?->toArray()['elements'][0]['props']['fragment']['html'],
        );
    }

    public function test_a_native_override_bypasses_the_legacy_wrapper(): void
    {
        $component = new NativeFormDefinitionComponent;

        self::assertSame([
            'elements' => [[
                'type' => 'craft:group',
            ]],
        ], $component->getSettingsFormDefinition(false)?->toArray());
    }

    public function test_a_null_native_result_does_not_fall_back_to_legacy_html(): void
    {
        $component = new SettingsFreeFormDefinitionComponent;

        self::assertNull($component->getSettingsFormDefinition(false));
    }

    public function test_it_deduplicates_deprecations_per_legacy_component_class(): void
    {
        DeprecatorService::$logTarget = 'db';
        $component = new LegacyFormDefinitionComponent;

        $component->getSettingsFormDefinition(false);
        $component->getSettingsFormDefinition(false);

        $logs = array_values(Deprecator::getRequestLogs());

        self::assertCount(1, $logs);
        self::assertStringContainsString(LegacyFormDefinitionComponent::class, $logs[0]->message);
        self::assertStringContainsString('getSettingsFormDefinition()', $logs[0]->message);

        new AnotherLegacyFormDefinitionComponent()->getSettingsFormDefinition(false);

        self::assertCount(2, Deprecator::getRequestLogs());
    }
}
