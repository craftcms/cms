<?php

declare(strict_types=1);

use craft\elements\NestedElementManager as LegacyNestedElementManager;
use craft\fields\Addresses as LegacyAddresses;
use craft\fields\Matrix as LegacyMatrix;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Actions\ChangeSortOrder;
use CraftCms\Cms\Element\Actions\MoveDown;
use CraftCms\Cms\Element\Actions\MoveUp;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::findOne());
});

it('renders legacy nested field inputs with their owner scope and content', function(string $fieldType, string $viewMode, bool $inline = false, string $mode = 'editable', bool $invalid = false) {
    $isMatrix = is_a($fieldType, LegacyMatrix::class, true);
    $entryType = $isMatrix
        ? EntryTypeModel::factory()->withFieldLayout(FieldLayout::factory()->withContentTab([
            new EntryTitleField(),
            new CustomField(config: ['fieldUid' => Field::factory()->create(['handle' => 'body', 'type' => PlainText::class])->uid]),
        ]))
            ->create(['name' => 'Card', 'handle' => 'card', 'hasTitleField' => true])
        : null;
    $fixture = EntryModel::factory()
        ->withField('nested', $fieldType, [
            'viewMode' => $viewMode,
            ...($isMatrix ? ['entryTypes' => [$entryType->id]] : []),
        ])
        ->createElementWithFields();
    $owner = $fixture->element;
    $owner->setFieldValue('nested', ['new1' => $isMatrix
        ? ['type' => 'card', 'title' => 'Nested content', 'fields' => ['body' => 'Block body']]
        : ['title' => 'Legacy address', 'countryCode' => 'US', 'addressLine1' => '123 Legacy Street', 'locality' => 'Portland', 'administrativeArea' => 'OR', 'postalCode' => '97201'],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();
    $owner = Entry::find()->id($owner->id)->one();
    $field = $owner->getFieldLayout()->getFieldByHandle('nested');
    $value = $owner->getFieldValue('nested');
    $nested = $value->one();
    if ($invalid) {
        $nested->addError('title', 'Please provide a valid title.');
        $nested->addError('body', 'Please provide valid body content.');
        $value->setResultOverride([$nested]);
    }
    if (in_array($mode, ['readOnly', 'disabled', 'form'])) {
        $context = new UiContext(mode: $mode === 'form' ? ControlMode::Editable : $mode);
        $control = $field->uiControl(new FieldContext(
            path: ['fields', 'nested'],
            value: $value,
            element: $owner,
            ui: $context,
            inline: $inline,
        ));
        $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make()->control($control)]), $context);
        $html = app(UiHtmlRenderer::class)->render($payload);
    } else {
        $html = InputNamespace::namespaceInputs(fn(): string => match (true) {
            $mode === 'static' => $field->getStaticHtml($value, $owner),
            $inline => $field->getInlineInputHtml($value, $owner),
            default => $field->getInputHtml($value, $owner),
        }, 'fields');
    }
    $crawler = new Crawler($html);

    if (in_array($fieldType, [PluginMatrixHtmlField::class, PluginAddressesHtmlField::class])) {
        expect($crawler->filter('[data-plugin-field-input]')->attr('data-plugin-field-input'))->toBe($inline ? 'inline' : 'input');
    }

    if ($viewMode === LegacyMatrix::VIEW_MODE_BLOCKS) {
        $host = $crawler->filter('craft-entry-field-layout-ui[data-payload]');
        $ui = json_decode($host->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);
        $control = $ui['nodes'][0]['control'];
        expect($host)->toHaveCount(1)
            ->and($ui['values']['fields']['nested']['entries'][$nested->uid]['fields']['body'])->toBe('Block body')
            ->and($ui['values']['fields']['nested']['sortOrder'])->toBe([$nested->uid])
            ->and($control['props']['create'])->toMatchArray([
                'fieldId' => $field->id,
                'ownerId' => $owner->id,
                'siteId' => $owner->siteId,
            ])
            ->and($control['mode'])->toBe(in_array($mode, ['editable', 'form']) ? 'editable' : ($mode === 'disabled' ? 'disabled' : 'readOnly'))
            ->and($host->filter('input[data-ui-field-name]')->attr('name'))->toBe('fields[nested]')
            ->and($host->filter('input[name]:not([disabled])'))->toHaveCount(0);

        if ($invalid) {
            expect($ui['errors'])->toContain(
                ['path' => ['fields', 'nested', 'entries', $nested->uid, 'title'], 'messages' => ['Please provide a valid title.']],
                ['path' => ['fields', 'nested', 'entries', $nested->uid, 'fields', 'body'], 'messages' => ['Please provide valid body content.']],
            );
        }

        return;
    }

    $host = $crawler->filter('craft-nested-elements-control');
    $control = json_decode($host->attr('data-control'), true, flags: JSON_THROW_ON_ERROR);
    $settings = $control['props']['manager'];
    $editable = in_array($mode, ['editable', 'form']);
    expect($settings)->toMatchArray([
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'fieldId' => $field->id,
        'attribute' => 'field:nested',
        'canCreate' => $editable,
    ])->and($control['path'])->toBe(['fields', 'nested'])
        ->and($control['mode'])->toBe($editable ? 'editable' : ($mode === 'disabled' ? 'disabled' : 'readOnly'))
        ->and(json_decode($host->attr('data-scope'), true, flags: JSON_THROW_ON_ERROR))->toBe([])
        ->and(SessionAuth::checkAuthorization("manageNestedElements::{$owner->id}::field:nested"))->toBe($editable);

    if ($editable) {
        $marker = $crawler->filter('input[data-nested-modified]');
        expect($marker->attr('name'))->toBe('fields[nested]')
            ->and($marker->attr('value'))->toBe('*')
            ->and($marker->attr('disabled'))->not->toBeNull();
    } else {
        expect($settings)->toMatchArray(['sortable' => false, 'canPaste' => false])
            ->and($crawler->filter('input[name]:not([disabled])'))->toHaveCount(0);
    }

    if ($viewMode === LegacyMatrix::VIEW_MODE_INDEX) {
        $index = $control['props']['index'];
        expect($index['initial']['data'][0]['id'])->toBe($nested->id)
            ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::field:nested"))->toBe($editable);
    } else {
        expect($control['props']['cards'][0]['id'])->toBe($nested->id)
            ->and($html)->toContain($isMatrix ? 'Nested content' : '123 Legacy Street');
    }

    if (!$editable) {
        $html = $field->getInputHtml($value, $owner);
        $control = json_decode(new Crawler($html)->filter('craft-nested-elements-control')->attr('data-control'), true, flags: JSON_THROW_ON_ERROR);
        expect($control['mode'])->toBe('editable')
            ->and($control['props']['manager']['canCreate'])->toBeTrue();
    }
})->with([
    'Matrix cards' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_CARDS],
    'Matrix cards grid' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_CARDS_GRID],
    'Matrix index' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_INDEX],
    'Matrix blocks' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_BLOCKS],
    'invalid Matrix blocks' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'editable', true],
    'invalid plugin Matrix form blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'form', true],
    'Addresses cards' => [LegacyAddresses::class, LegacyAddresses::VIEW_MODE_CARDS],
    'Addresses index' => [LegacyAddresses::class, LegacyAddresses::VIEW_MODE_INDEX],
    'plugin Matrix inline blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, true],
    'plugin Matrix inline cards' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_CARDS, true],
    'plugin Matrix inline index' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_INDEX, true],
    'plugin Addresses inline cards' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_CARDS, true],
    'plugin Addresses inline index' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_INDEX, true],
    'plugin Matrix form blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'form'],
    'plugin Matrix static blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'static'],
    'plugin Matrix disabled blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'disabled'],
    'plugin Matrix form cards' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_CARDS, false, 'form'],
    'plugin Addresses form index' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_INDEX, false, 'form'],
    'plugin Matrix static cards' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_CARDS, false, 'static'],
    'plugin Addresses static index' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_INDEX, false, 'static'],
    'plugin Matrix read-only index' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_INDEX, false, 'readOnly'],
    'plugin Addresses disabled cards' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_CARDS, false, 'disabled'],
]);

it('renders an attribute-backed legacy index on a plugin manager subclass with owner-scoped reorder actions', function() {
    $owner = User::findOne();
    $manager = new class(Address::class, fn(ElementInterface $owner) => Address::find()->ownerId($owner->id), ['attribute' => 'addresses']) extends LegacyNestedElementManager {
    };
    $html = $manager->getIndexHtml($owner, ['sortable' => true]);
    $settings = json_decode(new Crawler($html)->filter('craft-nested-element-manager')->attr('settings'), true, flags: JSON_THROW_ON_ERROR);

    expect($settings['ownerIdParam'])->toBe('ownerId')
        ->and($settings['attribute'])->toBe('addresses')
        ->and($settings['indexSettings']['criteria']['ownerId'])->toBe($owner->id)
        ->and(array_column($settings['indexSettings']['actions'], 'type'))->toBe([
            ChangeSortOrder::class,
            MoveUp::class,
            MoveDown::class,
        ])
        ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::addresses"))->toBeTrue();
});

it('adapts creation choices to the legacy HTML manager protocol', function(bool $multiple) {
    $owner = User::findOne();
    $icon = __DIR__ . '/../../tests/_data/assets/files/craft-logo.svg';
    $choices = [['label' => 'First', 'attributes' => ['typeId' => 17], 'icon' => $icon]];
    if ($multiple) {
        $choices[] = ['label' => 'Second', 'attributes' => ['typeId' => 23], 'icon' => $icon];
    }

    $html = $owner->getAddressManager()->getCardsHtml($owner, ['canCreate' => true, 'createAttributes' => $choices]);
    $settings = json_decode(new Crawler($html)->filter('craft-nested-element-manager')->attr('settings'), true, flags: JSON_THROW_ON_ERROR);

    if (!$multiple) {
        expect($settings['createAttributes'])->toBe(['typeId' => 17]);

        return;
    }

    expect(array_column(array_column($settings['createAttributes'], 'attributes'), 'typeId'))->toBe([17, 23])
        ->and(array_column($settings['createAttributes'], 'label'))->toBe(['First', 'Second']);
    foreach ($settings['createAttributes'] as $choice) {
        expect($choice['icon'])->toContain('<svg');
    }
})->with(['single choice' => false, 'multiple choices' => true]);

class PluginMatrixHtmlField extends LegacyMatrix
{
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::tag('div', parent::inputHtml($value, $element, $inline), [
            'data-plugin-field-input' => $inline ? 'inline' : 'input',
        ]);
    }
}

class PluginAddressesHtmlField extends LegacyAddresses
{
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::tag('div', parent::inputHtml($value, $element, $inline), [
            'data-plugin-field-input' => $inline ? 'inline' : 'input',
        ]);
    }
}
