<?php

declare(strict_types=1);

use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

class TestBufferedDependencyAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->js('window.testBufferedDependency = true;', Position::Head);
    }
}

class TestBufferedBundleAsset implements LegacyAssetInterface
{
    public array $depends = [TestBufferedDependencyAsset::class];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile('/test-buffered-bundle.js', ['position' => Position::Head->value]);
    }
}

function assertLatestRegistrationOrder(string $output): void
{
    $firstPos = strpos($output, 'first');
    $middlePos = strpos($output, 'middle');
    $updatedPos = strpos($output, 'updated');

    expect($output)
        ->not->toContain('initial')
        ->and($firstPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($updatedPos);
}

dataset('ordered asset cases', [
    'inline JS' => [[
        'bufferKey' => 'js',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->js($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->bodyEndHtml(),
    ]],
    'JS file' => [[
        'bufferKey' => 'jsFiles',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->jsFile($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->bodyEndHtml(),
    ]],
    'inline CSS' => [[
        'bufferKey' => 'css',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->css($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->headHtml(),
    ]],
    'CSS file' => [[
        'bufferKey' => 'cssFiles',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->cssFile($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->headHtml(),
    ]],
    'script tag' => [[
        'bufferKey' => 'scripts',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->script($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->bodyEndHtml(),
    ]],
    'HTML' => [[
        'bufferKey' => 'html',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->html($value, key: $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->bodyEndHtml(),
    ]],
    'import map entry' => [[
        'bufferKey' => 'jsImports',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->jsImport($key, $value);
        },
        'render' => fn (HtmlStack $registry): string => $registry->headHtml(),
    ]],
    'meta tag' => [[
        'bufferKey' => 'metaTags',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->metaTag(['name' => $value, 'content' => 'yes'], $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->headHtml(),
    ]],
    'link tag' => [[
        'bufferKey' => 'linkTags',
        'register' => function (HtmlStack $registry, string $value, string $key): void {
            $registry->linkTag(['rel' => 'preload', 'href' => $value], $key);
        },
        'render' => fn (HtmlStack $registry): string => $registry->headHtml(),
    ]],
]);

beforeEach(function () {
    $this->registry = app(HtmlStack::class);
});

describe('scoped resolution', function () {
    it('is resolved as a scoped instance', function () {
        $a = app(HtmlStack::class);
        $b = app(HtmlStack::class);

        expect($a)->toBe($b);
    });
});

describe('clear', function () {
    it('resets all state', function () {
        $this->registry->js('var x = 1');
        $this->registry->css('body { color: red }');
        $this->registry->html('<div>test</div>');
        $this->registry->metaTag(['name' => 'description', 'content' => 'test']);
        $this->registry->linkTag(['rel' => 'icon', 'href' => '/favicon.ico']);
        $this->registry->jsFile('/app.js');
        $this->registry->cssFile('/app.css');
        $this->registry->jsImport('lodash', '/vendor/lodash.js');
        $this->registry->icons(['heart']);
        $this->registry->script('console.log("hi")');

        $this->registry->clear();

        // The body end may still carry the client-asset sync script (what
        // the browser has loaded isn't undone by a clear), but none of the
        // cleared assets themselves.
        $bodyEndHtml = $this->registry->bodyEndHtml();

        expect($this->registry->headHtml())->toBe('')
            ->and($bodyEndHtml)->not->toContain('var x = 1')
            ->and($bodyEndHtml)->not->toContain('/app.js')
            ->and($bodyEndHtml)->not->toContain('console.log');
    });
});

describe('js registration', function () {
    it('registers inline JS at body position by default', function () {
        $this->registry->js('var x = 1');

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('var x = 1;');
    });

    it('registers inline JS at head position', function () {
        $this->registry->js('var x = 1', Position::Head);

        $head = $this->registry->headHtml();

        expect($head)->toContain('var x = 1;');
    });

    it('trims and appends semicolons', function () {
        $this->registry->js('  var x = 1;  ');

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('var x = 1;');
    });

    it('deduplicates JS by explicit key', function () {
        $this->registry->js('var x = 1', key: 'myKey');
        $this->registry->js('var x = 2', key: 'myKey');

        $body = $this->registry->bodyEndHtml();

        expect($body)
            ->not->toContain('var x = 1;')
            ->toContain('var x = 2;');
    });

    it('wraps inline JS in script tags', function () {
        $this->registry->js('var x = 1');

        $body = $this->registry->bodyEndHtml();

        expect($body)
            ->toContain("document.addEventListener('DOMContentLoaded'")
            ->toContain('run.call(document)')
            ->toContain('var x = 1;');
    });

    it('renders body end JS without a ready wrapper', function () {
        $this->registry->js('var x = 1', Position::BodyEnd);

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('<script type="module">var x = 1;</script>');
    });
});

describe('jsWithVars', function () {
    it('encodes and injects variables into callback result', function () {
        $this->registry->jsWithVars(
            fn ($name, $count) => "greet($name, $count)",
            ['Alice', 42],
        );

        $body = $this->registry->bodyEndHtml();

        expect($body)
            ->toContain('"Alice"')
            ->toContain('42');
    });
});

describe('jsFile registration', function () {
    it('registers a JS file at body position by default', function () {
        $this->registry->jsFile('/app.js');

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('<script src="/app.js"></script>');
    });

    it('registers a JS file at head position via options', function () {
        $this->registry->jsFile('/app.js', ['position' => Position::Head->value]);

        $head = $this->registry->headHtml();

        expect($head)->toContain('<script src="/app.js"></script>');
    });

    it('deduplicates by URL when no key is given', function () {
        $this->registry->jsFile('/app.js');
        $this->registry->jsFile('/app.js');

        $body = $this->registry->bodyEndHtml();

        expect(substr_count($body, '/app.js'))->toBe(1);
    });

    it('deduplicates by explicit key', function () {
        $this->registry->jsFile('/v1.js', key: 'app');
        $this->registry->jsFile('/v2.js', key: 'app');

        $body = $this->registry->bodyEndHtml();

        expect($body)
            ->not->toContain('/v1.js')
            ->toContain('/v2.js');
    });

    it('passes through HTML attributes', function () {
        $this->registry->jsFile('/app.js', ['defer' => true]);

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('defer');
    });
});

describe('css registration', function () {
    it('renders a style tag in head', function () {
        $this->registry->css('body { color: red }');

        $head = $this->registry->headHtml();

        expect($head)->toContain('<style>body { color: red }</style>');
    });

    it('deduplicates by explicit key', function () {
        $this->registry->css('body { color: red }', key: 'theme');
        $this->registry->css('body { color: blue }', key: 'theme');

        $head = $this->registry->headHtml();

        expect($head)
            ->not->toContain('color: red')
            ->toContain('color: blue');
    });

});

describe('content-hash deduplication', function () {
    it('deduplicates by content hash', function (string $method, string $content, string $needle, string $output) {
        $this->registry->{$method}($content);
        $this->registry->{$method}($content);

        $html = $this->registry->{$output}();

        expect(substr_count($html, $needle))->toBe(1);
    })->with([
        'js' => ['js', 'var x = 1', 'var x = 1;', 'bodyHtml'],
        'css' => ['css', 'body { color: red }', 'body { color: red }', 'headHtml'],
        'html' => ['html', '<div>same</div>', '<div>same</div>', 'bodyHtml'],
    ]);
});

describe('cssFile registration', function () {
    it('renders a link tag in head', function () {
        $this->registry->cssFile('/style.css');

        $head = $this->registry->headHtml();

        expect($head)->toContain('<link rel="stylesheet" href="/style.css">');
    });

    it('deduplicates by URL', function () {
        $this->registry->cssFile('/style.css');
        $this->registry->cssFile('/style.css');

        $head = $this->registry->headHtml();

        expect(substr_count($head, '/style.css'))->toBe(1);
    });

});

describe('script registration', function () {
    it('registers a generic script tag at body by default', function () {
        $this->registry->script('console.log("hi")');

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('<script>console.log("hi")</script>');
    });

    it('registers a generic script tag at head', function () {
        $this->registry->script('console.log("hi")', Position::Head);

        $head = $this->registry->headHtml();

        expect($head)->toContain('<script>console.log("hi")</script>');
    });

    it('passes through HTML attributes', function () {
        $this->registry->script('export default {}', Position::BodyEnd, ['type' => 'module']);

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('type="module"');
    });

});

describe('scriptWithVars', function () {
    it('encodes and injects variables', function () {
        $this->registry->scriptWithVars(
            fn ($config) => "init($config)",
            [['debug' => true]],
        );

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('{"debug":true}');
    });
});

describe('html registration', function () {
    it('registers arbitrary HTML at body by default', function () {
        $this->registry->html('<div id="portal"></div>');

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('<div id="portal"></div>');
    });

    it('registers arbitrary HTML at head', function () {
        $this->registry->html('<template id="t"></template>', Position::Head);

        $head = $this->registry->headHtml();

        expect($head)->toContain('<template id="t"></template>');
    });

});

describe('jsImport registration', function () {
    it('renders an importmap in head', function () {
        $this->registry->jsImport('lodash', '/vendor/lodash.js');

        $head = $this->registry->headHtml();

        expect($head)->toContain('<script type="importmap">')
            ->toContain('"lodash":"/vendor/lodash.js"');
    });

    it('combines multiple imports into one importmap', function () {
        $this->registry->jsImport('lodash', '/vendor/lodash.js');
        $this->registry->jsImport('vue', '/vendor/vue.js');

        $head = $this->registry->headHtml();

        expect(substr_count($head, '<script type="importmap">'))->toBe(1)
            ->and($head)->toContain('"lodash"')
            ->toContain('"vue"');
    });

});

describe('metaTag registration', function () {
    it('renders a meta tag in head', function () {
        $this->registry->metaTag(['name' => 'description', 'content' => 'My site']);

        $head = $this->registry->headHtml();

        expect($head)->toContain('<meta name="description" content="My site">');
    });

    it('deduplicates by explicit key', function () {
        $this->registry->metaTag(['name' => 'description', 'content' => 'Old'], 'desc');
        $this->registry->metaTag(['name' => 'description', 'content' => 'New'], 'desc');

        $head = $this->registry->headHtml();

        expect($head)
            ->not->toContain('Old')
            ->toContain('New');
    });

});

describe('linkTag registration', function () {
    it('renders a link tag in head', function () {
        $this->registry->linkTag(['rel' => 'icon', 'href' => '/favicon.ico']);

        $head = $this->registry->headHtml();

        expect($head)->toContain('<link rel="icon" href="/favicon.ico">');
    });

    it('deduplicates by explicit key', function () {
        $this->registry->linkTag(['rel' => 'icon', 'href' => '/old.ico'], 'favicon');
        $this->registry->linkTag(['rel' => 'icon', 'href' => '/new.ico'], 'favicon');

        $head = $this->registry->headHtml();

        expect($head)
            ->not->toContain('/old.ico')
            ->toContain('/new.ico');
    });

});

describe('latest registration order', function () {
    it('moves keyed registrations to their latest registration order', function (array $case) {
        $case['register']($this->registry, 'first', 'first');
        $case['register']($this->registry, 'initial', 'target');
        $case['register']($this->registry, 'middle', 'middle');
        $case['register']($this->registry, 'updated', 'target');

        $output = $case['render']($this->registry);

        assertLatestRegistrationOrder($output);
    })->with('ordered asset cases');
});

describe('headHtml output order', function () {
    it('renders assets in the correct order', function () {
        $this->registry->jsImport('lib', '/lib.js');
        $this->registry->script('init()', Position::Head);
        $this->registry->html('<noscript>Enable JS</noscript>', Position::Head);
        $this->registry->metaTag(['charset' => 'utf-8']);
        $this->registry->linkTag(['rel' => 'stylesheet', 'href' => '/a.css']);
        $this->registry->cssFile('/b.css');
        $this->registry->css('.red { color: red }');
        $this->registry->jsFile('/head.js', ['position' => Position::Head->value]);
        $this->registry->js('var headVar = 1', Position::Head);

        $head = $this->registry->headHtml();

        // Verify ordering: importmap → scripts → html → meta → link → cssFiles → css → jsFiles → js
        $importmapPos = strpos($head, 'importmap');
        $scriptPos = strpos($head, 'init()');
        $noscriptPos = strpos($head, '<noscript>');
        $metaPos = strpos($head, '<meta');
        $linkPos = strpos($head, '<link');
        $cssFilePos = strpos($head, '/b.css');
        $cssPos = strpos($head, '.red');
        $jsFilePos = strpos($head, '/head.js');
        $jsPos = strpos($head, 'headVar');

        expect($importmapPos)->toBeLessThan($scriptPos)
            ->and($scriptPos)->toBeLessThan($noscriptPos)
            ->and($noscriptPos)->toBeLessThan($metaPos)
            ->and($metaPos)->toBeLessThan($linkPos)
            ->and($linkPos)->toBeLessThan($cssFilePos)
            ->and($cssFilePos)->toBeLessThan($cssPos)
            ->and($cssPos)->toBeLessThan($jsFilePos)
            ->and($jsFilePos)->toBeLessThan($jsPos);
    });
});

describe('bodyHtml output order', function () {
    it('renders assets in the correct order', function () {
        $this->registry->script('bodyInit()', Position::BodyEnd);
        $this->registry->html('<div id="app"></div>', Position::BodyEnd);
        $this->registry->jsFile('/body.js');
        $this->registry->js('var bodyVar = 1');

        $body = $this->registry->bodyEndHtml();

        $scriptPos = strpos($body, 'bodyInit()');
        $htmlPos = strpos($body, '<div id="app">');
        $jsFilePos = strpos($body, '/body.js');
        $jsPos = strpos($body, 'bodyVar');

        expect($scriptPos)->toBeLessThan($htmlPos)
            ->and($htmlPos)->toBeLessThan($jsFilePos)
            ->and($jsFilePos)->toBeLessThan($jsPos);
    });
});

describe('headHtml and bodyHtml clearing', function () {
    it('clears head assets after rendering by default', function () {
        $this->registry->js('var x = 1', Position::Head);
        $this->registry->css('body {}');

        $first = $this->registry->headHtml();
        $second = $this->registry->headHtml();

        expect($first)->not->toBe('')
            ->and($second)->toBe('');
    });

    it('preserves head assets when clear is false', function () {
        $this->registry->js('var x = 1', Position::Head);

        $first = $this->registry->headHtml(clear: false);
        $second = $this->registry->headHtml(clear: false);

        expect($first)->toBe($second)
            ->and($first)->not->toBe('');
    });

    it('clears body assets after rendering by default', function () {
        $this->registry->js('var x = 1');

        $first = $this->registry->bodyEndHtml();
        $second = $this->registry->bodyEndHtml();

        expect($first)->not->toBe('')
            ->and($second)->toBe('');
    });

    it('preserves body assets when clear is false', function () {
        $this->registry->js('var x = 1');

        $first = $this->registry->bodyEndHtml(clear: false);
        $second = $this->registry->bodyEndHtml(clear: false);

        expect($first)->toBe($second)
            ->and($first)->not->toBe('');
    });

    it('does not clear body assets when rendering head', function () {
        $this->registry->js('var headJs = 1', Position::Head);
        $this->registry->js('var bodyJs = 1', Position::BodyEnd);

        $this->registry->headHtml();
        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('bodyJs');
    });

    it('does not clear head assets when rendering body', function () {
        $this->registry->js('var headJs = 1', Position::Head);
        $this->registry->js('var bodyJs = 1', Position::BodyEnd);

        $this->registry->bodyEndHtml();
        $head = $this->registry->headHtml();

        expect($head)->toContain('headJs');
    });
});

describe('empty output', function () {
    it('returns empty string when nothing is registered', function (string $method) {
        expect($this->registry->{$method}())->toBe('');
    })->with(['headHtml', 'bodyHtml']);
});

describe('per-key buffer: startBuffer and clearBuffer', function () {
    it('captures JS registered during a buffer', function () {
        $this->registry->js('var before = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var during = 1');
        $captured = $this->registry->clearBuffer('js');

        expect($captured)->toHaveKey(Position::Ready->value)
            ->and($this->registry->bodyEndHtml())->toContain('var before = 1;')
            ->and($this->registry->bodyEndHtml())->not->toContain('var during = 1;');
    });

    it('captures CSS registered during a buffer', function () {
        $this->registry->css('.before { color: red }');

        $this->registry->startBuffer('css');
        $this->registry->css('.during { color: blue }');
        $captured = $this->registry->clearBuffer('css');

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->headHtml())->toContain('.before')
            ->and($this->registry->headHtml())->not->toContain('.during');
    });

    it('captures multiple property types simultaneously', function () {
        $this->registry->js('var before = 1');
        $this->registry->css('.before {}');

        $this->registry->startBuffer(['js', 'css']);
        $this->registry->js('var during = 1');
        $this->registry->css('.during {}');
        $captured = $this->registry->clearBuffer(['js', 'css']);

        expect($captured)->toHaveKeys(['js', 'css'])
            ->and($this->registry->bodyEndHtml())->toContain('var before = 1;')
            ->and($this->registry->headHtml())->toContain('.before');
    });

    it('supports nested buffers for the same key', function () {
        $this->registry->js('var outer = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var middle = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var inner = 1');
        $innerCaptured = $this->registry->clearBuffer('js');

        $middleCaptured = $this->registry->clearBuffer('js');

        // Inner buffer captured only inner content
        $innerValues = implode('', array_map(fn ($entries) => implode('', $entries), $innerCaptured));
        expect($innerValues)->toContain('var inner = 1;')
            ->and($innerValues)->not->toContain('var middle = 1;');

        // Middle buffer captured only middle content
        $middleValues = implode('', array_map(fn ($entries) => implode('', $entries), $middleCaptured));
        expect($middleValues)->toContain('var middle = 1;')
            ->and($middleValues)->not->toContain('var outer = 1;');

        // Original state is restored
        expect($this->registry->bodyEndHtml())->toContain('var outer = 1;');
    });

    it('supports independent buffers for different keys', function () {
        $this->registry->js('var before = 1');
        $this->registry->css('.before {}');

        // Buffer only JS
        $this->registry->startBuffer('js');
        $this->registry->js('var buffered = 1');
        $this->registry->css('.unbuffered {}');
        $captured = $this->registry->clearBuffer('js');

        // JS was buffered
        expect($this->registry->bodyEndHtml())->toContain('var before = 1;')
            ->and($this->registry->bodyEndHtml())->not->toContain('var buffered = 1;');

        // CSS was NOT buffered — both registrations are present
        expect($this->registry->headHtml())
            ->toContain('.before')
            ->toContain('.unbuffered');
    });

    it('returns empty arrays when nothing was registered during buffer', function () {
        $this->registry->startBuffer(['js', 'css']);
        $captured = $this->registry->clearBuffer(['js', 'css']);

        expect($captured['js'])->toBe([])
            ->and($captured['css'])->toBe([]);
    });

    it('handles clearBuffer without prior startBuffer gracefully', function () {
        $this->registry->js('var x = 1');

        $captured = $this->registry->clearBuffer('js');

        // Captures current state and resets to empty
        expect($captured)->not->toBeEmpty()
            ->and($this->registry->bodyEndHtml())->toBe('');
    });

    it('returns the captured state directly when called with a single key', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var x = 1');
        $captured = $this->registry->clearBuffer('js');

        // Single key: returns the value directly (position-keyed array), not wrapped in ['js' => ...]
        expect($captured)->toBeArray()
            ->and($captured)->toHaveKey(Position::Ready->value)
            ->and($captured)->not->toHaveKey('js');
    });

    it('returns a keyed array when called with multiple keys', function () {
        $this->registry->startBuffer(['js', 'css']);
        $this->registry->js('var x = 1');
        $this->registry->css('.foo {}');
        $captured = $this->registry->clearBuffer(['js', 'css']);

        // Multiple keys: returns ['js' => ..., 'css' => ...]
        expect($captured)->toBeArray()
            ->and($captured)->toHaveKeys(['js', 'css'])
            ->and($captured['js'])->toHaveKey(Position::Ready->value);
    });

    it('returns an empty array when nothing was registered during a single-key buffer', function () {
        $this->registry->startBuffer('js');
        $captured = $this->registry->clearBuffer('js');

        expect($captured)->toBe([]);
    });
});

describe('applyBuffer', function () {
    it('merges captured buffer state back into the registry', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var buffered = 1');
        $captured = $this->registry->clearBuffer('js');

        $this->registry->applyBuffer(['js' => $captured]);

        expect($this->registry->bodyEndHtml())->toContain('var buffered = 1;');
    });

    it('merges position-keyed properties', function () {
        $this->registry->js('var existing = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var buffered = 1');
        $captured = $this->registry->clearBuffer('js');

        $this->registry->applyBuffer(['js' => $captured]);

        $body = $this->registry->bodyEndHtml();

        expect($body)
            ->toContain('var existing = 1;')
            ->toContain('var buffered = 1;');
    });

    it('merges flat-keyed properties', function () {
        $this->registry->css('.existing {}');

        $this->registry->startBuffer('css');
        $this->registry->css('.buffered {}');
        $captured = $this->registry->clearBuffer('css');

        $this->registry->applyBuffer(['css' => $captured]);

        $head = $this->registry->headHtml();

        expect($head)
            ->toContain('.existing')
            ->toContain('.buffered');
    });

    it('preserves latest keyed registration order when applying a buffer', function (array $case) {
        $case['register']($this->registry, 'first', 'first');
        $case['register']($this->registry, 'initial', 'target');

        $this->registry->startBuffer($case['bufferKey']);
        $case['register']($this->registry, 'updated', 'target');
        $captured = $this->registry->clearBuffer($case['bufferKey']);

        $case['register']($this->registry, 'middle', 'middle');
        $this->registry->applyBuffer([$case['bufferKey'] => $captured]);

        $output = $case['render']($this->registry);

        assertLatestRegistrationOrder($output);
    })->with('ordered asset cases');

    it('handles partial buffer state', function () {
        // applyBuffer with only some keys should not error
        $this->registry->applyBuffer(['css' => ['mykey' => '<style>.applied {}</style>']]);

        expect($this->registry->headHtml())->toContain('.applied');
    });

    it('merges icons from buffer', function () {
        $this->registry->startBuffer(['js', 'icons']);
        $this->registry->icons(['heart']);
        $captured = $this->registry->clearBuffer(['js', 'icons']);

        // Icons were captured
        expect($captured['icons'])->toContain('heart');
    });

    it('merges jsImports from buffer', function () {
        $this->registry->jsImport('existing', '/existing.js');

        $this->registry->startBuffer('jsImports');
        $this->registry->jsImport('buffered', '/buffered.js');
        $captured = $this->registry->clearBuffer('jsImports');

        $this->registry->applyBuffer(['jsImports' => $captured]);

        $head = $this->registry->headHtml();

        expect($head)
            ->toContain('"existing"')
            ->toContain('"buffered"');
    });

    it('merges metaTags from buffer', function () {
        $this->registry->metaTag(['name' => 'existing', 'content' => 'yes'], 'existing');

        $this->registry->startBuffer('metaTags');
        $this->registry->metaTag(['name' => 'buffered', 'content' => 'yes'], 'buffered');
        $captured = $this->registry->clearBuffer('metaTags');

        $this->registry->applyBuffer(['metaTags' => $captured]);

        $head = $this->registry->headHtml();

        expect($head)
            ->toContain('name="existing"')
            ->toContain('name="buffered"');
    });
});

describe('legacy asset registry', function () {
    it('does not capture queued dependency JS in an active JS buffer', function () {
        $assets = app(InternalAssetRegistry::class);

        $this->registry->startJsBuffer();
        $assets->register(TestBufferedBundleAsset::class);
        $capturedJs = $this->registry->clearJsBuffer(scriptTag: false);

        $head = $this->registry->headHtml(clear: false);

        expect($capturedJs)
            ->not->toContain('window.testBufferedDependency = true;')
            ->and($head)->toContain('window.testBufferedDependency = true;')
            ->and($head)->toContain('/test-buffered-bundle.js');
    });

    it('does not re-emit a bundle already flushed earlier in the same request', function () {
        // A bundle should be emitted at most once per request. Multiple render
        // passes in one request (e.g. ElementIndexResource emits headHtml() for
        // both actions and the index) must not duplicate a shared bundle.
        $assets = app(InternalAssetRegistry::class);

        $assets->register(TestBufferedBundleAsset::class);
        $first = $this->registry->headHtml();

        $assets->register(TestBufferedBundleAsset::class);
        $second = $this->registry->headHtml();

        expect($first)
            ->toContain('window.testBufferedDependency = true;')
            ->and($second)->not->toContain('window.testBufferedDependency = true;');
    });

    it('does not double-emit a bundle across a captured fragment and the outer render', function () {
        // Regression: a bundle queued before capture() (flushed into the outer
        // render) and re-registered while the fragment renders must land in
        // exactly one of the fragment or the outer output, never both.
        $assets = app(InternalAssetRegistry::class);

        // Queued for the page before the fragment is captured.
        $assets->register(TestBufferedBundleAsset::class);

        $fragment = $this->registry->capture(function () use ($assets): string {
            // Fields re-register CpAsset (and friends) while their settings render.
            $assets->register(TestBufferedBundleAsset::class);

            return '<div class="field-settings"></div>';
        });

        $outerHead = $this->registry->headHtml();

        $inFragment = substr_count((string) $fragment->headHtml, '/test-buffered-bundle.js');
        $inOuter = substr_count($outerHead, '/test-buffered-bundle.js');

        expect($inFragment + $inOuter)->toBe(1);
    });
});

describe('icons', function () {
    it('deduplicates icon registrations', function () {
        $this->registry->icons(['heart', 'star']);
        $this->registry->icons(['heart', 'moon']);

        // We can't easily inspect internal state, but we can verify the icons
        // are rendered via bodyHtml. The bodyHtml method calls Cp::iconSvg
        // which requires the CP to be running, so we just verify the icons
        // property was populated correctly via a buffer capture.
        $this->registry->startBuffer('icons');
        $this->registry->icons(['heart', 'star']);
        $this->registry->icons(['heart', 'moon']);
        $captured = $this->registry->clearBuffer('icons');

        expect($captured)->toEqualCanonicalizing(['heart', 'star', 'moon'])
            ->and($captured)->toHaveCount(3);
    });
});

describe('buffer with head and body position separation', function () {
    it('buffers JS at both positions independently', function () {
        $this->registry->js('var headBefore = 1', Position::Head);
        $this->registry->js('var bodyBefore = 1', Position::BodyEnd);

        $this->registry->startBuffer('js');
        $this->registry->js('var headDuring = 1', Position::Head);
        $this->registry->js('var bodyDuring = 1', Position::BodyEnd);
        $captured = $this->registry->clearBuffer('js');

        // Both head and body positions captured
        expect($captured)->toHaveKey(Position::Head->value)
            ->and($captured)->toHaveKey(Position::BodyEnd->value);

        // Pre-buffer state restored
        expect($this->registry->headHtml())->toContain('headBefore')
            ->and($this->registry->bodyEndHtml())->toContain('bodyBefore');
    });

    it('buffers scripts at both positions', function () {
        $this->registry->startBuffer('scripts');
        $this->registry->script('headScript()', Position::Head);
        $this->registry->script('bodyScript()', Position::BodyEnd);
        $captured = $this->registry->clearBuffer('scripts');

        expect($captured)->toHaveKey(Position::Head->value)
            ->and($captured)->toHaveKey(Position::BodyEnd->value);
    });

    it('buffers jsFiles at both positions', function () {
        $this->registry->startBuffer('jsFiles');
        $this->registry->jsFile('/head.js', ['position' => Position::Head->value]);
        $this->registry->jsFile('/body.js');
        $captured = $this->registry->clearBuffer('jsFiles');

        expect($captured)->toHaveKey(Position::Head->value)
            ->and($captured)->toHaveKey(Position::BodyEnd->value);
    });

    it('buffers html at both positions', function () {
        $this->registry->startBuffer('html');
        $this->registry->html('<div>head</div>', Position::Head);
        $this->registry->html('<div>body</div>', Position::BodyEnd);
        $captured = $this->registry->clearBuffer('html');

        expect($captured)->toHaveKey(Position::Head->value)
            ->and($captured)->toHaveKey(Position::BodyEnd->value);
    });
});

describe('buffer round-trip integrity', function () {
    it('preserves content through start → register → clear → apply cycle', function () {
        $this->registry->js('var original = 1');
        $this->registry->css('.original {}');
        $this->registry->cssFile('/original.css');
        $this->registry->jsFile('/original.js');
        $this->registry->html('<div>original</div>');
        $this->registry->metaTag(['name' => 'original', 'content' => 'yes'], 'orig');
        $this->registry->jsImport('original', '/original-import.js');

        // Start buffer, register new content
        $this->registry->startBuffer(['js', 'css', 'cssFiles', 'jsFiles', 'html', 'metaTags', 'jsImports']);
        $this->registry->js('var buffered = 1');
        $this->registry->css('.buffered {}');
        $this->registry->cssFile('/buffered.css');
        $this->registry->jsFile('/buffered.js');
        $this->registry->html('<div>buffered</div>');
        $this->registry->metaTag(['name' => 'buffered', 'content' => 'yes'], 'buf');
        $this->registry->jsImport('buffered', '/buffered-import.js');

        $captured = $this->registry->clearBuffer(['js', 'css', 'cssFiles', 'jsFiles', 'html', 'metaTags', 'jsImports']);

        // Original state restored
        $head = $this->registry->headHtml(clear: false);
        $body = $this->registry->bodyEndHtml(clear: false);

        expect($body)->toContain('var original = 1;')
            ->and($head)->toContain('.original')
            ->and($head)->toContain('/original.css')
            ->and($body)->toContain('/original.js')
            ->and($body)->toContain('<div>original</div>')
            ->and($head)->toContain('name="original"')
            ->and($head)->toContain('"original"');

        // Apply buffered content
        $this->registry->applyBuffer($captured);

        $head = $this->registry->headHtml(clear: false);
        $body = $this->registry->bodyEndHtml(clear: false);

        expect($body)->toContain('var original = 1;')
            ->and($body)->toContain('var buffered = 1;')
            ->and($head)->toContain('.original')
            ->and($head)->toContain('.buffered')
            ->and($head)->toContain('/original.css')
            ->and($head)->toContain('/buffered.css');
    });
});

describe('deeply nested buffers', function () {
    it('correctly restores through three levels of nesting', function () {
        $this->registry->js('var level0 = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var level1 = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var level2 = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var level3 = 1');

        $level3 = $this->registry->clearBuffer('js');
        $level2 = $this->registry->clearBuffer('js');
        $level1 = $this->registry->clearBuffer('js');

        // Each level captured only its own content
        $l3Values = implode('', array_map(fn ($e) => implode('', $e), $level3));
        $l2Values = implode('', array_map(fn ($e) => implode('', $e), $level2));
        $l1Values = implode('', array_map(fn ($e) => implode('', $e), $level1));

        expect($l3Values)->toContain('level3')
            ->and($l3Values)->not->toContain('level2')
            ->and($l2Values)->toContain('level2')
            ->and($l2Values)->not->toContain('level1')
            ->and($l1Values)->toContain('level1')
            ->and($l1Values)->not->toContain('level0');

        // Level 0 is fully restored
        expect($this->registry->bodyEndHtml())->toContain('var level0 = 1;');
    });
});

describe('mixed buffer types at different nesting depths', function () {
    it('buffers JS and CSS at different nesting levels independently', function () {
        $this->registry->js('var jsOuter = 1');
        $this->registry->css('.cssOuter {}');

        // Start JS buffer (depth 1 for JS)
        $this->registry->startBuffer('js');
        $this->registry->js('var jsInner = 1');

        // Start CSS buffer (depth 1 for CSS, while JS is already at depth 1)
        $this->registry->startBuffer('css');
        $this->registry->css('.cssInner {}');
        $this->registry->js('var jsAlsoInner = 1');

        // Clear CSS buffer first
        $cssCaptured = $this->registry->clearBuffer('css');

        // Clear JS buffer second
        $jsCaptured = $this->registry->clearBuffer('js');

        // CSS captured only cssInner
        expect(implode('', $cssCaptured))->toContain('.cssInner');

        // JS captured both jsInner and jsAlsoInner (both registered during JS buffer)
        $jsValues = implode('', array_map(fn ($e) => implode('', $e), $jsCaptured));
        expect($jsValues)->toContain('jsInner')
            ->and($jsValues)->toContain('jsAlsoInner');

        // Originals restored
        expect($this->registry->bodyEndHtml())->toContain('var jsOuter = 1;')
            ->and($this->registry->headHtml())->toContain('.cssOuter');
    });
});

describe('startCssBuffer / clearCssBuffer', function () {
    it('captures and restores inline CSS', function () {
        $this->registry->css('.before {}');

        $this->registry->startCssBuffer();
        $this->registry->css('.during {}');
        $captured = $this->registry->clearCssBuffer();

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->headHtml())->toContain('.before')
            ->and($this->registry->headHtml())->not->toContain('.during');
    });
});

describe('startCssFileBuffer / clearCssFileBuffer', function () {
    it('captures and restores CSS file registrations', function () {
        $this->registry->cssFile('/before.css');

        $this->registry->startCssFileBuffer();
        $this->registry->cssFile('/during.css');
        $captured = $this->registry->clearCssFileBuffer();

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->headHtml())->toContain('/before.css')
            ->and($this->registry->headHtml())->not->toContain('/during.css');
    });
});

describe('startHtmlBuffer / clearHtmlBuffer', function () {
    it('captures and restores HTML registrations', function () {
        $this->registry->html('<div>before</div>');

        $this->registry->startHtmlBuffer();
        $this->registry->html('<div>during</div>');
        $captured = $this->registry->clearHtmlBuffer();

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->bodyEndHtml())->toContain('<div>before</div>')
            ->and($this->registry->bodyEndHtml())->not->toContain('<div>during</div>');
    });
});

describe('startJsBuffer / clearJsBuffer', function () {
    it('captures and restores inline JS', function () {
        $this->registry->js('var before = 1');

        $this->registry->startJsBuffer();
        $this->registry->js('var during = 1');
        $result = $this->registry->clearJsBuffer(scriptTag: false);

        expect($result)->toContain('var during = 1;')
            ->and($this->registry->bodyEndHtml())->toContain('var before = 1;')
            ->and($this->registry->bodyEndHtml())->not->toContain('var during = 1;');
    });
});

describe('startJsFileBuffer / clearJsFileBuffer', function () {
    it('captures and restores JS file registrations', function () {
        $this->registry->jsFile('/before.js');

        $this->registry->startJsFileBuffer();
        $this->registry->jsFile('/during.js');
        $captured = $this->registry->clearJsFileBuffer();

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->bodyEndHtml())->toContain('/before.js')
            ->and($this->registry->bodyEndHtml())->not->toContain('/during.js');
    });
});

describe('startJsImportBuffer / clearJsImportBuffer', function () {
    it('captures and restores JS import registrations', function () {
        $this->registry->jsImport('before', '/before.js');

        $this->registry->startJsImportBuffer();
        $this->registry->jsImport('during', '/during.js');
        $captured = $this->registry->clearJsImportBuffer();

        expect($captured)->toHaveKey('during')
            ->and($this->registry->headHtml())->toContain('"before"')
            ->and($this->registry->headHtml())->not->toContain('"during"');
    });
});

describe('startMetaTagBuffer / clearMetaTagBuffer', function () {
    it('captures and restores meta tag registrations', function () {
        $this->registry->metaTag(['name' => 'before', 'content' => 'yes'], 'before');

        $this->registry->startMetaTagBuffer();
        $this->registry->metaTag(['name' => 'during', 'content' => 'yes'], 'during');
        $captured = $this->registry->clearMetaTagBuffer();

        expect($captured)->toHaveKey('during')
            ->and($this->registry->headHtml())->toContain('name="before"')
            ->and($this->registry->headHtml())->not->toContain('name="during"');
    });
});

describe('startScriptBuffer / clearScriptBuffer', function () {
    it('captures and restores script registrations', function () {
        $this->registry->script('before()');

        $this->registry->startScriptBuffer();
        $this->registry->script('during()');
        $captured = $this->registry->clearScriptBuffer();

        expect($captured)->not->toBeEmpty()
            ->and($this->registry->bodyEndHtml())->toContain('before()')
            ->and($this->registry->bodyEndHtml())->not->toContain('during()');
    });
});

describe('clearJsBuffer', function () {
    it('returns combined JS wrapped in a script tag by default', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var x = 1');
        $this->registry->js('var y = 2');

        $result = $this->registry->clearJsBuffer();

        expect($result)->toBeString()
            ->and($result)->toContain('<script type="text/javascript">')
            ->and($result)->toContain('var x = 1;')
            ->and($result)->toContain('var y = 2;');
    });

    it('returns combined JS without script tag when scriptTag is false', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var x = 1');
        $this->registry->js('var y = 2');

        $result = $this->registry->clearJsBuffer(scriptTag: false);

        expect($result)->toBeString()
            ->and($result)->not->toContain('<script')
            ->and($result)->toContain('var x = 1;')
            ->and($result)->toContain('var y = 2;');
    });

    it('combines JS from both head and body positions', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var headJs = 1', Position::Head);
        $this->registry->js('var bodyJs = 2', Position::BodyEnd);

        $result = $this->registry->clearJsBuffer(scriptTag: false);

        expect($result)->toBeString()
            ->and($result)->toContain('var headJs = 1;')
            ->and($result)->toContain('var bodyJs = 2;');
    });

    it('returns position-keyed array when combine is false', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var headJs = 1', Position::Head);
        $this->registry->js('var bodyJs = 2', Position::BodyEnd);

        $result = $this->registry->clearJsBuffer(scriptTag: false, combine: false);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey(Position::Head->value)
            ->and($result)->toHaveKey(Position::BodyEnd->value)
            ->and($result[Position::Head->value])->toContain('var headJs = 1;')
            ->and($result[Position::BodyEnd->value])->toContain('var bodyJs = 2;');
    });

    it('wraps each position in script tags when combine is false and scriptTag is true', function () {
        $this->registry->startBuffer('js');
        $this->registry->js('var headJs = 1', Position::Head);
        $this->registry->js('var bodyJs = 2', Position::BodyEnd);

        $result = $this->registry->clearJsBuffer(scriptTag: true, combine: false);

        expect($result)->toBeArray()
            ->and($result[Position::Head->value])->toContain('<script type="text/javascript">')
            ->and($result[Position::Head->value])->toContain('var headJs = 1;')
            ->and($result[Position::BodyEnd->value])->toContain('<script type="text/javascript">')
            ->and($result[Position::BodyEnd->value])->toContain('var bodyJs = 2;');
    });

    it('returns an empty string when buffer has no JS', function () {
        $this->registry->startBuffer('js');

        $result = $this->registry->clearJsBuffer();

        expect($result)->toBe('');
    });

    it('restores previous JS state after clearing the buffer', function () {
        $this->registry->js('var outer = 1');

        $this->registry->startBuffer('js');
        $this->registry->js('var inner = 2');
        $this->registry->clearJsBuffer();

        $body = $this->registry->bodyEndHtml();

        expect($body)->toContain('var outer = 1;')
            ->and($body)->not->toContain('var inner = 2;');
    });
});
