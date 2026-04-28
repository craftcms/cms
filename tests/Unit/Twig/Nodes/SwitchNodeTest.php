<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\SwitchNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Binary\OrBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\TextNode;

it('compiles with a single case', function () {
    $cases = new Node([
        new Node([
            'values' => new Node([
                new ConstantExpression('foo', 1),
            ]),
            'body' => new TextNode('Foo content', 1),
        ]),
    ]);

    $node = new SwitchNode([
        'value' => new ContextVariable('myVar', 1),
        'cases' => $cases,
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with multiple cases', function () {
    $cases = new Node([
        new Node([
            'values' => new Node([
                new ConstantExpression('foo', 1),
            ]),
            'body' => new TextNode('Foo content', 1),
        ]),
        new Node([
            'values' => new Node([
                new ConstantExpression('bar', 1),
            ]),
            'body' => new TextNode('Bar content', 2),
        ]),
    ]);

    $node = new SwitchNode([
        'value' => new ContextVariable('myVar', 1),
        'cases' => $cases,
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with default case', function () {
    $cases = new Node([
        new Node([
            'values' => new Node([
                new ConstantExpression('foo', 1),
            ]),
            'body' => new TextNode('Foo content', 1),
        ]),
    ]);

    $node = new SwitchNode([
        'value' => new ContextVariable('myVar', 1),
        'cases' => $cases,
        'default' => new TextNode('Default content', 3),
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with or values in a case', function () {
    $cases = new Node([
        new Node([
            'values' => new Node([
                new OrBinary(
                    new ConstantExpression('foo', 1),
                    new ConstantExpression('bar', 1),
                    1,
                ),
            ]),
            'body' => new TextNode('Foo or Bar content', 1),
        ]),
    ]);

    $node = new SwitchNode([
        'value' => new ContextVariable('myVar', 1),
        'cases' => $cases,
    ], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
