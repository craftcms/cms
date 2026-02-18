<?php

declare(strict_types=1);

use craft\web\twig\nodes\ExitNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles without status', function () {
    $node = new ExitNode([], [], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with 404 status', function () {
    $node = new ExitNode(
        ['status' => new ConstantExpression(404, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with 403 status', function () {
    $node = new ExitNode(
        ['status' => new ConstantExpression(403, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with 500 status', function () {
    $node = new ExitNode(
        ['status' => new ConstantExpression(500, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with 503 status', function () {
    $node = new ExitNode(
        ['status' => new ConstantExpression(503, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with unknown status code falling back to HttpException', function () {
    $node = new ExitNode(
        ['status' => new ConstantExpression(418, 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with status and message', function () {
    $node = new ExitNode(
        [
            'status' => new ConstantExpression(404, 1),
            'message' => new ConstantExpression('Page not found', 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with unknown status and message', function () {
    $node = new ExitNode(
        [
            'status' => new ConstantExpression(418, 1),
            'message' => new ConstantExpression("I'm a teapot", 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
