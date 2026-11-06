<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\RelationalFieldsController;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Drafts as DraftsFacade;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Structures as StructuresFacade;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

function createRelationalFieldEntries(): array
{
    $root = EntryModel::factory()
        ->title('Original Root')
        ->create();

    $child = EntryModel::factory()
        ->title('Child Entry')
        ->create();

    $secondRoot = EntryModel::factory()
        ->title('Second Root')
        ->create();

    $rootElement = EntryElement::find()->id($root->id)->status(null)->one();
    $rootElement->level = 1;

    $childElement = EntryElement::find()->id($child->id)->status(null)->one();
    $childElement->level = 2;

    $secondRootElement = EntryElement::find()->id($secondRoot->id)->status(null)->one();
    $secondRootElement->level = 1;

    return [
        'siteId' => Site::first()->id,
        'root' => $rootElement,
        'child' => $childElement,
        'secondRoot' => $secondRootElement,
    ];
}

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action([RelationalFieldsController::class, 'structuredInputHtml']), [
        'elementType' => EntryElement::class,
    ])->assertUnauthorized();
});

it('validates the structured input payload', function () {
    postJson(action([RelationalFieldsController::class, 'structuredInputHtml']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['elementType']);
});

it('renders an empty structured input', function () {
    $response = postJson(action([RelationalFieldsController::class, 'structuredInputHtml']), [
        'elementType' => EntryElement::class,
        'containerId' => 'related-entries',
        'name' => 'relatedEntries',
        'selectionLabel' => 'Choose entries',
    ])->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml']);

    expect($response->json('html'))
        ->toContain('id="related-entries"')
        ->toContain('class="elements structure"')
        ->toContain('Choose entries')
        ->toContain('name="relatedEntries"');
});

it('fills structure gaps and loads provisional changes', function () {
    ['siteId' => $siteId, 'root' => $root, 'child' => $child] = createRelationalFieldEntries();

    StructuresFacade::partialMock()
        ->shouldReceive('fillGapsInElements')
        ->once()
        ->andReturnUsing(function (array &$elements) use ($root, $child) {
            $elements = [$root, $child];
        });

    DraftsFacade::partialMock()
        ->shouldReceive('loadProvisionalChanges')
        ->once()
        ->andReturnUsing(function (array $elements) {
            $elements[0]->title = 'Draft Root';
        });

    $response = postJson(action([RelationalFieldsController::class, 'structuredInputHtml']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$child->id],
        'siteId' => $siteId,
        'selectionLabel' => 'Choose entries',
    ])->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml']);

    $html = $response->json('html');

    expect($html)
        ->toContain('Draft Root')
        ->toContain('Child Entry')
        ->not->toContain('Original Root')
        ->not->toContain('Second Root');

    expect(strpos((string) $html, 'Draft Root'))->toBeLessThan(strpos((string) $html, 'Child Entry'));
});

it('applies the branch limit to structured elements', function () {
    ['siteId' => $siteId, 'root' => $root, 'child' => $child, 'secondRoot' => $secondRoot] = createRelationalFieldEntries();

    StructuresFacade::partialMock()
        ->shouldReceive('fillGapsInElements')
        ->once()
        ->andReturnUsing(function (array &$elements) use ($root, $child, $secondRoot) {
            $elements = [$root, $child, $secondRoot];
        });

    StructuresFacade::partialMock()
        ->shouldReceive('applyBranchLimitToElements')
        ->once()
        ->withArgs(fn (array $elements, int $branchLimit) => $branchLimit === 1
            && $elements[0]->id === $root->id
            && $elements[1]->id === $child->id
            && $elements[2]->id === $secondRoot->id)
        ->andReturnUsing(function (array &$elements) {
            $elements = array_slice($elements, 0, 2);
        });

    $response = postJson(action([RelationalFieldsController::class, 'structuredInputHtml']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$secondRoot->id, $child->id],
        'siteId' => $siteId,
        'branchLimit' => 1,
        'selectionLabel' => 'Choose entries',
    ])->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml']);

    expect($response->json('html'))
        ->toContain('Original Root')
        ->toContain('Child Entry')
        ->not->toContain($secondRoot->title);
});

it('returns a hierarchical selection in structure order with missing ancestors filled in', function () {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    Sections::refreshSections();
    $news = EntryModel::factory()->forSection($section)->createElement(['title' => 'News']);
    $clubNews = EntryModel::factory()->forSection($section)->createElement(['title' => 'Club news']);
    $events = EntryModel::factory()->forSection($section)->createElement(['title' => 'Events']);
    StructuresFacade::appendToRoot($structure->id, $news);
    StructuresFacade::append($structure->id, $clubNews, $news);
    StructuresFacade::appendToRoot($structure->id, $events);

    $response = postJson(action([RelationalFieldsController::class, 'structuredElements']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$events->id, $clubNews->id],
        'siteId' => Site::first()->id,
    ])->assertOk();

    expect(array_map(
        fn (array $element) => [$element['id'], $element['label'], $element['level']],
        $response->json('elements'),
    ))->toBe([
        [$news->id, 'News', 1],
        [$clubNews->id, 'Club news', 2],
        [$events->id, 'Events', 1],
    ]);
});

it('returns the parts a view mode draws its elements with', function () {
    $entry = EntryModel::factory()->createElement();

    $elements = fn (?string $viewMode) => postJson(action([RelationalFieldsController::class, 'structuredElements']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$entry->id],
        'siteId' => Site::first()->id,
        'viewMode' => $viewMode,
    ])->assertOk()->json('elements');

    expect($elements('cards')[0])->toHaveKey('cardHeaderHtml')
        ->and($elements('cards')[0]['cardHeaderHtml'])->toContain('card-titlebar-label')
        ->and($elements(null)[0])->not->toHaveKey('cardHeaderHtml');

    postJson(action([RelationalFieldsController::class, 'structuredElements']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$entry->id],
        'viewMode' => 'carousel',
    ])->assertUnprocessable();
});

it('only draws cards for elements the user can view', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Private entry']);
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement(['admin' => false]));

    $elements = postJson(action([RelationalFieldsController::class, 'structuredElements']), [
        'elementType' => EntryElement::class,
        'elementIds' => [$entry->id],
        'siteId' => Site::first()->id,
        'viewMode' => 'cards',
    ])->assertOk()->json('elements');

    expect($elements[0])->not->toHaveKey('cardHeaderHtml')
        ->and($elements[0])->not->toHaveKey('cardContentHtml');
});
