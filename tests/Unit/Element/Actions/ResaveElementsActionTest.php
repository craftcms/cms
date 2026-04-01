<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\Actions\ResaveElementsAction;
use CraftCms\Cms\Element\Actions\SaveElementAction;
use CraftCms\Cms\Element\BulkOp\BulkOps as BulkOpsService;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\AfterResaveElement;
use CraftCms\Cms\Element\Events\AfterResaveElements;
use CraftCms\Cms\Element\Events\BeforeResaveElement;
use CraftCms\Cms\Element\Events\BeforeResaveElements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake([
        BeforeResaveElements::class,
        BeforeResaveElement::class,
        AfterResaveElement::class,
        AfterResaveElements::class,
    ]);

    app(BulkOpsService::class)->resume('test-bulk-op');

    $this->saveElementAction = new TestResaveSaveElementAction;
    $this->action = new ResaveElementsAction($this->saveElementAction);
});

it('resaves matching elements and dispatches lifecycle events', function () {
    $firstElement = new TestResaveElement(['id' => 1]);
    $secondElement = new TestResaveElement(['id' => 2]);
    $query = mockResaveQuery([$firstElement, $secondElement]);

    $this->action->handle(
        query: $query,
        updateSearchIndex: false,
        touch: true,
    );

    expect($this->saveElementAction->calls)->toHaveCount(2);
    expect($this->saveElementAction->calls[0]['element'])->toBe($firstElement);
    expect($this->saveElementAction->calls[0]['updateSearchIndex'])->toBeFalse();
    expect($this->saveElementAction->calls[0]['forceTouch'])->toBeTrue();
    expect($this->saveElementAction->calls[0]['saveContent'])->toBeTrue();
    expect($this->saveElementAction->calls[0]['scenario'])->toBe(Element::SCENARIO_ESSENTIALS);
    expect($this->saveElementAction->calls[0]['resaving'])->toBeTrue();
    expect($this->saveElementAction->calls[1]['element'])->toBe($secondElement);

    Event::assertDispatchedTimes(BeforeResaveElements::class, 1);
    Event::assertDispatched(fn (BeforeResaveElements $event) => $event->query === $query);
    Event::assertDispatchedTimes(BeforeResaveElement::class, 2);
    Event::assertDispatched(fn (BeforeResaveElement $event) => $event->element === $firstElement && $event->position === 1);
    Event::assertDispatched(fn (BeforeResaveElement $event) => $event->element === $secondElement && $event->position === 2);
    Event::assertDispatchedTimes(AfterResaveElement::class, 2);
    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $firstElement && $event->position === 1 && $event->exception === null);
    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $secondElement && $event->position === 2 && $event->exception === null);
    Event::assertDispatchedTimes(AfterResaveElements::class, 1);
    Event::assertDispatched(fn (AfterResaveElements $event) => $event->query === $query);
});

it('reports save errors and continues when continueOnError is enabled', function () {
    $firstElement = new TestResaveElement(['id' => 1]);
    $secondElement = new TestResaveElement(['id' => 2]);
    $query = mockResaveQuery([$firstElement, $secondElement]);

    $this->saveElementAction->exceptionsByElementId[1] = new RuntimeException('First save failed.');

    $this->action->handle(
        query: $query,
        continueOnError: true,
    );

    expect($this->saveElementAction->calls)->toHaveCount(2);

    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $firstElement &&
        $event->position === 1 &&
        $event->exception instanceof RuntimeException &&
        $event->exception->getMessage() === 'First save failed.');

    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $secondElement && $event->position === 2 && $event->exception === null);
    Event::assertDispatchedTimes(AfterResaveElements::class, 1);
});

it('rethrows save errors when continueOnError is disabled', function () {
    $firstElement = new TestResaveElement(['id' => 1]);
    $secondElement = new TestResaveElement(['id' => 2]);
    $query = mockResaveQuery([$firstElement, $secondElement]);

    $this->saveElementAction->exceptionsByElementId[1] = new RuntimeException('First save failed.');

    expect(fn () => $this->action->handle(query: $query))
        ->toThrow(RuntimeException::class, 'First save failed.');

    expect($this->saveElementAction->calls)->toHaveCount(1);

    Event::assertDispatchedTimes(BeforeResaveElements::class, 1);
    Event::assertDispatchedTimes(BeforeResaveElement::class, 1);
    Event::assertNotDispatched(AfterResaveElement::class);
    Event::assertNotDispatched(AfterResaveElements::class);
});

it('wraps revision skips with a fallback label when no UI label exists', function () {
    $element = new TestResaveElement(['id' => 42]);
    $element->revision = true;
    $query = mockResaveQuery([$element]);

    $this->action->handle(
        query: $query,
        continueOnError: true,
    );

    expect($this->saveElementAction->calls)->toBeEmpty();

    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $element &&
        $event->position === 1 &&
        $event->exception instanceof InvalidElementException &&
        $event->exception->getMessage() === "Skipped resaving test element 42 due to an error obtaining its root element: Skipped resaving test element 42 because it's a revision.");
});

it('wraps root lookup errors with the element UI label', function () {
    $element = new TestNestedResaveElement(['id' => 13]);
    $element->label = 'Block A';
    $element->throwOnOwnerLookup = true;
    $query = mockResaveQuery([$element]);

    $this->action->handle(
        query: $query,
        continueOnError: true,
    );

    expect($this->saveElementAction->calls)->toBeEmpty();

    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $element &&
        $event->position === 1 &&
        $event->exception instanceof InvalidElementException &&
        $event->exception->getMessage() === 'Skipped resaving Block A (13) due to an error obtaining its root element: Owner lookup failed');
});

it('resaves revisions when skipRevisions is disabled', function () {
    $element = new TestResaveElement(['id' => 7]);
    $element->revision = true;
    $query = mockResaveQuery([$element]);

    $this->action->handle(
        query: $query,
        skipRevisions: false,
    );

    expect($this->saveElementAction->calls)->toHaveCount(1);
    expect($this->saveElementAction->calls[0]['element'])->toBe($element);

    Event::assertDispatched(fn (AfterResaveElement $event) => $event->element === $element && $event->exception === null);
});

it('fails silently when the query aborts', function () {
    $query = mockAbortedResaveQuery();

    $this->action->handle(query: $query);

    expect($this->saveElementAction->calls)->toBeEmpty();

    Event::assertDispatchedTimes(BeforeResaveElements::class, 1);
    Event::assertDispatchedTimes(AfterResaveElements::class, 1);
    Event::assertNotDispatched(BeforeResaveElement::class);
    Event::assertNotDispatched(AfterResaveElement::class);
});

function mockResaveQuery(array $elements): ElementQueryInterface
{
    $query = Mockery::mock(ElementQueryInterface::class);
    $query->shouldReceive('each')
        ->once()
        ->with(Mockery::type(Closure::class))
        ->andReturnUsing(function (Closure $callback) use ($elements) {
            foreach ($elements as $element) {
                $callback($element);
            }

            return null;
        });

    return $query;
}

function mockAbortedResaveQuery(): ElementQueryInterface
{
    $query = Mockery::mock(ElementQueryInterface::class);
    $query->shouldReceive('each')
        ->once()
        ->with(Mockery::type(Closure::class))
        ->andThrow(new QueryAbortedException);

    return $query;
}

class TestResaveSaveElementAction extends SaveElementAction
{
    public array $calls = [];

    /** @var array<int, Throwable> */
    public array $exceptionsByElementId = [];

    public function __construct() {}

    #[Override]
    public function handle(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        ?array $supportedSites = null,
        bool $forceTouch = false,
        bool $crossSiteValidate = false,
        bool $saveContent = false,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        $this->calls[] = [
            'element' => $element,
            'updateSearchIndex' => $updateSearchIndex,
            'forceTouch' => $forceTouch,
            'saveContent' => $saveContent,
            'scenario' => $element->getScenario(),
            'resaving' => $element->resaving,
        ];

        if (isset($this->exceptionsByElementId[$element->id])) {
            throw $this->exceptionsByElementId[$element->id];
        }

        return true;
    }
}

class TestResaveElement extends Element
{
    public bool $revision = false;

    public string $label = '';

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public function getIsRevision(): bool
    {
        return $this->revision;
    }

    #[Override]
    protected function uiLabel(): ?string
    {
        return $this->label;
    }
}

class TestNestedResaveElement extends TestResaveElement implements NestedElementInterface
{
    public ?int $primaryOwnerId = null;

    public ?int $ownerId = null;

    public ?ElementInterface $owner = null;

    public ?int $sortOrder = null;

    public bool $saveOwnership = true;

    public bool $throwOnOwnerLookup = false;

    #[Override]
    public function getPrimaryOwnerId(): ?int
    {
        return $this->primaryOwnerId;
    }

    #[Override]
    public function setPrimaryOwnerId(?int $id): void
    {
        $this->primaryOwnerId = $id;
    }

    #[Override]
    public function getPrimaryOwner(): ?ElementInterface
    {
        return $this->owner;
    }

    #[Override]
    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        $this->owner = $owner;
        $this->primaryOwnerId = $owner?->id;
    }

    #[Override]
    public function getOwnerId(): ?int
    {
        return $this->ownerId ?? $this->owner?->id;
    }

    #[Override]
    public function setOwnerId(?int $id): void
    {
        $this->ownerId = $id;
    }

    #[Override]
    public function getOwner(): ?ElementInterface
    {
        if ($this->throwOnOwnerLookup) {
            throw new RuntimeException('Owner lookup failed');
        }

        return $this->owner;
    }

    #[Override]
    public function setOwner(?ElementInterface $owner): void
    {
        $this->owner = $owner;
    }

    #[Override]
    public function getOwners(array $criteria = []): array
    {
        return $this->owner ? [$this->owner] : [];
    }

    #[Override]
    public function getField(): ?ElementContainerFieldInterface
    {
        return null;
    }

    #[Override]
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    #[Override]
    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    #[Override]
    public function setSaveOwnership(bool $saveOwnership): void
    {
        $this->saveOwnership = $saveOwnership;
    }
}
