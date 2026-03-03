<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\Twig;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
    $this->env = app(Twig::class)->create();
});

function coreFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function coreFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('CoreTwigExtension', function () {
    it('registers expected surfaces', function () {
        $extension = new CoreTwigExtension($this->pageLifecycle, $this->env);

        expect(coreFilterNames($extension->getFilters()))->toContain('address', 't', 'json_encode', 'length');
        expect(coreFunctionNames($extension->getFunctions()))->toContain('entries', 'head', 'url', 'gql');
        expect($extension->getNodeVisitors())->toHaveCount(4);
        expect($extension->getTokenParsers())->not->toBeEmpty();
        expect($extension->getTests())->not->toBeEmpty();
        expect($extension->getExpressionParsers())->toHaveCount(2);
        expect($extension->getGlobals())->toHaveKeys(['craft', 'app', 'now']);
    });

    it('supports has some / has every helpers', function () {
        $env = new TwigEnvironment(new ArrayLoader);

        $hasSome = CoreTwigExtension::arraySome($env, [1, 2, 3], fn (int $value) => $value === 2);
        $hasEvery = CoreTwigExtension::arrayEvery($env, [1, 2, 3], fn (int $value) => $value > 0);

        expect($hasSome)->toBeTrue();
        expect($hasEvery)->toBeTrue();
    });
});
