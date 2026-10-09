<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;

it('shows no drafts when the user has none', function () {
    actingAs(User::find()->one());
    Session::start();

    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(MyDrafts::class);

    expect($widget->props())->toBe(['drafts' => []]);
});

it('links the user’s draft to its editor', function () {
    actingAs($user = User::find()->one());
    $entry = Entry::factory()->create();
    $element = Elements::getElementById($entry->id);
    $draft = app(Drafts::class)->createDraft($element, $user->id);

    $data = app(Dashboard::class)->createWidget(MyDrafts::class)->props();

    expect($data['drafts'])->toHaveCount(1)
        ->and($data['drafts'][0]['id'])->toBe($draft->id)
        ->and(new Crawler($data['drafts'][0]['html'])->filter('a')->attr('href'))->toBe($draft->getCpEditUrl());
});
