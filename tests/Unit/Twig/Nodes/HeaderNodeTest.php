<?php

declare(strict_types=1);

use craft\web\twig\nodes\HeaderNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    $node = new HeaderNode(
        ['header' => new ConstantExpression('X-Custom: value', 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
