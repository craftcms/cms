<?php

declare(strict_types=1);

use craft\elements\actions\ChangeSortOrder;
use craft\elements\actions\MoveDown;
use craft\elements\actions\MoveUp;
use craft\elements\NestedElementManager as LegacyNestedElementManager;
use craft\fields\Addresses as LegacyAddresses;
use craft\fields\Matrix as LegacyMatrix;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Auth\SessionAuth;
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

it('renders the core control for plugin nested fields without their own input HTML', function(string $fieldType, string $viewMode, bool $inline = false, string $mode = 'form') {
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
    if ($mode === 'static') {
        $html = $field->getStaticHtml($value, $owner);
    } else {
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
    }
    $crawler = new Crawler($html);

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
            ->and($control['mode'])->toBe($mode === 'form' ? 'editable' : ($mode === 'disabled' ? 'disabled' : 'readOnly'))
            ->and($host->filter('input[data-ui-field-name]')->attr('name'))->toBe('fields[nested]')
            ->and($host->filter('input[name]:not([disabled])'))->toHaveCount(0);

        return;
    }

    $host = $crawler->filter('craft-nested-elements-control');
    $control = json_decode($host->attr('data-control'), true, flags: JSON_THROW_ON_ERROR);
    $settings = $control['props']['manager'];
    $editable = $mode === 'form';
    expect($settings)->toMatchArray([
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'fieldId' => $field->id,
        'attribute' => 'field:nested',
        'canCreate' => $editable,
    ])->and($control['path'])->toBe($mode === 'static' ? ['nested'] : ['fields', 'nested'])
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
})->with([
    'Matrix cards' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_CARDS],
    'Matrix cards grid' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_CARDS_GRID],
    'Matrix index' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_INDEX],
    'Matrix blocks' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_BLOCKS],
    'Addresses cards' => [PluginAddressesField::class, LegacyAddresses::VIEW_MODE_CARDS],
    'Addresses index' => [PluginAddressesField::class, LegacyAddresses::VIEW_MODE_INDEX],
    'inline Matrix blocks' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_BLOCKS, true],
    'inline Matrix cards' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_CARDS, true],
    'inline Addresses index' => [PluginAddressesField::class, LegacyAddresses::VIEW_MODE_INDEX, true],
    'static Matrix cards' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_CARDS, false, 'static'],
    'static Addresses index' => [PluginAddressesField::class, LegacyAddresses::VIEW_MODE_INDEX, false, 'static'],
    'disabled Matrix blocks' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_BLOCKS, false, 'disabled'],
    'read-only Matrix index' => [PluginMatrixField::class, LegacyMatrix::VIEW_MODE_INDEX, false, 'readOnly'],
    'disabled Addresses cards' => [PluginAddressesField::class, LegacyAddresses::VIEW_MODE_CARDS, false, 'disabled'],
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

class PluginMatrixField extends LegacyMatrix
{
}

class PluginAddressesField extends LegacyAddresses
{
}
