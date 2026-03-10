<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->actionTrigger = 'actions';
    Cms::config()->pageTrigger = 'p';
    Cms::config()->tokenParam = 'token';
});

afterEach(function () {
    Context::forgetHidden(HandleTokenRequest::TOKEN_KEY);
});

describe('isCpRequest', function () {
    it('returns true for control panel requests', function () {
        expect(Request::create('/admin/settings')->isCpRequest())->toBeTrue();
    });

    it('returns false for site requests', function () {
        expect(Request::create('/news')->isCpRequest())->toBeFalse();
    });
});

describe('getToken', function () {
    it('returns the token from the query params', function () {
        expect(Request::create('/news?token=query-token')->getToken())->toBe('query-token');
    });

    it('falls back to the token header', function () {
        expect(Request::create('/news', server: [
            'HTTP_X_CRAFT_TOKEN' => 'header-token',
        ])->getToken())->toBe('header-token');
    });
});

describe('isSiteRequest', function () {
    it('returns true for site requests', function () {
        expect(Request::create('/news')->isSiteRequest())->toBeTrue();
    });

    it('returns false for control panel requests', function () {
        expect(Request::create('/admin/settings')->isSiteRequest())->toBeFalse();
    });
});

describe('actionSegments and isActionRequest', function () {
    it('extracts action segments from the path for site requests', function () {
        $request = Request::create('/actions/users/login');

        expect($request->actionSegments())->toBe(['users', 'login'])
            ->and($request->isActionRequest())->toBeTrue();
    });

    it('extracts action segments from the path for control panel requests', function () {
        $request = Request::create('/admin/actions/users/login');

        expect($request->actionSegments())->toBe(['users', 'login'])
            ->and($request->isActionRequest())->toBeTrue();
    });

    it('extracts action segments from the action query param', function () {
        $request = Request::create('/news?action=users/login');

        expect($request->actionSegments())->toBe(['users', 'login'])
            ->and($request->isActionRequest())->toBeTrue();
    });

    it('returns an empty array when there is no action request', function () {
        $request = Request::create('/news');

        expect($request->actionSegments())->toBe([])
            ->and($request->isActionRequest())->toBeFalse();
    });

    it('aborts when the action query param is not a string', function () {
        $request = Request::create('/news', 'GET', [
            'action' => ['users/login'],
        ]);

        expect(fn () => $request->actionSegments())
            ->toThrow(HttpException::class, 'Invalid action param');
    });
});

describe('actionSegmentsToRoute', function () {
    it('builds a site action route from the current request', function () {
        expect(Request::create('/actions/users/login')->actionSegmentsToRoute())
            ->toBe('/actions/users/login');
    });

    it('builds a control panel action route from the current request', function () {
        expect(Request::create('/admin/actions/users/login')->actionSegmentsToRoute())
            ->toBe('/admin/actions/users/login');
    });

    it('builds a route from explicit action segments', function () {
        expect(Request::create('/news')->actionSegmentsToRoute(['users', 'login']))
            ->toBe('/actions/users/login');
    });
});

describe('isPreview', function () {
    it('returns false when no preview token was provided', function () {
        expect(Request::create('/news')->isPreview())->toBeFalse();
    });

    it('returns false when the preview token cannot be decrypted', function () {
        expect(Request::create('/news?x-craft-preview=invalid')->isPreview())->toBeFalse();
    });

    it('returns false when there is no active token context', function () {
        $previewToken = Crypt::encrypt('preview-token');

        expect(Request::create("/news?x-craft-preview={$previewToken}")->isPreview())->toBeFalse();
    });

    it('returns true for a valid preview request with token context', function () {
        $previewToken = Crypt::encrypt('preview-token');
        Context::addHidden(HandleTokenRequest::TOKEN_KEY, 'route-token');

        expect(Request::create("/news?x-craft-preview={$previewToken}")->isPreview())->toBeTrue();
    });
});

describe('pageNumber', function () {
    it('returns 1 when the request is not paginated', function () {
        expect(Request::create('/news')->pageNumber())->toBe(1);
    });

    it('returns the page number for site pagination paths', function () {
        expect(Request::create('/news/p3')->pageNumber())->toBe(3);
    });

    it('returns the page number for control panel pagination paths', function () {
        expect(Request::create('/admin/news/p2')->pageNumber())->toBe(2);
    });

    it('returns the page number for query-string pagination', function () {
        Cms::config()->pageTrigger = '?page=';

        expect(Request::create('/news?page=4')->pageNumber())->toBe(4);
    });
});

describe('duplicateWithUri', function () {
    it('duplicates the request with a new uri and keeps query params by default', function () {
        $request = Request::create('/news?foo=bar');
        $duplicate = $request->duplicateWithUri('/entries');

        expect($duplicate->query())->toBe(['foo' => 'bar'])
            ->and($duplicate->server('REQUEST_URI'))->toBe('/entries');
    });

    it('duplicates the request with explicit query params and server overrides', function () {
        $request = Request::create('/news?foo=bar');
        $duplicate = $request->duplicateWithUri('/entries', ['page' => '2'], ['HTTP_HOST' => 'example.test']);

        expect($duplicate->query())->toBe(['page' => '2'])
            ->and($duplicate->server('REQUEST_URI'))->toBe('/entries')
            ->and($duplicate->server('HTTP_HOST'))->toBe('example.test');
    });
});

describe('getSigned', function () {
    it('returns the default value when the key is not present', function () {
        expect(Request::create('/news')->getSigned('missing', 'default'))->toBe('default');
    });

    it('decrypts signed request values', function () {
        $request = Request::create('/news', 'GET', [
            'payload' => Crypt::encrypt(['id' => 1]),
        ]);

        expect($request->getSigned('payload'))->toBe(['id' => 1]);
    });

    it('aborts when the signed value cannot be decrypted', function () {
        $request = Request::create('/news', 'GET', [
            'payload' => 'invalid',
        ]);

        expect(fn () => $request->getSigned('payload'))
            ->toThrow(HttpException::class, 'Request contained an invalid body param');
    });
});
