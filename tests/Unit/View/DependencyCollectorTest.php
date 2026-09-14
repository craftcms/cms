<?php

declare(strict_types=1);

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;

beforeEach(function () {
    $this->freezeSecond();
    app()->forgetScopedInstances();

    $this->collector = app(DependencyCollector::class);
    $this->context = new TemplateCacheContext(
        cacheKey: 'test-cache',
        global: true,
        resources: false,
    );
});

it('merges nested dependency payloads into the outer collection', function (int $outerExpiry, int $remaining) {
    $this->collector->begin($this->context);
    $this->collector->collectTags(['outer-tag']);
    $this->collector->setExpiryDate(now()->addSeconds($outerExpiry));
    $this->travel(20)->seconds();

    $this->collector->begin($this->context);
    $this->collector->collectTags(['inner-tag']);
    $expiryDate = now()->addSeconds(40);
    $this->collector->setExpiryDate($expiryDate);
    $this->travel(10)->seconds();

    $payload = $this->collector->end($this->context);
    $this->travel(10)->seconds();
    [$dependency, $duration] = $this->collector->stop();

    expect($payload['expiryDate'])->toBe(DateTimeHelper::toIso8601($expiryDate))
        ->and($duration)->toBe($remaining)
        ->and($payload['tags'])->toBe(['inner-tag'])
        ->and($dependency?->tags)->toEqualCanonicalizing(['outer-tag', 'inner-tag']);
})->with([[90, 20], [50, 10], [30, 0]]);

it('keeps the shortest expiry date in a collection', function () {
    $this->collector->begin($this->context);
    $this->collector->setExpiryDate(now()->add(2, 'minutes'));
    $this->collector->setExpiryDate(now()->add(1, 'minute'));

    $this->travel(20)->seconds();
    [, $duration] = $this->collector->stop();

    expect($duration)->toBe(40);
});

it('applies cached dependency payloads into an active outer collection', function (int $elapsed, int $remaining) {
    $this->collector->begin($this->context);

    $this->collector->apply([
        'tags' => ['cached-tag'],
        'expiryDate' => DateTimeHelper::toIso8601(now()->add(45, 'seconds')),
    ], $this->context);

    $this->travel($elapsed)->seconds();
    [$dependency, $duration] = $this->collector->stop();

    expect($dependency?->tags)->toContain('cached-tag')
        ->and($duration)->toBe($remaining);
})->with([[15, 30], [45, 0], [60, 0]]);

it('leaves collections without a deadline unconstrained', function () {
    $this->collector->begin($this->context);
    expect($this->collector->end($this->context))->toBe(['tags' => [], 'expiryDate' => null]);

    $this->collector->begin($this->context);
    $this->collector->setExpiryDate(now()->subSecond());
    expect($this->collector->stop())->toBe([null, null]);
});
