<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\PreloadSinglesNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('compiles with handles', function () {
    $node = new PreloadSinglesNode(
        [],
        ['handles' => ['homepage', 'about', 'contact']],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles without handles to empty string', function () {
    $node = new PreloadSinglesNode([], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toBe('');
});
