<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Http\Controllers\StructuresController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

dataset('routes', [
    fn () => action([StructuresController::class, 'getElementLevelDelta']),
    fn () => action([StructuresController::class, 'moveElement']),
]);

it('requires authentication', function (string $route) {
    Auth::logout();

    postJson($route)->assertUnauthorized();
})->with('routes');

it('requires valid data', function (string $route) {
    postJson($route, [])->assertJsonValidationErrors([
        'structureId',
        'elementId',
        'siteId',
    ]);
})->with('routes');

it('requires the editStructure permission', function (string $route) {
    // Set edition so permissions actually get checked
    Edition::set(Edition::Pro);

    $user = \CraftCms\Cms\User\Models\User::factory()->create([
        'admin' => false,
    ]);

    actingAs($user->asElement());

    $structure = Structure::factory()->create();

    postJson($route, [
        'structureId' => $structure->id,
        'elementId' => $structure->structureElements()->first()->elementId,
        'siteId' => Site::first()->id,
    ])->assertForbidden();

    $user->update(['admin' => true]);
    actingAs($user->asElement());

    $status = postJson($route, [
        'structureId' => $structure->id,
        'elementId' => $structure->structureElements()->first()->elementId,
        'siteId' => Site::first()->id,
    ])->getStatusCode();

    expect($status)->not()->toBe(403);
})->with('routes');

it('needs a valid element', function (string $route) {
    $structure = Structure::factory()->create();

    postJson($route, [
        'structureId' => $structure->id,
        'elementId' => 999,
        'siteId' => Site::first()->id,
    ])->assertNotFound();
})->with('routes');

it('can get element level delta', function (string $elementToTest, int $expected) {
    $structure = Structure::factory()->create();
    $root = $structure->structureElements()->firstOrFail();
    Entry::factory()->create(['id' => $root->elementId]);

    $child = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => Element::factory()->create()->id,
    ]);
    Entry::factory()->create(['id' => $child->elementId]);
    $child->appendTo($root);

    $childsChild = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => Element::factory()->create()->id,
    ]);
    Entry::factory()->create(['id' => $childsChild->elementId]);
    $childsChild->appendTo($child);

    postJson(action([StructuresController::class, 'getElementLevelDelta']), [
        'structureId' => $structure->id,
        'elementId' => ${$elementToTest}->elementId,
        'siteId' => Site::first()->id,
    ])->assertJson([
        'delta' => $expected,
    ]);
})->with([
    ['root', 2],
    ['child', 1],
    ['childsChild', 0],
]);

it('can move elements', function () {
    $this->withoutExceptionHandling();

    $structure = Structure::factory()->create();
    $root = $structure->structureElements()->firstOrFail();
    Entry::factory()->create(['id' => $root->elementId]);

    $child1 = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => Element::factory()->create()->id,
    ]);
    Entry::factory()->create(['id' => $child1->elementId]);
    $child1->appendTo($root);

    $child2 = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => Element::factory()->create()->id,
    ]);
    Entry::factory()->create(['id' => $child2->elementId]);
    $child2->appendTo($root);

    $child3 = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => Element::factory()->create()->id,
    ]);
    Entry::factory()->create(['id' => $child3->elementId]);
    $child3->appendTo($root);

    expect($child1->refresh()->lft)->toBe(2);
    expect($child2->refresh()->lft)->toBe(4);
    expect($child3->refresh()->lft)->toBe(6);

    postJson(action([StructuresController::class, 'moveElement']), [
        'structureId' => $structure->id,
        'elementId' => $child3->elementId,
        'prevId' => $child1->elementId,
        'siteId' => Site::first()->id,
    ])->assertOk();

    expect($child1->refresh()->lft)->toBe(2);
    expect($child2->refresh()->lft)->toBe(6);
    expect($child3->refresh()->lft)->toBe(4);
});
