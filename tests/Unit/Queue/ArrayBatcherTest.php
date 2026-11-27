<?php

declare(strict_types=1);

use CraftCms\Cms\Queue\ArrayBatcher;

it('slices items by position, ignoring keys', function () {
    $batcher = new ArrayBatcher([5 => 'a', 2 => 'b', 'c' => 'c', 0 => 'd']);

    expect($batcher->count())->toBe(4)
        ->and($batcher->getSlice(1, 2))->toBe(['b', 'c'])
        ->and($batcher->getSlice(3, 10))->toBe(['d']);
});
