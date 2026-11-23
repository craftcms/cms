<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\ImportLookups;

beforeEach(function () {
    $this->importer = new EntryImporter;
    $this->looked = [];
    // finds `ada` by any case, and nothing else
    $this->lookup = function (array $values): array {
        $this->looked[] = $values;

        return in_array('ada', array_map(mb_strtolower(...), $values), true) ? ['ada' => 7] : [];
    };
});

it('looks a value up once per scope, whatever its case', function () {
    expect(ImportLookups::remember($this->importer, 'authors', ['ada', 'missing'], $this->lookup))->toBe(['ada' => 7])
        ->and(ImportLookups::remember($this->importer, 'authors', ['Ada'], $this->lookup))->toBe(['ada' => 7])
        ->and(ImportLookups::remember($this->importer, 'reviewers', ['ada'], $this->lookup))->toBe(['ada' => 7])
        ->and(ImportLookups::remember(new EntryImporter, 'authors', ['ada'], $this->lookup))->toBe(['ada' => 7])
        ->and($this->looked)->toBe([['ada', 'missing'], ['ada'], ['ada']]);
});

it('looks a value that matched nothing up again in a run, which may since have created it', function () {
    ImportLookups::remember($this->importer, 'authors', ['missing'], $this->lookup);
    ImportLookups::remember($this->importer, 'authors', ['missing'], $this->lookup);

    expect($this->looked)->toBe([['missing'], ['missing']]);
});

it('remembers a value that matched nothing while previewing, which saves nothing', function () {
    $this->importer->preview(function () {
        ImportLookups::remember($this->importer, 'authors', ['missing'], $this->lookup);
        ImportLookups::remember($this->importer, 'authors', ['missing'], $this->lookup);
    });

    expect($this->looked)->toBe([['missing']]);
});
