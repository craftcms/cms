<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
use CraftCms\Cms\Http\Controllers\MatrixController;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\MatrixControllerFixture;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

use Illuminate\Support\Facades\Event;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
    $this->fixture = MatrixControllerFixture::create();
});

it('creates a new matrix entry draft and renders its block html', function(bool $eventAdded) {
    $typeId = $this->fixture['entryType']->id;
    $label = 'Matrix Block';

    if ($eventAdded) {
        $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
            'title' => 'Existing disabled block',
            'innerText' => 'Existing text',
            'enabled' => false,
        ]]);
        $this->fixture = MatrixControllerFixture::refresh($this->fixture);
        $existing = MatrixControllerFixture::entries($this->fixture)->sole();
        $type = EntryType::factory()->withField($this->fixture['innerField'])->create([
            'name' => 'Plugin type',
            'handle' => 'pluginType',
        ]);
        EntryTypes::refreshEntryTypes();
        $extraType = EntryTypes::getEntryTypeById($type->id);
        $ownerId = $this->fixture['owner']->id;
        $fieldId = $this->fixture['field']->id;
        Event::listen(function(EntryTypesForFieldResolving $event) use ($ownerId, $fieldId, $existing, $extraType): void {
            if ($event->field->id === $fieldId && $event->element?->id === $ownerId &&
                in_array($existing->id, array_column($event->value, 'id'), true)) {
                $event->entryTypes[] = $extraType;
            }
        });
        $typeId = $type->id;
        $label = 'Plugin type';
    }

    $response = $this->postJson(action([MatrixController::class, 'createEntry']), matrixBlockHtmlPayload($this->fixture, [
        'staticEntries' => !$eventAdded,
        'entryTypeId' => $typeId,
    ]))
        ->assertOk()
        ->assertJsonStructure(['blockHtml', 'headHtml', 'bodyHtml']);

    $entries = MatrixControllerFixture::entries($this->fixture);
    $entry = $entries->where('typeId', $typeId)->sole();

    $html = $response->json('blockHtml');
    if ($eventAdded) {
        expect($html)->toContain("Add {$label} above");
    }
    $host = new Crawler($html)->filter('craft-entry-field-layout-form[data-payload]');

    expect($html)
        ->toContain($label)
        ->toContain('testNamespace[matrixField][entries][uid:' . $entry->uid . '][fresh]')
        ->and($host)->toHaveCount(1)
        ->and(json_decode((string) $host->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR)['scope'])
        ->toBe(['testNamespace', 'matrixField', 'entries', "uid:{$entry->uid}"]);
})->with([false, true]);

it('renders localized actions and an escaped site status for dynamically loaded blocks', function() {
    Site::query()->whereKey($this->fixture['siteId'])->update(['name' => 'Primary <em>Site</em>']);
    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $this->fixture['section']->id,
        'siteId' => $secondSite->id,
        'hasUrls' => true,
    ]);
    Sections::refreshSections();

    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Localized Block',
        'innerText' => 'Localized text',
        'enabledForSite' => false,
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $entry = MatrixControllerFixture::entries($this->fixture)->sole();

    $html = $this->postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $this->fixture['siteId'],
        'namespace' => 'testNamespace',
    ])->assertOk()->json('blockHtml');
    $block = new Crawler($html);
    $visibleActions = $block
        ->filter('.menu li:not(.hidden) .menu-item-label')
        ->each(fn(Crawler $item): string => trim($item->text()));

    expect($visibleActions)
        ->toContain('Enable for Primary <em>Site</em>')
        ->and($block->filter('.status span')->text())
        ->toBe('Disabled for Primary <em>Site</em>');
});

it('returns empty html when render blocks cannot find entries', function() {
    $this->postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [999999],
        'siteId' => Site::firstOrFail()->id,
        'namespace' => 'testNamespace',
    ])
        ->assertOk()
        ->assertJsonPath('blockHtml', '')
        ->assertJsonStructure(['headHtml', 'bodyHtml']);
});

it('renders matrix blocks in the requested order', function() {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [
        [
            'title' => 'First Block',
            'innerText' => 'First text',
        ],
        [
            'title' => 'Second Block',
            'innerText' => 'Second text',
        ],
    ]);

    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    [$first, $second] = MatrixControllerFixture::entries($this->fixture)->values()->all();

    $blockHtml = $this->postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$second->id, $first->id],
        'siteId' => $this->fixture['siteId'],
        'namespace' => 'testNamespace',
    ])
        ->assertOk()
        ->assertJsonStructure(['blockHtml', 'headHtml', 'bodyHtml'])
        ->json('blockHtml');

    $secondPosition = strpos((string) $blockHtml, 'testNamespace[matrixField][entries][uid:' . $second->uid . ']');
    $firstPosition = strpos((string) $blockHtml, 'testNamespace[matrixField][entries][uid:' . $first->uid . ']');

    expect($blockHtml)->toContain('testNamespace[matrixField][entries][uid:' . $second->uid . ']')
        ->toContain('testNamespace[matrixField][entries][uid:' . $first->uid . ']')
        ->and($secondPosition)->not->toBeFalse()
        ->and($firstPosition)->not->toBeFalse()
        ->and($secondPosition)->toBeLessThan($firstPosition);
});

function matrixBlockHtmlPayload(array $fixture, array $overrides = []): array
{
    $payload = MatrixControllerFixture::payload($fixture, $overrides);
    unset($payload['path']);

    return $payload + ['namespace' => 'testNamespace'];
}
