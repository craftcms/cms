<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\RequireEditionNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    $node = new RequireEditionNode(
        ['editionName' => new ConstantExpression('pro', 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
