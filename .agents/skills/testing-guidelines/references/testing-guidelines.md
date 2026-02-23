# Testing Guidelines Reference

## Table of Contents

1. Test directory structure and base classes
2. CP URLs
3. Elements
4. Creating test elements
5. Creating an entry with a custom field
6. Testing element concerns (traits)
7. Pest data providers
8. Database best practices
9. Testing Laravel events

## Test directory structure and base classes

Tests are organized into two directories:

- **`tests/Unit/`** — Uses `UnitTestCase`. Lightweight tests that only need the Laravel service container. No database, no Yii2 bootstrap, no migrations.
- **`tests/Feature/`** — Uses `TestCase`. Full integration tests with database (`RefreshDatabase`), Yii2 bootstrapping, and migrations.

This is wired up in `tests/Pest.php`:

```php
uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');
```

### UnitTestCase (`tests/Unit/`)

`CraftCms\Cms\Tests\UnitTestCase` extends `Orchestra\Testbench\TestCase` and provides only the Laravel service container. It sets the edition to Pro, template mode to Cp, locale, and timezone — but does **not** touch the database.

Use this for:
- Testing pure logic, formatting, or config parsing
- Testing validation rules, string helpers, or data transformations
- Testing field construction or configuration (e.g., `new Money(...)`)
- Anything that does not require database state

### TestCase (`tests/Feature/`)

`CraftCms\Cms\Tests\TestCase` extends `Orchestra\Testbench\TestCase` with `RefreshDatabase`. It runs the full `Install` migration, bootstraps Yii2, seeds an admin user, and provides the complete Craft environment.

Use this for:
- Tests that create elements via factories
- Tests that query the database (element queries, Eloquent models)
- Tests that save elements or interact with project config
- Tests that require an authenticated user (`actingAs`)
- HTTP/route tests

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

## Pest data providers

Use Pest's `->with()` to consolidate tests that share the same structure but differ in inputs and expected values. This reduces duplication and makes it easy to add new cases. Always use named dataset entries for readable test output.

```php
it('validates handles', function (string $handle, bool $expected) {
    expect(HandleRule::isValid($handle))->toBe($expected);
})->with([
    'valid lowercase' => ['fooBar', true],
    'starts with number' => ['1foo', false],
    'contains spaces' => ['foo bar', false],
]);
```

When dataset values cannot be expressed as simple scalars (e.g. they require runtime evaluation), use closures:

```php
it('matches with TYPE_TODAY', function (Closure $createDate, bool $expected) {
    $entry = createEntryWithDate($createDate());
    $rule = createDateRangeRule(['rangeType' => DateRange::TYPE_TODAY]);

    expect($rule->matchElement($entry))->toBe($expected);
})->with([
    'created today' => [fn () => DateTimeHelper::today()->modify('+12 hours'), true],
    'created yesterday' => [fn () => DateTimeHelper::yesterday(), false],
]);
```

Use array parameters when different cases need different subsets of attributes:

```php
it('matches with relative date types', function (string $rangeType, array $ruleAttributes, bool $expected) {
    $entry = createEntryWithDate(DateTimeHelper::now());
    $rule = createDateRangeRule(['rangeType' => $rangeType, ...$ruleAttributes]);

    expect($rule->matchElement($entry))->toBe($expected);
})->with([
    'before 1 day from now' => [DateRange::TYPE_BEFORE, ['periodValue' => 1, 'periodType' => DateRange::PERIOD_DAYS_FROM_NOW], true],
    'before with no periodValue' => [DateRange::TYPE_BEFORE, ['periodValue' => null], true],
]);
```

**When to use data providers:**
- Multiple tests share the same assertion logic with different inputs/outputs.
- You find yourself copy-pasting a test body and only changing values.

**When NOT to use data providers:**
- Tests have structurally different assertions (e.g. one checks `toBeEmpty()`, another checks `toContain()`).
- The test body would need complex branching logic to handle different cases.

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
