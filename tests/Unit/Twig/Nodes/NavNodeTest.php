<?php

declare(strict_types=1);

use craft\web\twig\nodes\NavNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\TextNode;

it('compiles', function () {
    $node = new NavNode(
        keyTarget: new AssignContextVariable('_key', 1),
        valueTarget: new AssignContextVariable('item', 1),
        seq: new ContextVariable('entries', 1),
        upperBody: new TextNode('<li>item</li>', 1),
        lowerBody: new TextNode('</ul>', 1),
        indent: new TextNode('<ul>', 1),
        outdent: new TextNode('</ul>', 1),
        lineno: 1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles without lower body', function () {
    $node = new NavNode(
        keyTarget: new AssignContextVariable('_key', 1),
        valueTarget: new AssignContextVariable('item', 1),
        seq: new ContextVariable('entries', 1),
        upperBody: new TextNode('<li>item</li>', 1),
        lowerBody: new Node,
        indent: new TextNode('<ul>', 1),
        outdent: new TextNode('</ul>', 1),
        lineno: 1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
