<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\URL;

beforeEach(function () {
    Aliases::set('@web', 'https://localhost');

    swapUrlRequest('https://localhost/news');
});

describe('buildQuery', function () {
    test('builds query strings', function (string $expected, array $params) {
        expect(URL::buildQuery($params))->toBe($expected);
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
        ['foo=bar%5Bbaz%5D', ['foo' => 'bar[baz]']],
        ['foo={bar}', ['foo' => '{bar}']],
        ['foo%5B1%5D=bar', ['foo[1]' => 'bar']],
        ['foo%5B1%5D%5Bbar%5D=1&foo%5B1%5D%5Bbaz%5D=2', ['foo[1][bar]' => 1, 'foo[1][baz]' => 2]],
    ]);
});

describe('URL detection', function () {
    test('detects absolute URLs', function (bool $expected, string $url) {
        expect(URL::isAbsoluteUrl($url))->toBe($expected);
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

    test('detects full URLs', function (bool $expected, string $url) {
        expect(URL::isFullUrl($url))->toBe($expected);
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
        expect(URL::isRootRelativeUrl($url))->toBe($expected);
    })->with([
        [true, '/22'],
        [false, '//cdn.craftcms.com/22'],
        [false, 'https://www.craftcms.com/'],
        [false, '?p=test'],
    ]);
});

describe('query and encoding helpers', function () {
    test('adds query params to a URL', function (string $expected, string $url, array|string $params) {
        expect(URL::urlWithParams($url, $params))->toBe($expected);
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
            'https://www.craftcms.com/?param1=some/path',
            'https://www.craftcms.com/',
            '?param1=some/path',
        ],
        'pre-queried-url' => [
            'https://www.craftcms.com/?param3=name3&param1=name&param2=name2',
            'https://www.craftcms.com/?param3=name3',
            '?param1=name&param2=name2',
        ],
    ]);

    test('adds token params to a URL', function (string $expected, string $url, string $token) {
        Cms::config()->useSslOnTokenizedUrls = true;

        expect(URL::urlWithToken($url, $token))->toBe($expected);
    })->with([
        ['https://craftcms.com/?token=value', 'https://craftcms.com/', 'value'],
        ['https://craftcms.com/?token=value2', 'https://craftcms.com/?token=value1', 'value2'],
        ['https://craftcms.com/?token', 'https://craftcms.com/', ''],
        'scheme-is-overridden' => ['https://craftcms.com/?token=value', 'http://craftcms.com/', 'value'],
        'non-url-string' => ['craft?token=value', 'craft', 'value'],
    ]);

    test('replaces URL schemes', function (string $expected, string $url, string $scheme) {
        expect(URL::urlWithScheme($url, $scheme))->toBe($expected);
    })->with([
        'no-scheme' => ['imaurl', 'imaurl', ''],
        'empty-string' => ['', '', ''],
        'protocol-relative' => ['https://cdn.craftcms.com', '//cdn.craftcms.com', 'https'],
        'php-replace' => ['php://www.craftcms.com/', 'https://www.craftcms.com/', 'php'],
        'ftp-replace' => ['ftp://craftcms.com/', 'https://craftcms.com/', 'ftp'],
        'custom-protocol' => ['walawalabingbang://craftcms.com/', 'http://craftcms.com/', 'walawalabingbang'],
        'no-change-needed' => ['https://craftcms.com/', 'https://craftcms.com/', 'https'],
        'sftp' => ['sftp://www.craftcms.com/', 'https://www.craftcms.com/', 'sftp'],
    ]);

    test('strips query strings', function (string $expected, string $url) {
        expect(URL::stripQueryString($url))->toBe($expected);
    })->with([
        'invalid-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/&query=string'],
        'no-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/'],
        'single-query-string' => ['https://www.craftcms.com/', 'https://www.craftcms.com/?param1=entry1'],
        'multiple-question-marks' => ['https://www.craftcms.com/', 'https://www.craftcms.com/?param1=entry1?param2=entry2'],
    ]);

    test('encodes query params', function (string $expected, string $url) {
        expect(URL::encodeParams($url))->toBe($expected);
    })->with([
        ['http://example.test', 'http://example.test?'],
        ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar baz'],
        ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar+baz'],
        ['http://example.test?foo=bar+baz#hash', 'http://example.test?foo=bar baz#hash'],
        ['http://example.test?foo=bar%2Bbaz#hash', 'http://example.test?foo=bar%2Bbaz#hash'],
    ]);

    test('encodes URLs', function (string $expected, string $url) {
        expect(URL::encodeUrl($url))->toBe($expected);
    })->with([
        ['https://domain/fr/offices/gen%C3%AAve', rawurldecode('https://domain/fr/offices/gen%C3%AAve')],
        ['https://domain/fr/offices/gen%C3%AAve?foo=bar', rawurldecode('https://domain/fr/offices/gen%C3%AAve').'?foo=bar'],
        ['https://domain/fr/offices/gen%C3%AAve?foo=bar', 'https://domain/fr/offices/gen%C3%AAve?foo=bar'],
        ['foo+bar', 'foo bar'],
    ]);
});

describe('path helpers', function () {
    test('creates root-relative URLs', function (string $expected, string $url) {
        expect(URL::rootRelativeUrl($url))->toBe($expected);
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
        ['/', '//test.com'],
        ['/', '//test.com/'],
        ['/foo/bar', '//test.com/foo/bar'],
    ]);

    test('extracts host info', function (string $expected, string $url) {
        expect(URL::hostInfo($url))->toBe($expected);
    })->with([
        ['https://google.com', 'https://google.com'],
        ['http://facebook.com', 'http://facebook.com'],
        ['ftp://www.craftcms.com', 'ftp://www.craftcms.com/why/craft/is/cool/'],
        ['walawalabingbang://gt.com', 'walawalabingbang://gt.com/'],
        ['sftp://volkswagen', 'sftp://volkswagen////222////222'],
    ]);

    it('uses the configured tokenized URL scheme', function () {
        Cms::config()->useSslOnTokenizedUrls = true;
        expect(URL::getSchemeForTokenizedUrl())->toBe('https');

        Cms::config()->useSslOnTokenizedUrls = false;
        expect(URL::getSchemeForTokenizedUrl())->toBe('http');
    });
});

describe('generated URLs', function () {
    test('creates control panel URLs', function (string $expected, string $path, array $params, string $scheme) {
        swapUrlRequest('https://localhost/admin/dashboard');

        expect(URL::cpUrl($path, $params, $scheme))->toBe(buildExpectedUrl($expected, $scheme))
            ->and(URL::url($path, $params, $scheme))->toBe(buildExpectedUrl($expected, $scheme));
    })->with([
        'empty-path' => ['{cpUrl}', '', [], 'https'],
        'with-params' => ['{cpUrl}/nav?param1=entry1&param2=entry2', 'nav', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
        'preserves-query-string' => ['{cpUrl}/nav?param3=entry3&param1=entry1&param2=entry2', 'nav?param3=entry3', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
        'absolute-site-url' => ['{siteUrl}?param1=entry1&param2=entry2', 'https://localhost/', ['param1' => 'entry1', 'param2' => 'entry2'], 'https'],
    ]);

    test('creates site and absolute URLs', function (string $expected, string $path, ?array $params, string $scheme, ?bool $showScriptName) {
        swapUrlRequest('https://localhost/news');

        expect(URL::url($path, $params, $scheme, $showScriptName))
            ->toBe(buildExpectedUrl($expected, $scheme));
    })->with([
        ['{siteUrl}endpoint', 'endpoint', null, 'https', null],
        ['https://craftcms.com/', 'http://craftcms.com/', null, 'https', null],
        ['https://craftcms.com/?param1=entry1&param2=entry2', 'http://craftcms.com/', ['param1' => 'entry1', 'param2' => 'entry2'], 'https', null],
    ]);

    test('creates site URLs', function (string $expected, string $path, array|string|null $params, string $scheme, ?int $siteId) {
        expect(URL::siteUrl($path, $params, $scheme, $siteId))
            ->toBe(buildExpectedUrl($expected, $scheme));
    })->with([
        ['{siteUrl}endpoint', 'endpoint', null, 'https', null],
        ['{siteUrl}endpoint?param1=x&param2%5B0%5D=y&param2%5B1%5D=z', 'endpoint', 'param1=x&param2[]=y&param2[]=z', 'https', null],
    ]);

    it('creates action URLs', function () {
        swapUrlRequest('https://localhost/news');

        expect(URL::actionUrl('endpoint', null, null, false))
            ->toBe('https://localhost/actions/endpoint');
    });

    it('throws for invalid site IDs', function () {
        expect(fn () => URL::siteUrl('', null, null, 12892))
            ->toThrow(Exception::class, 'Invalid site ID: 12892');
    });
});

it('appends active route tokens to site URLs', function () {
    $token = str_repeat('a', 32);

    app(RouteTokens::class)->createToken('token/route', 2, token: $token);
    swapUrlRequest('https://localhost/news', parameters: [Cms::config()->tokenParam => $token]);

    expect(URL::url('endpoint'))->toBe("https://localhost/endpoint?token=$token")
        ->and(URL::siteUrl('endpoint'))->toBe("https://localhost/endpoint?token=$token");
});
