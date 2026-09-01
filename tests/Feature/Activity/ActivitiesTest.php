<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Enums\ActivityActorType;
use CraftCms\Cms\Activity\EventTypes\ElementStatusChanged;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Activities as ActivitiesFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;
use function Pest\Laravel\get;

beforeEach(function () {
    loadTestPlugin();
    $this->activities = app(Activities::class);
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

    $event = $this->activities->record(new TestPluginEntryUpdated(
        reason: 'Published',
        subject: $draft,
        site: $site,
        changes: [new ActivityChange('Summary', null, 'Ready')],
    ));

    expect($event->id)->toBeString()
        ->and($event->eventType)->toBe(TestPluginEntryUpdated::class)
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
        ->and($event->changes)->toEqual([
            new ActivityChange('Summary', null, 'Ready'),
        ])
        ->and($event->data)->toBe(['reason' => 'Published']);
});

it('distinguishes system anonymous and known user actors and captures impersonation', function () {
    $system = $this->activities->record(new TestPluginEntryUpdated(reason: 'System'));
    $anonymous = $this->activities->record(new TestPluginEntryUpdated(
        reason: 'Public form',
        actor: ActivityActor::anonymous(),
    ));

    $operator = User::factory()->createElement(['fullName' => 'Operator', 'admin' => true]);
    $actor = User::factory()->createElement(['fullName' => 'Editor']);
    $this->actingAs($actor);
    app(Impersonation::class)->setImpersonatorId($operator->id);

    $user = $this->activities->record(new TestPluginEntryUpdated(reason: 'Edited'));

    expect($system->actorType)->toBe(ActivityActorType::System)
        ->and($anonymous->actorType)->toBe(ActivityActorType::Anonymous)
        ->and($user->actorType)->toBe(ActivityActorType::User)
        ->and($user->snapshots['impersonator'])->toBe([
            'id' => $operator->id,
            'label' => $operator->name,
        ]);
});

it('uses the email for unnamed user actors', function () {
    $actor = User::factory()->createElement(['email' => 'editor@example.com']);
    $actor->setName('');
    $this->actingAs($actor);

    $event = $this->activities->record(new TestPluginEntryUpdated(reason: 'Edited'));

    expect($event->snapshots['actor']['label'])->toBe('editor@example.com');
});

it('attributes unauthenticated HTTP activity to an anonymous actor', function () {
    Route::get('test/activity-actor', fn () => ActivitiesFacade::record(
        new TestPluginEntryUpdated(reason: 'Public request'),
    )->actorType->value);

    get('test/activity-actor')
        ->assertOk()
        ->assertSeeText(ActivityActorType::Anonymous->value);
});

it('rejects invalid changes', function () {
    expect(fn () => new ActivityChange('', null, 'Ready'))
        ->toThrow(InvalidArgumentException::class);
});

it('rolls records back with their semantic action', function () {
    DB::beginTransaction();

    $this->activities->record(new TestPluginEntryUpdated(reason: 'Edited'));

    DB::rollBack();

    expect($this->activities->query()->get())->toBeEmpty();
});

it('queries fixed criteria and paginates equal timestamps without gaps', function () {
    Date::setTestNow('2026-08-25 12:00:00');

    $subject = new ActivitySubject('document', 'one', 'Document one');
    $otherSubject = new ActivitySubject('document', 'two', 'Document two');
    $craftSubject = Entry::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $craftEvent = $this->activities->record(new ElementStatusChanged(
        subject: $craftSubject,
        site: null,
        oldStatus: 'pending',
        newStatus: 'disabled',
    ));
    $first = $this->activities->record(new TestPluginEntryUpdated(reason: 'First', subject: $subject));
    $second = $this->activities->record(new TestPluginEntryUpdated(reason: 'Second', subject: $subject));
    $this->activities->record(new TestPluginEntryUpdated(reason: 'Other', subject: $otherSubject));
    $this->activities->record(new TestPluginEntryUpdated(
        reason: 'Anonymous',
        subject: $subject,
        actor: ActivityActor::anonymous(),
    ));
    $this->activities->record(new TestPluginEntryFeatured(subject: $subject));

    $page = $this->activities->query()
        ->subject($subject)
        ->eventTypes(TestPluginEntryUpdated::class)
        ->actor(ActivityActor::system())
        ->source('test-plugin')
        ->occurredFrom(Date::parse('2026-08-25 00:00:00'))
        ->occurredUntil(Date::parse('2026-08-25 23:59:59'))
        ->cursorPaginate(1);
    $nextPage = $this->activities->query()
        ->subject($subject)
        ->eventTypes(TestPluginEntryUpdated::class)
        ->actor(ActivityActor::system())
        ->source('test-plugin')
        ->occurredFrom(Date::parse('2026-08-25 00:00:00'))
        ->occurredUntil(Date::parse('2026-08-25 23:59:59'))
        ->cursorPaginate(1, cursor: $page->nextCursor());

    expect($page->items())->toHaveCount(1)
        ->and($page->items()[0]->id)->toBe($second->id)
        ->and($nextPage->items())->toHaveCount(1)
        ->and($nextPage->items()[0]->id)->toBe($first->id)
        ->and($nextPage->nextCursor())->toBeNull()
        ->and($this->activities->query()->source('craft')->sole()->id)->toBe($craftEvent->id);
});

it('applies occurrence bounds', function () {
    Date::setTestNow('2026-08-25 12:00:00');
    $early = $this->activities->record(new TestPluginEntryUpdated(reason: 'Early'));

    Date::setTestNow('2026-08-25 12:00:02');
    $late = $this->activities->record(new TestPluginEntryUpdated(reason: 'Late'));

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

    $neutral = $this->activities->record(new TestPluginEntryUpdated(reason: 'Neutral'));
    $matching = $this->activities->record(new TestPluginEntryUpdated(reason: 'Matching', site: $site));
    $this->activities->record(new TestPluginEntryUpdated(reason: 'Other', site: $otherSite));

    expect($this->activities->query()->site($site)->pluck('id')->all())
        ->toEqualCanonicalizing([$matching->id, $neutral->id]);
});

it('does not bind retained events to mutable Craft records', function () {
    $actor = User::factory()->createElement();
    $subject = Entry::factory()->createElement();
    $siteModel = Site::factory()->create();

    $event = $this->activities->record(new TestPluginEntryUpdated(
        reason: 'Edited',
        subject: $subject,
        actor: $actor,
        site: Sites::getSiteById($siteModel->id),
    ));

    DB::table(Table::USERS)->where('id', $actor->id)->delete();
    DB::table(Table::ELEMENTS)->where('id', $subject->id)->delete();
    DB::table(Table::SITES)->where('id', $siteModel->id)->delete();

    $retained = $this->activities->query()->firstOrFail();

    expect($retained->id)->toBe($event->id)
        ->and($retained->snapshots)->toMatchArray([
            'actor' => ['label' => $actor->name],
            'subject' => ['label' => $subject->getUiLabel()],
            'site' => ['name' => $siteModel->name],
        ]);
});

it('formats plugin events in the application locale as text or safe HTML', function () {
    app()->setLocale('nl');

    $textEvent = ActivitiesFacade::record(new TestPluginEntryPublished(
        reason: 'Klaar<script>alert(1)</script>',
    ));
    $htmlEvent = ActivitiesFacade::record(new TestPluginEntryFeatured);
    $translatedEvent = ActivitiesFacade::record(new TestPluginEntryTranslated);

    expect(ActivitiesFacade::format($textEvent))->toBe('Klaar')
        ->and(ActivitiesFacade::icon($textEvent))->toBe('bullhorn')
        ->and(ActivitiesFacade::format($htmlEvent))->toBeInstanceOf(Htmlable::class)
        ->and(ActivitiesFacade::format($htmlEvent)->toHtml())->toBe('<strong>Entry featured</strong>')
        ->and(ActivitiesFacade::format(
            ActivitiesFacade::record(new TestPluginEntryUpdated(reason: 'Klaar')),
        ))->toBe('Entry updated')
        ->and(ActivitiesFacade::format($translatedEvent))->toBe('Bewaren');
});

it('translates status labels for the application locale', function () {
    app()->setLocale('nl');

    $event = ActivitiesFacade::record(new ElementStatusChanged(
        subject: Entry::factory()->createElement(),
        site: null,
        oldStatus: 'pending',
        newStatus: 'disabled',
    ));

    expect(ActivitiesFacade::format($event))
        ->toContain(t('Pending'))
        ->toContain(t('Disabled'))
        ->not->toContain('Pending')
        ->not->toContain('Disabled');
});

it('reports formatter failures and keeps retained events readable when their class is unavailable', function () {
    Exceptions::fake();

    $event = ActivitiesFacade::record(new TestPluginEntryFailed);
    $retainedEvent = clone $event;
    $retainedEvent->eventType = 'Missing\\ActivityEventType';

    expect(ActivitiesFacade::format($event))->toBe('Entry formatting failed')
        ->and(ActivitiesFacade::format($retainedEvent))->toBe('Entry formatting failed')
        ->and(ActivitiesFacade::icon($retainedEvent))->toBe('wave-pulse');
    Exceptions::assertReported(RuntimeException::class);
});

abstract class TestPluginActivityEventType extends ActivityEventType
{
    public static function source(): ActivitySource
    {
        return ActivitySource::fromPlugin(TestPlugin::getInstance());
    }
}

class TestPluginEntryUpdated extends TestPluginActivityEventType
{
    protected const string LABEL = 'Entry updated';

    public function __construct(
        private readonly string $reason,
        ElementInterface|ActivitySubject|null $subject = null,
        UserElement|ActivityActor|null $actor = null,
        ?SiteData $site = null,
        array $changes = [],
    ) {
        parent::__construct($subject, $actor, $site, $changes);
    }

    public function data(): array
    {
        return ['reason' => $this->reason];
    }
}

class TestPluginEntryPublished extends TestPluginEntryUpdated
{
    protected const string LABEL = 'Entry published';

    protected const string ICON = 'bullhorn';

    public static function format(ActivityEvent $event): string
    {
        return $event->data['reason'];
    }
}

class TestPluginEntryFeatured extends TestPluginActivityEventType
{
    protected const string LABEL = 'Entry featured';

    public static function format(ActivityEvent $event): HtmlString
    {
        return new HtmlString('<strong>Entry featured</strong><script>alert(1)</script>');
    }
}

class TestPluginEntryTranslated extends TestPluginActivityEventType
{
    protected const string LABEL = 'Save';

    public static function source(): ActivitySource
    {
        return new ActivitySource('test-plugin', 'Test Plugin', 'app');
    }
}

class TestPluginEntryFailed extends TestPluginActivityEventType
{
    protected const string LABEL = 'Entry formatting failed';

    public static function format(ActivityEvent $event): never
    {
        throw new RuntimeException('Formatter failed.');
    }
}
