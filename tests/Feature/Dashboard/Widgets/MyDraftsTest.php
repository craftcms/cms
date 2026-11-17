<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Session;

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

    $drafts = $data['drafts'];

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]['id'])->toBe($draft->id)
        ->and($drafts[0]['url'])->toBe($draft->getCpEditUrl())
        ->and($drafts[0]['title'])->toBe($draft->getUiLabel())
        ->and($drafts[0]['draftName'])->toBe($draft->draftName);
});

it('labels the user’s drafts with their sections, newest first', function () {
    actingAs($user = User::find()->one());
    $news = Section::factory()->create(['name' => 'News']);
    $blog = Section::factory()->create(['name' => 'Blog']);
    $draftOf = fn (Section $section) => app(Drafts::class)->createDraft(
        Elements::getElementById(Entry::factory()->forSection($section)->create()->id),
        $user->id,
    );

    $newsDraft = $draftOf($news);
    $this->travel(1)->minutes();
    $blogDraft = $draftOf($blog);

    $drafts = app(Dashboard::class)->createWidget(MyDrafts::class)->props()['drafts'];

    expect(array_column($drafts, 'id'))->toBe([$blogDraft->id, $newsDraft->id])
        ->and(array_column($drafts, 'section'))->toBe(['Blog', 'News']);
});

it('leaves out the user’s drafts in sections they can’t view', function () {
    $news = Section::factory()->create(['name' => 'News']);
    $blog = Section::factory()->create(['name' => 'Blog']);
    $site = Sites::getPrimarySite();
    $user = UserModel::factory()
        ->withPermissions(['accessCp', "editSite:$site->uid", "viewEntries:$news->uid"])
        ->createElement(['admin' => false]);
    actingAs($user);
    $draftOf = fn (Section $section) => app(Drafts::class)->createDraft(
        Elements::getElementById(Entry::factory()->forSection($section)->create()->id),
        $user->id,
    );

    $newsDraft = $draftOf($news);
    $draftOf($blog);

    $drafts = app(Dashboard::class)->createWidget(MyDrafts::class)->props()['drafts'];

    expect(array_column($drafts, 'id'))->toBe([$newsDraft->id]);
});
