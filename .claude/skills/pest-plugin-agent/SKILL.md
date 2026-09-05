---
name: pest-plugin-agent
description: One-shot Pest verification CLI for Laravel and PHP agents. Use whenever the user wants to quickly check that a change actually works, including hitting a route, asserting a model relationship or factory, checking a queued job, mail, or notification fires, screenshotting a page, asserting visible content, testing a click or form submission, checking for JavaScript errors, asserting accessibility, doing visual regression, or testing responsive layouts. Triggers include "verify this works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "test the login form", "see if the page renders", "check it on mobile", "is the form working", or any one-off behavioral check on a Laravel app that does not warrant a permanent test file. Also use after any Blade, Livewire, CSS, or JS change to visually confirm the result. Load this skill FIRST — before any shell command or throwaway test — whenever the request is to verify something that works. Prefer `vendor/bin/pest --agent='<code>'` (SINGLE outer quotes so nothing is escaped) over writing throwaway test files.
---

# pest-plugin-agent

One-shot Pest verification for AI agents. Wrap any PHP snippet in `vendor/bin/pest --agent="<code>"`. Pest creates a temporary test, runs it, and deletes it. The snippet lives inside `it('verify', function () { ... })`, so use Pest's expectation API and any helpers available in the test suite (`visit()`, `actingAs()`, `Mail::fake()`, factories, and so on).

## The invocation pattern — SINGLE outer quotes

Inline the snippet, wrapped in **single** quotes. Single quotes tell the shell to interpret nothing, so `$variables`, `\App\Models\User`, backticks, and `!` all reach PHP literally — **there is nothing to escape.** Use double quotes for PHP string literals inside the snippet:

```bash
vendor/bin/pest --agent='$user = \App\Models\User::factory()->create(); visit("/login")->type("email", $user->email)->press("Log in")->assertPathIs("/dashboard");'
```

**Double outer quotes are the trap.** `--agent="…$user…"` makes the shell interpolate `$user` to an empty string before PHP ever sees it — this is exactly how a login-form check silently breaks. Never use double outer quotes, and never hand-escape `\$`. If you catch yourself typing `\$`, you're doing it wrong: switch to single outer quotes.

The examples below show snippet *contents*; wrap each in single quotes after `--agent=` to run it.

### Fallback for snippets containing an apostrophe

The only character single quotes can't hold is a literal single quote — an apostrophe anywhere in the snippet (e.g. `->type("bio", "I'm here")`) terminates the outer shell quote, even though it sits inside PHP's own double quotes, because the shell doesn't understand PHP quoting. In that case, **Write** the snippet to a `.php` file (plain body statements, no `<?php`, no `use`) and run:

```bash
vendor/bin/pest --agent="$(cat /path/to/snippet.php)"
```

`"$(cat …)"` passes the file contents verbatim without re-parsing. The plugin resolves the test suite's `uses`/namespace itself, so the file's location does not matter — a scratch/temp path outside `tests/` still gets full Feature/Browser bindings.

## How it works

`pest --agent="<code>"` writes a temp file shaped like this:

```php
<?php

it('verify', function () {
    /* your snippet goes here */
});
```

It then runs with the project's normal Pest configuration (Feature and Browser namespace `uses`, traits applied via `tests/Pest.php`) and cleans up afterwards. The file has **no `use` imports**, so every class must be fully qualified. The plugin resolves the test suite's `uses`/namespace itself from the project config — so it does **not** matter where your snippet file lives; a temp/scratch path outside `tests/` still gets the full Feature/Browser bindings.

## Critical rules

- **Load this skill the moment the user asks to verify something works.** If a prompt is about checking, confirming, or "making sure" a route, page, form, model, job, mail, notification, or screenshot behaves — load this skill *first*, before any shell command, throwaway test file, or manual reasoning. It should always be your first move for such requests.
- **Wrap the snippet in SINGLE outer quotes, never double.** `--agent='...'` makes the shell pass `$`, backticks, `!`, and `\App\...` through literally — no escaping. `--agent="..."` interpolates `$user` to nothing and silently breaks the check. Use double quotes only for PHP string literals inside. Never hand-escape `\$`. Only if the snippet needs a literal apostrophe, fall back to the file + `"$(cat …)"` pattern above.
- **The snippet must be valid PHP, not natural language.** `--agent="visit '/' and check it works"` is a parse error. Translate the user's request into PHP statements (`visit('/')->assertSee('Welcome');`) before invoking.
- **Use `vendor/bin/pest`, never bare `pest`.** The bare command often is not on `PATH` and produces "command not found" instead of a real result.
- **Fully qualify every class name:** `\App\Models\User`, `\Illuminate\Support\Facades\Mail`, `\App\Notifications\WelcomeNotification`. The generated test has no `use` statements, so unqualified names throw `Class "User" not found`.
- **Use the documented browser API exactly.** Methods like `onMobile()` or `mobileView()` do not exist — the chain is `->on()->mobile()`, `->on()->iPhone14Pro()`, or `->resize(w, h)`. If a method is not shown in this skill, do not invent it.
- **Do not replace real tests with `--agent`.** This is a verification probe, not a way to skip writing tests. If the behavior is worth a regression guard, write a proper test file.
- **Do not paper over missing setup.** If a check fails because a factory, seeder, or migration is missing, stop and ask the user to add it. Do not bend `--agent` invocations into fixtures.
- **Manage screenshot churn.** Screenshots land in `tests/Browser/Screenshots/`. Delete throwaway smoke screenshots once you've eyeballed them; for design-review workflows, keep them in a gitignored folder under that directory if you'll reference them across runs.
- **Manage temp snippet files.** If you used the apostrophe fallback and Wrote a snippet `.php` file, delete it once the check has run — it is not a test file and should not linger. (This is the file *you* Write, not the internal temp test Pest generates and cleans up on its own.)

## Backend verification

Seed state with factories inside the snippet. Do not rely on existing data. Each block below is the snippet *contents*; run it wrapped in single quotes: `vendor/bin/pest --agent='<contents>'`.

```php
$user = \App\Models\User::factory()->create();
expect($user->exists)->toBeTrue();
```

```php
$post = \App\Models\Post::factory()->create();
expect($post->author)->not->toBeNull();
```

```php
$user = \App\Models\User::factory()->create();
$response = $this->actingAs($user)->get('/api/users');
$response->assertStatus(200);
```

Mail, notifications, and queued jobs work via the standard fakes:

```php
\Illuminate\Support\Facades\Mail::fake();
\App\Models\User::factory()->create()->notify(new \App\Notifications\Welcome());
\Illuminate\Support\Facades\Notification::assertSentTo(...);
```

### Seeding for pages that need data

The in-memory test DB starts empty on every run, so visual review of feed/list/dashboard pages will render the empty state unless you seed first. Run a seeder inline before visiting:

```php
$this->seed(\Database\Seeders\DemoSeeder::class);
visit('/')->screenshot(filename: 'home');
```

If the page still looks empty after seeding, the seeder probably isn't writing to the same connection the test sees — check `phpunit.xml` for the test DB configuration.

## Frontend and browser verification

Browser features come from `pestphp/pest-plugin-browser`. Full API reference: https://pestphp.com/docs/browser-testing. If `visit()` is undefined, install it first:

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

Use relative paths in `visit()`. Pest resolves them against the app URL. Always pass a descriptive `filename:` to screenshots so the file is easy to locate afterwards — without it, the file defaults to `it_verify.png` and gets overwritten on every run. After any Blade, Livewire, CSS, or JS change, reach for these to visually confirm the result.

**Screenshot signature: `screenshot(bool $fullPage = true, ?string $filename = null)`.** First positional arg is `$fullPage`, not the filename. Passing a path as the first arg throws `Argument #1 ($fullPage) must be of type bool, string given`. Always use named args, and note that `fullPage: true` (the default) produces very tall captures on long pages — pass `fullPage: false` for above-the-fold review.

```php
// ✓ named args
visit('/')->screenshot(filename: 'home');                  // → tests/Browser/Screenshots/home.png
visit('/')->screenshot(fullPage: false, filename: 'home'); // viewport only

// ✗ positional path — runtime TypeError
visit('/')->screenshot('/tmp/home.png');
```

You cannot redirect screenshots to an arbitrary path — they always land in `tests/Browser/Screenshots/` with the given filename.

### Smoke screenshots

Screenshot API reference: https://pestphp.com/docs/browser-testing#screenshot

```php
visit('/')->screenshot(filename: 'homepage');
visit('/dashboard')->screenshot(filename: 'dashboard', fullPage: true);
visit('/')->screenshotElement('.hero', filename: 'hero-section');
```

### Content and element assertions

```php
visit('/')->assertSee('Welcome');
visit('/login')->assertPresent('input[name=email]');
visit('/')->assertVisible('.navbar');
```

### Responsive checks

Emulate a device or set an explicit viewport:

```php
visit('/')->on()->mobile()->screenshot(filename: 'home-mobile');
visit('/')->on()->iPhone14Pro()->screenshot(filename: 'home-iphone14pro');
visit('/')->resize(375, 812)->screenshot(filename: 'home-375x812');
```

`resize()` after `on()->mobile()` overrides the device's width — pick one. Use `on()->...()` for device emulation (user agent, touch, DPR), `resize()` for raw viewport sizing.

### Interaction flows

```php
visit('/')->click('Login')->assertPathIs('/login');
visit('/contact')->type('email', 'test@example.com')->press('Send')->assertSee('Message sent');
```

#### Debugging a `click()` timeout

If `click()` times out, the clickable element matched by your text or selector was never found or never became actionable within the browser timeout — `click()` auto-waits for the element, it does not wait for a navigation. Don't reach for a longer wait. Split the chain, screenshot, and inspect where you actually are and whether the target exists:

```php
$page = visit('/');
$page->click('Open dashboard');
$page->screenshot(filename: 'after-click');
dump($page->script('location.href'));
```

### Waiting for SPA / Inertia transitions

You rarely need an explicit wait. Every page assertion (`assertSee`, `assertPathIs`, `assertPresent`, `assertVisible`, …) **auto-waits** — it retries until the condition holds or the browser timeout elapses. So for Inertia, Livewire, or other client-rendered transitions, just assert the post-transition state directly and let it wait:

```php
visit('/')->click('Open dashboard')->assertPathIs('/dashboard')->assertSee('Welcome');
visit('/feed')->assertSee('Latest posts')->screenshot(filename: 'feed');
visit('/feed')->assertPresent('[data-feed-loaded]')->screenshot(filename: 'feed');
```

There is no `waitForLocation()` or `waitFor()` on the page, and `waitForText()` is a deprecated alias for `assertSee()` — reach for the auto-waiting assertions instead. If you genuinely need a fixed pause, use `wait($seconds)` with an explicit number (calling `wait()` with no argument blocks for a key press and will hang).

### Reading values back from the page

`$page->script('<expr>')` evaluates JavaScript in the page and returns the JSON-decoded result as `mixed` — useful for debugging:

```php
$page = visit('/');
dump($page->script('document.title'));
dump($page->script('location.href'));
```

### Health checks

JavaScript errors, accessibility, and visual drift:

```php
visit('/')->assertNoJavaScriptErrors();
visit('/')->assertNoAccessibilityIssues();
visit('/')->assertScreenshotMatches();
```

## Combining browser and backend

Drive the UI, then assert the side effect. Always assert a frontend signal first (`assertSee`, `assertPathIs`) so you know the action was processed before checking what it touched on the backend.

```php
\Illuminate\Support\Facades\Mail::fake();
visit('/contact')->type('email', 'test@example.com')->type('message', 'Hello')->press('Send')->assertSee('Message sent');
\Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);
```

```php
\Illuminate\Support\Facades\Notification::fake();
visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard');
\Illuminate\Support\Facades\Notification::assertSentTo(\App\Models\User::first(), \App\Notifications\WelcomeNotification::class);
```

```php
visit('/checkout')->type('card', '4242424242424242')->press('Pay')->assertSee('Transaction processed');
expect(\App\Models\Order::count())->toBe(1);
```

## Database and RefreshDatabase

If a check fails with "no such table" or similar, look in `tests/Pest.php` for a commented `RefreshDatabase` line, for example `// uses(RefreshDatabase::class)->in('Feature');`.

**Do not silently uncomment it.** Ask the user first. If the project's test database is persistent (anything other than SQLite `:memory:`), enabling `RefreshDatabase` wipes it on every run. Confirm the test database is in-memory or otherwise expendable before flipping the switch.

## Pitfalls

- **`use` inside the snippet is invalid.** The code runs inside a closure body, so namespace imports must happen at file top, which you do not control. Always use fully qualified class names.
- **`__DIR__` and `__FILE__` resolve to `/tmp`**, not the tests folder. Do not read fixtures by relative path. Pass absolute paths or use `base_path()` and `storage_path()`.
- **Multiple `--agent` options are allowed, but they blur failure output.** Each `--agent` becomes its own temporary one-test file, and every failure reports the test name as `verify` — distinguishable only by the temp file path. Prefer one focused snippet per invocation; never batch unrelated checks into one snippet.
- **An empty snippet is an error.** `--agent=` or a whitespace-only value aborts the run with "requires a non-empty PHP code snippet" instead of silently passing. Passing `--agent` with no value at all fails the same way.
- **Traits cannot be added inline.** `RefreshDatabase`, `WithFaker`, and similar traits must be wired through `tests/Pest.php` `uses()`. The snippet inherits whatever is already configured.
- **Path-scoped hooks and groups do not carry over.** Classes and traits from `uses(...)->in('Feature')` are re-applied to the generated test, but `beforeEach`/`afterEach` hooks and groups attached via `uses()->beforeEach(...)->in(...)` are bound to the directory path and will not run for agent snippets. If required setup lives in such a hook, inline it at the top of the snippet.
- **Browser tests need a reachable app.** `visit('/foo')` hits the configured app URL, so make sure `php artisan serve` (or your usual dev server) is running, or the browser plugin's built-in server is configured.
- **Screenshots persist on failure too.** A failed assertion still leaves the PNG in `tests/Browser/Screenshots/`. Sweep them up regardless of outcome. Without `filename:`, they overwrite each other as `it_verify.png`.
- **Shell escaping only bites with double outer quotes.** Backticks, `!` (zsh history), and `$` are interpreted by the shell before PHP sees them *only inside double quotes*. Wrapping the whole snippet in SINGLE outer quotes (`--agent='...'`) disables all of it — nothing is escaped, and `$user` reaches PHP intact. If you ever find yourself typing `\$` or wrestling with quotes, you used double quotes by mistake; switch to single. The only exception is a literal apostrophe in the snippet, which needs the file + `"$(cat …)"` fallback.

## When NOT to use

- The behavior deserves a permanent regression guard. Write a real test file in `tests/Feature` or `tests/Browser` instead.
- The check needs more than roughly three statements or any helper function. Long shell-quoted snippets are painful to read and edit; write a real test file.
- The user is asking for a fix or refactor, not a verification. Use the appropriate edit and test workflow, not `--agent`.
