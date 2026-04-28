<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    gqlDisablePublicToken();

    $this->fixture = createGraphqlMutationSectionFixture(
        sectionName: 'Articles',
        sectionHandle: 'articles',
        entryTypeName: 'Article',
        entryTypeHandle: 'article',
    );

    gqlActivateFullAccessSchema();
});

it('publishes a draft with the publish draft mutation', function () {
    $entry = createGraphqlMutationEntry(
        section: $this->fixture['section'],
        entryType: $this->fixture['entryType'],
        title: 'Canonical Entry',
        slug: 'canonical-entry',
    );

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft(
        canonical: $entry,
        creatorId: User::findOne()->id,
        name: 'Publishable Draft',
        notes: 'Published via GraphQL',
    );

    $draft->title = 'Published Title';
    expect(Elements::saveElement($draft))->toBeTrue();

    graphQL(<<<GRAPHQL
mutation {
  publishDraft(id: {$draft->draftId})
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.publishDraft', (string) $entry->id);

    /** @var EntryElement $updatedEntry */
    $updatedEntry = EntryElement::find()->id($entry->id)->status(null)->one();

    expect($updatedEntry->title)->toBe('Published Title')
        ->and(EntryElement::find()->status(null)->draftId($draft->draftId)->one())->toBeNull();
});
