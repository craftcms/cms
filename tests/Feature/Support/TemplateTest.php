<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Factories\EntryFactory;
use CraftCms\Cms\Database\Factories\FieldFactory;
use CraftCms\Cms\Database\Factories\FieldLayoutFactory;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Template;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Source;

it('returns null for entry fields that are not on the entry field layout', function () {
    FieldFactory::new()->create([
        'handle' => 'kicker',
    ]);

    Fields::refreshFields();

    $entry = EntryFactory::new()
        ->withFieldLayout(FieldLayoutFactory::new()->withContentTab())
        ->createElement();

    expect($entry->kicker)->toBeNull()
        ->and(isset($entry->kicker))->toBeTrue()
        ->and(Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $entry,
            'kicker',
        ))->toBeNull()
        ->and(Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $entry,
            'kicker',
            [],
            isDefinedTest: true,
        ))->toBeTrue();
});
