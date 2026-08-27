<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityEventRecorder;
use CraftCms\Cms\Activity\ActivityEventTypes;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Enums\ActivityActorType;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Activities as ActivitiesFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    loadTestPlugin();
    $this->source = ActivitySource::fromPlugin(TestPlugin::getInstance());
    $this->activities = app(Activities::class);
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.updated',
        source: $this->source,
        label: 'Entry updated',
        rules: ['reason' => ['required', 'string']],
    );
});

afterEach(function () {
    Date::setTestNow();
});

it('records durable actor subject site and payload snapshots', function () {
    $actor = User::factory()->createElement(['fullName' => 'Ada Lovelace']);
    $subject = Entry::factory()->createElement(['title' => 'Release notes']);
    $site = Sites::getSiteById(Site::factory()->create()->id);
    $draft = app(Drafts::class)->createDraft($subject, $actor->id);

    $this->actingAs($actor);

    $event = $this->activities->record(
        eventType: 'test-plugin.entry.updated',
        subject: $draft,
        site: $site,
        data: ['reason' => 'Published'],
        changes: [[
            'type' => 'field',
            'id' => 'summary',
            'label' => 'Summary',
            'old' => null,
            'new' => 'Ready',
        ]],
    );

    expect($event->id)->toBeString()
        ->and($event->eventType)->toBe('test-plugin.entry.updated')
        ->and($event->source)->toBe('test-plugin')
        ->and($event->actorType)->toBe(ActivityActorType::User)
        ->and($event->actorId)->toBe($actor->id)
        ->and($event->subjectType)->toBe($subject::class)
        ->and($event->subjectId)->toBe($subject->uid)
        ->and($event->siteId)->toBe($site->id)
        ->and($event->snapshots)->toMatchArray([
            'actor' => ['label' => 'Ada Lovelace'],
            'subject' => ['label' => 'Release notes'],
            'site' => ['name' => $site->getName(false)],
            'source' => ['label' => 'Test Plugin'],
            'event' => ['label' => 'Entry updated'],
        ])
        ->and($event->changes)->toBe([[
            'type' => 'field',
            'id' => 'summary',
            'label' => 'Summary',
            'old' => null,
            'new' => 'Ready',
        ]])
        ->and($event->data)->toBe(['reason' => 'Published']);
});

it('distinguishes system anonymous and known user actors and captures impersonation', function () {
    $system = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'System']);
    $anonymous = $this->activities->record(
        'test-plugin.entry.updated',
        actor: ActivityActor::anonymous(),
        data: ['reason' => 'Public form'],
    );

    $operator = User::factory()->createElement(['fullName' => 'Operator', 'admin' => true]);
    $actor = User::factory()->createElement(['fullName' => 'Editor']);
    $this->actingAs($actor);
    app(Impersonation::class)->setImpersonatorId($operator->id);

    $user = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Edited']);

    expect($system->actorType)->toBe(ActivityActorType::System)
        ->and($anonymous->actorType)->toBe(ActivityActorType::Anonymous)
        ->and($user->actorType)->toBe(ActivityActorType::User)
        ->and($user->snapshots['impersonator'])->toBe([
            'id' => $operator->id,
            'label' => $operator->name,
        ]);
});

it('rejects unregistered types and invalid payload sections', function () {
    expect(fn () => $this->activities->record('test.unknown.happened'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->activities->record('test-plugin.entry.updated', data: []))
        ->toThrow(ValidationException::class)
        ->and(fn () => $this->activities->record(
            'test-plugin.entry.updated',
            data: ['reason' => 'Edited'],
            changes: [['type' => 'field', 'id' => 'summary']],
        ))->toThrow(ValidationException::class);
});

it('rolls records back with their semantic action', function () {
    DB::beginTransaction();

    $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Edited']);

    DB::rollBack();

    expect($this->activities->query()->get())->toBeEmpty();
});

it('queries fixed criteria and paginates equal timestamps without gaps', function () {
    Date::setTestNow('2026-08-25 12:00:00');

    $subject = new ActivitySubject('document', 'one', 'Document one');
    $otherSubject = new ActivitySubject('document', 'two', 'Document two');

    $first = $this->activities->record('test-plugin.entry.updated', subject: $subject, data: ['reason' => 'First']);
    $second = $this->activities->record('test-plugin.entry.updated', subject: $subject, data: ['reason' => 'Second']);
    $this->activities->record('test-plugin.entry.updated', subject: $otherSubject, data: ['reason' => 'Other']);

    $page = $this->activities->query()
        ->subject($subject)
        ->eventTypes('test-plugin.entry.updated')
        ->actor(ActivityActor::system())
        ->source('test-plugin')
        ->occurredFrom(Date::parse('2026-08-25 00:00:00'))
        ->occurredUntil(Date::parse('2026-08-25 23:59:59'))
        ->cursorPaginate(1);
    $nextPage = $this->activities->query()
        ->subject($subject)
        ->cursorPaginate(1, cursor: $page->nextCursor());

    expect($page->items())->toHaveCount(1)
        ->and($page->items()[0]->id)->toBe($second->id)
        ->and($nextPage->items())->toHaveCount(1)
        ->and($nextPage->items()[0]->id)->toBe($first->id)
        ->and($nextPage->nextCursor())->toBeNull();
});

it('blocks updates and deletions through model instances', function () {
    $event = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Recorded']);

    expect(fn () => $event->update(['source' => 'changed']))
        ->toThrow(LogicException::class)
        ->and(fn () => $event->delete())
        ->toThrow(LogicException::class);
});

it('applies occurrence bounds', function () {
    Date::setTestNow('2026-08-25 12:00:00');
    $early = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Early']);

    Date::setTestNow('2026-08-25 12:00:02');
    $late = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Late']);

    $bound = Date::parse('2026-08-25 12:00:01');
    $from = $this->activities->query()->occurredFrom($bound)->get();
    $until = $this->activities->query()->occurredUntil($bound)->get();

    expect($from)->toHaveCount(1)
        ->and($from[0]->id)->toBe($late->id)
        ->and($until)->toHaveCount(1)
        ->and($until[0]->id)->toBe($early->id);
});

it('keeps site-neutral events in site-scoped queries', function () {
    $site = Sites::getSiteById(Site::factory()->create()->id);
    $otherSite = Sites::getSiteById(Site::factory()->create()->id);

    $neutral = $this->activities->record('test-plugin.entry.updated', data: ['reason' => 'Neutral']);
    $matching = $this->activities->record('test-plugin.entry.updated', site: $site, data: ['reason' => 'Matching']);
    $this->activities->record('test-plugin.entry.updated', site: $otherSite, data: ['reason' => 'Other']);

    expect($this->activities->query()->site($site)->get())
        ->sequence(
            fn ($event) => $event->id->toBe($matching->id),
            fn ($event) => $event->id->toBe($neutral->id),
        );
});

it('does not bind retained events to mutable Craft records', function () {
    $actor = User::factory()->createElement();
    $subject = Entry::factory()->createElement();
    $siteModel = Site::factory()->create();

    $event = $this->activities->record(
        'test-plugin.entry.updated',
        subject: $subject,
        actor: $actor,
        site: Sites::getSiteById($siteModel->id),
        data: ['reason' => 'Edited'],
    );

    DB::table(Table::USERS)->where('id', $actor->id)->delete();
    DB::table(Table::ELEMENTS)->where('id', $subject->id)->delete();
    DB::table(Table::SITES)->where('id', $siteModel->id)->delete();

    expect($this->activities->query()->first()->id)->toBe($event->id);
});

it('rejects duplicate plugin event registrations', function () {
    expect(fn () => ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.updated',
        source: $this->source,
        label: 'Entry updated',
    ))->toThrow(LogicException::class);
});

it('formats plugin events for the requested locale as text or safe HTML', function () {
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.published',
        source: $this->source,
        label: 'Entry published',
        formatter: fn (ActivityEvent $event, string $locale): string => "$locale: {$event->data['reason']}",
        rules: ['reason' => ['required', 'string']],
        icon: 'bullhorn',
    );
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.featured',
        source: $this->source,
        label: 'Entry featured',
        formatter: fn (): HtmlString => new HtmlString('<strong>Entry featured</strong><script>alert(1)</script>'),
    );
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.translated',
        source: new ActivitySource('test-plugin', 'Test Plugin', 'app'),
        label: 'Save',
    );

    $textEvent = ActivitiesFacade::record('test-plugin.entry.published', data: ['reason' => 'Klaar']);
    $htmlEvent = ActivitiesFacade::record('test-plugin.entry.featured');
    $translatedEvent = ActivitiesFacade::record('test-plugin.entry.translated');

    expect(ActivitiesFacade::format($textEvent, 'nl'))->toBe('nl: Klaar')
        ->and(ActivitiesFacade::icon($textEvent))->toBe('bullhorn')
        ->and(ActivitiesFacade::format($htmlEvent, 'en'))->toBeInstanceOf(HtmlString::class)
        ->and(ActivitiesFacade::format($htmlEvent, 'en')->toHtml())->toBe('<strong>Entry featured</strong>')
        ->and(ActivitiesFacade::format(
            ActivitiesFacade::record('test-plugin.entry.updated', data: ['reason' => 'Klaar']),
            'nl',
        ))->toBe('Entry updated')
        ->and(ActivitiesFacade::format($translatedEvent, 'nl'))->toBe('Bewaren');
});

it('reports formatter failures and keeps retained plugin events readable without registration', function () {
    Exceptions::fake();
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.failed',
        source: $this->source,
        label: 'Entry formatting failed',
        formatter: fn () => throw new RuntimeException('Formatter failed.'),
    );

    $event = ActivitiesFacade::record('test-plugin.entry.failed');
    $eventTypes = new ActivityEventTypes;
    $htmlSanitizers = app(HtmlSanitizerManager::class);
    $events = new ActivityEventRecorder($eventTypes, app(Impersonation::class));
    $retainedActivities = new Activities(
        $eventTypes,
        $htmlSanitizers,
        $events,
    );

    expect(ActivitiesFacade::format($event))->toBe('Entry formatting failed')
        ->and($retainedActivities->format($event))->toBe('Entry formatting failed')
        ->and($retainedActivities->icon($event))->toBe('wave-pulse');
    Exceptions::assertReported(RuntimeException::class);
});

it('reports invalid formatter output and uses the captured fallback', function () {
    Exceptions::fake();
    ActivitiesFacade::extend(
        eventType: 'test-plugin.entry.invalid',
        source: $this->source,
        label: 'Entry formatter invalid',
        formatter: fn (): int => 42,
    );

    $event = ActivitiesFacade::record('test-plugin.entry.invalid');

    expect(ActivitiesFacade::format($event))->toBe('Entry formatter invalid');
    Exceptions::assertReported(UnexpectedValueException::class);
});
