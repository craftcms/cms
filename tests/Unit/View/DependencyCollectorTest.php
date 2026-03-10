<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;

beforeEach(function () {
    app()->forgetScopedInstances();

    $this->collector = app(DependencyCollector::class);
    $this->context = new TemplateCacheContext(
        cacheKey: 'test-cache',
        global: true,
        resources: false,
    );
});

it('merges nested dependency payloads into the outer collection', function () {
    $this->collector->begin($this->context);
    $this->collector->collectTags(['outer-tag']);

    $this->collector->begin($this->context);
    $this->collector->collectTags(['inner-tag']);

    $payload = $this->collector->end($this->context);
    [$dependency] = $this->collector->stop();

    expect($payload['tags'])->toBe(['inner-tag'])
        ->and($dependency?->tags)->toEqualCanonicalizing(['outer-tag', 'inner-tag']);
});

it('keeps the shortest expiry date in a collection', function () {
    $this->collector->begin($this->context);
    $this->collector->setExpiryDate(DateTimeHelper::now()->modify('+2 minutes'));
    $this->collector->setExpiryDate(DateTimeHelper::now()->modify('+1 minute'));

    [, $duration] = $this->collector->stop();

    expect($duration)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(60);
});

it('applies cached dependency payloads into an active outer collection', function () {
    $this->collector->begin($this->context);

    $this->collector->apply([
        'tags' => ['cached-tag'],
        'expiryDate' => DateTimeHelper::toIso8601(DateTimeHelper::now()->modify('+45 seconds')),
    ], $this->context);

    [$dependency, $duration] = $this->collector->stop();

    expect($dependency?->tags)->toContain('cached-tag')
        ->and($duration)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(45);
});
