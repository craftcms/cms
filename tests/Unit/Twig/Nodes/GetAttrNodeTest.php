<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Nodes\GetAttrNode;
use CraftCms\Cms\Twig\NodeVisitors\GetAttrAdjuster;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
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
        'null_safe' => false,
        'is_short_circuited' => false,
        'var_name' => null,
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
        'null_safe' => false,
        'is_short_circuited' => false,
        'var_name' => null,
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
        'null_safe' => false,
        'is_short_circuited' => false,
        'var_name' => null,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

it('compiles a null-safe attribute access', function () {
    $node = new GetAttrNode([
        'node' => new ContextVariable('entry', 1),
        'attribute' => new ConstantExpression('title', 1),
    ], [
        'type' => Template::ANY_CALL,
        'optimizable' => false,
        'ignore_strict_check' => false,
        'is_defined_test' => false,
        'null_safe' => true,
        'is_short_circuited' => false,
        'var_name' => null,
    ], 1);
    $compiler = new Compiler(new Environment(new ArrayLoader));

    expect(trim($compiler->compile($node)->getSource()))->toMatchSnapshot();
});

// Array keys are normalized the same way as CoreExtension::getAttribute() within the optimized
// array call path, which is only used when strict variables are disabled.
it('normalizes array keys in the optimized array call path', function (string $expected, string $template, array $variables = []) {
    $twig = new Environment(new ArrayLoader(['template' => $template]), [
        'cache' => false,
        'strict_variables' => false,
    ]);
    $twig->addExtension(new class extends AbstractExtension
    {
        public function getNodeVisitors(): array
        {
            return [new GetAttrAdjuster];
        }
    });

    $variables['arr'] = [0 => 'zero', '' => 'empty', 'foo' => 'bar'];

    expect($twig->render('template', $variables))->toBe($expected);
})->with([
    'constant false' => ['zero', '{{ arr[false] }}'],
    'variable false' => ['zero', '{{ arr[key] }}', ['key' => false]],
    'variable null' => ['empty', '{{ arr[key] }}', ['key' => null]],
    'constant string' => ['bar', '{{ arr["foo"] }}'],
    'stringable' => ['bar', '{{ arr[key] }}', ['key' => new Markup('foo', 'UTF-8')]],
]);
