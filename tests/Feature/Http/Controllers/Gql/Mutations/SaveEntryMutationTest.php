<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    gqlDisablePublicToken();

    $this->channelFixture = createGraphqlMutationSectionFixture(
        sectionName: 'Articles',
        sectionHandle: 'articles',
        entryTypeName: 'Article',
        entryTypeHandle: 'article',
    );

    gqlActivateFullAccessSchema();
});

it('creates a channel entry with the save entry mutation', function () {
    graphQL(<<<'GRAPHQL'
mutation {
  save_articles_article_Entry(title: "Mutation Entry", slug: "mutation-entry") {
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
                'save_articles_article_Entry' => [
                    'title' => 'Mutation Entry',
                    'slug' => 'mutation-entry',
                    'sectionHandle' => 'articles',
                ],
            ],
        ]);
});

it('updates an existing channel entry with the save entry mutation', function () {
    $entry = createGraphqlMutationEntry(
        section: $this->channelFixture['section'],
        entryType: $this->channelFixture['entryType'],
        title: 'Original Title',
        slug: 'original-title',
    );

    graphQL(<<<GRAPHQL
mutation {
  save_articles_article_Entry(id: {$entry->id}, title: "Updated Title", slug: "updated-title") {
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
                'save_articles_article_Entry' => [
                    'title' => 'Updated Title',
                    'slug' => 'updated-title',
                    'sectionHandle' => 'articles',
                ],
            ],
        ]);
});

it('saves a single entry with the single-section mutation', function () {
    $fixture = createGraphqlMutationSectionFixture(
        sectionName: 'Homepage',
        sectionHandle: 'homepage',
        entryTypeName: 'Homepage',
        entryTypeHandle: 'homepage',
        sectionType: SectionType::Single,
    );

    createGraphqlMutationEntry(
        section: $fixture['section'],
        entryType: $fixture['entryType'],
        title: 'Existing Homepage',
        slug: 'existing-homepage',
    );

    gqlActivateFullAccessSchema();

    graphQL(<<<'GRAPHQL'
mutation {
  save_homepage_homepage_Entry(title: "Homepage Title", slug: "homepage-title") {
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
                'save_homepage_homepage_Entry' => [
                    'title' => 'Homepage Title',
                    'slug' => 'homepage-title',
                    'sectionHandle' => 'homepage',
                ],
            ],
        ]);
});
