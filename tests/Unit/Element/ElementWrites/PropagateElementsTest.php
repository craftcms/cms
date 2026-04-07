<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterPropagateElement;
use CraftCms\Cms\Element\Events\AfterPropagateElements;
use CraftCms\Cms\Element\Events\BeforePropagateElement;
use CraftCms\Cms\Element\Events\BeforePropagateElements;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Operations\ElementUris;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites as SitesService;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

class TestPropagateElementsActionElement extends Element
{
    public bool $afterPropagateCalled = false;

    #[Override]
    public static function displayName(): string
    {
        return 'Propagation Test Element';
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    #[Override]
    public function afterPropagate(bool $isNew): void
    {
        $this->afterPropagateCalled = true;
    }
}

beforeEach(function () {
    $primarySite = new Site;
    $primarySite->id = 1;
    $primarySite->uid = 'site-1';
    $primarySite->handle = 'primary';
    $primarySite->language = 'en-US';
    $primarySite->primary = true;

    $secondarySite = new Site;
    $secondarySite->id = 2;
    $secondarySite->uid = 'site-2';
    $secondarySite->handle = 'secondary';
    $secondarySite->language = 'en-US';
    $secondarySite->primary = false;

    $tertiarySite = new Site;
    $tertiarySite->id = 3;
    $tertiarySite->uid = 'site-3';
    $tertiarySite->handle = 'tertiary';
    $tertiarySite->language = 'fr';
    $tertiarySite->primary = false;

    $sites = collect([$primarySite, $secondarySite, $tertiarySite]);

    app()->instance(SitesService::class, new class($sites) extends SitesService
    {
        protected ?Site $currentSite = null;

        public function __construct(private readonly Collection $sites) {}

        public function getAllSites(?bool $withDisabled = null): Collection
        {
            return $this->sites;
        }

        public function getAllSiteIds(?bool $withDisabled = null): Collection
        {
            return $this->sites->pluck('id')->values();
        }

        public function getPrimarySite(): Site
        {
            return $this->sites->firstWhere('primary', true);
        }

        public function getSiteById(int $siteId, ?bool $withDisabled = null): ?Site
        {
            return $this->sites->firstWhere('id', $siteId);
        }

        public function setCurrentSite(mixed $site): void
        {
            $this->currentSite = $site instanceof Site ? $site : $this->getSiteById((int) $site);
        }

        public function getCurrentSite(): Site
        {
            return $this->currentSite ?? $this->getPrimarySite();
        }
    });

    Facade::clearResolvedInstance(SitesService::class);
    Sites::setCurrentSite($primarySite);

    $this->elementCaches = Mockery::mock(ElementCaches::class);
    $this->elements = Mockery::mock(Elements::class);

    $this->action = new TestPropagateElementsWrites(
        $this->elements,
        Mockery::mock(ElementUris::class),
        $this->elementCaches,
        Mockery::mock(Search::class),
        app(SitesService::class),
    );
    $this->writes = $this->action;
});

afterEach(function () {
    app()->forgetInstance(BulkOps::class);
    app()->forgetInstance(SitesService::class);

    Facade::clearResolvedInstance(BulkOps::class);
    Facade::clearResolvedInstance(SitesService::class);
});

function fakeBulkOps(): void
{
    $bulkOps = new class
    {
        public int $ensureCalls = 0;

        /** @var array<int, Element> */
        public array $trackedElements = [];

        public function ensure(callable $callback): mixed
        {
            $this->ensureCalls++;

            return $callback();
        }

        public function trackElement(Element $element): void
        {
            $this->trackedElements[] = $element;
        }
    };

    app()->instance(BulkOps::class, $bulkOps);
    Facade::clearResolvedInstance(BulkOps::class);
}

function createQueryMock(array $elements): ElementQueryInterface
{
    $query = Mockery::mock(ElementQueryInterface::class);

    $query->shouldReceive('each')
        ->once()
        ->andReturnUsing(function (callable $callback) use ($elements): void {
            foreach ($elements as $element) {
                $callback($element);
            }
        });

    return $query;
}

function createElement(int $id, int $siteId = 1, ?DateTime $dateUpdated = null): TestPropagateElementsActionElement
{
    $element = new TestPropagateElementsActionElement;
    $element->id = $id;
    $element->siteId = $siteId;
    $element->dateUpdated = $dateUpdated ?? new DateTime('2026-04-01 12:00:00');
    $element->markAsClean();

    return $element;
}

it('propagates elements to supported target sites and dispatches lifecycle events', function () {
    fakeBulkOps();
    Event::fake([
        BeforePropagateElements::class,
        BeforePropagateElement::class,
        AfterPropagateElement::class,
        AfterPropagateElements::class,
    ]);

    $element = createElement(100);
    $query = createQueryMock([$element]);
    $olderSiteElement = createElement(100, 2, new DateTime('2026-03-31 12:00:00'));

    $this->elements
        ->shouldReceive('getElementById')
        ->once()
        ->with(100, TestPropagateElementsActionElement::class, 2)
        ->andReturn($olderSiteElement);

    $this->elements
        ->shouldReceive('getElementById')
        ->once()
        ->with(100, TestPropagateElementsActionElement::class, 3)
        ->andReturnNull();

    $this->elementCaches
        ->shouldReceive('invalidateForElement')
        ->once()
        ->with($element);

    $this->action->propagateElements($query);

    expect($element->getScenario())->toBe(Element::SCENARIO_ESSENTIALS)
        ->and($element->newSiteIds)->toBe([])
        ->and($element->afterPropagateCalled)->toBeTrue();

    $bulkOps = app(BulkOps::class);

    expect($bulkOps->ensureCalls)->toBe(1)
        ->and($bulkOps->trackedElements)->toBe([$element])
        ->and($this->writes->propagateCalls)->toHaveCount(2)
        ->and($this->writes->propagateCalls[0]['element'])->toBe($element)
        ->and(array_keys($this->writes->propagateCalls[0]['supportedSites']))->toBe([1, 2, 3])
        ->and($this->writes->propagateCalls[0]['siteId'])->toBe(2)
        ->and($this->writes->propagateCalls[0]['siteElement'])->toBe($olderSiteElement)
        ->and($this->writes->propagateCalls[1]['siteId'])->toBe(3)
        ->and($this->writes->propagateCalls[1]['siteElement'])->toBeFalse();

    Event::assertDispatched(fn (BeforePropagateElements $event): bool => $event->query === $query);
    Event::assertDispatched(fn (BeforePropagateElement $event): bool => $event->query === $query
        && $event->element === $element
        && $event->position === 1);
    Event::assertDispatched(fn (AfterPropagateElement $event): bool => $event->query === $query
        && $event->element === $element
        && $event->position === 1
        && $event->exception === null);
    Event::assertDispatched(fn (AfterPropagateElements $event): bool => $event->query === $query);
});

it('filters requested site ids and skips the source site and newer localized elements', function () {
    fakeBulkOps();

    $element = createElement(200);
    $query = createQueryMock([$element]);
    $newerSiteElement = createElement(200, 3, new DateTime('2026-04-02 12:00:00'));

    $this->elements
        ->shouldReceive('getElementById')
        ->once()
        ->with(200, TestPropagateElementsActionElement::class, 3)
        ->andReturn($newerSiteElement);

    $this->elementCaches
        ->shouldReceive('invalidateForElement')
        ->once()
        ->with($element);

    $this->action->propagateElements($query, [1, 3, 99]);

    expect($element->afterPropagateCalled)->toBeTrue()
        ->and($this->writes->propagateCalls)->toBeEmpty();
});

it('rethrows propagation errors when continueOnError is false', function () {
    fakeBulkOps();

    $element = createElement(300);
    $query = createQueryMock([$element]);
    $exception = new RuntimeException('Propagation failed.');

    $this->elements
        ->shouldReceive('getElementById')
        ->once()
        ->with(300, TestPropagateElementsActionElement::class, 2)
        ->andReturnNull();

    $this->writes->exceptionToThrow = $exception;

    $this->elementCaches
        ->shouldNotReceive('invalidateForElement');

    expect(fn () => $this->action->propagateElements($query, 2))
        ->toThrow($exception);

    $bulkOps = app(BulkOps::class);

    expect($bulkOps->trackedElements)->toBe([])
        ->and($element->afterPropagateCalled)->toBeFalse();
});

it('continues after propagation errors when continueOnError is true', function () {
    fakeBulkOps();
    Event::fake([AfterPropagateElement::class]);

    $element = createElement(400);
    $query = createQueryMock([$element]);
    $exception = new RuntimeException('Propagation failed.');

    $this->elements
        ->shouldReceive('getElementById')
        ->once()
        ->with(400, TestPropagateElementsActionElement::class, 2)
        ->andReturnNull();

    $this->writes->exceptionToThrow = $exception;

    $this->elementCaches
        ->shouldReceive('invalidateForElement')
        ->once()
        ->with($element);

    $this->action->propagateElements($query, 2, true);

    $bulkOps = app(BulkOps::class);

    expect($bulkOps->trackedElements)->toBe([$element])
        ->and($element->afterPropagateCalled)->toBeFalse();

    Event::assertDispatched(fn (AfterPropagateElement $event): bool => $event->element === $element
        && $event->exception === $exception);
});

it('swallows aborted queries and still dispatches the final event', function () {
    fakeBulkOps();
    Event::fake([BeforePropagateElements::class, AfterPropagateElements::class]);

    $query = Mockery::mock(ElementQueryInterface::class);
    $query->shouldReceive('each')
        ->once()
        ->andThrow(new QueryAbortedException);

    $this->elementCaches
        ->shouldNotReceive('invalidateForElement');

    $this->action->propagateElements($query);

    $bulkOps = app(BulkOps::class);

    expect($bulkOps->ensureCalls)->toBe(1)
        ->and($bulkOps->trackedElements)->toBe([])
        ->and($this->writes->propagateCalls)->toBeEmpty();

    Event::assertDispatched(fn (BeforePropagateElements $event): bool => $event->query === $query);
    Event::assertDispatched(fn (AfterPropagateElements $event): bool => $event->query === $query);
});

readonly class TestPropagateElementsWrites extends ElementWrites
{
    public array $propagateCalls = [];

    public ?Throwable $exceptionToThrow = null;

    #[Override]
    public function propagate(
        ElementInterface $element,
        array $supportedSites,
        int $siteId,
        ElementInterface|false|null &$siteElement = null,
        bool $crossSiteValidate = false,
        bool $saveContent = true,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        $this->propagateCalls[] = compact('element', 'supportedSites', 'siteId', 'siteElement');

        if ($this->exceptionToThrow !== null) {
            throw $this->exceptionToThrow;
        }

        return true;
    }
}
