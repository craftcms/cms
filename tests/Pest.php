<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Mcp\Public\Access;
use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Tests\BrowserTestCase;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\UnitTestCase;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\Playwright;
use Pest\Browser\Playwright\Servers\PlaywrightNpmServer;
use Pest\Browser\ServerManager;
use Pest\Browser\Support\PackageJsonDirectory;
use Pest\Browser\Support\Port;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');
uses(BrowserTestCase::class)->in('Browser');

// Collect cycles in bounded batches, rather than during each application boot.
$testsSinceCollection = 0;
pest()->beforeAll(fn () => gc_disable())
    ->afterEach(function () use (&$testsSinceCollection) {
        if (++$testsSinceCollection >= 50) {
            gc_collect_cycles();
            $testsSinceCollection = 0;
        }
    })
    ->afterAll(fn () => gc_enable())
    ->in('Feature', 'Unit');

// pest-plugin-browser 5.2.0 and earlier start the Playwright server with `sh -c` and no `exec`, so stopping it at the
// end of the run ends only the shell. Node keeps running, along with any copy of Pest's output pipe it inherited, so
// `pest | tail` never returns. Starting it with `exec` makes the shell and the server one process. Remove this once
// a release includes the fix (https://github.com/pestphp/pest-plugin-browser/pull/281); a fixed release adds its own
// `exec`, and `exec exec` fails.
if (PHP_OS_FAMILY !== 'Windows' && version_compare(InstalledVersions::getVersion('pestphp/pest-plugin-browser') ?? '', '5.2.0.0', '<=')) {
    (fn () => $this->playwright ??= PlaywrightNpmServer::create(
        PackageJsonDirectory::find(),
        'exec ./node_modules/.bin/playwright run-server --host %s --port %d --mode launchServer',
        Playwright::host() ?? ServerManager::DEFAULT_HOST,
        Port::find(),
        'Listening on',
    ))->call(ServerManager::instance());
}

// MCP routes follow the MCP settings, and tests start from cached routes registered before any settings existed.
pest()->beforeEach(fn () => enableMcp())->in('Feature/Mcp');

// Tests that exercise the external MCP server explicitly clear this cache.
pest()->beforeEach(fn () => cache()->put('craftcms-mcp-tools', []))->in('Feature/Ai');

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

/**
 * Opens a control panel page in the browser, once its main landmark has rendered.
 *
 *     visitCp('settings/sections')->assertSee('Sections');
 */
function visitCp(string $path = ''): AwaitableWebpage
{
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

    return visit('/'.ltrim("$cpTrigger/".ltrim($path, '/'), '/'))->assertPresent('main[id]');
}

/**
 * Asserts the page has thrown no JavaScript errors. Chromium's “ResizeObserver loop” notice is left out: it reports
 * that a resize observer's callback ran out of frame time, not an error in the page, and it comes and goes.
 */
function assertNoJavaScriptErrors(AwaitableWebpage $page): AwaitableWebpage
{
    return $page->assertScript(<<<'JS'
        window.__pestBrowser.jsErrors
            .map(error => error.message)
            .filter(message => ! message.startsWith('ResizeObserver loop'))
        JS, []);
}

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

/**
 * The `autocomplete` attribute of each input and select a field layout element renders, by name.
 *
 * @return array<string, string|null>
 */
function renderedAutocompletes(BaseField $field, ?ElementInterface $element): array
{
    $context = new UiContext;
    $node = $field->uiNode(new FieldLayoutElementContext($element, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $inputs = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('input:not([type="hidden"]), select');

    return array_combine(
        $inputs->each(fn (Crawler $input) => $input->attr('name')),
        $inputs->each(fn (Crawler $input) => $input->attr('autocomplete')),
    );
}

function buildExpectedUrl(string $url, string $scheme): string
{
    $siteUrl = "$scheme://localhost/";
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    $cpUrl = $cpTrigger === '' ? rtrim($siteUrl, '/') : rtrim($siteUrl, '/')."/$cpTrigger";

    return str_replace(['{siteUrl}', '{cpUrl}'], [$siteUrl, $cpUrl], $url);
}

function passportKey(): OpenSSLAsymmetricKey
{
    static $key = openssl_pkey_new(['private_key_bits' => 2048]);

    return $key;
}
