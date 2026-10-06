<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Mcp\Capabilities\Structures as StructureCapability;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

it('moves an element within a structure through MCP', function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    $structure = Structure::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    EntryTypes::refreshEntryTypes();

    [$first, $second] = EntryModel::factory()->forSection($section)->count(2)->create()->all();
    $firstElement = Entry::find()->id($first->id)->one();
    $secondElement = Entry::find()->id($second->id)->one();
    Structures::appendToRoot($structure->id, $firstElement);
    Structures::appendToRoot($structure->id, $secondElement);

    $result = app(StructureCapability::class)->moveElement(
        elementType: 'entries',
        operation: 'after',
        structureId: $structure->id,
        elementId: $first->id,
        targetElementId: $second->id,
    );

    expect($result)->toMatchArray([
        'moved' => true,
        'operation' => 'after',
        'structureId' => $structure->id,
    ])
        ->and($result['element']['id'])->toBe($first->id)
        ->and(Entry::find()->structureId($structure->id)->orderBy('lft')->ids())
        ->toBe([$second->id, $first->id]);
});
