<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fields\Entries as LegacyEntries;
use craft\fields\Matrix as LegacyMatrix;
use craft\records\GlobalSet as GlobalSetRecord;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Controllers\Elements\UpdateFieldLayoutController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Field\BaseRelationField as LegacyBaseRelationField;
use CraftCms\Yii2Adapter\Field\Field as LegacyField;
use CraftCms\Yii2Adapter\FieldLayout\FieldLayoutForm;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use CraftCms\Yii2Adapter\Ui\LegacyHtml;
use Illuminate\Support\Facades\Event;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
});

it('preserves owner-specific Matrix creation choices while rendering existing excluded types', function() {
    $types = EntryTypeModel::factory()->withFieldLayout()->count(3)->sequence(
        ['name' => 'Existing', 'handle' => 'existing', 'hasTitleField' => true],
        ['name' => 'First choice', 'handle' => 'firstChoice'],
        ['name' => 'Second choice', 'handle' => 'secondChoice'],
    )->create();
    $fieldModel = Field::factory()->create([
        'name' => 'Blocks', 'handle' => 'blocks', 'type' => LegacyMatrix::class,
        'settings' => ['entryTypes' => $types->modelKeys(), 'viewMode' => LegacyMatrix::VIEW_MODE_BLOCKS],
    ]);
    $model = EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($fieldModel))->create();
    $owner = Entry::find()->id($model->id)->one();
    $owner->setFieldValue('blocks', ['new1' => ['type' => 'existing', 'title' => 'Existing content']]);
    expect(Elements::saveElement($owner))->toBeTrue();
    $owner = Entry::find()->id($model->id)->one();
    $field = Fields::getFieldByHandle('blocks');
    Event::listen(EntryTypesForFieldResolving::class, function($event) use ($owner) {
        if ($event->element?->id === $owner->id) {
            $event->entryTypes = [$event->entryTypes[2], $event->entryTypes[1]];
        }
    });
    $context = new UiContext();
    $control = $field->uiControl(new FieldContext(['fields', 'blocks'], $owner->getFieldValue('blocks'), $owner, $context));
    $html = app(UiHtmlRenderer::class)->render(app(UiResolver::class)->resolve(Ui::make([UiField::make()->control($control)]), $context));
    $ui = json_decode(new Crawler($html)->filter('craft-entry-field-layout-ui')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);
    $control = $ui['nodes'][0]['control'];

    expect(array_keys($control['props']['create']['entryTypeIds']))->toBe(['secondChoice', 'firstChoice'])
        ->and($control['props']['createEntryTypes'] ?? null)->toBe(['secondChoice', 'firstChoice'])
        ->and($ui['values']['fields']['blocks']['entries'])->toHaveCount(1)
        ->and($html)->toContain('Existing content');
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

it('shows nested entry validation errors in legacy field layout forms', function(string $fieldType) {
    $entryType = EntryTypeModel::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new EntryTitleField(['required' => true])]))
        ->create(['name' => 'Card', 'handle' => 'card', 'hasTitleField' => true]);
    $owner = EntryModel::factory()
        ->withField('blocks', $fieldType, ['viewMode' => LegacyMatrix::VIEW_MODE_BLOCKS, 'entryTypes' => [$entryType->id]])
        ->createElementWithFields()
        ->element;
    $uid = Str::uuid()->toString();
    $owner->setFieldValue('blocks', ['entries' => ["uid:$uid" => ['type' => 'card', 'title' => '']], 'sortOrder' => [$uid]]);
    $owner->setScenario(ElementRules::SCENARIO_LIVE);

    expect(Elements::saveElement($owner))->toBeFalse();

    // On the block's own Title field, rather than the form's error list.
    $html = FieldLayoutForm::fromLayout($owner->getFieldLayout(), $owner)->render();
    $ui = Json::decode(new Crawler($html)->filter('craft-entry-field-layout-ui')->attr('data-payload'));

    expect($ui['errors'])->toBe([[
        'path' => ['fields', 'blocks', 'entries', $uid, 'title'],
        'messages' => ['The title field is required.'],
    ]])
        ->and($ui['globalErrors'])->toBe([]);
})->with([
    'Matrix' => LegacyMatrix::class,
    'plugin Matrix' => PluginMatrixUiField::class,
]);

it('refreshes native Matrix controls after deletion in Global Set content forms', function(bool $insideBlock, string $viewMode, bool $plugin = false) {
    $this->artisan('craft:add-global-sets-support', ['--no-interaction' => true])->assertSuccessful();
    $entryType = EntryTypeModel::factory()->withFieldLayout()->create([
        'name' => 'Card',
        'handle' => 'card',
        'hasTitleField' => true,
    ]);
    $field = Field::factory()->create([
        'name' => 'Cards',
        'handle' => 'cards',
        'type' => $plugin ? PluginMatrixUiField::class : LegacyMatrix::class,
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
    if ($insideBlock) {
        $blocksUi = json_decode(new Crawler($html)->filter('craft-entry-field-layout-ui')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);
        $nodes = new RecursiveIteratorIterator(new RecursiveArrayIterator($blocksUi['nodes']), RecursiveIteratorIterator::SELF_FIRST);
        $control = collect(iterator_to_array($nodes, false))->first(
            fn(mixed $node): bool => is_array($node) && ($node['path'] ?? null) === $expectedPath,
        );
        $scope = ['fields', 'blocks', 'entries', $owner->uid];
    } else {
        $host = new Crawler($html)->filter('craft-nested-elements-control');
        $control = json_decode($host->attr('data-control'), true, flags: JSON_THROW_ON_ERROR);
        $scope = json_decode($host->attr('data-scope'), true, flags: JSON_THROW_ON_ERROR);
    }
    $items = $viewMode === LegacyMatrix::VIEW_MODE_INDEX ? $control['props']['index']['initial']['data'] : $control['props']['cards'];
    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($card->id)
        ->and($control['props']['manager']['ownerHasDrafts'])->toBeFalse();

    expect(Elements::deleteElement($card))->toBeTrue();
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
        'X-Craft-Ui-Root-Scope' => json_encode($scope, JSON_THROW_ON_ERROR),
    ]);
    $response->assertSuccessful();
    $nodes = new RecursiveIteratorIterator(new RecursiveArrayIterator($response->json('ui.nodes')), RecursiveIteratorIterator::SELF_FIRST);
    $refreshed = collect(iterator_to_array($nodes, false))->first(
        fn(mixed $node): bool => is_array($node) && ($node['path'] ?? null) === $expectedPath,
    );
    expect($refreshed)->not->toBeNull();
    $items = $viewMode === LegacyMatrix::VIEW_MODE_INDEX ? $refreshed['props']['index']['initial']['data'] : $refreshed['props']['cards'];
    expect($items)->toBeEmpty();
})->with([
    'direct cards' => [false, LegacyMatrix::VIEW_MODE_CARDS],
    'direct index' => [false, LegacyMatrix::VIEW_MODE_INDEX],
    'cards inside blocks' => [true, LegacyMatrix::VIEW_MODE_CARDS],
    'index inside blocks' => [true, LegacyMatrix::VIEW_MODE_INDEX],
    'plugin cards inside blocks' => [true, LegacyMatrix::VIEW_MODE_CARDS, true],
    'plugin index inside blocks' => [true, LegacyMatrix::VIEW_MODE_INDEX, true],
]);

it('names legacy field inputs inside nested blocks by the block namespace and layout handle', function(string $fieldType) {
    $legacyField = Field::factory()->create(['name' => 'Target', 'handle' => 'target3', 'type' => NestedLegacyTargetField::class]);
    $layoutElement = ['type' => CustomField::class, 'uid' => Str::uuid()->toString(), 'fieldUid' => $legacyField->uid, 'handle' => 'target'];

    if ($fieldType === Matrix::class) {
        $entryType = EntryTypeModel::factory()
            ->withFieldLayout(FieldLayout::factory()->withContentTab([new CustomField(config: $layoutElement)]))
            ->create(['name' => 'Link', 'handle' => 'link']);
        $settings = ['entryTypes' => [$entryType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS];
    } else {
        $settings = ['fieldLayouts' => [Str::uuid()->toString() => ['tabs' => [['name' => 'Content', 'elements' => [$layoutElement]]]]]];
    }

    $field = Fields::createField(['name' => 'Links', 'handle' => 'links', 'type' => $fieldType, 'settings' => $settings]);
    expect(Fields::saveField($field))->toBeTrue();
    $model = EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($field))->create();
    $owner = Entry::find()->id($model->id)->one();

    if ($fieldType === Matrix::class) {
        $owner->setFieldValue('links', ['new1' => ['type' => 'link', 'title' => 'Link']]);
        expect(Elements::saveElement($owner))->toBeTrue();
        $owner = Entry::find()->id($model->id)->one();
        $scope = ['fields', 'links', 'entries', $owner->getFieldValue('links')->one()->uid];
    } else {
        $scope = ['fields', 'links'];
    }

    $payload = app(FieldLayoutCompiler::class)->compile($owner->getFieldLayout(), $owner);
    $nodes = new RecursiveIteratorIterator(new RecursiveArrayIterator(Json::decode(Json::encode($payload->nodes))), RecursiveIteratorIterator::SELF_FIRST);
    $control = collect(iterator_to_array($nodes, false))->first(
        fn(mixed $node): bool => is_array($node) && ($node['path'] ?? null) === [...$scope, 'fields', 'target'],
    );
    $name = LegacyHtml::namespace([...$scope, 'fields', 'target']);

    expect($control)->not->toBeNull()
        ->and(new Crawler($control['props']['fragment']['html'])->filter('input')->each(fn(Crawler $input) => $input->attr('name')))
        ->toBe([$name, "{$name}[]"]);
})->with([
    'Matrix' => Matrix::class,
    'Content Block' => ContentBlock::class,
]);

it('saves untouched legacy relation fields in nested entries from the values their HTML captured', function(string $fieldType) {
    $targetIds = [EntryModel::factory()->createElement()->id, EntryModel::factory()->createElement()->id];
    $legacyField = Field::factory()->create(['name' => 'Target', 'handle' => 'target3', 'type' => $fieldType]);
    $entryType = EntryTypeModel::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new CustomField(config: [
            'uid' => Str::uuid()->toString(),
            'fieldUid' => $legacyField->uid,
            'handle' => 'target',
        ])]))
        ->create(['name' => 'Link', 'handle' => 'link', 'hasTitleField' => false]);
    $fieldModel = Field::factory()->create([
        'name' => 'Links', 'handle' => 'links', 'type' => Matrix::class,
        'settings' => ['entryTypes' => [$entryType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
    ]);
    $model = EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($fieldModel))->create();
    $owner = Entry::find()->id($model->id)->one();
    $owner->setFieldValue('links', ['new1' => ['type' => 'link', 'fields' => ['target' => $targetIds]]]);
    expect(Elements::saveElement($owner))->toBeTrue();
    $owner = Entry::find()->id($model->id)->one();

    $payload = app(FieldLayoutCompiler::class)->compile($owner->getFieldLayout(), $owner);

    $this->app->forgetInstance(ElementRequest::class);
    $this->postJson(action([ElementDraftsController::class, 'store']), [
        'elementType' => Entry::class,
        'elementId' => $owner->id,
        'siteId' => $owner->siteId,
        'provisional' => true,
        'fields' => ['links' => $payload->values['fields']['links']],
    ])->assertOk();

    $draft = Entry::find()->draftOf($owner)->provisionalDrafts()->status(null)->one();

    expect($draft->getFieldValue('links')->status(null)->one()->getFieldValue('target')->ids())->toBe($targetIds);
})->with([
    'adapter relation field' => NestedLegacyEntriesField::class,
    'plugin subclass of a built-in relation field' => PluginEntriesField::class,
]);

class PluginMatrixUiField extends LegacyMatrix
{
}

class PluginEntriesField extends LegacyEntries
{
}

class NestedLegacyEntriesField extends LegacyBaseRelationField
{
    #[Override]
    public static function displayName(): string
    {
        return 'Legacy entries';
    }

    #[Override]
    public static function elementType(): string
    {
        return Entry::class;
    }
}

class NestedLegacyTargetField extends LegacyField
{
    #[Override]
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        return sprintf('<input type="hidden" name="%1$s" value=""><input type="hidden" name="%1$s[]" value="1">', $this->handle);
    }
}
