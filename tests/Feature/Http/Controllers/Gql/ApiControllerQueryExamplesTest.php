<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql as GqlFacade;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Testing\TestResponse;

use function CraftCms\Cms\action_url;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    queryExamplesDisablePublicToken();
    $this->queryExamplesFixture = createGraphqlQueryExamplesFixture();

    app(Gql::class)->flushCaches();
    queryExamplesSetActiveSchema(queryExamplesFullAccessSchema());
});

it('queries entries using the docs list-query shape', function () {
    $response = queryExamplesPostGraphqlRequest($this, <<<'GRAPHQL'
{
  entries(section: "ingredients", orderBy: "slug ASC") {
    title
    slug
    sectionHandle
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json');

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
    queryExamplesPostGraphqlRequest($this, <<<'GRAPHQL'
{
  entry(section: "ingredients", slug: "salt") {
    title
    slug
    sectionHandle
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
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
    queryExamplesPostGraphqlRequest($this, <<<'GRAPHQL'
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
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
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
    $ingredientsSection = Section::factory()->create([
        'name' => 'Ingredients',
        'handle' => 'ingredients',
    ]);
    $ingredientsSection->entryTypes()->attach($ingredientType, ['sortOrder' => 1]);

    $salt = createQueryExamplesEntry(
        section: $ingredientsSection,
        type: $ingredientType,
        title: 'Salt',
        slug: 'salt',
    );
    $pepper = createQueryExamplesEntry(
        section: $ingredientsSection,
        type: $ingredientType,
        title: 'Pepper',
        slug: 'pepper',
    );

    $articleType = EntryType::factory()->create([
        'name' => 'Article',
        'handle' => 'article',
    ]);
    $articlesSection = Section::factory()->create([
        'name' => 'Articles',
        'handle' => 'articles',
    ]);
    $articlesSection->entryTypes()->attach($articleType, ['sortOrder' => 1]);

    $relatedArticleResult = EntryModel::factory()
        ->withField('relatedIngredients', EntriesField::class, [
            'sources' => ['section:'.$ingredientsSection->uid],
        ], value: new ElementCollection([$salt]))
        ->createElementWithFields([
            'sectionId' => $articlesSection->id,
            'typeId' => $articleType->id,
        ]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $relatedArticle = $relatedArticleResult->element;
    $relatedArticle->title = 'Simple Soup';
    $relatedArticle->slug = 'simple-soup';

    expect(Craft::$app->getElements()->saveElement($relatedArticle))->toBeTrue();

    $articleType->refresh();

    $dessert = createQueryExamplesEntry(
        section: $articlesSection,
        type: $articleType,
        title: 'Dessert',
        slug: 'dessert',
        fieldLayoutId: $articleType->fieldLayoutId,
    );

    return [
        'ingredientsSection' => $ingredientsSection,
        'articlesSection' => $articlesSection,
        'salt' => $salt,
        'pepper' => $pepper,
        'relatedArticle' => $relatedArticle,
        'dessert' => $dessert,
    ];
}

function createQueryExamplesEntry(
    Section $section,
    EntryType $type,
    string $title,
    string $slug,
    ?int $fieldLayoutId = null,
): EntryElement {
    $model = EntryModel::factory()->create([
        'sectionId' => $section->id,
        'typeId' => $type->id,
    ]);

    if ($fieldLayoutId !== null) {
        $model->element->update([
            'fieldLayoutId' => $fieldLayoutId,
        ]);
    }

    /** @var EntryElement $entry */
    $entry = EntryElement::find()->id($model->id)->one();

    expect($entry)->not->toBeNull();

    $entry->title = $title;
    $entry->slug = $slug;

    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();

    /** @var EntryElement $savedEntry */
    $savedEntry = EntryElement::find()->id($model->id)->one();

    return $savedEntry;
}

function queryExamplesPostGraphqlRequest(TestCase $test, string $query): TestResponse
{
    return $test->call('POST', action_url('graphql/api'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/graphql-response+json',
    ], json_encode([
        'query' => $query,
    ]));
}

function queryExamplesFullAccessSchema(): GqlSchema
{
    return new GqlSchema([
        'name' => 'Examples '.bin2hex(random_bytes(4)),
        'scope' => GqlHelper::createFullAccessSchema()->scope,
    ]);
}

function queryExamplesSetActiveSchema(GqlSchema $schema): void
{
    GqlFacade::setActiveSchema($schema);
}

function queryExamplesDisablePublicToken(): void
{
    $token = GqlFacade::getPublicToken();
    $token->enabled = false;

    GqlFacade::saveToken($token);
}
