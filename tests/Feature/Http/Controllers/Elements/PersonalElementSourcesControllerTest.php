<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\PersonalElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\PersonalElementSourcesController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function CraftCms\Cms\currentUser;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

function personalSources(): array
{
    return app(PersonalElementSources::class)->all(currentUser(), Entry::class);
}

function entryCondition(array $rules = []): array
{
    return [...Entry::createCondition()->getConfig(), 'conditionRules' => $rules, 'forQuery' => true];
}

function createPersonalSource(array $data = []): array
{
    return postJson(action([PersonalElementSourcesController::class, 'store']), [
        'elementType' => Entry::class,
        'source' => '*',
        'label' => 'Mine',
        ...$data,
    ])->assertOk()->json();
}

it('saves an index’s view as a source of the user’s own, narrowing the source it came from', function () {
    $response = createPersonalSource([
        'label' => '  Drafty  ',
        'condition' => entryCondition([['class' => SlugConditionRule::class, 'uid' => Str::uuid()->toString(), 'operator' => '=', 'value' => 'a']]),
        'defaultSort' => ['attr' => 'title', 'dir' => 'asc'],
        'tableAttributes' => ['slug'],
    ]);

    $source = personalSources()[0];

    expect($response['sourceKey'])->toStartWith('user:')
        ->and($source)->toMatchArray([
            'key' => $response['sourceKey'],
            'label' => 'Drafty',
            'baseSource' => '*',
            'defaultSort' => ['title', 'asc'],
            'tableAttributes' => ['slug'],
        ])
        ->and($source['condition']['forQuery'])->toBeFalse()
        ->and(app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class))->toBeNull();
});

it('lists a user’s own sources on their index and nav only', function () {
    $key = createPersonalSource()['sourceKey'];
    $sources = app(ElementSources::class);

    expect($sources->getSources(Entry::class)->firstWhere('key', $key))->toMatchArray(['personal' => true, 'label' => 'Mine'])
        ->and($sources->getSources(Entry::class)->last()['key'])->toBe($key)
        ->and($sources->getSources(Entry::class, ElementSources::CONTEXT_MODAL)->firstWhere('key', $key))->toBeNull();

    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]));
    $sources->forgetSources(Entry::class);

    expect($sources->getSources(Entry::class)->firstWhere('key', $key))->toBeNull();
});

it('never shows a user more than the source it narrows would', function () {
    $visible = Section::factory()->create();
    $hidden = Section::factory()->create();
    $shown = EntryModel::factory()->forSection($visible)->createElement();
    EntryModel::factory()->forSection($hidden)->createElement();
    Sections::refreshSections();

    actingAs(UserModel::factory()->withPermissions(['accessCp', "viewEntries:$visible->uid", "viewPeerEntries:$visible->uid"])->createElement(['admin' => false]));

    // No filters at all, so only the base source stands between them.
    $key = createPersonalSource()['sourceKey'];

    $ids = app(ElementIndexes::class)->buildQueryState(
        Entry::class,
        app(ElementSources::class)->findSource(Entry::class, $key),
    )['query']->ids();

    expect($ids)->toBe([$shown->id]);
});

it('saves an index’s view and a new name onto a user’s own source', function () {
    $key = createPersonalSource()['sourceKey'];

    postJson(action([PersonalElementSourcesController::class, 'update']), [
        'elementType' => Entry::class,
        'source' => $key,
        'label' => 'Renamed',
        // Kept to yourself, so it can hold what project config can't.
        'condition' => entryCondition([['class' => IdConditionRule::class, 'uid' => Str::uuid()->toString(), 'operator' => '=', 'value' => '1']]),
        'defaultViewMode' => 'cards',
    ])->assertOk()->assertJsonPath('message', 'Saved changes to “Renamed”.');

    expect(personalSources()[0])->toMatchArray(['label' => 'Renamed', 'defaultViewMode' => 'cards'])
        ->and(personalSources()[0]['condition']['conditionRules'][0]['class'] ?? personalSources()[0]['condition']['conditionRules']['rules'][0]['class'])->toBe(IdConditionRule::class);
});

it('only touches a user’s own sources', function () {
    $key = createPersonalSource()['sourceKey'];

    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]));

    postJson(action([PersonalElementSourcesController::class, 'destroy']), [
        'elementType' => Entry::class,
        'source' => $key,
    ])->assertNotFound();
});

it('deletes a user’s own source', function () {
    $key = createPersonalSource()['sourceKey'];

    postJson(action([PersonalElementSourcesController::class, 'destroy']), [
        'elementType' => Entry::class,
        'source' => $key,
    ])->assertOk();

    expect(personalSources())->toBe([]);
});

it('makes a user’s own source visible to everyone', function () {
    $key = createPersonalSource(['label' => 'Team'])['sourceKey'];

    $sharedKey = postJson(action([PersonalElementSourcesController::class, 'share']), [
        'elementType' => Entry::class,
        'source' => $key,
    ])->assertOk()->json('sourceKey');

    $shared = collect(app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class))->firstWhere('key', $sharedKey);

    expect($sharedKey)->toStartWith('custom:')
        ->and($shared)->toMatchArray(['type' => ElementSources::TYPE_CUSTOM, 'label' => 'Team', 'baseSource' => '*'])
        ->and(personalSources())->toBe([]);
});

it('won’t share a source whose filters project config can’t hold', function () {
    $key = createPersonalSource([
        'condition' => entryCondition([['class' => IdConditionRule::class, 'uid' => Str::uuid()->toString(), 'operator' => '=', 'value' => '1']]),
    ])['sourceKey'];

    postJson(action([PersonalElementSourcesController::class, 'share']), [
        'elementType' => Entry::class,
        'source' => $key,
    ])->assertUnprocessable()->assertJsonValidationErrors('source');

    expect(personalSources())->toHaveCount(1);
});

it('lists a user’s own sources in the sources editor, kept to them', function () {
    $key = createPersonalSource()['sourceKey'];

    $source = collect(postJson(action([ElementSourcesController::class, 'show']), ['elementType' => Entry::class])
        ->assertOk()->json('sources'))->firstWhere('key', $key);

    expect($source['personal'])->toBeTrue()
        ->and($source['ui']['values']['sources'][$key]['visibility'])->toBe('me');
});

it('never writes a user’s own sources to project config', function () {
    $key = createPersonalSource()['sourceKey'];

    postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => Entry::class,
        'sourceOrder' => ['*', $key],
        'sourcePages' => ['*' => 'Entries', $key => 'Entries'],
        'pageSettings' => ['Entries' => ['label' => 'Entries']],
    ])->assertOk();

    app(ElementSources::class)->addSource(Entry::class, [...personalSources()[0], 'personal' => true]);

    expect(collect(app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class))->pluck('key'))
        ->not->toContain($key)
        ->and(personalSources())->toHaveCount(1);
});

it('shares a user’s own source with the groups they pick', function () {
    $key = createPersonalSource()['sourceKey'];

    $sharedKey = postJson(action([PersonalElementSourcesController::class, 'share']), [
        'elementType' => Entry::class,
        'source' => $key,
        'userGroups' => ['group-uid'],
    ])->assertOk()->json('sourceKey');

    expect(collect(app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class))->firstWhere('key', $sharedKey)['userGroups'])
        ->toBe(['group-uid']);
});

it('shows anyone the sources, with only their own editable', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]));
    $key = createPersonalSource()['sourceKey'];

    $response = postJson(action([ElementSourcesController::class, 'show']), ['elementType' => Entry::class])->assertOk();
    $sources = collect($response->json('sources'))->keyBy('key');

    expect($response->json('canEditShared'))->toBeFalse()
        ->and($sources['*']['readOnly'])->toBeTrue()
        ->and($sources['*']['ui']['nodes'][0]['control']['mode'] ?? $sources['*']['ui']['nodes'][0]['mode'] ?? 'readOnly')->toBe('readOnly')
        ->and($sources[$key]['readOnly'])->toBeFalse();

    // Still only admins can change project config.
    postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => Entry::class,
        'sourceOrder' => ['*'],
    ])->assertForbidden();
});

it('doesn’t offer anyone else a choice of who sees a new source', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]));
    $key = 'custom:'.Str::uuid()->toString();

    $ui = postJson(action([ElementSourcesController::class, 'ui']), [
        'elementType' => Entry::class,
        'sourceKey' => $key,
        'type' => ElementSources::TYPE_CUSTOM,
        'settings' => ['visibility' => 'everyone'],
    ])->assertOk()->json('ui');

    // No choosing who sees it; the sources editor keeps it to them.
    expect(json_encode($ui))->not->toContain('"visibility"');
});

it('lists a user’s own sources under a User sources heading, at the end until an admin moves it', function () {
    $first = createPersonalSource(['label' => 'First'])['sourceKey'];
    $second = createPersonalSource(['label' => 'Second'])['sourceKey'];

    expect(array_slice(app(ElementSources::class)->getSources(Entry::class)->pluck('key')->all(), -3))
        ->toBe([ElementSources::USER_SOURCES_HEADING_KEY, $first, $second]);

    // Saved like any other heading: moved to the top and renamed.
    postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => Entry::class,
        'sourceOrder' => [ElementSources::USER_SOURCES_HEADING_KEY, 'heading:rest', '*'],
        'sources' => [
            ElementSources::USER_SOURCES_HEADING_KEY => ['heading' => 'Mine'],
            'heading:rest' => ['heading' => 'Everything'],
        ],
    ])->assertOk();

    $sources = app(ElementSources::class)->getSources(Entry::class);

    expect($sources->pluck('key')->take(4)->all())->toBe([ElementSources::USER_SOURCES_HEADING_KEY, $first, $second, 'heading:rest'])
        ->and($sources->first()['heading'])->toBe('Mine')
        ->and(collect(app(ProjectConfig::class)->get(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class))->pluck('key'))
        ->toContain(ElementSources::USER_SOURCES_HEADING_KEY)
        ->not->toContain($first);
});
