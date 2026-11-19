<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql as GqlFacade;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    gqlDisablePublicToken();
    Cms::config()->enableGraphqlCaching = false;

    $categoriesField = Field::factory()->create(['handle' => 'categories', 'type' => EntriesField::class]);
    Field::factory()->create(['handle' => 'summary', 'type' => PlainText::class]);
    $layout = fn () => FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => $categoriesField->uid])]);

    $categoryType = EntryType::factory()->create(['handle' => 'category']);
    $postType = EntryType::factory()->withFieldLayout($layout())->create(['handle' => 'post']);
    $secretType = EntryType::factory()->withFieldLayout($layout())->create(['handle' => 'secret']);
    FieldLayout::factory()->forField($categoriesField)->create(['type' => User::class]);

    $this->categoriesSection = Section::factory()->withEntryTypes($categoryType)->create(['handle' => 'categories']);
    $this->postsSection = Section::factory()->withEntryTypes($postType)->create(['handle' => 'posts']);
    $this->secretsSection = Section::factory()->withEntryTypes($secretType)->create(['handle' => 'secrets']);
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();

    $this->categories = collect(['A', 'B', 'C', 'D', 'E'])->mapWithKeys(fn (string $name) => [
        $name => EntryModel::factory()->forSection($this->categoriesSection)->forEntryType($categoryType)->createElement(['title' => "Category $name"]),
    ]);

    $this->createRelating = function (mixed $element, array $categoryNames): mixed {
        $element->setFieldValue('categories', $this->categories->only($categoryNames)->pluck('id')->all());
        expect(Elements::saveElement($element))->toBeTrue();

        return $element;
    };

    $this->createPost = fn (string $title, array $categoryNames, ?Section $section = null): Entry => ($this->createRelating)(
        EntryModel::factory()
            ->forSection($section ?? $this->postsSection)
            ->forEntryType($section === $this->secretsSection ? $secretType : $postType)
            ->createElement(['title' => $title]),
        $categoryNames,
    );

    gqlActivateFullAccessSchema();
});

function relatedByTitles(array $elements): array
{
    return collect($elements)->pluck('title')->sort()->values()->all();
}

it('returns the entries that relate to each element through a field', function () {
    ($this->createPost)('Post 1', ['A', 'B']);
    ($this->createPost)('Post 2', ['A']);
    ($this->createPost)('Post 3', ['C']);

    $response = graphQL(<<<'GRAPHQL'
{
  entries(section: "categories", title: ["Category A", "Category B", "Category C", "Category D"], orderBy: "title ASC") {
    title
    relatedEntriesBy(field: "categories") { title }
  }
}
GRAPHQL);

    expect($response->json('errors'))->toBeNull()
        ->and(collect($response->json('data.entries'))->mapWithKeys(fn (array $category) => [
            $category['title'] => relatedByTitles($category['relatedEntriesBy']),
        ])->all())->toBe([
            'Category A' => ['Post 1', 'Post 2'],
            'Category B' => ['Post 1'],
            'Category C' => ['Post 3'],
            'Category D' => [],
        ]);
});

it('only returns related entries the schema can query', function (string $query, string $path) {
    ($this->createPost)('Post 1', ['A']);
    ($this->createPost)('Secret 1', ['A', 'B'], $this->secretsSection);

    $query = str_replace('SECRETS_ID', (string) $this->secretsSection->id, $query);
    $fullAccessResponse = graphQL($query);

    $secretsScope = "sections.{$this->secretsSection->uid}:";
    app(Gql::class)->flushCaches();
    GqlFacade::setActiveSchema(new GqlSchema([
        'name' => 'No secrets',
        'scope' => array_values(array_filter(
            GqlHelper::createFullAccessSchema()->scope,
            fn (string $component) => ! str_starts_with($component, $secretsScope),
        )),
    ]));

    $response = graphQL($query);

    expect($fullAccessResponse->json("$path.title"))->toBe('Category A')
        ->and($fullAccessResponse->json("$path.posts"))->toEqualCanonicalizing([['title' => 'Post 1'], ['title' => 'Secret 1']])
        ->and($fullAccessResponse->json("$path.secrets"))->toBe([['title' => 'Secret 1']])
        ->and($response->json('errors'))->toBeNull()
        ->and($response->json("$path.posts"))->toBe([['title' => 'Post 1']])
        ->and($response->json("$path.secrets"))->toBe([]);
})->with([
    'eager-loaded' => [<<<'GRAPHQL'
{
  entries(section: "categories", title: "Category A") {
    title
    posts: relatedEntriesBy(field: "categories") { title }
    secrets: relatedEntriesBy(field: "categories", sectionId: SECRETS_ID) { title }
  }
}
GRAPHQL, 'data.entries.0'],
    'lazy-loaded' => [<<<'GRAPHQL'
{
  entries(section: "categories", title: ["Category A", "Category B"], orderBy: "title ASC") {
    prev {
      title
      posts: relatedEntriesBy(field: "categories") { title }
      secrets: relatedEntriesBy(field: "categories", sectionId: SECRETS_ID) { title }
    }
  }
}
GRAPHQL, 'data.entries.1.prev'],
]);

it('eager-loads the related entries for every element at once', function () {
    foreach ($this->categories->keys() as $name) {
        ($this->createPost)("Post $name", [$name]);
    }

    $countQueries = function (int $limit): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = graphQL(<<<GRAPHQL
{
  entries(section: "categories", orderBy: "title ASC", limit: $limit) {
    title
    relatedEntriesBy(field: "categories") { title }
  }
}
GRAPHQL);

        DB::disableQueryLog();

        expect($response->json('data.entries'))->toHaveCount($limit)
            ->and($response->json('data.entries.1.relatedEntriesBy'))->toBe([['title' => 'Post B']]);

        return count(DB::getQueryLog());
    };

    $countQueries(5);

    expect($countQueries(5))->toBe($countQueries(2));
});

it('loads aliased fields with their own arguments, and nothing for fields without relations', function () {
    ($this->createPost)('Post 1', ['A', 'B']);
    ($this->createPost)('Post 2', ['A']);

    $response = graphQL(<<<'GRAPHQL'
{
  entries(section: "categories", title: ["Category A", "Category B"], orderBy: "title ASC") {
    all: relatedEntriesBy(field: "categories", orderBy: "title DESC") { title }
    second: relatedEntriesBy(field: "categories", title: "Post 2") { title }
    unknownField: relatedEntriesBy(field: "title") { title }
    textField: relatedEntriesBy(field: "summary") { title }
  }
}
GRAPHQL);

    expect($response->json('errors'))->toBeNull()
        ->and($response->json('data.entries'))->toBe([
            [
                'all' => [['title' => 'Post 2'], ['title' => 'Post 1']],
                'second' => [['title' => 'Post 2']],
                'unknownField' => [],
                'textField' => [],
            ],
            [
                'all' => [['title' => 'Post 1']],
                'second' => [],
                'unknownField' => [],
                'textField' => [],
            ],
        ]);
});

it('returns the users that relate to an element through a field, apart from entries', function () {
    ($this->createPost)('Post 1', ['A']);
    ($this->createRelating)(UserModel::factory()->createElement(['username' => 'fan-a']), ['A']);
    ($this->createRelating)(UserModel::factory()->createElement(['username' => 'fan-b']), ['B']);

    $query = <<<'GRAPHQL'
{
  entries(section: "categories", title: ["Category A", "Category B"], orderBy: "title ASC") {
    relatedUsersBy(field: "categories") { username }
    relatedEntriesBy(field: "categories") { title }
  }
}
GRAPHQL;

    $response = graphQL($query);

    expect($response->json('errors'))->toBeNull()
        ->and($response->json('data.entries'))->toBe([
            ['relatedUsersBy' => [['username' => 'fan-a']], 'relatedEntriesBy' => [['title' => 'Post 1']]],
            ['relatedUsersBy' => [['username' => 'fan-b']], 'relatedEntriesBy' => []],
        ]);

    app(Gql::class)->flushCaches();
    GqlFacade::setActiveSchema(new GqlSchema([
        'name' => 'No users',
        'scope' => array_values(array_filter(
            GqlHelper::createFullAccessSchema()->scope,
            fn (string $component) => ! str_starts_with($component, 'usergroups.'),
        )),
    ]));

    expect(graphQL($query)->json('errors.0.message'))->toBe('Cannot query field "relatedUsersBy" on type "EntryInterface". Did you mean "relatedEntriesBy"?');
});
