<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator as DeprecatorFacade;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    /**
     * Reset initial state
     */
    Deprecator::$logTarget = 'db';
    Deprecator::$throwExceptions = false;

    $this->deprecator = app(Deprecator::class);
});

it('has a working facade', function () {
    expect(DeprecatorFacade::getTotalLogs())->toBe(0);
});

it('can log deprecations to the log', function () {
    Deprecator::$logTarget = 'logs';

    $logged = [];
    Log::listen(function ($event) use (&$logged) {
        $logged[] = $event->message;
    });

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBe(0);
    expect($logged)->toContain('A deprecation message');
});

it('can log deprecations to the database', function () {
    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBeGreaterThanOrEqual(1);
    tap(DeprecationError::query()->where('key', 'foo')->firstOrFail(), function (DeprecationError $error) {
        expect($error->traces)->toBeArray();
        expect($error->uid)->not()->toBe('0');
    });
});

it('can get the logs in the current request', function () {
    expect($this->deprecator->getRequestLogs())->toBeEmpty();

    $this->deprecator->log('foo', 'A deprecation message');

    expect($this->deprecator->getRequestLogs())->toHaveCount(1);
});

it('can get the total log count', function () {
    expect($this->deprecator->getTotalLogs())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect($this->deprecator->getTotalLogs())->toBe(0);

    $this->deprecator->storeLogs();

    expect($this->deprecator->getTotalLogs())->toBe(1);
});

it('can get all logs', function () {
    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->log('foo', 'A deprecation message');

    expect(DeprecationError::count())->toBe(0);

    $this->deprecator->storeLogs();

    expect($this->deprecator->getLogs())->toHaveCount(1);
});

it('can get a log by id', function () {
    expect($this->deprecator->getLogById(1))->toBeNull();

    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    $first = DeprecationError::firstOrFail();

    expect($this->deprecator->getLogById($first->id)?->is($first))->toBeTrue();
});

it('can delete a log by id', function () {
    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->storeLogs();

    $first = DeprecationError::firstOrFail();

    $this->deprecator->deleteLogById($first->id);

    expect(DeprecationError::count())->toBe(0);
});

it('can delete all logs', function () {
    $this->deprecator->log('foo', 'A deprecation message');
    $this->deprecator->log('foo2', 'A deprecation message');
    $this->deprecator->storeLogs();

    expect(DeprecationError::count())->toBe(2);

    $this->deprecator->deleteAllLogs();

    expect(DeprecationError::count())->toBe(0);
});

it('serializes logs without passing through raw HTML', function () {
    $this->deprecator->log('foo', 'Use `bar` <img src=x onerror=alert(1)>', '/path/<b>file</b>.php', 12);
    $this->deprecator->storeLogs();

    $serialized = DeprecationError::firstOrFail()->jsonSerialize();

    expect($serialized['message'])->toBe('Use <code>bar</code> &lt;img src=x onerror=alert(1)&gt;')
        ->and($serialized['origin'])->toBe('/path/<b>file</b>.php:12');
});

it('records the PHP caller of a deprecated API', function () {
    Arr::toArray('a,b');
    $line = __LINE__ - 1;

    $log = Arr::first($this->deprecator->getRequestLogs());

    expect($log->key)->toBe('Arr::toArray(string)')
        ->and($log->file)->toBe(__FILE__)
        ->and($log->line)->toBe($line);
});

it('records the caller of a deprecated API called back by PHP', function () {
    array_map(Arr::toArray(...), ['a,b']);
    $line = __LINE__ - 1;

    $log = Arr::first($this->deprecator->getRequestLogs());

    expect($log->file)->toBe(__FILE__)
        ->and($log->line)->toBe($line);
});

it('records the caller of a deprecated API reached through a property assignment', function () {
    $query = Entry::find();
    $query->orderBy = ['title' => SORT_ASC];
    $line = __LINE__ - 1;

    $log = Arr::first($this->deprecator->getRequestLogs());

    expect($log->key)->toBe('CraftCms\\Cms\\Element\\Queries\\EntryQuery::orderBy')
        ->and($log->file)->toBe(__FILE__)
        ->and($log->line)->toBe($line);
});

it('records the template line of a deprecated API called from Twig', function (string $call, string $key) {
    $tempDir = sys_get_temp_dir().'/craft-deprecator-test-'.uniqid();
    File::ensureDirectoryExists($tempDir);
    File::put("$tempDir/caller.twig", "Line one\n\n{% set q = query %}\n{% do $call %}\n{% set after = 1 %}\nLine six\n");
    Aliases::set('@templates', $tempDir);

    $query = Entry::find();
    $query->macro('legacyMethod', fn () => DeprecatorFacade::log('legacyMethod', 'legacyMethod() is deprecated.'));

    try {
        app(TemplateManager::class)->renderTemplate('caller', ['query' => $query], TemplateMode::Site);

        $log = Arr::first($this->deprecator->getRequestLogs());

        expect($log->key)->toBe($key)
            ->and(realpath($log->file))->toBe(realpath("$tempDir/caller.twig"))
            ->and($log->line)->toBe(4);
    } finally {
        File::deleteDirectory($tempDir);
    }
})->with([
    'Twig function' => ["svg('<svg></svg>', class='foo')", 'svg()-class'],
    'method call' => ["q.orderBy({title: 'asc'})", 'CraftCms\\Cms\\Element\\Queries\\EntryQuery::orderBy'],
    'macro' => ['q.legacyMethod()', 'legacyMethod'],
]);
