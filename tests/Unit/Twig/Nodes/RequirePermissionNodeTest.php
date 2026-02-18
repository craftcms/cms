<?php

declare(strict_types=1);

use craft\web\twig\nodes\RequirePermissionNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    $node = new RequirePermissionNode(
        ['permissionName' => new ConstantExpression('editEntries', 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
