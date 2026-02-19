<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\expressions\binaries\HasSomeBinary;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Variable\ContextVariable;

it('compiles', function () {
    $node = new HasSomeBinary(
        new ContextVariable('items', 1),
        new ContextVariable('callback', 1),
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
