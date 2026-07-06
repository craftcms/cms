<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->cpTrigger = Cms::config()->cpTrigger;
});

function entriesIndexUrl(string $path = 'content/entries', array $query = []): string
{
    $url = '/'.Cms::config()->cpTrigger.'/'.$path;

    return $query ? $url.'?'.http_build_query($query) : $url;
}

it('requires authentication', function () {
    Auth::logout();

    get(entriesIndexUrl())->assertRedirect();
});

it('renders the entries index page', function () {
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);
    EntryModel::factory()->forSection($section)->createElement(['title' => 'First post']);

    get(entriesIndexUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/Index')
            ->where('title', 'Entries')
            ->has('sources')
            ->has('columns')
            ->has('sortOptions')
            ->has('elements')
            ->has('pagination'));
});

it('lists the section as a source and its entries as rows', function () {
    $section = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);
    $entry = EntryModel::factory()->forSection($section)->createElement(['title' => 'Hello world']);

    get(entriesIndexUrl('content/entries', ['source' => "section:{$section->uid}"]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/Index')
            ->where('selectedSource', "section:{$section->uid}")
            ->where('elements.0.id', $entry->id)
            ->where('elements.0.title', 'Hello world')
            ->where('elements.0.status', Entry::STATUS_LIVE)
            ->has('elements.0.attributeHtml'));
});

it('scopes elements to the selected source', function () {
    $blog = Section::factory()->create(['name' => 'Blog', 'handle' => 'blog']);
    $news = Section::factory()->create(['name' => 'News', 'handle' => 'news']);
    EntryModel::factory()->forSection($blog)->createElement(['title' => 'Blog post']);
    EntryModel::factory()->forSection($news)->createElement(['title' => 'News post']);

    get(entriesIndexUrl('content/entries', ['source' => "section:{$blog->uid}"]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 1)
            ->where('elements.0.title', 'Blog post'));
});

it('redirects to the index when the requested source does not exist', function () {
    Section::factory()->create();

    get(entriesIndexUrl('content/entries', ['source' => 'section:nope']))
        ->assertRedirect();
});

it('sorts elements by the requested attribute', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Banana']);
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Apple']);

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'sort' => [['field' => 'title', 'direction' => 'asc']],
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('elements.0.title', 'Apple')
            ->where('elements.1.title', 'Banana')
            ->where('sort.0.field', 'title')
            ->where('sort.0.direction', 'asc'));

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'sort' => [['field' => 'title', 'direction' => 'desc']],
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('elements.0.title', 'Banana')
            ->where('elements.1.title', 'Apple'));
});

it('paginates elements', function () {
    $section = Section::factory()->create();

    foreach (['A', 'B', 'C'] as $title) {
        EntryModel::factory()->forSection($section)->createElement(['title' => "Entry $title"]);
    }

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'sort' => [['field' => 'title', 'direction' => 'asc']],
        'per_page' => 2,
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 2)
            ->where('pagination.total', 3)
            ->where('pagination.per_page', 2)
            ->where('pagination.current_page', 1)
            ->where('pagination.last_page', 2));

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'sort' => [['field' => 'title', 'direction' => 'asc']],
        'per_page' => 2,
        Cms::config()->getPageTriggerParam() => 2,
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 1)
            ->where('elements.0.title', 'Entry C')
            ->where('pagination.current_page', 2));
});

it('clamps out-of-range page numbers to the last page', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Only entry']);

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        Cms::config()->getPageTriggerParam() => 99,
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.current_page', 1)
            ->count('elements', 1));
});

it('filters elements by status', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Live entry']);
    EntryModel::factory()->forSection($section)->pending()->title('Pending entry')->create();

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'status' => Entry::STATUS_PENDING,
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 1)
            ->where('elements.0.title', 'Pending entry')
            ->where('selectedStatus', Entry::STATUS_PENDING));
});

it('ignores unknown statuses', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Live entry']);

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'status' => 'bogus',
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 1));
});

it('searches elements', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->indexed()->createElement(['title' => 'Needle entry']);
    EntryModel::factory()->forSection($section)->indexed()->createElement(['title' => 'Haystack entry']);

    get(entriesIndexUrl('content/entries', [
        'source' => "section:{$section->uid}",
        'search' => 'Needle',
    ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('elements', 1)
            ->where('elements.0.title', 'Needle entry')
            ->where('searchTerm', 'Needle'));
});

it('redirects a section handle URL to its index page with the section selected', function () {
    $section = Section::factory()->create(['handle' => 'blog']);

    get(entriesIndexUrl('entries/blog'))
        ->assertRedirect();

    get(entriesIndexUrl('content/entries/blog'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('selectedSource', "section:{$section->uid}"));
});

it('exposes the new entry URL when the user can create entries in the section', function () {
    $section = Section::factory()->create(['handle' => 'blog', 'type' => SectionType::Channel]);

    get(entriesIndexUrl('content/entries', ['source' => "section:{$section->uid}"]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canCreate', true)
            ->where('newEntryUrl', fn ($url) => str_contains((string) $url, 'entries/blog/new')));
});

it('includes table columns and sort options for the source', function () {
    $section = Section::factory()->create();

    get(entriesIndexUrl('content/entries', ['source' => "section:{$section->uid}"]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('columns.0.key', 'title')
            ->has('sortOptions', fn (AssertableInertia $options) => $options->etc())
            ->has('statuses'));
});
