<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Routing\ActionRoute;
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
    Context::forgetHidden(HandleTokenRequest::HAD_TOKEN_KEY);
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

describe('getHadToken', function () {
    it('returns true when the current request has a token', function () {
        expect(Request::create('/news?token=query-token')->getHadToken())->toBeTrue();
    });

    it('returns true when a token was previously handled', function () {
        Context::addHidden(HandleTokenRequest::HAD_TOKEN_KEY, true);

        expect(Request::create('/news')->getHadToken())->toBeTrue();
    });

    it('returns false when no token is present or has been handled', function () {
        expect(Request::create('/news')->getHadToken())->toBeFalse();
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

describe('craftPath', function () {
    it('returns the trimmed site request path', function () {
        expect(Request::create('/news/latest')->craftPath())->toBe('news/latest');
    });

    it('normalizes repeated slashes', function () {
        expect(Request::create('/news//latest')->craftPath())->toBe('news/latest');
    });

    it('returns the control panel path without the control panel trigger', function () {
        expect(Request::create('/admin/assets')->craftPath())->toBe('assets');
    });

    it('returns an empty string for the control panel root', function () {
        expect(Request::create('/admin')->craftPath())->toBe('');
    });

    it('returns site action paths without the action trigger', function () {
        expect(Request::create('/actions/users/login')->craftPath())->toBe('users/login');
    });

    it('returns control panel action paths without the control panel or action triggers', function () {
        expect(Request::create('/admin/actions/users/login')->craftPath())->toBe('users/login');
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

    it('returns an empty string when the current request is not an action request', function () {
        expect(Request::create('/news')->actionSegmentsToRoute())
            ->toBe('');
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

    it('preserves the session on the duplicated request', function () {
        $request = Request::create('/news?foo=bar');
        $request->setLaravelSession(session()->driver());

        $duplicate = $request->duplicateWithUri('/entries');

        expect($duplicate->hasSession())->toBeTrue()
            ->and($duplicate->session())->toBe($request->session());
    });

    it('does not preserve resolved action routes on the duplicated request', function () {
        $request = Request::create('/actions/users/login');
        $request->attributes->set(ActionRoute::class, ActionRoute::fromSegments(['users', 'login'], false));

        $duplicate = $request->duplicateWithUri('/entries');

        expect($duplicate->attributes->has(ActionRoute::class))->toBeFalse();
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
