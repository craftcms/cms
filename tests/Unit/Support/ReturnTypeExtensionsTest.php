<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\Support\ReturnTypeExtensionsAssertions;

it('infers public return types without losing array or JSON guarantees', function () {
    new ReturnTypeExtensionsAssertions('assertReturnTypes')->assertReturnTypes();
});
