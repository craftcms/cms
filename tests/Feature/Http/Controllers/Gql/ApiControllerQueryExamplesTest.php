<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    gqlDisablePublicToken();

    $this->queryExamplesFixture = createGraphqlQueryExamplesFixture();

    gqlActivateFullAccessSchema('Examples '.bin2hex(random_bytes(4)));
});

it('queries entries using the docs list-query shape', function () {
    $response = graphQL(<<<'GRAPHQL'
{
  entries(section: "ingredients", orderBy: "slug ASC") {
    title
    slug
    sectionHandle
  }
}
GRAPHQL);

    expect(collect($response->json('data.entries'))->sortBy('slug')->values()->all())
        ->toBe([
            [
                'title' => 'Pepper',
                'slug' => 'pepper',
                'sectionHandle' => 'ingredients',
            ],
            [
                'title' => 'Salt',
                'slug' => 'salt',
                'sectionHandle' => 'ingredients',
            ],
        ]);
});

it('queries a single entry by slug', function () {
    graphQL(<<<'GRAPHQL'
{
  entry(section: "ingredients", slug: "salt") {
    title
    slug
    sectionHandle
  }
}
GRAPHQL)
        ->assertExactJson([
            'data' => [
                'entry' => [
                    'title' => 'Salt',
                    'slug' => 'salt',
                    'sectionHandle' => 'ingredients',
                ],
            ],
        ]);
});

it('queries related entries using native entry relation criteria', function () {
    graphQL(<<<'GRAPHQL'
{
  entries(
    section: "articles"
    relatedToEntries: [{ section: "ingredients", slug: "salt" }]
  ) {
    title
    slug
  }
}
GRAPHQL)
        ->assertExactJson([
            'data' => [
                'entries' => [
                    [
                        'title' => 'Simple Soup',
                        'slug' => 'simple-soup',
                    ],
                ],
            ],
        ]);
});

function createGraphqlQueryExamplesFixture(): array
{
    $ingredientType = EntryType::factory()->create([
        'name' => 'Ingredient',
        'handle' => 'ingredient',
    ]);
    $ingredientsSection = Section::factory()
        ->withEntryTypes($ingredientType)
        ->create(['name' => 'Ingredients', 'handle' => 'ingredients']);

    $salt = EntryModel::factory()
        ->forSection($ingredientsSection)
        ->forEntryType($ingredientType)
        ->createElement(['title' => 'Salt', 'slug' => 'salt']);

    $pepper = EntryModel::factory()
        ->forSection($ingredientsSection)
        ->forEntryType($ingredientType)
        ->createElement(['title' => 'Pepper', 'slug' => 'pepper']);

    $articleType = EntryType::factory()->create([
        'name' => 'Article',
        'handle' => 'article',
    ]);
    $articlesSection = Section::factory()
        ->withEntryTypes($articleType)
        ->create(['name' => 'Articles', 'handle' => 'articles']);

    $relatedArticleResult = EntryModel::factory()
        ->forSection($articlesSection)
        ->forEntryType($articleType)
        ->withField('relatedIngredients', EntriesField::class, [
            'sources' => ['section:'.$ingredientsSection->uid],
        ], value: new ElementCollection([$salt]))
        ->createElementWithFields([
            'title' => 'Simple Soup',
            'slug' => 'simple-soup',
        ]);

    $articleType->refresh();

    $dessert = EntryModel::factory()
        ->forSection($articlesSection)
        ->forEntryType($articleType)
        ->createElement(['title' => 'Dessert', 'slug' => 'dessert']);

    return [
        'ingredientsSection' => $ingredientsSection,
        'articlesSection' => $articlesSection,
        'salt' => $salt,
        'pepper' => $pepper,
        'relatedArticle' => $relatedArticleResult->element,
        'dessert' => $dessert,
    ];
}
