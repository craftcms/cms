<?php

declare(strict_types=1);

use craft\web\twig\Extension as LegacyExtension;
use CraftCms\Cms\Twig\Twig;

beforeEach(function () {
    $this->env = app(Twig::class)->create();
});

describe('Legacy Twig Extension', function () {
    it('provides legacy globals and deprecated filters', function () {
        $extension = new LegacyExtension;

        $filterNames = array_map(fn ($filter) => $filter->getName(), $extension->getFilters());
        $globals = $extension->getGlobals();

        expect($filterNames)->toContain('ucfirst', 'ucwords');
        expect($globals)->toHaveKey('view');
    });
});
