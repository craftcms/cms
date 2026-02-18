<?php

declare(strict_types=1);

use craft\web\twig\nodes\ProfileNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('compiles begin profile', function () {
    $node = new ProfileNode('beginProfile', 'template', 'index.twig');
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles end profile', function () {
    $node = new ProfileNode('endProfile', 'block', 'content');
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
