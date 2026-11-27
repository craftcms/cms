<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\JsonlBatcher;
use CraftCms\Cms\Support\Json;

beforeEach(function () {
    $this->path = tempnam(sys_get_temp_dir(), 'jsonl');
    file_put_contents($this->path, "{\"id\":1,\"note\":\"line one\\nline two\"}\n\n{\"id\":2}\n  \n{\"id\":3}\n{\"id\":4}");
});

afterEach(function () {
    unlink($this->path);
});

it('counts lines, skipping blank ones', function () {
    expect(new JsonlBatcher($this->path)->count())->toBe(4);
});

it('decodes each line', function () {
    $items = iterator_to_array(new JsonlBatcher($this->path)->getSlice(0, 2), false);

    expect($items)->toBe([
        ['id' => 1, 'note' => "line one\nline two"],
        ['id' => 2],
    ]);
});

it('skips lines by offset when there is no position', function (bool $reverse, array $ids) {
    $items = iterator_to_array(new JsonlBatcher($this->path, $reverse)->getSlice(1, 2), false);

    expect(array_column($items, 'id'))->toBe($ids);
})->with([
    'forward' => [false, [2, 3]],
    'reverse' => [true, [3, 2]],
]);

it('resumes from the position after the last line read', function (bool $reverse, array $ids) {
    $batcher = new JsonlBatcher($this->path, $reverse);

    foreach ($batcher->getSlice(0, 10) as $i => $item) {
        if ($i === 1) {
            break;
        }
    }

    $items = iterator_to_array(new JsonlBatcher($this->path, $reverse, $batcher->position)->getSlice(0, 10), false);

    expect(array_column($items, 'id'))->toBe($ids);
})->with([
    'forward' => [false, [3, 4]],
    'reverse' => [true, [2, 1]],
]);

it('reads lines in reverse across chunk boundaries', function () {
    $ids = range(0, 299);
    $lines = array_map(fn (int $id) => Json::encode(['id' => $id, 'pad' => str_repeat('x', $id === 150 ? 200000 : $id * 37 % 1000)]), $ids);
    file_put_contents($this->path, implode("\n", $lines)."\n");

    $read = [];
    $position = null;

    for ($i = 0; $i < ceil(count($ids) / 7); $i++) {
        $batcher = new JsonlBatcher($this->path, reverse: true, position: $position);
        $read = [...$read, ...array_column(iterator_to_array($batcher->getSlice(0, 7), false), 'id')];
        $position = $batcher->position;
    }

    expect($read)->toBe(array_reverse($ids));
});

it('throws when there are no more lines to read', function (bool $reverse) {
    expect(fn () => iterator_to_array(new JsonlBatcher($this->path, $reverse)->getSlice(4, 1)))
        ->toThrow(UnexpectedValueException::class, 'no more lines');
})->with([
    'forward' => [false],
    'reverse' => [true],
]);

it('rejects lines that aren’t valid JSON', function () {
    file_put_contents($this->path, "{\"id\":1}\nnope\n");

    expect(fn () => iterator_to_array(new JsonlBatcher($this->path)->getSlice(0, 2)))
        ->toThrow(UnexpectedValueException::class);
});
