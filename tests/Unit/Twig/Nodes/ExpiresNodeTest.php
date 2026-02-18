<?php

declare(strict_types=1);

use craft\web\twig\nodes\ExpiresNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\Variable\ContextVariable;

it('compiles with static duration', function () {
    $node = new ExpiresNode(
        [],
        ['durationNum' => 1, 'durationUnit' => 'hours'],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with dynamic expiration', function () {
    $node = new ExpiresNode(
        ['expiration' => new ContextVariable('expiresAt', 1)],
        [],
        1,
    );
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
