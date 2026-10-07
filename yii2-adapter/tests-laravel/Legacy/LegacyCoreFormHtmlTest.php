<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\fieldlayoutelements\entries\EntryTitleField as LegacyEntryTitleField;
use craft\fields\Assets;
use craft\fields\Entries;
use craft\fields\Table;
use craft\fields\Users;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\View\TemplateMode;
use Mockery;
use Symfony\Component\DomCrawler\Crawler;

it('uses each built-in relation settings template through legacy parent hooks', function(string $class, string $template) {
    $field = Mockery::mock($class)->makePartial()->shouldAllowMockingProtectedMethods();
    $condition = Mockery::mock(ElementCondition::class)->makePartial();
    $condition->__construct();
    $condition->shouldReceive('getSelectableConditionRules')->andReturn([]);
    $field->shouldReceive('getSelectionCondition')->andReturn($condition);
    Template::shouldReceive('renderTemplate')->once()->withArgs(function($name, $variables) use ($template, $field) {
        expect($name)->toBe($template)
            ->and($variables['field'])->toBe($field);

        return true;
    })->andReturn('<input name="selectionLabel" value="Choose">');
    $context = new UiContext(namespace: 'settings');

    $payload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $control = $payload->nodes[0]->control;
    $html = new Crawler($control->props['fragment']['html']);

    expect($html->filter('input')->attr('name'))->toBe('settings[selectionLabel]')
        ->and($html->filter('input')->attr('value'))->toBe('Choose')
        ->and($control->props['fragment']['bodyHtml'])->toContain('Craft.ElementFieldSettings');
})->with([
    'entries' => [Entries::class, '_components/fieldtypes/Entries/settings.twig'],
    'assets' => [Assets::class, '_components/fieldtypes/Assets/settings.twig'],
    'users' => [Users::class, '_components/fieldtypes/elementfieldsettings.twig'],
]);

it('preserves nullable settings overrides on built-in relation fields', function() {
    $field = new class() extends Entries {
        public function getSettingsHtml(): ?string
        {
            return null;
        }
    };

    $payload = app(UiResolver::class)->resolve($field->settingsUi(), new UiContext());

    expect($payload->nodes)->toBe([]);
});

it('renders the legacy relation templates with overridable settings HTML', function(string $class) {
    TemplateMode::set(TemplateMode::Cp);
    Sites::partialMock()->shouldReceive('isMultiSite')->andReturn(false);
    Sites::shouldReceive('getEditableSiteIds')->andReturn(collect());
    $condition = Mockery::mock(ElementCondition::class)->makePartial();
    $condition->__construct();
    $condition->shouldReceive('getSelectableConditionRules')->andReturn([]);
    $field = Mockery::mock($class)->makePartial();
    $field->shouldReceive('getSelectionCondition')->andReturn($condition);
    $field->shouldReceive('getSourceOptions')->andReturn([
        ['label' => 'All sources', 'value' => '*', 'data' => ['structure-id' => null]],
    ]);
    $field->shouldReceive('getViewModeFieldHtml')->once()->andReturn('<input name="viewMode" value="plugin-view">');

    $html = new Crawler($field->getSettingsHtml());

    expect($html->filter('[name="viewMode"]')->attr('value'))->toBe('plugin-view')
        ->and($html->filter('input[name="selectionLabel"]'))->toHaveCount(1);
})->with([Entries::class, Assets::class, Users::class]);

it('captures legacy entry title inputs while retaining the core title type', function(bool $multiline) {
    $entry = Mockery::mock(Entry::class)->makePartial();
    $entry->title = 'A title';
    $entry->shouldReceive('getType')->andReturn(new EntryType([
        'hasTitleField' => true,
        'allowLineBreaksInTitles' => $multiline,
    ]));
    $entry->shouldReceive('getAttributeStatus')->andReturn(null);
    $field = new LegacyEntryTitleField(['uid' => 'title', 'name' => 'headline']);
    $context = new UiContext(namespace: ['nested']);
    $node = $field->uiNode(new FieldLayoutElementContext($entry, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $html = new Crawler($payload->nodes[0]->control->props['fragment']['html']);

    expect($field)->toBeInstanceOf(EntryTitleField::class)
        ->and($field::class)->toBe(LegacyEntryTitleField::class)
        ->and($html->filter($multiline ? 'textarea[name="nested[headline]"]' : 'input[name="nested[headline]"]'))->toHaveCount(1);
})->with([false, true]);

it('captures plugin title overrides once in every form mode', function(ControlMode $mode) {
    $field = new class(['uid' => 'title', 'name' => 'headline']) extends LegacyEntryTitleField {
        public int $calls = 0;

        public function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
        {
            $this->calls++;
            HtmlStack::css('.plugin-title { color: red; }');

            return '<input name="headline" data-static="' . ($static ? 'true' : 'false') . '">';
        }
    };
    $context = new UiContext(namespace: ['nested'], mode: $mode);
    $node = $field->uiNode(new FieldLayoutElementContext(null, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $control = $payload->nodes[0]->control;
    $input = new Crawler($control->props['fragment']['html'])->filter('input');

    expect($field->calls)->toBe(1)
        ->and($control->mode)->toBe($mode)
        ->and($control->props['fragment']['headHtml'])->toContain('.plugin-title')
        ->and($input->attr('name'))->toBe('nested[headline]')
        ->and($input->attr('data-static'))->toBe($mode === ControlMode::Editable ? 'false' : 'true');
})->with(ControlMode::cases());

it('serializes the adapter title class and omits titles disabled by the entry type', function() {
    $field = new LegacyEntryTitleField(['uid' => 'title', 'name' => 'headline', 'required' => false]);
    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn(FieldLayoutTab $tab) => $tab->add($field));
    $entry = Mockery::mock(Entry::class)->makePartial();
    $entry->shouldReceive('getType')->andReturn(new EntryType(['hasTitleField' => false]));

    expect($layout->getConfig()['tabs'][0]['elements'][0])->toMatchArray([
        'type' => LegacyEntryTitleField::class,
        'uid' => 'title',
        'name' => 'headline',
        'required' => false,
    ])->and($field->uiNode(new FieldLayoutElementContext($entry, new UiContext())))->toBeNull();
});

it('renders the complete legacy Table settings UI with its namespace and effective mode', function(ControlMode $mode) {
    $field = new class(['columns' => ['col1' => ['heading' => 'Status', 'handle' => 'status', 'type' => 'singleline']], 'defaults' => [['col1' => 'Draft']]]) extends Table {
        public function getSettingsHtml(): ?string
        {
            return parent::getSettingsHtml() . '<input name="pluginOption" value="Custom">';
        }
    };
    $html = InputNamespace::namespaceInputs(fn() => $mode === ControlMode::Editable
        ? $field->getSettingsHtml()
        : $field->getReadOnlySettingsHtml(), 'plugin[settings]');
    $crawler = new Crawler($html);
    $host = $crawler->filter('craft-field-settings-ui');
    $pluginInput = $crawler->filter('input[name="plugin[settings][pluginOption]"]');
    $payload = json_decode($host->attr('data-payload'), true);
    $columns = $payload['nodes'][0]['control'];
    $defaults = $payload['nodes'][1]['children'][0]['control'];

    expect($pluginInput)->toHaveCount(1)
        ->and($pluginInput->attr('value'))->toBe('Custom')
        ->and($pluginInput->matches('[disabled]'))->toBe($mode === ControlMode::ReadOnly)
        ->and($host)->toHaveCount(1)
        ->and($host->attr('name'))->toBe('plugin[settings][__fieldSettings]')
        ->and($payload['scope'])->toBe(['settings'])
        ->and($payload['refreshable'])->toBe($mode === ControlMode::Editable)
        ->and($payload['values']['settings']['defaults'])->toBe([['col1' => 'Draft']])
        ->and($columns['component'])->toBe('craft:table-columns')
        ->and($columns['deltaGroup'])->toBe(['settings', 'columns'])
        ->and($columns['mode'])->toBe($mode->value)
        ->and($defaults['mode'])->toBe($mode->value)
        ->and($defaults['uis'][0]['nodes'][0]['control']['mode'])->toBe($mode->value);

    $editablePayload = json_decode(new Crawler($field->getSettingsHtml())->filter('craft-field-settings-ui')->attr('data-payload'), true);

    expect($editablePayload['nodes'][0]['control']['mode'])->toBe(ControlMode::Editable->value);
})->with([ControlMode::Editable, ControlMode::ReadOnly]);
