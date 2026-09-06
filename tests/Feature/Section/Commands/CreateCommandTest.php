<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\UniqueConstraintViolationException;

it('uses supplied entry types without creation prompts', function (array $handles, array $expected) {
    EntryType::factory()->create(['name' => 'Article', 'handle' => 'article']);
    EntryType::factory()->create(['name' => 'Page', 'handle' => 'page']);

    $this->artisan('craft:sections:create', [
        '--entryTypes' => $handles,
        '--uriFormat' => 'news/{slug}',
        '--template' => 'news/_entry',
    ])
        ->expectsQuestion('Section name', 'News')
        ->expectsQuestion('Section handle', 'news')
        ->expectsQuestion('Section type', 'channel')
        ->expectsConfirmation('Enable entry versioning for the section?', 'yes')
        ->assertSuccessful();

    $section = Sections::getSectionByHandle('news');
    expect(array_column($section->getEntryTypes(), 'handle'))->toBe($expected)
        ->and(EntryType::count())->toBe(2);

    foreach ($section->getSiteSettings() as $settings) {
        expect($settings->hasUrls)->toBeTrue()
            ->and($settings->uriFormat)->toBe('news/{slug}')
            ->and($settings->template)->toBe('news/_entry');
    }
})->with([
    'single' => [['article'], ['article']],
    'repeated options' => [['page', 'article'], ['page', 'article']],
    'comma separated' => [['page,article'], ['page', 'article']],
]);

it('resolves an interactive entry type before saving the section', function (bool $existing, bool $save) {
    EntryType::factory()->create(['name' => 'Article', 'handle' => 'article']);
    if (! $save) {
        $this->partialMock(EntryTypes::class)
            ->shouldReceive('saveEntryType')->once()->andReturnFalse();
    }

    $command = $this->artisan('craft:sections:create')
        ->expectsQuestion('Section name', 'News')
        ->expectsQuestion('Section handle', 'news')
        ->expectsQuestion('Section type', 'channel')
        ->expectsConfirmation('Enable entry versioning for the section?', 'yes')
        ->expectsConfirmation('Have you already created an entry type for this section?', $existing ? 'yes' : 'no');

    if ($existing) {
        $command->expectsQuestion('Which entry type should be used?', 'article');
    } else {
        $command->expectsQuestion('Entry type name', 'Story')->expectsQuestion('Entry type handle', 'story');
    }

    if (! $save) {
        expect(fn () => $command->run())->toThrow(RuntimeException::class, 'Unable to save entry type: story');
        expect(Sections::getSectionByHandle('news'))->toBeNull();
    } else {
        $command->assertSuccessful()->run();
        expect(array_column(Sections::getSectionByHandle('news')->getEntryTypes(), 'handle'))
            ->toBe([$existing ? 'article' : 'story']);
    }
})->with([[true, true], [false, true], [false, false]]);

it('rejects invalid entry type handles before creating a section', function () {
    expect(fn () => $this->artisan('craft:sections:create', ['--entryTypes' => ['missing']])->run())
        ->toThrow(RuntimeException::class, 'Invalid entry type handle: missing');
    expect(Sections::getAllSections())->toBeEmpty()
        ->and(EntryType::count())->toBe(0);
});

it('preserves rejection of duplicate entry type assignments', function () {
    EntryType::factory()->create(['handle' => 'article']);

    expect(fn () => $this->artisan('craft:sections:create', [
        '--entryTypes' => ['article,article'], '--uriFormat' => 'news/{slug}',
    ])->expectsQuestion('Section name', 'News')
        ->expectsQuestion('Section handle', 'news')
        ->expectsQuestion('Section type', 'channel')
        ->expectsConfirmation('Enable entry versioning for the section?', 'yes')
        ->run())->toThrow(UniqueConstraintViolationException::class);
});
