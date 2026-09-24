<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Enums\Size;

it('covers every size the button web component accepts', function () {
    $source = file_get_contents(
        dirname(__DIR__, 4).'/packages/craftcms-ui/src/components/button/button.ts',
    );

    expect($source)->not->toBeFalse();

    // The `size` property's union, which this enum documents itself as being
    // the shared half of. A size the component accepts but the enum doesn't
    // makes `HasSize::getSize()` throw for anyone passing it from PHP.
    preg_match('/\bsize:\s*((?:\s*\|\s*\'[a-z-]+\')+)/', (string) $source, $matches);

    expect($matches)->not->toBeEmpty();

    preg_match_all("/'([a-z-]+)'/", $matches[1], $sizes);

    expect($sizes[1])->not->toBeEmpty()
        ->and(array_map(fn (Size $size): string => $size->value, Size::cases()))
        ->toContain(...$sizes[1]);
});
