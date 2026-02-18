<?php

declare(strict_types=1);

use craft\web\twig\nodes\NamespaceNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\TextNode;

it('compiles', function () {
    $node = new NamespaceNode([
        'namespace' => new ConstantExpression('myNamespace', 1),
        'body' => new TextNode('<input name="title">', 1),
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with classes', function () {
    $node = new NamespaceNode([
        'namespace' => new ConstantExpression('myNamespace', 1),
        'body' => new TextNode('<input name="title">', 1),
    ], ['withClasses' => true], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
