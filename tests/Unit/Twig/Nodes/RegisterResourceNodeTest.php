<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\RegisterResourceNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\TextNode;

it('compiles with direct value', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('body { color: red; }', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerCss',
        'position' => null,
        'defaultPosition' => null,
        'allowOptions' => false,
        'capture' => false,
        'allowPosition' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with capture', function () {
    $node = new RegisterResourceNode([
        'value' => new TextNode('body { color: red; }', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerCss',
        'position' => null,
        'defaultPosition' => null,
        'allowOptions' => false,
        'capture' => true,
        'allowPosition' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with position', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('alert("hi");', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerJs',
        'position' => 'head',
        'defaultPosition' => null,
        'allowOptions' => false,
        'capture' => false,
        'allowPosition' => true,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with options', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('app.js', 1),
        'options' => new ContextVariable('jsOptions', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerJsFile',
        'position' => null,
        'defaultPosition' => null,
        'allowOptions' => true,
        'capture' => false,
        'allowPosition' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with position and options merged', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('app.js', 1),
        'options' => new ContextVariable('jsOptions', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerJsFile',
        'position' => 'head',
        'defaultPosition' => null,
        'allowOptions' => true,
        'capture' => false,
        'allowPosition' => true,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with position and no options', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('app.js', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerJsFile',
        'position' => 'endBody',
        'defaultPosition' => null,
        'allowOptions' => true,
        'capture' => false,
        'allowPosition' => true,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles with default position', function () {
    $node = new RegisterResourceNode([
        'value' => new ConstantExpression('console.log("hi");', 1),
    ], [
        'method' => '\Craft::$app->getView()->registerJs',
        'position' => null,
        'defaultPosition' => 4,
        'allowOptions' => false,
        'capture' => false,
        'allowPosition' => false,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});
