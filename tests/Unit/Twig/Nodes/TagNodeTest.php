<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\TagNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\TextNode;

it('compiles', function () {
    $node = new TagNode([
        'name' => new ConstantExpression('div', 1),
        'content' => new TextNode('Hello World', 1),
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with options', function () {
    $node = new TagNode([
        'name' => new ConstantExpression('div', 1),
        'content' => new TextNode('Hello World', 1),
        'options' => new ContextVariable('attrs', 1),
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
