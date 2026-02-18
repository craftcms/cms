<?php

declare(strict_types=1);

use craft\web\twig\nodes\GetAttrNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Template;

it('compiles method call', function () {
    $node = new GetAttrNode([
        'node' => new ContextVariable('entry', 1),
        'attribute' => new ConstantExpression('title', 1),
    ], [
        'type' => Template::ANY_CALL,
        'optimizable' => true,
        'ignore_strict_check' => false,
        'is_defined_test' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles array access with optimization', function () {
    $node = new GetAttrNode([
        'node' => new ContextVariable('data', 1),
        'attribute' => new ConstantExpression('key', 1),
    ], [
        'type' => Template::ARRAY_CALL,
        'optimizable' => true,
        'ignore_strict_check' => true,
        'is_defined_test' => false,
    ], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => false]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with defined test', function () {
    $node = new GetAttrNode([
        'node' => new ContextVariable('entry', 1),
        'attribute' => new ConstantExpression('title', 1),
    ], [
        'type' => Template::ANY_CALL,
        'optimizable' => true,
        'ignore_strict_check' => false,
        'is_defined_test' => true,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
