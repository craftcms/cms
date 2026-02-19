<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\RedirectNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;

it('compiles', function () {
    $node = new RedirectNode(
        [
            'path' => new ConstantExpression('/dashboard', 1),
            'httpStatusCode' => new ConstantExpression(302, 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with flash error', function () {
    $node = new RedirectNode(
        [
            'path' => new ConstantExpression('/login', 1),
            'httpStatusCode' => new ConstantExpression(302, 1),
            'error' => new ConstantExpression('You must log in first', 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with flash notice', function () {
    $node = new RedirectNode(
        [
            'path' => new ConstantExpression('/home', 1),
            'httpStatusCode' => new ConstantExpression(301, 1),
            'notice' => new ConstantExpression('Redirected successfully', 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with both flash error and notice', function () {
    $node = new RedirectNode(
        [
            'path' => new ConstantExpression('/somewhere', 1),
            'httpStatusCode' => new ConstantExpression(302, 1),
            'error' => new ConstantExpression('Something went wrong', 1),
            'notice' => new ConstantExpression('But here is a notice', 1),
        ],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
