<?php

declare(strict_types=1);

use craft\web\twig\nodes\DeprecatedNode;
use CraftCms\Cms\Support\Str;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    Str::createRandomStringsUsing(fn () => 'RANDOM_STRING');

    $node = new DeprecatedNode(
        new ConstantExpression('This feature is deprecated', 1),
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();

    Str::createRandomStringsNormally();
});
