<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\UnitTestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Assert;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');

pest()->tia()
    ->locally()    // run TIA on every local invocation, no --tia flag needed
    ->baselined()  // fetch the shared baseline from CI when no local graph exists
    ->filtered();  // narrow PHPUnit to only affected test files

/**
 * Asserts the HTML under expectation contains an element with the given tag
 * name, optionally carrying the given attributes. Attribute matching is
 * order-independent (the HTML is parsed into a DOM), so tests don't break when
 * attribute order or whitespace changes.
 *
 *     expect($html)->toContainTag('craft-button-group', ['id' => 'bg', 'role' => 'group']);
 *     expect($html)->toContainTag('craft-button', ['disabled' => true]);   // boolean attr present
 *     expect($html)->not->toContainTag('input', ['type' => 'hidden']);     // no matching element
 *
 * Attribute values: a string must match exactly; `true` asserts the attribute
 * is present (any value); `false` asserts it is absent.
 */
expect()->extend('toContainTag', function (string $tag, array $attributes = []) {
    $html = (string) $this->value;

    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    // The XML encoding hint keeps multibyte content (e.g. “…”) intact; libxml
    // wraps the fragment in <html><body>, which getElementsByTagName sees past.
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    /** @var list<DOMElement> $elements */
    $elements = iterator_to_array($dom->getElementsByTagName($tag));

    $matches = array_values(array_filter($elements, function (DOMElement $el) use ($attributes): bool {
        foreach ($attributes as $name => $expected) {
            if ($expected === false) {
                if ($el->hasAttribute($name)) {
                    return false;
                }

                continue;
            }

            if (! $el->hasAttribute($name)) {
                return false;
            }

            if ($expected !== true && $el->getAttribute($name) !== (string) $expected) {
                return false;
            }
        }

        return true;
    }));

    // Assert through PHPUnit so the assertion is counted (and `->not` works).
    Assert::assertNotEmpty($matches, sprintf(
        "Expected the HTML to contain a <%s>%s, but %s.\nHTML: %s",
        $tag,
        $attributes === []
            ? ''
            : ' with attributes '.json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $elements === []
            ? "no <$tag> elements were found"
            : sprintf('none of the %d <%s> element(s) matched', count($elements), $tag),
        $html,
    ));

    return $this;
});

beforeEach(function () {
    app()->forgetInstance(GeneralConfig::class);
});

function swapUrlRequest(string $uri, string $method = 'GET', array $parameters = []): void
{
    app()->instance('request', Request::create($uri, $method, $parameters));
    app()->forgetScopedInstances();
}

/**
 * @param  list<array<string, mixed>>  $nodes
 * @return list<array<string, mixed>>
 */
function flattenFormNodes(array $nodes): array
{
    $flattened = [];

    foreach ($nodes as $node) {
        $flattened[] = $node;
        array_push($flattened, ...flattenFormNodes($node['children'] ?? []));
    }

    return $flattened;
}

function buildExpectedUrl(string $url, string $scheme): string
{
    $siteUrl = "$scheme://localhost/";
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    $cpUrl = $cpTrigger === '' ? rtrim($siteUrl, '/') : rtrim($siteUrl, '/')."/$cpTrigger";

    return str_replace(['{siteUrl}', '{cpUrl}'], [$siteUrl, $cpUrl], $url);
}
