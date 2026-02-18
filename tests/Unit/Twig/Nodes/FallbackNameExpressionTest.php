<?php

declare(strict_types=1);

use craft\web\twig\nodes\FallbackNameExpression;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('compiles in non-strict mode', function () {
    $node = new FallbackNameExpression('entry', [], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => false]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles in strict mode', function () {
    $node = new FallbackNameExpression('entry', [], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => true]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with ignore_strict_check', function () {
    $node = new FallbackNameExpression('entry', ['ignore_strict_check' => true], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => true]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles as defined test in non-strict mode', function () {
    $node = new FallbackNameExpression('entry', ['is_defined_test' => true], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => false]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('delegates to parent for underscore variables', function () {
    $node = new FallbackNameExpression('_self', [], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => false]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('delegates to parent for always_defined variables', function () {
    $node = new FallbackNameExpression('myVar', ['always_defined' => true], 1);
    $env = new Environment(new ArrayLoader, ['strict_variables' => false]);
    $compiler = new Compiler($env);

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
