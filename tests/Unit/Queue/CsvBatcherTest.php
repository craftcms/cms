<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\CsvBatcher;

beforeEach(function () {
    $this->path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($this->path, "\xEF\xBB\xBF\"name\",notes\nAlpha,\"line one\nline two, with a comma\"\n\nBravo\nCharlie,c\nDelta,d\n");
});

afterEach(function () {
    unlink($this->path);
});

it('counts rows after the header, skipping blank lines', function () {
    expect(new CsvBatcher($this->path)->count())->toBe(4);
});

it('counts every row when there is no header', function () {
    expect(new CsvBatcher($this->path, hasHeader: false)->count())->toBe(5);
});

it('returns rows keyed by the header', function () {
    $rows = iterator_to_array(new CsvBatcher($this->path)->getSlice(0, 2), false);

    expect($rows)->toBe([
        ['name' => 'Alpha', 'notes' => "line one\nline two, with a comma"],
        ['name' => 'Bravo', 'notes' => null],
    ]);
});

it('skips rows by offset when there is no position', function () {
    $rows = iterator_to_array(new CsvBatcher($this->path)->getSlice(1, 2), false);

    expect(array_column($rows, 'name'))->toBe(['Bravo', 'Charlie']);
});

it('resumes from the position after the last row read', function () {
    $batcher = new CsvBatcher($this->path);

    foreach ($batcher->getSlice(0, 10) as $i => $row) {
        if ($i === 1) {
            break;
        }
    }

    $rows = iterator_to_array(new CsvBatcher($this->path, position: $batcher->position)->getSlice(0, 10), false);

    expect(array_column($rows, 'name'))->toBe(['Charlie', 'Delta']);
});

it('rejects rows with more values than the header has columns', function () {
    file_put_contents($this->path, "name\nAlpha,extra\n");

    expect(fn () => iterator_to_array(new CsvBatcher($this->path)->getSlice(0, 1)))
        ->toThrow(UnexpectedValueException::class);
});
