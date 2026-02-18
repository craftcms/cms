<?php

declare(strict_types=1);

use craft\web\twig\nodes\RequireGuestNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('compiles', function () {
    $node = new RequireGuestNode([], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
