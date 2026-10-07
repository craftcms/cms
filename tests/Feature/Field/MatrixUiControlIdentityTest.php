<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

function matrixIdentityFixture(): array
{
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
    ]);

    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();

    /** @var EntryElement $entry */
    $entry = entryQuery()->id($entryModel->id)->one();
    $blockUids = [Str::uuid()->toString(), Str::uuid()->toString()];

    $entry->setFieldValueFromRequest('matrixField', [
        'entries' => array_combine(
            array_map(fn (string $uid): string => "uid:$uid", $blockUids),
            array_map(fn (string $uid, int $index): array => [
                'type' => $matrixEntryType->handle,
                'title' => sprintf('Block %s', $index + 1),
                'enabled' => true,
                'fields' => ['innerText' => sprintf('Canonical %s', $index + 1)],
            ], $blockUids, array_keys($blockUids)),
        ),
        'sortOrder' => $blockUids,
    ]);

    Elements::saveElement($entry);

    return [$entry, $blockUids, $matrixEntryType];
}

/** @return list<string> */
function matrixControlSortOrder(EntryElement $owner): array
{
    /** @var Matrix $field */
    $field = app(Fields::class)->getFieldByHandle('matrixField');
    $control = $field->uiControl(new FieldContext(
        path: 'matrixField',
        value: $owner->getFieldValue('matrixField'),
        element: $owner,
    ));

    return $control->getValue()['sortOrder'];
}

/** @return list<string> */
function matrixControlUiScopes(EntryElement $owner): array
{
    /** @var Matrix $field */
    $field = app(Fields::class)->getFieldByHandle('matrixField');
    $control = $field->uiControl(new FieldContext(
        path: 'matrixField',
        value: $owner->getFieldValue('matrixField'),
        element: $owner,
    ));

    return array_map(
        fn (array $ui): string => array_last($ui['scope']),
        $control->nestedUis($control->getValue()),
    );
}

it('keeps Matrix block identities stable when a provisional draft duplicates them', function () {
    actingAs(User::findOne());

    [$entry, $blockUids, $matrixEntryType] = matrixIdentityFixture();

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);

    // The browser still holds the identities it was rendered with, so that's
    // what an autosave posts back.
    $draft->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$blockUids[0]" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 1',
                'enabled' => true,
                'fields' => ['innerText' => 'Draft 1'],
            ],
            "uid:$blockUids[1]" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 2',
                'enabled' => true,
                'fields' => ['innerText' => 'Draft 2'],
            ],
        ],
        'sortOrder' => $blockUids,
    ]);

    Elements::saveElement($draft);

    // The blocks were duplicated as drafts of their own, which gave them new
    // element UUIDs — but the UI the browser gets back has to keep speaking
    // the identities the browser posted, or its blocks lose their nested UIs.
    expect(matrixControlSortOrder($draft))->toBe($blockUids)
        ->and(matrixControlUiScopes($draft))->toBe($blockUids);

    // …and again once the draft is reloaded from the database.
    /** @var EntryElement $reloaded */
    $reloaded = entryQuery()->draftId($draft->draftId)->provisionalDrafts()->status(null)->one();

    expect(matrixControlSortOrder($reloaded))->toBe($blockUids)
        ->and(matrixControlUiScopes($reloaded))->toBe($blockUids);
});

it('returns a save-draft UI that still identifies the posted Matrix blocks', function () {
    actingAs(User::findOne());

    [$entry, $blockUids, $matrixEntryType] = matrixIdentityFixture();

    // One request per test — the controllers constructor-inject ElementRequest.
    $response = postJson(cp_url('actions/elements/save-draft'), [
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'provisional' => 1,
        'title' => 'Autosaved Title',
        'fields' => [
            'matrixField' => [
                'entries' => [
                    "uid:$blockUids[0]" => [
                        'type' => $matrixEntryType->handle,
                        'title' => 'Block 1',
                        'enabled' => true,
                        'fields' => ['innerText' => 'Draft 1'],
                    ],
                    "uid:$blockUids[1]" => [
                        'type' => $matrixEntryType->handle,
                        'title' => 'Block 2',
                        'enabled' => true,
                        'fields' => ['innerText' => 'Draft 2'],
                    ],
                ],
                'sortOrder' => $blockUids,
            ],
        ],
    ])->assertOk();

    // The browser renders its blocks from the UI's values and looks their nested UIs up by
    // the same identities — anything the response renames is a block it strands without a UI.
    expect($response->json('ui.values.fields.matrixField.sortOrder'))->toBe($blockUids)
        ->and(array_keys($response->json('ui.values.fields.matrixField.entries')))->toBe($blockUids)
        ->and(matrixNestedUiScopes($response->json('ui.nodes')))->toBe(array_map(
            fn (string $uid): array => ['fields', 'matrixField', 'entries', $uid],
            $blockUids,
        ));
});

/**
 * @param  list<array<string, mixed>>  $nodes
 * @return list<list<string>>
 */
function matrixNestedUiScopes(array $nodes): array
{
    $scopes = [];

    foreach ($nodes as $node) {
        foreach ($node['control']['uis'] ?? [] as $ui) {
            $scopes[] = $ui['scope'];
        }

        $scopes = [...$scopes, ...matrixNestedUiScopes($node['children'] ?? [])];
    }

    return $scopes;
}

it('keeps Matrix block identities stable across repeated provisional draft saves', function () {
    actingAs(User::findOne());

    [$entry, $blockUids, $matrixEntryType] = matrixIdentityFixture();

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);

    foreach (['first', 'second', 'third'] as $pass) {
        /** @var EntryElement $draft */
        $draft = entryQuery()->draftId($draft->draftId)->provisionalDrafts()->status(null)->one();

        $draft->setFieldValueFromRequest('matrixField', [
            'entries' => [
                "uid:$blockUids[0]" => [
                    'type' => $matrixEntryType->handle,
                    'title' => 'Block 1',
                    'enabled' => true,
                    'fields' => ['innerText' => "Draft 1 $pass"],
                ],
                "uid:$blockUids[1]" => [
                    'type' => $matrixEntryType->handle,
                    'title' => 'Block 2',
                    'enabled' => true,
                    'fields' => ['innerText' => "Draft 2 $pass"],
                ],
            ],
            'sortOrder' => $blockUids,
        ]);

        Elements::saveElement($draft);

        expect(matrixControlSortOrder($draft))->toBe($blockUids);
    }
});
