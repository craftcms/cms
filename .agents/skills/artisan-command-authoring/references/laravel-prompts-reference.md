# Laravel Prompts Reference

## Source And Scope

- Official Laravel 12 docs: <https://laravel.com/docs/12.x/prompts>
- Prompt helper signatures: <https://github.com/laravel/prompts/blob/main/src/helpers.php>
- Form builder signatures: <https://github.com/laravel/prompts/blob/main/src/FormBuilder.php>
- Prompt test expectations in Artisan tests: `vendor/laravel/framework/src/Illuminate/Testing/PendingCommand.php`
- Last synced: 2026-03-05

This reference is for authoring and reviewing interactive Artisan commands in Craft CMS.

## Import Patterns

Import only the helpers you use:

```php
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;
```

Or import many in one statement:

```php
use function Laravel\Prompts\{confirm, error, info, multiselect, search, text};
```

## Global Prompt Helpers

### Input Helpers

| Helper | Return | Key arguments | Notes |
| --- | --- | --- | --- |
| `text()` | `string` | `label`, `placeholder`, `default`, `required`, `validate`, `hint`, `transform` | Single line text input. |
| `textarea()` | `string` | `label`, `placeholder`, `default`, `required`, `validate`, `hint`, `rows`, `transform` | Multiline input. |
| `number()` | `int|string` | `label`, `placeholder`, `default`, `required`, `validate`, `hint`, `min`, `max`, `step` | Numeric input with arrow key adjustments. |
| `password()` | `string` | `label`, `placeholder`, `required`, `validate`, `hint`, `transform` | Input is masked. |
| `confirm()` | `bool` | `label`, `default`, `yes`, `no`, `required`, `validate`, `hint`, `transform` | Yes/no selection. |
| `select()` | `int|string` | `label`, `options`, `default`, `scroll`, `validate`, `hint`, `required`, `transform` | Single option from fixed list. |
| `multiselect()` | `array<int|string>` | `label`, `options`, `default`, `scroll`, `required`, `validate`, `hint`, `transform` | Multi option selection with space bar. |
| `suggest()` | `string` | `label`, `options` (array or closure), `placeholder`, `default`, `scroll`, `required`, `validate`, `hint`, `transform` | Autocomplete, freeform value still allowed. |
| `search()` | `int|string` | `label`, `options` (closure), `placeholder`, `scroll`, `validate`, `hint`, `required`, `transform` | Search first, then select one option. |
| `multisearch()` | `array<int|string>` | `label`, `options` (closure), `placeholder`, `scroll`, `required`, `validate`, `hint`, `transform` | Search first, then select multiple options. |
| `pause()` | `bool` | `message` | Waits for enter/return confirmation. |

### Output And Utility Helpers

| Helper | Return | Purpose |
| --- | --- | --- |
| `info()` | `void` | Informational message. |
| `warning()` | `void` | Warning message. |
| `error()` | `void` | Error message. |
| `alert()` | `void` | Alert message. |
| `note()` | `void` | Generic note style. |
| `intro()` | `void` | Introductory message. |
| `outro()` | `void` | Closing message. |
| `table()` | `void` | Render rows and headers as a table. |
| `grid()` | `void` | Render items in a grid. |
| `spin()` | `mixed` | Spinner while callback executes; returns callback result. |
| `progress()` | `array|Progress` | Progress bar for iterable or fixed step count. |
| `clear()` | `void` | Clear terminal. |
| `form()` | `FormBuilder` | Build a multi-step prompt flow with backtracking. |

## Shared Argument Behavior

- `required`:
  - `false` means optional.
  - `true` enforces input.
  - `string` enforces input with a custom message.
- `validate`:
  - Closure returning `null` or an error message.
  - For many text-like prompts, validation rule arrays are also accepted (for example `['name' => 'required|max:255']`).
- `transform`:
  - Runs before validation.
  - Use for normalization, such as `trim`, lowercase conversion, or casting.
- `hint`:
  - Help text shown under the prompt.
- `scroll`:
  - Visible option count before scrolling.
- `options` return value:
  - Associative arrays return keys.
  - Indexed arrays return values.

## Search Prompt Notes

- `search()` and `multisearch()` expect an `options` closure that receives current input and returns options.
- For value-based filtering, ensure the array is reindexed (`values()->all()` or `array_values(...)`) so it is not treated as associative.

## Form Builder Reference

Use `form()` when prompts are a sequence and users may need to go back (`CTRL + U` in supported terminals).

### Core Methods

| Method | Purpose |
| --- | --- |
| `add(Closure $step, ?string $name = null, bool $ignoreWhenReverting = false)` | Add custom step closure. |
| `addIf(Closure|bool $condition, Closure $step, ?string $name = null, bool $ignoreWhenReverting = false)` | Add conditional custom step. |
| `submit(): array` | Execute steps and return responses. |

### Built-in Prompt Methods On `FormBuilder`

- `text(...)`
- `textarea(...)`
- `password(...)`
- `confirm(...)`
- `select(...)`
- `multiselect(...)`
- `suggest(...)`
- `search(...)`
- `multisearch(...)`
- `pause(...)`
- `spin(...)`
- `note(...)`
- `info(...)`
- `warning(...)`
- `error(...)`
- `alert(...)`
- `intro(...)`
- `outro(...)`
- `table(...)`
- `progress(...)`

Most form methods accept `name: 'key'` so `submit()` returns named responses.
For numeric input in a form flow, use `add(...)` and call `number(...)` inside the closure.

## Transform Before Validation

Use `transform` when users might include formatting you do not want to validate directly:

```php
$slug = text(
    label: 'Slug',
    transform: fn (string $value) => trim(strtolower($value)),
    validate: ['slug' => 'required|alpha_dash']
);
```

## Informational Messages In Commands

Prefer one style per command path and keep message noise low:

- Prompt helpers for rich, prompt-themed messaging (`info()`, `warning()`, etc.).
- `$this->components->...` for conventional Artisan status output.

Use one approach intentionally and avoid mixing styles in every line unless there is a reason.

## Progress Patterns

Map-style progress:

```php
use function Laravel\Prompts\progress;

$results = progress(
    label: 'Processing users',
    steps: $users,
    callback: fn ($user, $progress) => handleUser($user),
);
```

Manual progress:

```php
$progress = progress(label: 'Processing users', steps: count($users));
$progress->start();

foreach ($users as $user) {
    handleUser($user);
    $progress->advance();
}

$progress->finish();
```

## Fallbacks And Unsupported Environments

- Laravel Prompts supports macOS, Linux, and Windows under WSL.
- Laravel framework configures fallbacks automatically in unsupported environments.
- For non-Laravel or custom behavior:
  - `Laravel\Prompts\Prompt::fallbackWhen(bool)`
  - Per prompt class: `SomePrompt::fallbackUsing(Closure $fallback)`

## Terminal Constraints

- Keep labels/options/validation messages short enough for narrow terminals.
- Prompts with `scroll` automatically clamp to terminal height.

## Testing Prompt Output In Artisan Tests

Available prompt message assertions on `PendingCommand`:

- `expectsPromptsInfo(string $message)`
- `expectsPromptsWarning(string $message)`
- `expectsPromptsError(string $message)`
- `expectsPromptsAlert(string $message)`
- `expectsPromptsIntro(string $message)`
- `expectsPromptsOutro(string $message)`
- `expectsPromptsTable(array|Collection $headers, array|Collection|null $rows)`

## Craft Command Integration Pattern

Always gate prompts in case the command is running non-interactively:

```php
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

$name = $this->argument('name');

if (!$name) {
    if (!$this->input->isInteractive()) {
        $this->components->error('The name argument is required in non-interactive mode.');
        return self::FAILURE;
    }

    $name = text(label: 'Name', required: true);
}

$shouldProceed = $this->input->isInteractive()
    ? confirm('Continue?', default: true)
    : (bool)$this->option('force');
```
