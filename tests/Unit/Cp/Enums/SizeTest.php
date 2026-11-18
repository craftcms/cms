<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Enums\Size;

it('matches the size scale the web components share', function () {
    $source = file_get_contents(
        dirname(__DIR__, 4).'/packages/craftcms-ui/src/constants/size.ts',
    );

    expect($source)->not->toBeFalse();

    preg_match('/export const Size = \{(.*?)\}/s', (string) $source, $matches);
    preg_match_all("/:\s*'([a-z-]+)'/", $matches[1] ?? '', $sizes);

    expect($sizes[1])->not->toBeEmpty()
        ->and(array_map(fn (Size $size): string => $size->value, Size::cases()))
        ->toEqual($sizes[1]);
});
