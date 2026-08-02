<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\ConfigurableComponent;
use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Deprecator\Deprecator as DeprecatorService;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Yii2Adapter\Tests\TestCase;
use LogicException;

class LegacyFormComponent extends ConfigurableComponent
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

class NativeFormComponent extends LegacyFormComponent
{
    public function getSettingsForm(bool $readOnly): ?Form
    {
        return Form::make([
            Group::make([]),
        ]);
    }

    public function getSettingsHtml(): string
    {
        throw new LogicException('The legacy wrapper was invoked.');
    }
}

class SettingsFreeFormComponent extends LegacyFormComponent
{
    public function getSettingsForm(bool $readOnly): ?Form
    {
        return null;
    }

    public function getSettingsHtml(): string
    {
        throw new LogicException('The legacy wrapper was invoked.');
    }
}

class AnotherLegacyFormComponent extends LegacyFormComponent {}

class ConfigurableComponentFormTest extends TestCase
{
    public function test_it_bridges_editable_settings_inside_the_final_input_namespace(): void
    {
        $component = new LegacyFormComponent;

        $definition = InputNamespace::with(
            'widgets[example][settings]',
            fn (): ?Form => $component->getSettingsForm(false),
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
        $component = new LegacyFormComponent;

        $definition = $component->getSettingsForm(true);

        self::assertSame(
            '<span data-read-only-settings>Read only</span>',
            $definition?->toArray()['elements'][0]['props']['fragment']['html'],
        );
    }

    public function test_a_native_override_bypasses_the_legacy_wrapper(): void
    {
        $component = new NativeFormComponent;

        self::assertSame([
            'elements' => [[
                'type' => 'craft:group',
            ]],
        ], $component->getSettingsForm(false)?->toArray());
    }

    public function test_a_null_native_result_does_not_fall_back_to_legacy_html(): void
    {
        $component = new SettingsFreeFormComponent;

        self::assertNull($component->getSettingsForm(false));
    }

    public function test_it_deduplicates_deprecations_per_legacy_component_class(): void
    {
        DeprecatorService::$logTarget = 'db';
        $component = new LegacyFormComponent;

        $component->getSettingsForm(false);
        $component->getSettingsForm(false);

        $logs = array_values(Deprecator::getRequestLogs());

        self::assertCount(1, $logs);
        self::assertStringContainsString(LegacyFormComponent::class, $logs[0]->message);
        self::assertStringContainsString('getSettingsForm()', $logs[0]->message);

        new AnotherLegacyFormComponent()->getSettingsForm(false);

        self::assertCount(2, Deprecator::getRequestLogs());
    }
}
