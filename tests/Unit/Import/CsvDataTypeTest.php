<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DataTypes\Csv;

/** CSV carries flat rows only - format() zips each row against the heading row. */
const CSV = <<<'CSVDATA'
title,plainText
first entry,text one
second entry,text two
CSVDATA;

// format()

it('formats CSV rows keyed by the heading row', function () {
    $result = Csv::format(CSV);

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toBe([
            ['title' => 'first entry', 'plainText' => 'text one'],
            ['title' => 'second entry', 'plainText' => 'text two'],
        ]);
});

it('formats a heading-only CSV into no rows', function () {
    $result = Csv::format("title,plainText\n");

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toBe([]);
});

it('reads a quoted value containing a comma as one field', function () {
    $result = Csv::format("title,plainText\n\"first, entry\",text one");

    expect($result['data'][0]['title'])->toBe('first, entry');
});

// getHeadings()

it('returns the heading row as sorted label/value pairs', function () {
    $result = Csv::getHeadings(CSV);

    expect($result)->toBe([
        ['label' => 'plainText', 'value' => 'plainText'],
        ['label' => 'title', 'value' => 'title'],
    ]);
});
