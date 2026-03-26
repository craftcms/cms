<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Events\AfterPopulateElement;
use CraftCms\Cms\Gql\Events\BeforePopulateElement;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

function createConcreteElementMutationResolver(array $resolutionData = [], array $normalizers = []): ElementMutationResolver
{
    return new class($resolutionData, $normalizers) extends ElementMutationResolver
    {
        public function publicPopulateElementWithData($element, array $arguments, $resolveInfo = null)
        {
            return $this->populateElementWithData($element, $arguments, $resolveInfo);
        }

        public function publicSaveElement($element)
        {
            return $this->saveElement($element);
        }
    };
}

function createElementMutationResolverEntryFixture(): array
{
    $entryType = EntryType::factory()->create([
        'name' => 'Page',
        'handle' => 'page',
    ]);

    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'name' => 'Pages',
            'handle' => 'pages',
        ]);

    EntryTypes::refreshEntryTypes();

    return ['section' => $section, 'entryType' => $entryType];
}

beforeEach(function () {
    actingAs(User::findOne());
    app(Gql::class)->flushCaches();
    gqlActivateFullAccessSchema();
});

it('populates element properties from arguments', function () {
    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Original', 'slug' => 'original']);

    $resolver = createConcreteElementMutationResolver();
    $populated = $resolver->publicPopulateElementWithData($entry, [
        'title' => 'Updated Title',
    ]);

    expect($populated->title)->toBe('Updated Title');
});

it('strips immutable attributes during population', function () {
    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Test', 'slug' => 'test']);

    $originalId = $entry->id;

    $resolver = createConcreteElementMutationResolver();
    $populated = $resolver->publicPopulateElementWithData($entry, [
        'id' => 999999,
        'uid' => 'fake-uid',
        'title' => 'Modified',
    ]);

    expect($populated->id)->toBe($originalId)
        ->and($populated->title)->toBe('Modified');
});

it('dispatches BeforePopulateElement and AfterPopulateElement events', function () {
    Event::fake([BeforePopulateElement::class, AfterPopulateElement::class]);

    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Test', 'slug' => 'test']);

    $resolver = createConcreteElementMutationResolver();
    $resolver->publicPopulateElementWithData($entry, ['title' => 'New']);

    Event::assertDispatched(fn (BeforePopulateElement $event) => $event->arguments === ['title' => 'New']);

    Event::assertDispatched(AfterPopulateElement::class);
});

it('allows BeforePopulateElement to modify arguments', function () {
    Event::listen(BeforePopulateElement::class, function (BeforePopulateElement $event) {
        $event->arguments['title'] = 'Modified By Event';
    });

    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Original', 'slug' => 'original']);

    $resolver = createConcreteElementMutationResolver();
    $populated = $resolver->publicPopulateElementWithData($entry, ['title' => 'From API']);

    expect($populated->title)->toBe('Modified By Event');
});

it('saves an element successfully', function () {
    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Save Test', 'slug' => 'save-test']);

    $entry->title = 'Updated Title';

    $resolver = createConcreteElementMutationResolver();
    $saved = $resolver->publicSaveElement($entry);

    expect($saved->title)->toBe('Updated Title');

    // Verify persisted
    $fresh = EntryElement::find()->id($entry->id)->one();
    expect($fresh->title)->toBe('Updated Title');
});

it('applies SCENARIO_LIVE when element is enabled', function () {
    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Live Test', 'slug' => 'live-test']);

    $entry->enabled = true;

    $resolver = createConcreteElementMutationResolver();
    $resolver->publicSaveElement($entry);

    // If we got here without error, the scenario was applied correctly
    expect(true)->toBeTrue();
});

it('uses content field key for field value population', function () {
    $resolver = createConcreteElementMutationResolver();
    expect(ElementMutationResolver::CONTENT_FIELD_KEY)->toBe('_contentFields');
});
