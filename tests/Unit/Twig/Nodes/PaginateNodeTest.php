<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\PaginateNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;

it('compiles', function () {
    $node = new PaginateNode([
        'query' => new ContextVariable('entries', 1),
        'infoVariable' => new AssignContextVariable('pageInfo', 1),
        'resultVariable' => new AssignContextVariable('pageEntries', 1),
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
