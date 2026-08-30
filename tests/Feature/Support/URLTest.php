<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Url;

beforeEach(function () {
    swapUrlRequest('https://localhost/news');
});

describe('buildQuery', function () {
    test('builds query strings', function (string $expected, array $params) {
        expect(Url::buildQuery($params))->toBe($expected);
    })->with([
        ['', []],
        ['', ['foo' => null]],
        ['foo', ['foo' => '']],
        ['foo=0', ['foo' => false]],
        ['foo=1', ['foo' => true]],
        ['foo=1&bar=2', ['foo' => 1, 'bar' => 2]],
        ['foo%5B0%5D=1&foo%5B1%5D=2', ['foo' => [1, 2]]],
        ['foo%5Bbar%5D=baz', ['foo[bar]' => 'baz']],
        ['foo%5Bbar%5D=baz', ['foo' => ['bar' => 'baz']]],
        ['foo=bar%2Bbaz', ['foo' => 'bar+baz']],
        ['foo%2Bbar=baz', ['foo+bar' => 'baz']],
        ['foo%2Fbar=baz', ['foo/bar' => 'baz']],
        ['foo%5B{bar}%5D=baz', ['foo[{bar}]' => 'baz']],
        ['foo=bar%5Bbaz%5D', ['foo' => 'bar[baz]']],
        ['foo={bar}', ['foo' => '{bar}']],
        ['foo=some%2Fpath%2F{bar}', ['foo' => 'some/path/{bar}']],
        ['returnUrl=https%3A%2F%2Fexample.test%2Fpath%2F{id}%3Ffoo%3Dbar', ['returnUrl' => 'https://example.test/path/{id}?foo=bar']],
        ['foo%5B1%5D=bar', ['foo[1]' => 'bar']],
        ['foo%5B1%5D%5Bbar%5D=1&foo%5B1%5D%5Bbaz%5D=2', ['foo[1][bar]' => 1, 'foo[1][baz]' => 2]],
    ]);
});

describe('URL detection', function () {
    test('detects absolute URLs', function (bool $expected, string $url) {
        expect(Url::isAbsoluteUrl($url))->toBe($expected);
    })->with([
        [true, 'http://craftcms.com/'],
        [true, 'https://craftcms.com/'],
        [true, 'https://www.craftcms.com/'],
        [true, 'http://www.craftcms.com/'],
        [false, 'craftcms.com/'],
        [false, 'www.craftcms.com/'],
        [true, 'mailto:test@abc.com'],
        [true, 'tel:+10123456789'],
        [false, 'C:'],
        [false, 'C:\foo\bar.txt'],
    ]);

    test('detects protocol-relative URLs', function (bool $expected, string $url) {
        expect(Url::isProtocolRelativeUrl($url))->toBe($expected);
    })->with([
        'protocol-relative' => [true, '//cdn.craftcms.com/assets/app.css'],
        'root-relative' => [false, '/assets/app.css'],
        'absolute' => [false, 'https://cdn.craftcms.com/assets/app.css'],
        'relative' => [false, 'assets/app.css'],
    ]);

    test('detects full URLs', function (bool $expected, string $url) {
        expect(Url::isFullUrl($url))->toBe($expected);
    })->with([
        [true, 'http://craftcms.com/'],
        [true, 'https://craftcms.com/'],
        [true, 'https://www.craftcms.com/'],
        [true, 'http://www.craftcms.com/'],
        [true, '/22'],
        [true, '//craftcms.com/'],
        [false, mb_chr(0x1F600).mb_chr(0x1F618)],
        [false, '!@#$%^&*()<>'],
        [false, 'hello'],
        [false, 'craftcms.com/'],
        [false, 'www.craftcms.com/'],
    ]);

    test('detects root-relative URLs', function (bool $expected, string $url) {
        expect(Url::isRootRelativeUrl($url))->toBe($expected);
    })->with([
        [true, '/22'],
        [false, '//cdn.craftcms.com/22'],
        [false, 'https://www.craftcms.com/'],
        [false, '?page=test'],
    ]);
});

describe('query and encoding helpers', function () {
    test('adds query params to a URL', function (string $expected, string $url, array|string $params) {
        expect(Url::urlWithParams($url, $params))->toBe($expected);
    })->with([
        'with-fragment' => [
            'https://craftcms.com/?param1=entry1#some-hashtag',
            'https://craftcms.com/',
            ['param1' => 'entry1', '#' => 'some-hashtag'],
        ],
        'anchor-gets-kept' => [
            'https://craftcms.com/?param1=entry1&param2=entry2#anchor',
            'https://craftcms.com/#anchor',
            'param1=entry1&param2=entry2',
        ],
        'previous-param-gets-kept' => [
            'https://www.craftcms.com/?param3=entry3&param1=entry1&param2=entry2#anchor',
            'https://www.craftcms.com/?param3=entry3#anchor',
            '?param1=entry1&param2=entry2',
        ],
        'fragment-param' => [
            'https://www.craftcms.com/?param1=value&param2=value2#anchor',
            'https://www.craftcms.com/',
            ['param1' => 'value', 'param2' => 'value2', '#' => 'anchor'],
        ],
        'basic-array' => [
            'https://www.craftcms.com/?param1=value&param2=value2',
            'https://www.craftcms.com/',
            ['param1' => 'value', 'param2' => 'value2'],
        ],
        'empty-array' => [
            'https://www.craftcms.com/',
            'https://www.craftcms.com/',
            [],
        ],
        'spaces-only-string' => [
            'https://www.craftcms.com/',
            'https://www.craftcms.com/',
            '    ',
        ],
        'numerical-index-array' => [
            'https://www.craftcms.com/?0=someparam',
            'https://www.craftcms.com/',
            ['someparam'],
        ],
        'query-string' => [
            'https://www.craftcms.com/?param1=value1&param2=value2',
            'https://www.craftcms.com/',
            '?param1=value1&param2=value2',
        ],
        'query-string-with-token' => [
            'https://www.craftcms.com/?param1={value}',
            'https://www.craftcms.com/',
            '?param1={value}',
        ],
        'query-string-with-token-name' => [
            'https://www.craftcms.com/?param1%5B{key}%5D={value}',
            'https://www.craftcms.com/',
            '?param1[{key}]={value}',
        ],
        'query-string-with-array' => [
            'https://www.craftcms.com/?param1%5Bkey%5D={value}&param1%5Bkey2%5D=value2&param2%5Bkey%5D={value3}',
            'https://www.craftcms.com/',
            '?param1[key]={value}&param1[key2]=value2&param2[key]={value3}',
        ],
        'query-string-with-unindexed-array' => [
            'https://www.craftcms.com/?param1%5B0%5D=value1&param1%5B1%5D=value2',
            'https://www.craftcms.com/',
            '?param1[]=value1&param1[]=value2',
        ],
        'query-string-with-forward-slash' => [
            'https://www.craftcms.com/?param1=some%2Fpath',
            'https://www.craftcms.com/',
            '?param1=some/path',
        ],
        'query-string-with-token-and-forward-slash' => [
            'https://www.craftcms.com/?param1=some%2Fpath%2F{value}',
            'https://www.craftcms.com/',
            '?param1=some/path/{value}',
        ],
        'return-url-with-query-string' => [
            'https://www.craftcms.com/?returnUrl=https%3A%2F%2Fexample.test%2Fadmin%2Fcontent%2Fentries%2Fsingles%3Fsource%3Dsingles',
            'https://www.craftcms.com/',
            ['returnUrl' => 'https://example.test/admin/content/entries/singles?source=singles'],
        ],
        'query-string-with-fragment' => [
            'https://www.craftcms.com/?param1=value#anchor',
            'https://www.craftcms.com/',
            '?param1=value#anchor',
        ],
        'pre-queried-url' => [
            'https://www.craftcms.com/?param3=name3&param1=name&param2=name2',
            'https://www.craftcms.com/?param3=name3',
            '?param1=name&param2=name2',
        ],
        'preserves-existing-site-param' => [
            'https://localhost/admin/content/entries/blog/4?site=defaultSite&draftId=3&fresh=1',
            'https://localhost/admin/content/entries/blog/4?site=defaultSite&draftId=3',
            ['fresh' => 1],
        ],
        'preserves-existing-version-param' => [
            'https://localhost/cpresources/hash/app.css?v=123&buildId=456',
            'https://localhost/cpresources/hash/app.css?v=123',
            ['buildId' => 456],
        ],
    ]);

    test('removes query params from a URL', function (string $expected, string $url, string $param) {
        expect(Url::removeParam($url, $param))->toBe($expected);
    })->with([
        'removes-middle-param' => [
            'https://craftcms.com/?bar=2#anchor',
            'https://craftcms.com/?foo=1&bar=2#anchor',
            'foo',
        ],
        'removes-last-param' => [
            'https://craftcms.com/#anchor',
            'https://craftcms.com/?foo=1#anchor',
            'foo',
        ],
        'keeps-url-when-param-is-missing' => [
            'https://craftcms.com/?foo=1#anchor',
            'https://craftcms.com/?foo=1#anchor',
            'bar',
        ],
    ]);

    test('removes multiple query params from a URL', function (string $expected, string $url, array $params) {
        expect(Url::removeParams($url, $params))->toBe($expected);
    })->with([
        'removes-multiple' => [
            'https://craftcms.com/?bar=2#anchor',
            'https://craftcms.com/?foo=1&bar=2&baz=3#anchor',
            ['foo', 'baz'],
        ],
        'removes-all-listed' => [
            'https://craftcms.com/#anchor',
            'https://craftcms.com/?foo=1&bar=2#anchor',
            ['foo', 'bar'],
        ],
        'keeps-url-when-params-are-missing' => [
            'https://craftcms.com/?foo=1',
            'https://craftcms.com/?foo=1',
            ['bar', 'baz'],
        ],
        'empty-params' => [
            'https://craftcms.com/?foo=1',
            'https://craftcms.com/?foo=1',
            [],
        ],
    ]);

    test('removes all query params from a URL', function (string $expected, string $url, array $except) {
        expect(Url::removeAllParams($url, $except))->toBe($expected);
    })->with([
        'removes-all' => [
            'https://craftcms.com/',
            'https://craftcms.com/?foo=1&bar=2',
            [],
        ],
        'keeps-fragment' => [
            'https://craftcms.com/#anchor',
            'https://craftcms.com/?foo=1&bar=2#anchor',
            [],
        ],
        'except' => [
            'https://craftcms.com/?bar=2',
            'https://craftcms.com/?foo=1&bar=2&baz=3',
            ['bar'],
        ],
        'except-multiple' => [
            'https://craftcms.com/?foo=1&baz=3',
            'https://craftcms.com/?foo=1&bar=2&baz=3',
            ['foo', 'baz'],
        ],
        'except-missing' => [
            'https://craftcms.com/',
            'https://craftcms.com/?foo=1&bar=2',
            ['baz'],
        ],
    ]);

    test('adds token params to a URL', function (string $expected, string $url, string $token) {
        Cms::config()->useSslOnTokenizedUrls = true;

        expect(Url::urlWithToken($url, $token))->toBe($expected);
    })->with([
        ['https://craftcms.com/?token=value', 'https://craftcms.com/', 'value'],
        ['https://craftcms.com/?token=value2', 'https://craftcms.com/?token=value1', 'value2'],
        ['https://craftcms.com/?token', 'https://craftcms.com/', ''],
        'scheme-is-overridden' => ['https://craftcms.com/?token=value', 'http://craftcms.com/', 'value'],
        'non-url-string' => ['craft?token=value', 'craft', 'value'],
    ]);

    test('replaces URL schemes', function (string $expected, string $url, string $scheme) {
        expect(Url::urlWithScheme($url, $scheme))->toBe($expected);
    })->with([
        'no-scheme' => ['imaurl', 'imaurl', ''],
        'empty-string' => ['', '', ''],
        'protocol-relative' => ['https://cdn.craftcms.com', '//cdn.craftcms.com', 'https'],
        'root-relative' => ['https://localhost/news', '/news', 'https'],
        'php-replace' => ['php://www.craftcms.com/', 'https://www.craftcms.com/', 'php'],
        'ftp-replace' => ['ftp://craftcms.com/', 'https://craftcms.com/', 'ftp'],
        'custom-protocol' => ['walawalabingbang://craftcms.com/', 'http://craftcms.com/', 'walawalabingbang'],
        'no-change-needed' => ['https://craftcms.com/', 'https://craftcms.com/', 'https'],
        'sftp' => ['sftp://www.craftcms.com/', 'https://www.craftcms.com/', 'sftp'],
    ]);

    test('strips query strings', function (string $expected, string $url) {
        expect(Url::stripQueryString($url))->toBe($expected);
    })->with([
        'invalid-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/&query=string'],
        'no-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/'],
        'single-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/?param1=entry1'],
        'multiple-question-marks' => ['https://www.craftcms.com/', 'https://www.craftcms.com/?param1=entry1?param2=entry2'],
    ]);

    test('encodes query params', function (string $expected, string $url) {
        expect(Url::encodeParams($url))->toBe($expected);
    })->with([
        ['http://example.test', 'http://example.test?'],
        ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar baz'],
        ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar+baz'],
        ['http://example.test?foo=bar+baz#hash', 'http://example.test?foo=bar baz#hash'],
        ['http://example.test?foo=bar%2Bbaz#hash', 'http://example.test?foo=bar%2Bbaz#hash'],
        ['http://example.test?foo=some%2Fpath%2F{token}', 'http://example.test?foo=some/path/{token}'],
        ['http://example.test?returnUrl=https%3A%2F%2Fexample.test%2Fadmin%2Fentries%3Fsite%3D{handle}', 'http://example.test?returnUrl=https://example.test/admin/entries?site={handle}'],
    ]);

    test('encodes URLs', function (string $expected, string $url) {
        expect(Url::encodeUrl($url))->toBe($expected);
    })->with([
        ['https://domain/fr/offices/gen%C3%AAve', rawurldecode('https://domain/fr/offices/gen%C3%AAve')],
        ['https://domain/fr/offices/gen%C3%AAve?foo=bar', rawurldecode('https://domain/fr/offices/gen%C3%AAve').'?foo=bar'],
        ['https://domain/fr/offices/gen%C3%AAve?foo=bar', 'https://domain/fr/offices/gen%C3%AAve?foo=bar'],
        ['foo+bar', 'foo bar'],
    ]);
});

describe('path helpers', function () {
    test('creates root-relative URLs', function (string $expected, string $url) {
        expect(Url::rootRelativeUrl($url))->toBe($expected);
    })->with([
        ['/', ''],
        ['/foo/bar', 'foo/bar'],
        ['/', '/'],
        ['/foo/bar', '/foo/bar'],
        ['/', 'http://test.com'],
        ['/', 'http://test.com/'],
        ['/foo/bar', 'http://test.com/foo/bar'],
        ['/', 'https://test.com'],
        ['/', 'https://test.com/'],
        ['/foo/bar', 'https://test.com/foo/bar'],
        ['/foo/bar', 'https://test.com/foo/bar?query=value#anchor'],
        ['/', '//test.com'],
        ['/', '//test.com/'],
        ['/foo/bar', '//test.com/foo/bar'],
    ]);

    test('extracts host info', function (string $expected, string $url) {
        expect(Url::hostInfo($url))->toBe($expected);
    })->with([
        ['https://google.com', 'https://google.com'],
        ['http://facebook.com', 'http://facebook.com'],
        ['ftp://www.craftcms.com', 'ftp://www.craftcms.com/why/craft/is/cool/'],
        ['//cdn.craftcms.com', '//cdn.craftcms.com/assets/app.js'],
        ['walawalabingbang://gt.com', 'walawalabingbang://gt.com/'],
        ['sftp://volkswagen', 'sftp://volkswagen////222////222'],
    ]);

    it('uses the configured tokenized URL scheme', function () {
        Cms::config()->useSslOnTokenizedUrls = true;
        expect(Url::getSchemeForTokenizedUrl())->toBe('https');

        Cms::config()->useSslOnTokenizedUrls = false;
        expect(Url::getSchemeForTokenizedUrl())->toBe('http');

        Cms::config()->useSslOnTokenizedUrls = 'auto';
        Cms::config()->baseCpUrl = 'https://cms.example.test';
        expect(Url::getSchemeForTokenizedUrl(cp: true))->toBe('https');

        Cms::config()->baseCpUrl = 'http://cms.example.test';
        expect(Url::getSchemeForTokenizedUrl(cp: true))->toBe('http');
    });
});

describe('base and control panel helpers', function () {
    it('returns base URLs and hosts', function () {
        swapUrlRequest('https://localhost/news');

        expect(Url::baseUrl())->toBe('https://localhost/')
            ->and(Url::baseSiteUrl())->toBe('https://localhost/')
            ->and(Url::baseCpUrl())->toBe('https://localhost/')
            ->and(Url::host())->toBe('https://localhost')
            ->and(Url::siteHost())->toBe('https://localhost');

        Cms::config()->baseCpUrl = 'https://cms.example.test';
        swapUrlRequest('https://localhost/admin/dashboard');

        expect(Url::baseUrl())->toBe('https://cms.example.test/')
            ->and(Url::baseCpUrl())->toBe('https://cms.example.test/')
            ->and(Url::host())->toBe('https://cms.example.test')
            ->and(Url::cpHost())->toBe('https://cms.example.test');
    });

    test('prepends the control panel trigger', function (string $expected, ?string $cpTrigger, string $path) {
        Cms::config()->cpTrigger = $cpTrigger;

        expect(Url::prependCpTrigger($path))->toBe($expected);
    })->with([
        'default-trigger' => ['admin/settings', 'admin', 'settings'],
        'empty-path' => ['admin', 'admin', ''],
        'missing-trigger' => ['settings', null, 'settings'],
        'existing-trigger' => ['admin/settings', 'admin', 'admin/settings'],
        'similar-prefix' => ['admin/administrator/settings', 'admin', 'administrator/settings'],
    ]);
});

describe('generated URLs', function () {
    test('creates control panel URLs', function (string $expected, string $path, array $params, string $scheme) {
        swapUrlRequest('https://localhost/admin/dashboard');

        expect(Url::cpUrl($path, $params, $scheme))->toBe(buildExpectedUrl($expected, $scheme))
            ->and(Url::url($path, $params, $scheme))->toBe(buildExpectedUrl($expected, $scheme));
    })->with([
        'empty-path' => ['{cpUrl}', '', [], 'https'],
        'with-params' => ['{cpUrl}/nav?param1=entry1&param2=entry2', 'nav', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
        'with-token-and-forward-slash-params' => ['{cpUrl}/nav?redirect=some%2Fpath%2F{id}&site={handle}', 'nav', ['redirect' => 'some/path/{id}', 'site' => '{handle}'], 'https'],
        'with-return-url-param' => ['{cpUrl}/nav?returnUrl=https%3A%2F%2Fexample.test%2Fadmin%2Fentries%3Fsite%3D{handle}', 'nav', ['returnUrl' => 'https://example.test/admin/entries?site={handle}'], 'https'],
        'with-trigger' => ['{cpUrl}/login', 'admin/login', [], 'https'],
        'preserves-query-string' => ['{cpUrl}/nav?param3=entry3&param1=entry1&param2=entry2', 'nav?param3=entry3', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
        'absolute-site-url' => ['{siteUrl}?param1=entry1&param2=entry2', 'https://localhost/', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
    ]);

    test('creates site and absolute URLs', function (string $expected, string $path, ?array $params, ?string $scheme) {
        swapUrlRequest('https://localhost/news');

        expect(Url::url($path, $params, $scheme))
            ->toBe(buildExpectedUrl($expected, $scheme ?? 'https'));
    })->with([
        'base' => ['{siteUrl}endpoint', 'endpoint', null, null],
        'full-url-scheme' => ['https://craftcms.com/', 'http://craftcms.com/', null, 'https'],
        'scheme-override-param-add' => ['https://craftcms.com/?param1=entry1&param2=entry2', 'http://craftcms.com/', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
        'token-and-forward-slash-param-add' => ['http://craftcms.com/?redirect=some%2Fpath%2F{id}', 'http://craftcms.com/', ['redirect' => 'some/path/{id}'], null],
        'return-url-param-add' => ['http://craftcms.com/?returnUrl=https%3A%2F%2Fexample.test%2Fadmin%2Fentries%3Fsite%3D{handle}', 'http://craftcms.com/', ['returnUrl' => 'https://example.test/admin/entries?site={handle}'], null],
    ]);

    test('creates site URLs', function (string $expected, string $path, array|string|null $params, string $scheme, ?int $siteId) {
        expect(Url::siteUrl($path, $params, $scheme, $siteId))
            ->toBe(buildExpectedUrl($expected, $scheme));
    })->with([
        ['{siteUrl}endpoint', 'endpoint', null, 'https', null],
        ['{siteUrl}endpoint?param1=x&param2%5B0%5D=y&param2%5B1%5D=z', 'endpoint', 'param1=x&param2[]=y&param2[]=z', 'https', null],
        ['{siteUrl}endpoint?redirect=some%2Fpath%2F{id}', 'endpoint', ['redirect' => 'some/path/{id}'], 'https', null],
        ['{siteUrl}endpoint?returnUrl=https%3A%2F%2Fexample.test%2Fadmin%2Fentries%3Fsite%3D{handle}', 'endpoint', ['returnUrl' => 'https://example.test/admin/entries?site={handle}'], 'https', null],
    ]);

    it('removes all params from a full URL when params is false', function () {
        swapUrlRequest('https://localhost/news');

        expect(Url::url('https://craftcms.com/?x-craft-preview=foo&test=bar', false))
            ->toBe('https://craftcms.com/');
    });

    it('creates action URLs', function () {
        swapUrlRequest('https://localhost/news');

        expect(Url::actionUrl('endpoint'))
            ->toBe('https://localhost/actions/endpoint');
    });

    it('creates control panel action URLs', function () {
        swapUrlRequest('https://localhost/admin/dashboard');

        expect(Url::actionUrl('endpoint'))
            ->toBe(buildExpectedUrl('{cpUrl}/actions/endpoint', 'https'));
    });

    it('throws for invalid site IDs', function () {
        expect(fn () => Url::siteUrl('', null, null, 12892))
            ->toThrow(Exception::class, 'Invalid site ID: 12892');
    });
});

it('appends active site tokens to site URLs', function () {
    $siteToken = 'site-token';
    $siteTokenParam = Cms::config()->siteToken;

    swapUrlRequest('https://localhost/news', parameters: [$siteTokenParam => $siteToken]);

    expect(Url::url('endpoint'))->toBe("https://localhost/endpoint?$siteTokenParam=$siteToken")
        ->and(Url::siteUrl('endpoint'))->toBe("https://localhost/endpoint?$siteTokenParam=$siteToken");
});

it('appends active route tokens to site URLs', function () {
    $token = str_repeat('a', 32);

    app(RouteTokens::class)->createToken('token/route', 2, token: $token);
    swapUrlRequest('https://localhost/news', parameters: [Cms::config()->tokenParam => $token]);

    expect(Url::url('endpoint'))->toBe("https://localhost/endpoint?token=$token")
        ->and(Url::siteUrl('endpoint'))->toBe("https://localhost/endpoint?token=$token");
});
