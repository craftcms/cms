<?php

declare(strict_types=1);

use craft\web\twig\nodes\DdNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Variable\ContextVariable;

it('compiles with a variable', function () {
    $node = new DdNode(
        ['var' => new ContextVariable('entry', 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles without a variable', function () {
    $node = new DdNode([], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
