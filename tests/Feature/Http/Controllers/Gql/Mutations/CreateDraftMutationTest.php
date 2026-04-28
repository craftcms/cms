<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
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

it('creates a draft with the create draft mutation', function () {
    $entry = createGraphqlMutationEntry(
        section: $this->fixture['section'],
        entryType: $this->fixture['entryType'],
        title: 'Canonical Entry',
        slug: 'canonical-entry',
    );

    $response = graphQL(<<<GRAPHQL
mutation {
  createDraft(id: {$entry->id}, name: "API Draft", notes: "Mutation notes")
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json');

    $draftId = (int) $response->json('data.createDraft');

    /** @var EntryElement $draft */
    $draft = EntryElement::find()
        ->status(null)
        ->draftId($draftId)
        ->one();

    expect($draft)->not->toBeNull()
        ->and($draft->draftName)->toBe('API Draft')
        ->and($draft->draftNotes)->toBe('Mutation notes');
});
