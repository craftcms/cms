<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ActivityCommentsController;
use CraftCms\Cms\Http\Controllers\Elements\ActivityMentionSuggestionsController;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
use CraftCms\Cms\Http\Requests\ActivityCommentRequest;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ActivityMentionNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->entry = EntryModel::factory()->createElement(['title' => 'Release notes']);
    $this->createCommentUrl = action([ActivityCommentsController::class, 'store']);
    $this->editCommentUrl = action([ActivityCommentsController::class, 'update']);
    $this->deleteCommentUrl = action([ActivityCommentsController::class, 'destroy']);
    DB::table(Table::ACTIVITYEVENTS)->delete();
});

it('renders the entry activity page', function () {
    $url = cp_url(sprintf(
        '%s/%d-%s/activity',
        $this->entry->getSection()->getCpIndexUri(),
        $this->entry->id,
        $this->entry->slug,
    ));

    get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Activity')
            ->where('title', 'Activity for “Release notes”')
            ->where('elementType', Entry::class)
            ->where('elementId', $this->entry->id)
            ->where('siteId', $this->entry->siteId)
            ->where('activityTimelineUrl', fn (string $url) => str_contains($url, 'elements/activity') && str_contains($url, 'all=1'))
        );
});

it('requires authentication and the subject view permission', function () {
    Auth::logout();

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])->assertUnauthorized();

    actingAs(UserModel::factory()->createElement(['admin' => false]));

    postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => 'Unauthorized comment',
    ])->assertForbidden();
});

it('requires a comment ID for edit and delete requests', function () {
    $data = [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ];

    patchJson($this->editCommentUrl, [...$data, 'markdown' => 'Edited'])
        ->assertUnprocessable();
    deleteJson($this->deleteCommentUrl, $data)
        ->assertUnprocessable();
});

it('returns the requested site timeline oldest first with safe formatted details', function () {
    $activities = app(Activities::class);
    $otherSite = Site::factory()->create();
    Sites::refreshSites();
    $actor = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);

    Date::setTestNow('2026-08-24 09:00:00');
    $neutral = $activities->record(new ElementCreated(subject: $this->entry, actor: $actor));

    Date::setTestNow('2026-08-25 10:30:00');
    $updated = $activities->record(new ElementUpdated(
        subject: $this->entry,
        actor: $actor,
        site: Sites::getSiteById($this->entry->siteId),
        changes: [new ActivityChange('Title', 'Draft', 'Release notes')],
    ));

    $activities->record(new ElementUpdated(
        subject: $this->entry,
        site: Sites::getSiteById($otherSite->id),
    ));

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('events', 2)
            ->where('events.0.id', $neutral->id)
            ->where('events.0.icon', 'plus')
            ->where('events.0.actor.label', 'Ada Lovelace')
            ->whereType('events.0.actor.url', 'string')
            ->where('events.0.actor.deleted', false)
            ->where('events.1.id', $updated->id)
            ->where('events.1.changes.0.label', 'Title')
            ->where('events.1.changes.0.old', 'Draft')
            ->where('events.1.changes.0.new', 'Release notes')
            ->etc());
});

it('returns impersonator details', function () {
    $impersonator = UserModel::factory()->admin()->createElement(['fullName' => 'Grace Hopper']);
    $actor = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);
    app(Impersonation::class)->setImpersonatorId($impersonator->id);
    app(Activities::class)->record(new ElementUpdated(subject: $this->entry, actor: $actor));
    app(Impersonation::class)->setImpersonatorId(null);

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonPath('events.0.impersonator.label', 'Grace Hopper')
        ->assertJsonPath('events.0.impersonator.deleted', false)
        ->assertJsonPath('events.0.actor.label', 'Ada Lovelace');
});

it('formats timeline dates in the viewer preferred timezone', function () {
    $viewer = User::findOne();
    Users::saveUserPreferences($viewer, ['timeZone' => 'Europe/Brussels']);
    Date::setTestNow(new DateTimeImmutable('2026-08-25 22:30:00', new DateTimeZone('UTC')));
    app(Activities::class)->record(new ElementUpdated(subject: $this->entry));

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonPath('events.0.formattedOccurredAt.date', '2026-08-26')
        ->assertJsonPath('events.0.formattedOccurredAt.dateLabel', 'Aug 26, 2026')
        ->assertJsonPath('events.0.formattedOccurredAt.time', '12:30 AM')
        ->assertJsonPath('events.0.formattedOccurredAt.full', 'August 26, 2026 at 12:30:00 AM GMT+2');
});

it('limits embedded timelines and returns every event for the full timeline', function () {
    Date::setTestNow('2026-08-25 10:30:00');
    $events = collect(range(1, 26))
        ->map(fn () => app(Activities::class)->record(new ElementUpdated(subject: $this->entry)));

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonCount(25, 'events')
        ->assertJsonPath('events.0.id', $events[1]->id)
        ->assertJsonPath('events.24.id', $events[25]->id);

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'all' => true,
    ])
        ->assertOk()
        ->assertJsonCount(26, 'events')
        ->assertJsonPath('events.0.id', $events[0]->id)
        ->assertJsonPath('events.25.id', $events[25]->id);
});

it('keeps deleted actors identifiable and sanitizes plugin descriptions', function () {
    $activities = app(Activities::class);
    $actor = UserModel::factory()->createElement(['fullName' => 'Deleted editor']);

    $event = $activities->record(new TimelineFormattedActivity(subject: $this->entry, actor: $actor));
    DB::table(Table::ELEMENTS)->where('id', $actor->id)->delete();

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('events.0.id', $event->id)
            ->where('events.0.icon', 'bolt')
            ->where('events.0.actor.label', 'Deleted editor')
            ->where('events.0.actor.url', null)
            ->where('events.0.actor.deleted', true)
            ->where('events.0.description.html', '<strong>Formatted safely</strong>')
            ->where('events.0.description.text', null)
            ->etc());
});

it('shows plugin actors without profile links and allows a default icon', function () {
    $event = app(Activities::class)->record(new TimelineUniconedActivity(
        subject: $this->entry,
        actor: new ActivityActor('test.webhook', 'Webhook', auth()->id()),
    ));

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('events.0.id', $event->id)
            ->where('events.0.icon', null)
            ->where('events.0.actor.label', 'Webhook')
            ->where('events.0.actor.url', null)
            ->where('events.0.actor.deleted', false)
            ->etc());
});

it('shows canonical activity for drafts and revisions', function (string $derivative) {
    $activities = app(Activities::class);
    $event = $activities->record(new ElementCreated(subject: $this->entry));

    $derivativeId = match ($derivative) {
        'draftId' => app(Drafts::class)->createDraft($this->entry, auth()->id(), name: 'Working draft')->draftId,
        'revisionId' => Elements::getElementById(
            app(Revisions::class)->createRevision($this->entry, auth()->id(), 'Revision notes'),
        )->revisionId,
    };

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        $derivative => $derivativeId,
    ])
        ->assertOk()
        ->assertJsonPath('events.0.id', $event->id);
})->with([
    'draft' => 'draftId',
    'revision' => 'revisionId',
]);

it('lets a collaborator with view access post a safe Markdown comment without save access', function () {
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $collaborator = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "editSite:$site->uid",
            "viewEntries:$section->uid",
            "viewPeerEntries:$section->uid",
        ])
        ->createElement(['admin' => false, 'fullName' => 'Grace Hopper']);

    actingAs($collaborator);

    $response = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => '**Ship it.** <script>bad()</script>',
    ])
        ->assertOk()
        ->assertJsonPath('event.description.text', 'Commented.')
        ->assertJsonPath('event.actor.label', 'Grace Hopper')
        ->assertJsonPath('event.comment.edited', false)
        ->assertJsonPath('event.comment.deleted', false)
        ->assertJsonPath('event.comment.canEdit', true)
        ->assertJsonPath('event.comment.canDelete', true);

    expect($response->json('event.comment.html'))->toContain('<strong>Ship it.</strong>')
        ->not->toContain('<script>');

    postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => " \n\t ",
    ])->assertUnprocessable();

    postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => str_repeat('x', ActivityCommentRequest::MaxLength + 1),
    ])->assertUnprocessable();

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.comment.html', $response->json('event.comment.html'));
});

it('suggests active control panel users who can view the subject', function () {
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $permissions = [
        'accessCp',
        "editSite:$site->uid",
        "viewEntries:$section->uid",
        "viewPeerEntries:$section->uid",
    ];
    UserModel::factory()
        ->count(50)
        ->withPermissions(['accessCp'])
        ->create(['fullName' => 'Grace Denied']);
    $eligible = UserModel::factory()
        ->withPermissions($permissions)
        ->createElement(['fullName' => 'Grace Hopper', 'username' => 'grace']);
    UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement(['fullName' => 'Grace Denied', 'username' => 'grace-denied']);
    UserModel::factory()
        ->withPermissions($permissions)
        ->createElement([
            'active' => false,
            'fullName' => 'Grace Inactive',
            'username' => 'grace-inactive',
        ]);

    getJson(action(ActivityMentionSuggestionsController::class).'?'.http_build_query([
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'query' => '@grace',
        'limit' => 10,
    ]))
        ->assertOk()
        ->assertExactJson([[
            'label' => 'Grace Hopper',
            'value' => "[@grace](craft-user:$eligible->id)",
            'keywords' => ['grace'],
            'data' => ['hint' => '@grace'],
        ]]);
});

it('renders eligible structured mentions as links and other mention syntax as text', function () {
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $mentioned = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "editSite:$site->uid",
            "viewEntries:$section->uid",
            "viewPeerEntries:$section->uid",
        ])
        ->createElement(['fullName' => 'Grace Hopper', 'username' => 'grace']);

    $response = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => "Hello [@grace](craft-user:$mentioned->id) and @plain.",
    ])->assertOk();

    $document = new DOMDocument;
    $document->loadHTML(
        $response->json('event.comment.html'),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );
    $link = $document->getElementsByTagName('a')->item(0);

    expect($link)->toBeInstanceOf(DOMElement::class)
        ->and($link->textContent)->toBe('@grace')
        ->and($document->textContent)->toBe('Hello @grace and @plain.');

    $ineligible = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement(['username' => 'ineligible']);

    $ineligibleHtml = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => "Hello [@ineligible](craft-user:$ineligible->id).",
    ])->assertOk()->json('event.comment.html');

    $plainHtml = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => 'Hello @grace.',
    ])->assertOk()->json('event.comment.html');

    $literalHtml = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => "Literal \\[@ineligible](craft-user:$ineligible->id) and `[@ineligible](craft-user:$ineligible->id)`.",
    ])->assertOk()->json('event.comment.html');

    expect(html_entity_decode(strip_tags($ineligibleHtml)))->toContain('@ineligible')
        ->and($ineligibleHtml)->not->toContain('<a')
        ->and(html_entity_decode(strip_tags($plainHtml)))->toContain('@grace')
        ->and($plainHtml)->not->toContain('<a')
        ->and(html_entity_decode(strip_tags($literalHtml)))->toContain('@ineligible')
        ->and($literalHtml)->not->toContain('<a');
});

it('keeps mentions readable when users are renamed or deleted', function () {
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $author = User::findOne();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $viewPermissions = [
        'accessCp',
        "editSite:$site->uid",
        "viewEntries:$section->uid",
        "viewPeerEntries:$section->uid",
    ];
    $mentioned = UserModel::factory()
        ->withPermissions($viewPermissions)
        ->createElement(['username' => 'grace']);
    $markdown = "Hello [@grace](craft-user:$mentioned->id).";

    $commentId = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => $markdown,
    ])->assertOk()->json('event.id');

    DB::table(Table::USERS)->where('id', $mentioned->id)->update(['username' => 'grace-new']);

    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $commentId,
        'markdown' => $markdown,
    ])->assertOk();

    $viewer = UserModel::factory()
        ->withPermissions($viewPermissions)
        ->createElement(['username' => 'viewer']);
    actingAs($viewer);

    $hiddenLiveUserHtml = postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])->assertOk()->json('events.0.comment.html');

    expect($hiddenLiveUserHtml)->toContain('grace-new')->not->toContain('<a');

    DB::table(Table::ELEMENTS)->where('id', $mentioned->id)->delete();
    actingAs($author);

    $deletedUserHtml = postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])->assertOk()->json('events.0.comment.html');

    expect($deletedUserHtml)->toContain('grace-new')->not->toContain('<a');
});

it('notifies mentioned users with the comment text', function () {
    Notification::fake();
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $mentioned = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "editSite:$site->uid",
            "viewEntries:$section->uid",
            "viewPeerEntries:$section->uid",
        ])
        ->createElement(['username' => 'grace']);
    postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => "Hello [@grace](craft-user:$mentioned->id).",
    ])->assertOk();

    Notification::assertSentTo(
        $mentioned,
        ActivityMentionNotification::class,
        fn (ActivityMentionNotification $notification): bool => $notification->toDatabase($mentioned)['message']
            === 'Hello @grace.',
    );
});

it('notifies users newly mentioned in each comment edit', function () {
    Notification::fake();
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    $section = $this->entry->getSection();
    $site = Sites::getSiteById($this->entry->siteId);
    $permissions = [
        'accessCp',
        "editSite:$site->uid",
        "viewEntries:$section->uid",
        "viewPeerEntries:$section->uid",
    ];
    $grace = UserModel::factory()->withPermissions($permissions)->createElement(['username' => 'grace']);
    $ada = UserModel::factory()->withPermissions($permissions)->createElement(['username' => 'ada']);

    $commentId = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => "Hello [@grace](craft-user:$grace->id).",
    ])->assertOk()->json('event.id');

    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $commentId,
        'markdown' => "Hello [@ada](craft-user:$ada->id).",
    ])->assertOk();

    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $commentId,
        'markdown' => "Hello [@grace](craft-user:$grace->id) and [@ada](craft-user:$ada->id).",
    ])->assertOk();

    Notification::assertSentTimes(ActivityMentionNotification::class, 3);
    Notification::assertSentToTimes(
        $grace,
        ActivityMentionNotification::class,
        2,
    );
    Notification::assertSentTo(
        $ada,
        ActivityMentionNotification::class,
    );
});

it('edits and removes a comment in place on the timeline', function () {
    $author = User::findOne();
    Date::setTestNow('2026-08-25 10:00:00');

    $created = postJson($this->createCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'markdown' => 'First version',
    ])->assertOk()->json('event');

    app(Activities::class)->record(new ElementUpdated(subject: $this->entry));

    Date::setTestNow('2026-08-25 11:00:00');
    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $created['id'],
        'markdown' => 'Second version',
    ])
        ->assertOk()
        ->assertJsonPath('event.id', $created['id'])
        ->assertJsonPath('event.description.text', 'Commented.')
        ->assertJsonPath('event.occurredAt', $created['occurredAt'])
        ->assertJsonPath('event.comment.edited', true)
        ->assertJsonPath('event.comment.deleted', false)
        ->assertJsonPath('event.comment.markdown', 'Second version');

    $otherAdmin = UserModel::factory()->createElement(['admin' => true]);
    actingAs($otherAdmin);

    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $created['id'],
        'markdown' => 'Admin rewrite',
    ])->assertForbidden();

    deleteJson($this->deleteCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $created['id'],
    ])
        ->assertOk()
        ->assertJsonPath('event.id', $created['id'])
        ->assertJsonPath('event.icon', 'comment-slash')
        ->assertJsonPath('event.actor.label', $otherAdmin->name)
        ->assertJsonPath('event.description.text', 'Removed a comment.')
        ->assertJsonPath('event.occurredAt', $created['occurredAt'])
        ->assertJsonPath('event.comment.deleted', true)
        ->assertJsonPath('event.comment.canEdit', false)
        ->assertJsonPath('event.comment.canDelete', false);

    actingAs($author);

    patchJson($this->editCommentUrl, [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'commentId' => $created['id'],
        'markdown' => 'Resurrected',
    ])->assertUnprocessable();

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
    ])
        ->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.0.id', $created['id'])
        ->assertJsonPath('events.0.description.text', 'Removed a comment.')
        ->assertJsonPath('events.0.comment.deleted', true);
});

class TimelineFormattedActivity extends ActivityEventType
{
    protected const string LABEL = 'Fallback label';

    protected const string ICON = 'bolt';

    public static function source(): ActivitySource
    {
        return new ActivitySource('test', 'Test Plugin', 'app');
    }

    public static function format(ActivityEvent $event): HtmlString
    {
        return new HtmlString('<strong>Formatted safely</strong><script>bad()</script>');
    }
}

class TimelineUniconedActivity extends ActivityEventType
{
    protected const string LABEL = 'Received webhook';
}
