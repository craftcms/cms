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
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::findOne());
});

it('renders legacy nested field inputs with their owner scope and content', function(string $fieldType, string $viewMode, bool $inline = false) {
    $isMatrix = is_a($fieldType, LegacyMatrix::class, true);
    $entryType = $isMatrix
        ? EntryTypeModel::factory()->withField(Field::factory()->create(['handle' => 'body', 'type' => PlainText::class]))
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
    $crawler = new Crawler($inline
        ? $field->getInlineInputHtml($value, $owner)
        : $field->getInputHtml($value, $owner));

    if ($inline) {
        expect($crawler->filter('[data-plugin-field-input]')->attr('data-plugin-field-input'))->toBe('inline');
    }

    if ($viewMode === LegacyMatrix::VIEW_MODE_BLOCKS) {
        $host = $crawler->filter('craft-entry-field-layout-form[data-payload]');
        expect($host)->toHaveCount(1)
            ->and(json_decode($host->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR)['values']['nested']['entries']["uid:{$nested->uid}"]['fields']['body'])->toBe('Block body')
            ->and($crawler->filter('input[name="nested[sortOrder][]"]')->attr('value'))->toBe($nested->uid);

        return;
    }

    $settings = json_decode($crawler->filter('craft-nested-element-manager')->attr('settings'), true, flags: JSON_THROW_ON_ERROR);
    expect($settings)->toMatchArray([
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'fieldId' => $field->id,
        'attribute' => 'field:nested',
        'canCreate' => true,
    ])->and(SessionAuth::checkAuthorization("manageNestedElements::{$owner->id}::field:nested"))->toBeTrue();

    if ($viewMode === LegacyMatrix::VIEW_MODE_INDEX) {
        expect($settings['indexSettings']['criteria'])->toMatchArray(['ownerId' => $owner->id, 'fieldId' => $field->id])
            ->and(array_column($settings['indexSettings']['actions'], 'type'))->toBe([
                ChangeSortOrder::class,
                MoveUp::class,
                MoveDown::class,
            ])
            ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::field:nested"))->toBeTrue();
    } else {
        expect($crawler->filter("craft-card[data-id=\"{$nested->id}\"]"))->toHaveCount(1)
            ->and($crawler->text())->toContain($isMatrix ? 'Nested content' : '123 Legacy Street')
            ->and($crawler->filter('[data-delete-action]'))->toHaveCount(1)
            ->and($crawler->filter('[data-duplicate-action]'))->toHaveCount(1);
    }
})->with([
    'Matrix cards' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_CARDS],
    'Matrix cards grid' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_CARDS_GRID],
    'Matrix index' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_INDEX],
    'Matrix blocks' => [LegacyMatrix::class, LegacyMatrix::VIEW_MODE_BLOCKS],
    'Addresses cards' => [LegacyAddresses::class, LegacyAddresses::VIEW_MODE_CARDS],
    'Addresses index' => [LegacyAddresses::class, LegacyAddresses::VIEW_MODE_INDEX],
    'plugin Matrix inline blocks' => [PluginMatrixHtmlField::class, LegacyMatrix::VIEW_MODE_BLOCKS, true],
    'plugin Addresses inline index' => [PluginAddressesHtmlField::class, LegacyAddresses::VIEW_MODE_INDEX, true],
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
