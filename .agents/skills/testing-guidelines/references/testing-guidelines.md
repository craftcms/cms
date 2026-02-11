# Testing Guidelines Reference

## Table of Contents

1. CP URLs
2. Elements
3. Creating test elements
4. Creating an entry with a custom field
5. Testing element concerns (traits)
6. Testing Laravel events

## CP URLs

- When testing CP URLs, always use `CraftCms\Cms\Cms::config()->cpTrigger` instead of testing for `/admin`.

## Elements

Important: Do not instantiate element classes directly with `new Entry()` in tests. Use factories to ensure proper database state.

## Creating test elements

```php
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Elements\User;
use function Pest\Laravel\actingAs;

// Authenticate as user (required for most element operations)
actingAs(User::findOne());

// Create an entry using the Eloquent model factory, then query the element
Entry::factory()->create();
$entry = EntryElement::findOne();

// With specific attributes
Entry::factory()->create(['title' => 'Test Entry']);
```

## Creating an entry with a custom field

```php
use craft\behaviors\CustomFieldBehavior;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Fields;

$field = Field::factory()->create([
    'handle' => 'textField',
    'type' => CraftCms\Cms\Field\PlainText::class,
]);

$fieldLayout = FieldLayout::factory()->forField($field)->create();
$entry = EntryModel::factory()->create();

$entry->element->update([
    'fieldLayoutId' => $fieldLayout->id,
]);

CustomFieldBehavior::$fieldHandles[$field->handle] = true;
Fields::refreshFields();

$entry = entryQuery()->id($entry->id)->firstOrFail();
$entry->title = 'Test entry';
$entry->setFieldValue('textField', 'Foo');

Craft::$app->getElements()->saveElement($entry);
```

## Testing element concerns (traits)

When testing element traits/concerns, create a minimal test element class that extends `Element` and overrides only the methods you need to test:

```php
use CraftCms\Cms\Element\Element;

class TestRoutableElement extends Element
{
    protected ?string $customRoute = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    public function setCustomRoute(?string $route): void
    {
        $this->customRoute = $route;
    }

    #[Override]
    protected function route(): array|string|null
    {
        return $this->customRoute;
    }
}

test('returns custom route', function () {
    $element = new TestRoutableElement;
    $element->setCustomRoute('my/route');

    expect($element->getRoute())->toBe('my/route');
});
```

This pattern allows testing trait behavior without needing factories or database state. See `tests/Element/Concerns/` for examples.

**Important**: When writing integration tests for a trait using a concrete subclass (like `Entry`), assert against the subclass's actual behavior, not the trait's default return values. For example, `Entry` overrides `getCardTitle()`, `getCrumbs()`, and status values — your assertions must account for these overrides.

## Database best practices

- Never use hardcoded IDs in tests involving database relations. Always use factories to generate valid records and reference their dynamic IDs. Hardcoded IDs will cause foreign key violations.
- When tests require an authenticated user, ensure the database is seeded first. A `Call to a member function update() on null` error during `actingAs` typically means no user exists in the database yet.

## Testing Laravel events

Use Laravel's event fakes to test that events are dispatched correctly:

```php
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Element\Events\AfterSave;
use Illuminate\Support\Facades\Event;

test('dispatches save events', function () {
    Event::fake([BeforeSave::class, AfterSave::class]);

    $entry = Entry::factory()->create();
    $element = entryQuery()->id($entry->id)->one();

    Craft::$app->getElements()->saveElement($element);

    Event::assertDispatched(BeforeSave::class, function ($event) use ($element) {
        return $event->element->id === $element->id;
    });

    Event::assertDispatched(AfterSave::class);
});
```

Testing cancellable events:

```php
test('can cancel save via event', function () {
    Event::listen(BeforeSave::class, function (BeforeSave $event) {
        $event->isValid = false;
    });

    $entry = Entry::factory()->create();
    $element = entryQuery()->id($entry->id)->one();

    $result = Craft::$app->getElements()->saveElement($element);

    expect($result)->toBeFalse();
});
```

Testing event data modification:

```php
test('can modify event data', function () {
    Event::listen(DefineUrl::class, function (DefineUrl $event) {
        $event->url = 'https://custom-url.com';
    });

    $element = new TestElement();

    expect($element->getUrl())->toBe('https://custom-url.com');
});
```
