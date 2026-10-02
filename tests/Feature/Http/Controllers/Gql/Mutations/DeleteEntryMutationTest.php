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

it('deletes an entry with the delete entry mutation', function () {
    $entry = createGraphqlMutationEntry(
        section: $this->fixture['section'],
        entryType: $this->fixture['entryType'],
        title: 'Delete Me',
        slug: 'delete-me',
    );

    graphQL(<<<GRAPHQL
mutation {
  deleteEntry(id: {$entry->id})
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.deleteEntry', true);

    expect(EntryElement::find()->id($entry->id)->status(null)->one())->toBeNull();
});
