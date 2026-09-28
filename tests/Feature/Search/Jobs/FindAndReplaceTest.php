<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

it('provides a description with find and replace values', function () {
    $job = new FindAndReplace(
        find: 'old',
        replace: 'new',
    );

    $description = $job->getDescription();

    expect($description)->toContain('old')
        ->and($description)->toContain('new');
});

it('replaces text in element titles', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    // Update the title to contain our search text
    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update(['title' => 'Hello World Title']);

    $job = new FindAndReplace(
        find: 'World',
        replace: 'Universe',
    );

    $job->handle();

    // Verify the replacement occurred
    $updated = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->first();

    expect($updated->title)->toBe('Hello Universe Title');
});

it('leaves content unchanged when the find string is empty', function () {
    $result = Entry::factory()
        ->withField('body', PlainText::class, value: 'Body text')
        ->createElementWithFields(['title' => 'Entry title']);
    $originalRow = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $result->element->id)
        ->first(['title', 'content']);

    $updates = 0;
    DB::listen(function (QueryExecuted $query) use (&$updates) {
        if (str_starts_with(strtolower($query->sql), 'update') && str_contains($query->sql, Table::ELEMENTS_SITES)) {
            $updates++;
        }
    });

    $job = new FindAndReplace(
        find: '',
        replace: 'something',
    );

    $job->handle();

    expect($updates)->toBe(0);

    $entry = EntryElement::findOne($result->element->id);

    expect($entry->title)->toBe('Entry title')
        ->and($entry->getFieldValue('body'))->toBe('Body text')
        ->and(DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $result->element->id)
            ->first(['title', 'content']))->toEqual($originalRow);
});

it('can replace with empty string', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    // Update the title to contain our search text
    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update(['title' => 'Remove This Word']);

    $job = new FindAndReplace(
        find: ' This',
        replace: '',
    );

    $job->handle();

    // Verify the replacement occurred
    $updated = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->first();

    expect($updated->title)->toBe('Remove Word');
});
