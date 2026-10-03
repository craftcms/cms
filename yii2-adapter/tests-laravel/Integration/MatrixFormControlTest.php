<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fields\Matrix as LegacyMatrix;
use craft\records\GlobalSet as GlobalSetRecord;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\UpdateFieldLayoutController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\FieldLayout\FieldLayoutForm;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
});

it('renders legacy Matrix content read-only without granting editing permissions', function(string $viewMode) {
    $entryType = EntryTypeModel::factory()->create([
        'name' => 'Card',
        'handle' => 'card',
    ]);
    $fieldModel = Field::factory()->create([
        'name' => 'Cards',
        'handle' => 'cards',
        'type' => LegacyMatrix::class,
        'settings' => [
            'entryTypes' => [$entryType->id],
            'viewMode' => $viewMode,
        ],
    ]);
    $ownerModel = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($fieldModel))
        ->create();
    /** @var Entry $owner */
    $owner = Entry::find()->id($ownerModel->id)->one();
    $owner->setFieldValue('cards', [
        'new1' => [
            'type' => $entryType->handle,
            'title' => 'Nested card',
        ],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();
    /** @var Entry $owner */
    $owner = Entry::find()->id($ownerModel->id)->one();
    /** @var LegacyMatrix $field */
    $field = Fields::getFieldByHandle('cards');

    $html = $field->getStaticHtml($owner->getFieldValue('cards'), $owner);
    $crawler = new Crawler($html);
    $control = json_decode(
        $crawler->filter('craft-nested-elements-control')->attr('data-control'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $elements = $viewMode === LegacyMatrix::VIEW_MODE_INDEX ? $control['props']['index']['initial']['data'] : $control['props']['cards'];

    expect($elements)->toHaveCount(1)
        ->and($html)->toContain('Nested card')
        ->and($control['mode'])->toBe('readOnly')
        ->and($control['props']['manager'])->toMatchArray([
            'sortable' => false,
            'canCreate' => false,
            'canPaste' => false,
        ])->and($crawler->filter('input[name]'))->toHaveCount(0)
        ->and(SessionAuth::checkAuthorization("manageNestedElements::{$owner->id}::field:cards"))->toBeFalse()
        ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::field:cards"))->toBeFalse();
})->with([
    'cards' => LegacyMatrix::VIEW_MODE_CARDS,
    'index' => LegacyMatrix::VIEW_MODE_INDEX,
]);

it('refreshes native Matrix controls after deletion in Global Set content forms', function(bool $insideBlock, string $viewMode) {
    $this->artisan('craft:add-global-sets-support', ['--no-interaction' => true])->assertSuccessful();
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create([
        'name' => 'Card',
        'handle' => 'card',
        'hasTitleField' => true,
    ]);
    $field = Field::factory()->create([
        'name' => 'Cards',
        'handle' => 'cards',
        'type' => LegacyMatrix::class,
        'settings' => ['entryTypes' => [$entryType->id], 'viewMode' => $viewMode],
    ]);
    $rootField = $field;
    if ($insideBlock) {
        $blockType = EntryTypeModel::factory()->withField($field)->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => true,
        ]);
        $rootField = Field::factory()->create([
            'name' => 'Blocks',
            'handle' => 'blocks',
            'type' => LegacyMatrix::class,
            'settings' => ['entryTypes' => [$blockType->id], 'viewMode' => LegacyMatrix::VIEW_MODE_BLOCKS],
        ]);
    }
    $layout = FieldLayout::factory()->forField($rootField)->create(['type' => GlobalSet::class]);
    Fields::refreshFields();

    $global = new GlobalSet(['name' => 'Site content', 'handle' => 'siteContent', 'fieldLayoutId' => $layout->id]);
    expect(Elements::saveElement($global))->toBeTrue();
    $record = new GlobalSetRecord();
    $record->id = $global->id;
    $record->uid = $global->uid;
    $record->name = $global->name;
    $record->handle = $global->handle;
    $record->fieldLayoutId = $global->fieldLayoutId;
    expect($record->save())->toBeTrue();

    $global = GlobalSet::find()->id($global->id)->one();
    $cards = ['new1' => ['type' => 'card', 'title' => 'Global card']];
    $global->setFieldValue($rootField->handle, $insideBlock
        ? ['new1' => ['type' => 'block', 'title' => 'Global block', 'fields' => ['cards' => $cards]]]
        : $cards);
    expect(Elements::saveElement($global))->toBeTrue();
    $global = GlobalSet::find()->id($global->id)->one();

    $owner = $insideBlock ? $global->getFieldValue('blocks')->one() : $global;
    $card = $owner->getFieldValue('cards')->one();
    $expectedPath = $insideBlock
        ? ['fields', 'blocks', 'entries', $owner->uid, 'fields', 'cards']
        : ['fields', 'cards'];
    $html = FieldLayoutForm::fromLayout($global->getFieldLayout(), $global)->render();
    $host = new Crawler($html)->filter('craft-nested-elements-control');
    $control = json_decode($host->attr('data-control'), true, flags: JSON_THROW_ON_ERROR);
    $items = $viewMode === LegacyMatrix::VIEW_MODE_INDEX ? $control['props']['index']['initial']['data'] : $control['props']['cards'];
    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($card->id)
        ->and($control['props']['manager']['ownerHasDrafts'])->toBeFalse();

    expect(Elements::deleteElement($card))->toBeTrue();
    $scope = json_decode($host->attr('data-scope'), true, flags: JSON_THROW_ON_ERROR);
    $body = [];
    foreach ([
        'elementType' => $owner::class,
        'elementId' => $owner->id,
        'siteId' => $owner->siteId,
        'fields' => ['cards' => '*'],
    ] as $name => $value) {
        Arr::set($body, implode('.', [...$scope, $name]), $value);
    }

    $this->app->forgetInstance(ElementRequest::class);
    $response = $this->postJson(action(UpdateFieldLayoutController::class), $body, [
        'X-Craft-Namespace' => implode('.', $scope),
        'X-Craft-Form-Root-Scope' => json_encode($scope, JSON_THROW_ON_ERROR),
    ]);
    $response->assertSuccessful();
    $nodes = new RecursiveIteratorIterator(new RecursiveArrayIterator($response->json('form.nodes')), RecursiveIteratorIterator::SELF_FIRST);
    $refreshed = collect(iterator_to_array($nodes, false))->first(
        fn(mixed $node): bool => is_array($node) && ($node['component'] ?? null) === 'craft:nested-elements' && ($node['path'] ?? null) === $expectedPath,
    );
    expect($refreshed)->not->toBeNull();
    $items = $viewMode === LegacyMatrix::VIEW_MODE_INDEX ? $refreshed['props']['index']['initial']['data'] : $refreshed['props']['cards'];
    expect($items)->toBeEmpty();
})->with([
    'direct cards' => [false, LegacyMatrix::VIEW_MODE_CARDS],
    'direct index' => [false, LegacyMatrix::VIEW_MODE_INDEX],
    'cards inside blocks' => [true, LegacyMatrix::VIEW_MODE_CARDS],
    'index inside blocks' => [true, LegacyMatrix::VIEW_MODE_INDEX],
]);
