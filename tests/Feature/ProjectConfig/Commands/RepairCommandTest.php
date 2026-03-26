<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;

it('reports when there is nothing to repair', function () {
    $this->artisan('craft:project-config:repair', ['--no-interaction' => true])
        ->expectsOutputToContain('Nothing to repair.')
        ->assertSuccessful();
});

it('repairs double-packed associative arrays in project config', function () {
    app(ProjectConfig::class)->set('testFixture.item', doublePackedArray());

    $this->artisan('repair:project-config', ['--no-interaction' => true])
        ->expectsOutputToContain('testFixture.item')
        ->expectsOutputToContain('Finished repairing project config. 1 item was matched.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('testFixture.item'))->toBe(singlePackedArray());
});

it('supports a dry run without changing the project config', function () {
    app(ProjectConfig::class)->set('testFixture.item', doublePackedArray());

    $this->artisan('craft:project-config:repair', ['--dry-run' => true, '--no-interaction' => true])
        ->expectsOutputToContain('testFixture.item')
        ->expectsOutputToContain('[DRY RUN] Finished repairing project config. 1 item was matched.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('testFixture.item'))->toBe(doublePackedArray());
});

function singlePackedArray(): array
{
    return [
        ProjectConfig::ASSOC_KEY => [
            ['foo', 'bar'],
            ['baz', 'qux'],
        ],
    ];
}

function doublePackedArray(): array
{
    return [
        ProjectConfig::ASSOC_KEY => [
            [ProjectConfig::ASSOC_KEY, singlePackedArray()[ProjectConfig::ASSOC_KEY]],
        ],
    ];
}
