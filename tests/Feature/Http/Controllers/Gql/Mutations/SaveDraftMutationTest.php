<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
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

it('creates an unpublished draft with the save draft mutation', function () {
    graphQL(<<<'GRAPHQL'
mutation {
  save_articles_article_Draft(
    title: "Draft Title"
    slug: "draft-title"
    asUnpublishedDraft: true
    draftName: "Mutation Draft"
  ) {
    title
    slug
    isDraft
    draftName
    sectionHandle
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertExactJson([
            'data' => [
                'save_articles_article_Draft' => [
                    'title' => 'Draft Title',
                    'slug' => 'draft-title',
                    'isDraft' => true,
                    'draftName' => 'Mutation Draft',
                    'sectionHandle' => 'articles',
                ],
            ],
        ]);
});

it('updates an existing draft with the save draft mutation', function () {
    $entry = createGraphqlMutationEntry(
        section: $this->channelFixture['section'],
        entryType: $this->channelFixture['entryType'],
        title: 'Canonical Entry',
        slug: 'canonical-entry',
    );

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft(
        canonical: $entry,
        creatorId: User::findOne()->id,
        name: 'Existing Draft',
    );

    graphQL(<<<GRAPHQL
mutation {
  save_articles_article_Draft(draftId: {$draft->draftId}, title: "Updated Draft Title") {
    title
    isDraft
    draftId
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.save_articles_article_Draft.title', 'Updated Draft Title')
        ->assertJsonPath('data.save_articles_article_Draft.isDraft', true)
        ->assertJsonPath('data.save_articles_article_Draft.draftId', $draft->draftId);
});

it('creates a single-section draft with the single draft mutation', function () {
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
        title: 'Homepage Entry',
        slug: 'homepage-entry',
    );

    gqlActivateFullAccessSchema();

    graphQL(<<<'GRAPHQL'
mutation {
  save_homepage_homepage_Draft(
    title: "Homepage Draft"
    slug: "homepage-draft"
    asUnpublishedDraft: true
    draftName: "Homepage Preview"
  ) {
    title
    slug
    isDraft
    draftName
    sectionHandle
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertExactJson([
            'data' => [
                'save_homepage_homepage_Draft' => [
                    'title' => 'Homepage Draft',
                    'slug' => 'homepage-draft',
                    'isDraft' => true,
                    'draftName' => 'Homepage Preview',
                    'sectionHandle' => 'homepage',
                ],
            ],
        ]);
});
