<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Public\Access;
use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\UnitTestCase;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');

// MCP routes follow the MCP settings, and tests start from cached routes registered before any settings existed.
pest()->beforeEach(fn () => enableMcp())->in('Feature/Mcp');

/**
 * Saves MCP settings, enabling the authenticated server by default, and registers the routes they call for.
 *
 * @param  array<string, mixed>  $settings
 */
function enableMcp(array $settings = []): void
{
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->set('mcp', ['route' => '/mcp', 'authorizationRoute' => '/mcp/auth', ...($projectConfig->get('mcp') ?? []), 'enabled' => true, ...$settings]);
    app()->forgetInstance(Settings::class);
    app()->forgetInstance(Access::class);

    // Cached routes are compiled, and compiled collections can't look up routes named after they were added. Start
    // from a plain collection without any MCP routes from earlier settings.
    $routes = new RouteCollection;

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (($route->defaults['mcp'] ?? false) !== true && ! str_starts_with((string) $route->getName(), 'craft.mcp.')) {
            $routes->add($route);
        }
    }

    Route::setRoutes($routes);
    app(UrlGenerator::class)->setRoutes($routes);
    app(RouteRegistrar::class)->register();
}

/**
 * Asserts the response flashed a message of the given type, and optionally
 * text, to the next page — as a control panel message or,
 * on site requests, the plain session key.
 *
 *     post('/admin/settings/sites')->assertMessage('success', 'Site saved.');
 */
TestResponse::macro('assertMessage', function (string $type, ?string $message = null): TestResponse {
    /** @var TestResponse $this */
    $messages = array_column(array_filter(
        session()->get(Flash::SESSION_KEY, []),
        fn (array $flashed): bool => $flashed['type'] === $type,
    ), 'message');

    if (is_string($plain = session()->get($type))) {
        $messages[] = $plain;
    }

    Assert::assertNotEmpty($messages, "No [$type] message was flashed.");

    if ($message !== null) {
        Assert::assertContains($message, $messages, "No [$type] message with the expected text was flashed.");
    }

    return $this;
});

$tia = pest()->tia()
    ->defaultBranch('6.x')
    ->filtered();

$tiaDirectory = getenv('CRAFT_TIA_DIRECTORY');

if (is_string($tiaDirectory) && $tiaDirectory !== '') {
    $tia->directory($tiaDirectory);

    if (getenv('GITHUB_EVENT_NAME') === 'pull_request') {
        $tia->always();
    }
} else {
    $tia->locally()->baselined();
}

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
function flattenUiNodes(array $nodes): array
{
    $flattened = [];

    foreach ($nodes as $node) {
        $flattened[] = $node;
        array_push($flattened, ...flattenUiNodes($node['children'] ?? []));
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
