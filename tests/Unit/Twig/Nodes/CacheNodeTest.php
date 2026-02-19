<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Nodes\CacheNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\TextNode;

beforeEach(function () {
    Str::createRandomStringsUsing(fn () => 'RANDOM_STRING');
});

afterEach(function () {
    Str::createRandomStringsNormally();
});

it('compiles with defaults', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
    ], [
        'durationUnit' => null,
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with key', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
        'key' => new ConstantExpression('my-cache-key', 1),
    ], [
        'durationUnit' => null,
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles as global', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
    ], [
        'durationUnit' => null,
        'global' => true,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with duration', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
        'durationNum' => new ConstantExpression(1, 1),
    ], [
        'durationUnit' => 'hours',
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with expiration', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
        'expiration' => new ContextVariable('expiresAt', 1),
    ], [
        'durationUnit' => null,
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with conditions', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
        'conditions' => new ContextVariable('shouldCache', 1),
    ], [
        'durationUnit' => null,
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with ignore conditions', function () {
    $node = new CacheNode([
        'body' => new TextNode('cached content', 1),
        'ignoreConditions' => new ContextVariable('skipCache', 1),
    ], [
        'durationUnit' => null,
        'global' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
