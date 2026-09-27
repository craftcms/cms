<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Jobs\PropagateElements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;

it('can be instantiated with site id as int', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
        siteId: 1,
    );

    expect($job->siteId)->toBe([1]);
});

it('can be instantiated with null site id', function () {
    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    expect($job->siteId)->toBeNull();
});

it('provides a description', function () {
    Entry::factory()->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('Propagating');
});

it('uses singular description when only one element', function () {
    Entry::factory()->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
        criteria: ['id' => EntryElement::find()->one()->id],
    );

    $description = $job->getDescription();

    expect($description)->toContain('entry');
});

it('uses plural description when multiple elements', function () {
    Entry::factory()->count(3)->create();

    $job = new PropagateElements(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('entries');
});
