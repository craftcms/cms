<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

describe('password-reset rate limiter', function () {
    it('does not throttle when loginName is empty', function () {
        $request = Request::create('/actions/users/send-password-reset-email', 'POST', [
            'loginName' => '',
        ]);

        $limiter = RateLimiter::limiter('password-reset');
        $limit = $limiter($request);

        // Limit::none() returns PHP_INT_MAX as maxAttempts (effectively unlimited)
        expect($limit)->toBeInstanceOf(Limit::class)
            ->and($limit->maxAttempts)->toBe(PHP_INT_MAX);
    });

    it('does not throttle when loginName is missing', function () {
        $request = Request::create('/actions/users/send-password-reset-email', 'POST');

        $limiter = RateLimiter::limiter('password-reset');
        $limit = $limiter($request);

        expect($limit)->toBeInstanceOf(Limit::class)
            ->and($limit->maxAttempts)->toBe(PHP_INT_MAX);
    });

    it('throttles to 1 request per minute when loginName is provided', function () {
        $request = Request::create('/actions/users/send-password-reset-email', 'POST', [
            'loginName' => 'test@example.com',
        ]);

        $limiter = RateLimiter::limiter('password-reset');
        $limit = $limiter($request);

        expect($limit)->toBeInstanceOf(Limit::class)
            ->and($limit->maxAttempts)->toBe(1)
            ->and($limit->decaySeconds)->toBe(60);
    });

    it('keys rate limit by IP address', function () {
        $request = Request::create(
            '/actions/users/send-password-reset-email',
            'POST',
            ['loginName' => 'test@example.com'],
            [],
            [],
            ['REMOTE_ADDR' => '192.168.1.100']
        );

        $limiter = RateLimiter::limiter('password-reset');
        $limit = $limiter($request);

        expect($limit->key)->toBe('192.168.1.100');
    });

    it('allows different IPs to make requests independently', function () {
        $request1 = Request::create(
            '/actions/users/send-password-reset-email',
            'POST',
            ['loginName' => 'test@example.com'],
            [],
            [],
            ['REMOTE_ADDR' => '192.168.1.1']
        );

        $request2 = Request::create(
            '/actions/users/send-password-reset-email',
            'POST',
            ['loginName' => 'test@example.com'],
            [],
            [],
            ['REMOTE_ADDR' => '192.168.1.2']
        );

        $limiter = RateLimiter::limiter('password-reset');
        $limit1 = $limiter($request1);
        $limit2 = $limiter($request2);

        expect($limit1->key)->toBe('192.168.1.1')
            ->and($limit2->key)->toBe('192.168.1.2')
            ->and($limit1->key)->not->toBe($limit2->key);
    });
});
