<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\Events\CacheControlApplied;
use CraftCms\Cms\Http\Middleware\ApplyCacheControl;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\CacheControl;
use CraftCms\Cms\Support\Facades\ResponseHeaders;
use CraftCms\Cms\Support\Html;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->resolved = null;
    $this->resolvedExampleHeader = null;
    Event::listen(CacheControlApplied::class, function (CacheControlApplied $event) {
        $this->resolved = $event;
        $this->resolvedExampleHeader = $event->response->headers->get('X-Example');
    });
});

it('keeps disabled site access private for site tokens even after their removal', function (string $transport) {
    Cms::config()->siteToken('siteAccess');
    $site = Site::factory()->create([
        'baseUrl' => 'https://localhost/disabled',
        'enabled' => false,
    ]);
    app(Sites::class)->refreshSites();

    Route::get('/cache-control', function () use ($transport) {
        CacheControl::setMaxAge(123)->setSharedMaxAge(789)
            ->setStaleWhileRevalidate(37)->setStaleIfError(91);

        if ($transport === 'consumed') {
            request()->headers->remove('X-Craft-Site-Token');
        }

        return response('site='.app(Sites::class)->getCurrentSite()->id);
    })->withLazySession()->middleware(['web', 'craft', 'craft.web']);

    $token = Crypt::encrypt((string) $site->id);
    $url = '/cache-control';

    if ($transport === 'query') {
        $url .= '?'.http_build_query([Cms::config()->siteToken => $token]);
    } else {
        $this->withHeader('X-Craft-Site-Token', $token);
    }

    $response = $this->get($url)->assertOk()->assertContent('site='.$site->id);

    expect($response->headers->has('Set-Cookie'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('123')
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('stale-while-revalidate'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('stale-if-error'))->toBeFalse()
        ->and($this->resolved->policy->isCacheable())->toBeFalse()
        ->and(array_column($this->resolved->reasons, 'code'))->toContain('site-token');

    if ($transport === 'consumed') {
        expect($this->resolved->request->siteToken())->toBeNull();
    }
})->with(['query', 'header', 'consumed']);

it('leaves incoming session cookie eligibility to integrations', function () {
    Route::get('/cache-control', function () {
        CacheControl::setMaxAge(0)->setSharedMaxAge(300);

        return response('page');
    })->middleware(ApplyCacheControl::class);

    $response = $this->call('GET', '/cache-control', [], [config('session.cookie') => 'existing'])->assertOk();

    expect($this->resolved->request->cookies->has(config('session.cookie')))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('private'))->toBeFalse()
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('300')
        ->and($this->resolved->policy->isCacheable())->toBeTrue()
        ->and(array_column($this->resolved->reasons, 'code'))->not->toContain('session-cookie');
});

it('leaves preference cookie storage decisions to integrations', function () {
    Route::get('/cache-control', function () {
        CacheControl::setMaxAge(0)->setSharedMaxAge(300);

        return response('page')->cookie('preference', 'grid');
    })->middleware(ApplyCacheControl::class);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->hasCacheControlDirective('private'))->toBeFalse()
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('300')
        ->and($this->resolved->policy->isCacheable())->toBeTrue();
});

it('applies independent browser and shared lifetimes through the facade', function (string $method) {
    Route::get('/cache-control', function () {
        CacheControl::setMaxAge(123)
            ->setSharedMaxAge(789)
            ->setStaleWhileRevalidate(37)
            ->setStaleIfError(91);

        return response('cacheable');
    })->middleware(ApplyCacheControl::class);

    $response = $this->call($method, '/cache-control')->assertOk();

    expect($response->headers->getCacheControlDirective('max-age'))->toBe('123')
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('789')
        ->and($response->headers->getCacheControlDirective('stale-while-revalidate'))->toBe('37')
        ->and($response->headers->getCacheControlDirective('stale-if-error'))->toBe('91')
        ->and($response->headers->hasCacheControlDirective('public'))->toBeTrue()
        ->and($this->resolved->policy->getMaxAge())->toBe(789)
        ->and(array_column($this->resolved->reasons, 'code'))->toBe(['policy', 'policy', 'policy', 'policy']);
})->with(['GET', 'HEAD']);

it('leaves default responses without freshness and browser-only durations private', function (bool $browserDuration) {
    Route::get('/cache-control', function () use ($browserDuration) {
        if ($browserDuration) {
            CacheControl::setMaxAge(123);
        }

        return response('no shared lifetime');
    })->middleware(ApplyCacheControl::class);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->getCacheControlDirective('max-age'))->toBe($browserDuration ? '123' : null)
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('private'))->toBeTrue();
})->with([false, true]);

it('makes unsafe responses private even when shared freshness is explicitly requested', function (string $scenario, string $reason) {
    Route::get('/cache-control', function () use ($scenario) {
        CacheControl::setMaxAge(123)->setSharedMaxAge(789)->setStaleWhileRevalidate(37)->setStaleIfError(91);

        if ($scenario === 'private-api') {
            CacheControl::setPrivate()->setSharedMaxAge(456);
        }

        if ($scenario === 'storage-prohibited') {
            CacheControl::setPrivate()->setNoCache()->setNoStore();
        }

        if ($scenario === 'consumed-token') {
            Context::addHidden(HandleTokenRequest::HAD_TOKEN_KEY, true);
        }

        $response = response('unsafe');

        match ($scenario) {
            'session-cookie' => $response->headers->setCookie(cookie(config('session.cookie'), 'persisted')),
            'private-header' => $response->headers->set('Cache-Control', 'private, max-age=31'),
            'private-no-cache' => $response->headers->set('Cache-Control', 'no-cache, private'),
            default => null,
        };

        return $response;
    })->middleware(ApplyCacheControl::class);

    $parameters = match ($scenario) {
        'preview' => ['x-craft-preview' => 'preview'],
        'token' => [Cms::config()->tokenParam => 'unknown-token'],
        default => [],
    };
    $server = match ($scenario) {
        'authorization' => ['HTTP_AUTHORIZATION' => 'Bearer example'],
        'token-header' => ['HTTP_X_CRAFT_TOKEN' => 'unknown-token'],
        default => [],
    };

    $response = $this->call('GET', '/cache-control', $parameters, [], [], $server);

    expect($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('public'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('stale-while-revalidate'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('stale-if-error'))->toBeFalse()
        ->and($this->resolved->policy->isCacheable())->toBeFalse()
        ->and(array_column($this->resolved->reasons, 'code'))->toContain($reason);

    if ($scenario === 'storage-prohibited') {
        expect($response->headers->hasCacheControlDirective('no-store'))->toBeTrue()
            ->and($response->headers->hasCacheControlDirective('max-age'))->toBeFalse();
    }
})->with([
    ['session-cookie', 'session-cookie'],
    ['authorization', 'authorization'],
    ['preview', 'preview'],
    ['token', 'token'],
    ['token-header', 'token'],
    ['consumed-token', 'token'],
    ['private-api', 'private'],
    ['private-header', 'private'],
    ['private-no-cache', 'private'],
    ['storage-prohibited', 'no-store'],
]);

it('sees final session and queued cookies without changing the host web group', function (string $source) {
    Route::get('/cache-control', function () use ($source) {
        CacheControl::setSharedMaxAge(789);

        if ($source === 'queued') {
            Cookie::queue('preference', 'custom');
        } else {
            session()->put('cart', ['item']);
        }

        return response('personalized');
    })->middleware([
        ...($source === 'queued' ? [EncryptCookies::class, AddQueuedCookiesToResponse::class] : ['web']),
        ApplyCacheControl::class,
    ]);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->getCookies())->not->toBeEmpty()
        ->and($response->headers->hasCacheControlDirective('private'))->toBe($source === 'session')
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe($source === 'session' ? null : '789')
        ->and($this->resolved->policy->headers->getCookies())->not->toBeEmpty();
})->with(['queued', 'session']);

it('applies accumulated headers before resolving and keeps csrfInput storage protection', function (bool $expiryAfterCsrf) {
    Route::get('/cache-control', function () use ($expiryAfterCsrf) {
        CacheControl::setSharedMaxAge(789);
        if (! $expiryAfterCsrf) {
            CacheControl::setDuration(600);
        }

        ResponseHeaders::add('X-Example', 'present');

        $input = Html::csrfInput();

        if ($expiryAfterCsrf) {
            CacheControl::setDuration(600);
        }

        return response($input);
    })->middleware([SetHeaders::class, ApplyCacheControl::class, 'web']);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->hasCacheControlDirective('no-cache'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('max-age'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($this->resolvedExampleHeader)->toBe('present');
})->with([false, true]);

it('combines lazy sessions with Craft cache control for read-only and session-writing responses', function (bool $writeSession) {
    Route::get('/cache-control', function () use ($writeSession) {
        CacheControl::setPublic()->setMaxAge(600);
        CacheControl::setSharedMaxAge(789);

        if ($writeSession) {
            session()->put('cart', ['item']);
        }

        return response('cache settings');
    })->withLazySession()->middleware(['web', 'craft', 'craft.web']);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->getCacheControlDirective('max-age'))->toBe('600')
        ->and($response->headers->has('Set-Cookie'))->toBe($writeSession)
        ->and($response->headers->hasCacheControlDirective('private'))->toBe($writeSession)
        ->and($response->headers->hasCacheControlDirective('public'))->toBe(! $writeSession)
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe($writeSession ? null : '789')
        ->and($this->resolved->policy->isCacheable())->toBe(! $writeSession);
})->with([false, true]);

it('documents the snapshot boundary without rewriting later integration output', function (bool $lateCookie) {
    Event::listen(CacheControlApplied::class, function (CacheControlApplied $event) use ($lateCookie) {
        if ($lateCookie) {
            $event->response->headers->setCookie(cookie('preference', 'custom'));
        }
    });
    $stored = null;
    Event::listen(RequestHandled::class, function (RequestHandled $event) use (&$stored) {
        $stored = clone $event->response;
    });
    Route::get('/cache-control', function () {
        CacheControl::setSharedMaxAge(789)->setStaleWhileRevalidate(37);

        return response('page');
    })->withLazySession()->middleware(['web', 'craft', 'craft.web']);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->has('Set-Cookie'))->toBe($lateCookie)
        ->and($response->headers->hasCacheControlDirective('private'))->toBeFalse()
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('789')
        ->and($this->resolved->policy->headers->getCookies())->toBe([])
        ->and($stored->headers->hasCacheControlDirective('private'))->toBeFalse();
})->with([false, true]);

it('isolates directives across long-lived request scopes and leaves routes without opt-in alone', function () {
    Route::get('/cache-control', function () {
        CacheControl::setSharedMaxAge(789);
        app(ElementCaches::class)->collectCacheTags(['first-page']);

        return response('first');
    })->middleware(ApplyCacheControl::class);
    Route::get('/cache-control-default', fn () => response('second'))->middleware(ApplyCacheControl::class);
    Route::get('/cache-control-unmanaged', function () {
        CacheControl::setSharedMaxAge(456);

        return response('unmanaged');
    });

    $first = $this->get('/cache-control')->assertOk();
    expect($first->headers->getCacheControlDirective('s-maxage'))->toBe('789')
        ->and($this->resolved->tags)->toBe(['first-page']);

    app()->forgetScopedInstances();

    $second = $this->get('/cache-control-default')->assertOk();
    expect($second->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($this->resolved->tags)->toBe([])
        ->and($this->resolved->reasons)->toBe([]);

    $unmanaged = $this->get('/cache-control-unmanaged')->assertOk();
    expect($unmanaged->headers->hasCacheControlDirective('s-maxage'))->toBeFalse();
});

it('gives both consumers the same policy and collected tags despite header translation', function (bool $edgeFirst) {
    $stored = null;
    $origin = function (CacheControlApplied $event) use (&$stored): void {
        $stored = [$event->response->getContent(), $event->policy->getTtl(), $event->tags];
    };
    $edge = function (CacheControlApplied $event): void {
        $event->response->headers->removeCacheControlDirective('s-maxage');
        $event->response->headers->set('CDN-Cache-Control', 'public, max-age='.$event->policy->getMaxAge());
    };

    foreach ($edgeFirst ? [$edge, $origin] : [$origin, $edge] as $consumer) {
        Event::listen(CacheControlApplied::class, $consumer);
    }

    Route::get('/cache-control', function () {
        CacheControl::setMaxAge(0)->setSharedMaxAge(300, reason: 'Public news listing');
        $collector = app(ElementCaches::class);
        $collector->collectCacheTags(['element::42']);
        $collector->startCollectingCacheInfo();
        $collector->collectCacheTags(['news', 'element::42']);
        $collector->stopCollectingCacheInfo();

        return response('news page')->header('Age', '11');
    })->middleware(ApplyCacheControl::class);

    $response = $this->get('/cache-control')->assertOk();

    expect($stored)->toBe(['news page', 289, ['element::42', 'news']])
        ->and($response->headers->get('CDN-Cache-Control'))->toBe('public, max-age=300')
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('0')
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse()
        ->and($this->resolved->policy->headers->getCacheControlDirective('s-maxage'))->toBe('300')
        ->and($this->resolved->reasons[1]['message'])->toBe('Public news listing')
        ->and($this->resolved->reasons[1]['source'])->toContain(__FILE__.':')
        ->and(app(ElementCaches::class)->isCollectingCacheInfo())->toBeFalse();
})->with([false, true]);

it('keeps HTTP revalidation and storage restrictions distinct from privacy', function (string $directive, string $source) {
    Route::get('/cache-control', function () use ($directive, $source) {
        CacheControl::setSharedMaxAge(300);

        if ($source === 'api') {
            if ($directive === 'no-cache') {
                CacheControl::setNoCache();
            } else {
                CacheControl::setNoStore();
            }

            return response('page');
        }

        return response('page')->header('Cache-Control', "public, $directive");
    })->middleware(ApplyCacheControl::class);

    $response = $this->get('/cache-control')->assertOk();

    expect($response->headers->hasCacheControlDirective('private'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective($directive))->toBeTrue()
        ->and($this->resolved->policy->isCacheable())->toBe($directive === 'no-cache')
        ->and(array_column($this->resolved->reasons, 'code'))->toContain($directive);
})->with(['no-cache', 'no-store'])->with(['header', 'api']);

it('caps supplied freshness by collected expiry without inventing a lifetime', function (string $scenario, ?int $ttl) {
    $this->freezeSecond();

    Route::get('/cache-control', function () use ($scenario) {
        $collector = app(ElementCaches::class);
        $collector->setCacheExpiryDate(now()->addSeconds(90));
        $this->travel($scenario === 'expired' ? 100 : 0)->seconds();
        $response = response('page')->header('Age', '11');
        $response->setDate(now()->toDateTimeImmutable());

        if (in_array($scenario, ['shared', 'expired'], true)) {
            CacheControl::setMaxAge(120)->setSharedMaxAge(300);
        } elseif ($scenario === 'common') {
            CacheControl::setPublic()->setMaxAge(300);
        } elseif ($scenario === 'expires') {
            $response->setPublic()->setExpires(now()->addSeconds(300));
        } elseif ($scenario === 'shorter') {
            CacheControl::setSharedMaxAge(40);
        }

        return $response;
    })->middleware(ApplyCacheControl::class);

    $this->get('/cache-control')->assertOk();

    expect($this->resolved->policy->getTtl())->toBe($ttl)
        ->and(in_array('dependency-expiry', array_column($this->resolved->reasons, 'code'), true))
        ->toBe(in_array($scenario, ['shared', 'common', 'expires', 'expired'], true));
})->with([['shared', 90], ['common', 90], ['expires', 90], ['expired', 0], ['shorter', 29], ['none', null]]);

it('restores an enclosing dependency collection when downstream handling throws', function () {
    $this->withoutExceptionHandling();
    $collector = app(ElementCaches::class);
    $collector->startCollectingCacheInfo();
    $collector->collectCacheTags(['outer']);

    Route::get('/cache-control', function () {
        app(ElementCaches::class)->collectCacheTags(['inner']);

        throw new RuntimeException('Render failed');
    })->middleware(ApplyCacheControl::class);

    try {
        expect(fn () => $this->get('/cache-control'))->toThrow(RuntimeException::class, 'Render failed');
        expect($collector->isCollectingCacheInfo())->toBeTrue();
    } finally {
        [$dependency] = $collector->stopCollectingCacheInfo();
    }

    expect($dependency->tags)->toBe(['outer', 'inner'])
        ->and($collector->isCollectingCacheInfo())->toBeFalse();
});

it('preserves specialized responses and leaves their storage eligibility to integrations', function (string $type) {
    $native = match ($type) {
        'stream' => response()->stream(fn () => print 'streamed'),
        'json' => new JsonResponse(['message' => 'hello']),
        'redirect' => redirect('/destination'),
        'not-found' => response('not found', 404),
    };

    Route::get('/cache-control', function () use ($native) {
        CacheControl::setSharedMaxAge(300);

        return $native;
    })->middleware(ApplyCacheControl::class);

    $response = $this->get('/cache-control');

    expect($this->resolved->response)->toBe($native)
        ->and($this->resolved->policy->headers->getCacheControlDirective('s-maxage'))->toBe('300');

    match ($type) {
        'stream' => $response->assertStreamedContent('streamed'),
        'json' => $response->assertJson(['message' => 'hello']),
        'redirect' => $response->assertRedirect('/destination'),
        'not-found' => $response->assertNotFound()->assertContent('not found'),
    };
})->with(['stream', 'json', 'redirect', 'not-found']);
