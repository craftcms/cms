<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Gql\Events\ElementPopulated;
use CraftCms\Cms\Gql\Events\ElementPopulating;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Types\Input\ContentBlock;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
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

        public function publicNormalizeArguments(ResolveInfo $info, array $arguments): array
        {
            return $this->recursivelyNormalizeArgumentValues($info, $arguments);
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

it('dispatches ElementPopulating and ElementPopulated events', function () {
    Event::fake([ElementPopulating::class, ElementPopulated::class]);

    $fixture = createElementMutationResolverEntryFixture();

    $entry = EntryModel::factory()
        ->forSection($fixture['section'])
        ->forEntryType($fixture['entryType'])
        ->createElement(['title' => 'Test', 'slug' => 'test']);

    $resolver = createConcreteElementMutationResolver();
    $resolver->publicPopulateElementWithData($entry, ['title' => 'New']);

    Event::assertDispatched(fn (ElementPopulating $event) => $event->arguments === ['title' => 'New']);

    Event::assertDispatched(ElementPopulated::class);
});

it('allows ElementPopulating to modify arguments', function () {
    Event::listen(ElementPopulating::class, function (ElementPopulating $event) {
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

it('validates required fields only when saving an enabled element', function (bool $enabled) {
    $field = Field::factory()->create([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ]);
    $entryType = EntryType::factory()->withField($field, required: true)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Live Test', 'slug' => 'live-test']);

    $entry->setAuthorIds([User::findOne()->id]);
    $entry->title = 'Updated Title';
    $entry->enabled = $enabled;

    $save = fn () => createConcreteElementMutationResolver()->publicSaveElement($entry);

    if ($enabled) {
        expect($save)->toThrow(UserError::class, 'The Summary field is required.');
        expect(EntryElement::find()->id($entry->id)->status(null)->one()->title)->toBe('Live Test');
    } else {
        $save();
        expect(EntryElement::find()->id($entry->id)->status(null)->one()->title)->toBe('Updated Title');
    }
})->with([
    'enabled' => true,
    'disabled' => false,
]);

it('keeps nested argument types local regardless of input order', function () {
    $input = new InputObjectType([
        'name' => 'ContentInput',
        'fields' => ['value' => Type::string()],
        'normalizeValue' => [ContentBlock::class, 'normalizeValue'],
    ]);
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldDefinition = new FieldDefinition([
        'name' => 'save',
        'type' => Type::string(),
        'args' => ['children' => Type::nonNull(Type::listOf($input)), 'value' => $input],
    ]);
    $resolver = createConcreteElementMutationResolver();
    $arguments = ['children' => [['value' => 'child'], ['value' => null]], 'value' => ['value' => 'parent']];
    $expected = [
        'children' => ['fields' => [['value' => 'child'], ['value' => null]]],
        'value' => ['fields' => ['value' => 'parent']],
    ];

    foreach ([$arguments, array_reverse($arguments, true), $arguments] as $inputArguments) {
        expect($resolver->publicNormalizeArguments($info, $inputArguments))->toEqual($expected);
    }
});
