<?php

declare(strict_types=1);

use craft\web\twig\nodes\RequireAdminNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    $node = new RequireAdminNode([], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with requireAdminChanges', function () {
    $node = new RequireAdminNode(
        ['requireAdminChanges' => new ConstantExpression(true, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
