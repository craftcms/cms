<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Tests\TestClasses\Release\ReleaseQueryFixture;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

function createResolverEntryFixture(string $sectionHandle = 'articles'): array
{
    $entryType = EntryType::factory()->create([
        'name' => 'Article',
        'handle' => $sectionHandle.'Type',
    ]);

    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'name' => ucfirst($sectionHandle),
            'handle' => $sectionHandle,
        ]);

    EntryTypes::refreshEntryTypes();

    return ['section' => $section, 'entryType' => $entryType];
}

beforeEach(function () {
    actingAs(User::findOne());
    app(Gql::class)->flushCaches();
});

it('returns an EntryQuery for top-level resolution with full access schema', function () {
    createResolverEntryFixture();
    gqlActivateFullAccessSchema();

    $query = Entry::prepareQuery(null, []);

    expect($query)->toBeInstanceOf(EntryQuery::class);
});

it('returns empty collection when schema has no section or nested entry field access', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [],
    ]));

    $result = Entry::prepareQuery(null, []);

    expect($result)->toBeInstanceOf(ElementCollection::class)
        ->and($result)->toBeEmpty();
});

it('exposes releaseId only to schemas with draft read access', function () {
    $fixture = createResolverEntryFixture('news');
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema([
        'scope' => ["sections.{$fixture['section']->uid}:read"],
    ]));

    expect(EntryArguments::getArguments())->not->toHaveKey('releaseId');

    $gql->setActiveSchema(new GqlSchema([
        'scope' => [
            "sections.{$fixture['section']->uid}:read",
            'elements.drafts:read',
        ],
    ]));

    expect(EntryArguments::getArguments())->toHaveKey('releaseId');
});

it('keeps release queries within the schema section scope', function () {
    $allowed = createResolverEntryFixture('allowed');
    $restricted = createResolverEntryFixture('restricted');
    $drafts = app(Drafts::class);
    $userId = User::findOne()->id;

    $allowedDraft = $drafts->createDraft(EntryModel::factory()
        ->forSection($allowed['section'])
        ->forEntryType($allowed['entryType'])
        ->createElement(), $userId);
    $restrictedDraft = $drafts->createDraft(EntryModel::factory()
        ->forSection($restricted['section'])
        ->forEntryType($restricted['entryType'])
        ->createElement(), $userId);

    $release = ReleaseQueryFixture::create([$allowedDraft, $restrictedDraft]);

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [
            "sections.{$allowed['section']->uid}:read",
            'elements.drafts:read',
        ],
    ]));

    $query = Entry::prepareQuery(null, ['releaseId' => $release->id]);

    expect($query->ids())->toBe([$allowedDraft->id]);
});

it('returns preloaded data when source field is not a query', function () {
    gqlActivateFullAccessSchema();

    $preloaded = collect([
        (object) ['id' => 1, 'title' => 'Test'],
    ]);

    $source = new stdClass;
    $source->entries = $preloaded;

    $result = Entry::prepareQuery($source, [], 'entries');

    expect($result)->toBe($preloaded);
});

it('applies arguments as method calls on the query', function () {
    createResolverEntryFixture();
    gqlActivateFullAccessSchema();

    $query = Entry::prepareQuery(null, [
        'limit' => 10,
    ]);

    expect($query)->toBeInstanceOf(EntryQuery::class);
});

it('ignores null argument values without throwing', function () {
    createResolverEntryFixture();
    gqlActivateFullAccessSchema();

    $query = Entry::prepareQuery(null, [
        'nonExistentMethod' => null,
    ]);

    expect($query)->toBeInstanceOf(EntryQuery::class);
});

it('reads from source field for relational resolution', function () {
    gqlActivateFullAccessSchema();

    $entryQuery = EntryElement::find();

    $source = new stdClass;
    $source->relatedEntries = $entryQuery;

    $result = Entry::prepareQuery($source, [], 'relatedEntries');

    expect($result)->toBeInstanceOf(EntryQuery::class);
});
