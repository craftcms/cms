<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\PageLifecycle;
use GuzzleHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader;
use yii\behaviors\AttributeTypecastBehavior;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
    $this->env = app(Twig::class)->create();
});

function coreFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function coreFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('CoreTwigExtension', function () {
    it('registers expected surfaces', function () {
        $extension = new CoreTwigExtension($this->pageLifecycle, $this->env);

        expect(coreFilterNames($extension->getFilters()))->toContain('address', 't', 'json_encode', 'length');
        expect(coreFunctionNames($extension->getFunctions()))->toContain('entries', 'head', 'url', 'gql');
        expect($extension->getNodeVisitors())->toHaveCount(4);
        expect($extension->getTokenParsers())->not->toBeEmpty();
        expect($extension->getTests())->not->toBeEmpty();
        expect($extension->getExpressionParsers())->toHaveCount(2);
        expect($extension->getGlobals())->toHaveKeys(['craft', 'now']);
    });

    it('supports has some / has every helpers', function () {
        $env = new TwigEnvironment(new ArrayLoader);

        $hasSome = CoreTwigExtension::arraySome($env, [1, 2, 3], fn (int $value) => $value === 2);
        $hasEvery = CoreTwigExtension::arrayEvery($env, [1, 2, 3], fn (int $value) => $value > 0);

        expect($hasSome)->toBeTrue();
        expect($hasEvery)->toBeTrue();
    });

    it('blocks disallowed classes from create()', function (bool $allowed, string $class) {
        if (! class_exists($class) && ! interface_exists($class)) {
            $this->markTestSkipped(sprintf('%s isn\'t available in this environment.', $class));
        }

        $extension = new CoreTwigExtension($this->pageLifecycle);

        if (! $allowed) {
            expect(fn () => $extension->createFunction($class))
                ->toThrow(InvalidArgumentException::class, sprintf('create() cannot be used to create instances of %s.', $class));

            return;
        }

        expect($extension->createFunction($class))->toBeInstanceOf($class);
    })->with([
        // Ordinary classes remain creatable.
        'Craft field layout model' => [true, FieldLayout::class],
        'CraftCms\Support\Str helper' => [true, Str::class],
        // The read gadget from the report: not on the denylist by class or by the
        // Spl*/*Iterator name patterns, so it was reachable despite GHSA-957r-qf9p-67xw.
        'DOMDocument (reported bypass)' => [false, DOMDocument::class],
        // Pre-existing denylist entries.
        'SplFileObject' => [false, SplFileObject::class],
        'SimpleXMLElement' => [false, SimpleXMLElement::class],
        'DirectoryIterator' => [false, DirectoryIterator::class],
        'AttributeTypecastBehavior' => [false, AttributeTypecastBehavior::class],
        // Newly added denylist entries.
        'XMLReader' => [false, XMLReader::class],
        'XSLTProcessor' => [false, XSLTProcessor::class],
        'SoapClient' => [false, SoapClient::class],
        Client::class => [false, GuzzleClient::class],
        'PDO' => [false, PDO::class],
        'mysqli' => [false, mysqli::class],
        'ReflectionClass (via the Reflector interface)' => [false, ReflectionClass::class],
        'ReflectionMethod (via the Reflector interface)' => [false, ReflectionMethod::class],
        // Only present when the optional imagick extension is installed; skipped
        // otherwise (see the class_exists() guard above).
        'Imagick' => [false, Imagick::class],
        // Phar/PharData aren't listed explicitly: they extend RecursiveDirectoryIterator,
        // which extends FilesystemIterator, which extends DirectoryIterator, so is_a()'s
        // ancestry walk already denies them via the DirectoryIterator entry above.
        'PharData (via DirectoryIterator ancestry)' => [false, PharData::class],
    ]);
});
