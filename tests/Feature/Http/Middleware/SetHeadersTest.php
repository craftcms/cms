<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\Http\ResponseHeaders as ResponseHeaderAccumulator;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->generalConfig = Cms::config();
    $this->responseHeaders = app(ResponseHeaderAccumulator::class);
    $this->middleware = app(SetHeaders::class);

    Route::get('/test-response-headers', function () {
        if (($value = request()->query('value')) !== null) {
            ResponseHeaders::add('X-Test', $value);
        }

        return response('');
    })->middleware(SetHeaders::class);
});

it('passes through non-Response values', function () {
    $request = Request::create('foo');

    $result = $this->middleware->handle($request, fn () => 'bar');

    expect($result)->toBe('bar');
});

it('sets no-cache headers for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Cache-Control'))->toBeTrue();
});

it('sets X-Robots-Tag header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets Content-Security-Policy header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('Content-Security-Policy'))->toBe("frame-ancestors 'self'");
});

it('sets X-Frame-Options header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
});

it('sets X-Content-Type-Options header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('sets no-cache headers for action requests', function () {
    $request = Request::create('/'.Cms::config()->actionTrigger.'/test');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Cache-Control'))->toBeTrue();
});

it('sets X-Robots-Tag header when disallowRobots is true', function () {
    $this->generalConfig->disallowRobots();

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets X-Robots-Tag header when token param is present', function () {
    $request = Request::create('foo', parameters: [
        HandleTokenRequest::TOKEN_KEY => 'test-token',
    ]);

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets X-Robots-Tag header when token header is present', function () {
    $request = Request::create('foo');
    $request->headers->set(HandleTokenRequest::TOKEN_HEADER, 'test-token');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('does not set X-Robots-Tag header for normal site requests', function () {
    $this->generalConfig->disallowRobots(false);

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();
});

it('does not set security headers for site requests', function () {
    $this->generalConfig->disallowRobots(false);

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
    expect($response->headers->has('X-Frame-Options'))->toBeFalse();
    expect($response->headers->has('X-Content-Type-Options'))->toBeFalse();
});

it('removes the powered-by header when disabled', function () {
    $this->generalConfig->sendPoweredByHeader(false);

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response(headers: ['X-Powered-By' => 'Foo']));

    expect($response->headers->has('X-Powered-By'))->toBeFalse();
});

it('adds the powered-by header when enabled', function () {
    $this->generalConfig->sendPoweredByHeader();

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->get('X-Powered-By'))->toBe('Craft CMS');
});

it('appends the powered-by header to existing values', function () {
    $this->generalConfig->sendPoweredByHeader();

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response(headers: ['X-Powered-By' => 'Foo']));

    expect($response->headers->get('X-Powered-By'))->toBe('Foo,Craft CMS');
});

it('applies request-scoped headers', function () {
    $this->responseHeaders->add('X-Test', 'value');

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->get('X-Test'))->toBe('value');
});

it('preserves request-scoped header order and duplicate values', function () {
    $this->responseHeaders->add('X-Test', 'first', false);
    $this->responseHeaders->add('X-Test', 'second', false);

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->all('X-Test'))->toBe(['first', 'second']);
});

it('replaces request-scoped header values by default', function () {
    $this->responseHeaders->add('X-Test', 'first');
    $this->responseHeaders->add('X-Test', 'second');

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->all('X-Test'))->toBe(['second']);
});

it('applies request-scoped cache settings', function () {
    $this->responseHeaders->setCache(60);

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->get('Cache-Control'))->toBe('max-age=60, public');
});

it('applies queued cache settings in order, so the last one wins', function (Closure $queue, string $cacheControl) {
    $queue($this->responseHeaders);

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->get('Cache-Control'))->toBe($cacheControl);
})->with([
    'cache after no-cache' => [function (ResponseHeaderAccumulator $headers) {
        $headers->noCache();
        $headers->setCache(60);
    }, 'max-age=60, public'],
    'no-cache after cache' => [function (ResponseHeaderAccumulator $headers) {
        $headers->setCache(60);
        $headers->noCache();
    }, 'must-revalidate, no-cache, no-store, private'],
    'header after cache' => [function (ResponseHeaderAccumulator $headers) {
        $headers->cacheControl()->setSharedMaxAge(60);
        $headers->add('Cache-Control', 'private');
    }, 'private'],
]);

it('applies request-scoped no-cache settings', function () {
    $this->responseHeaders->noCache();

    $response = $this->middleware->handle(Request::create('/'), fn () => new Response);

    expect($response->headers->hasCacheControlDirective('no-cache'))->toBeTrue();
});

it('applies request-scoped headers and cache settings to JSON responses', function () {
    Route::get('/test-json-response-headers', function () {
        ResponseHeaders::add('Access-Control-Allow-Origin', '*');
        ResponseHeaders::setCache(60);

        return new JsonResponse(['ok' => true]);
    })->middleware(SetHeaders::class);

    $response = $this->get('/test-json-response-headers')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', '*');

    expect($response->baseResponse->getMaxAge())->toBe(60);
});

it('isolates facade state across long-lived request scopes', function () {
    $this->get('/test-response-headers?value=first')->assertHeader('X-Test', 'first');

    app()->forgetScopedInstances();

    $this->get('/test-response-headers?value=second')->assertHeader('X-Test', 'second');
});

it('sets no-cache headers for responses that contain the CSRF token', function (string $template) {
    TemplateMode::set(TemplateMode::Site);

    Route::middleware(['web', 'craft', 'craft.web'])->get('csrf-page', fn () => app(TemplateManager::class)->renderString($template));

    $response = $this->get('/csrf-page')->assertOk();

    expect($response->headers->get('Cache-Control'))->toBe('must-revalidate, no-cache, no-store, private');
})->with([
    'csrfInput() before expires' => '{{ csrfInput() }}{% expires in 1 hour %}',
    'csrf_token() before expires' => '<meta name="csrf-token" content="{{ csrf_token() }}">{% expires in 1 hour %}',
    'token in a header' => '{% header "X-CSRF-Token: " ~ csrf_token() %}{% expires in 1 hour %}',
]);

it('keeps cache headers for responses without the CSRF token', function () {
    TemplateMode::set(TemplateMode::Site);

    Route::middleware(['web', 'craft', 'craft.web'])->get('cached-page', fn () => app(TemplateManager::class)->renderString('{{ csrfInput({async: true}) }}{% expires in 1 hour %}'));

    $response = $this->get('/cached-page')->assertOk();

    expect($response->headers->get('Cache-Control'))->toBe('max-age=3600, public');
});

it('sets no-cache headers for site token requests', function () {
    Route::middleware(['web', 'craft', 'craft.web'])->get('site-token-page', function () {
        ResponseHeaders::setCache(3600);

        return 'page';
    });

    $response = $this->get('/site-token-page?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) Sites::getPrimarySite()->id),
    ]))->assertOk();

    expect($response->headers->get('Cache-Control'))->toBe('must-revalidate, no-cache, no-store, private');
});
